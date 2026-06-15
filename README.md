# QR Check-in System

ระบบเช็คชื่อนักศึกษาด้วย QR Code แบบ Dynamic สำหรับห้องเรียน

---

## หลักการทำงาน

- อาจารย์เปิด session แล้วฉาย QR บน Projector
- QR เปลี่ยนทุก N วินาที (default 3 วิ) ป้องกันส่งต่อออกนอกห้อง
- นศ. **สแกน QR ครั้งเดียว** → browser เปิดฟอร์มพร้อม sig และ timestamp ในมือแล้ว
- นศ. กรอกชื่อแล้วกด ยืนยัน → ระบบบันทึกเวลา elapsed_scan และ elapsed_fill

### Flow การทำงาน

```
session_create.php → session_ready.php → qr_display.php (Projector)
                                               ↓ นศ. สแกน QR
                                        checkin_form.php
                                               ↓ กรอกชื่อ กด ยืนยัน
                                        checkin_process.php → Database
```

---

## โครงสร้างไฟล์

```
checkin_form.php      หน้า นศ. กรอกชื่อ (เปิดจาก QR โดยตรง)
checkin_process.php   ตรวจ sig และบันทึกข้อมูลลง Database
config.php            ตั้งค่าระบบ (ไม่ขึ้น GitHub)
connect.php           เชื่อมต่อ Database (ไม่ขึ้น GitHub)
index.php             redirect ไปหน้า session_create.php
qr_ajax.php           API สร้าง QR ใหม่ทุก N วินาที
qr_display.php        หน้าฉาย QR บน Projector
qr_tmp.png            QR Dynamic (สร้างอัตโนมัติ)
qrlib/                Library สร้าง QR Code (phpqrcode)
report.php            หน้ารายงาน session ทั้งหมด
report_detail.php     หน้ารายชื่อ นศ. ใน session พร้อม Export CSV
session_create.php    หน้า Login และกรอกข้อมูลวิชา
session_ready.php     หน้าสรุป session ก่อนเปิด Projector
```

---

## Database

ใช้ MySQL มี 2 ตาราง

### `ckn_sessions` — เก็บข้อมูล session การเช็คชื่อ

| Column | Type | Description |
|--------|------|-------------|
| id | INT AUTO_INCREMENT | Primary Key — ใช้ใน QR URL แทน sid ยาวๆ |
| sid | VARCHAR(100) UNIQUE | Session ID เช่น `DisMath-2-2026-06-15` |
| secret_key | VARCHAR(64) | คีย์ลับสำหรับสร้าง HMAC |
| created_at | TIMESTAMP | เวลาที่เปิด session |

### `ckn_checkins` — เก็บข้อมูลการเช็คชื่อ นศ.

| Column | Type | Description |
|--------|------|-------------|
| id | INT AUTO_INCREMENT | Primary Key |
| sid | VARCHAR(100) | อ้างอิง session |
| student_id | VARCHAR(20) | รหัสนักศึกษา (ตัวเลขเท่านั้น) |
| student_fname | VARCHAR(80) | ชื่อ |
| student_lname | VARCHAR(80) | นามสกุล |
| sig | VARCHAR(20) | Signature ของ QR ที่ใช้สแกน |
| ip_address | VARCHAR(45) | IP Address ของ นศ. (รองรับ IPv6) |
| elapsed_scan | INT | เวลาตั้งแต่ QR generate ถึงหน้าโหลด (วิ) — ใช้ตรวจทุจริต |
| elapsed_fill | INT | เวลาตั้งแต่หน้าโหลดถึงกด Submit (วิ) — ข้อมูลเพิ่มเติม |
| t_receive | TIMESTAMP | เวลาที่ server รับ request |

---

## การติดตั้ง

### 1. สร้าง Database

```sql
CREATE DATABASE IF NOT EXISTS qrcheckin
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE qrcheckin;

CREATE TABLE IF NOT EXISTS ckn_sessions (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    sid         VARCHAR(100) NOT NULL UNIQUE,
    secret_key  VARCHAR(64)  NOT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ckn_checkins (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    sid             VARCHAR(100) NOT NULL,
    student_id      VARCHAR(20)  NOT NULL,
    student_fname   VARCHAR(80)  NOT NULL,
    student_lname   VARCHAR(80)  NOT NULL,
    sig             VARCHAR(20)  NOT NULL,
    ip_address      VARCHAR(45)  DEFAULT NULL,
    elapsed_scan    INT          DEFAULT NULL,
    elapsed_fill    INT          DEFAULT NULL,
    t_receive       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sid        (sid),
    INDEX idx_student_id (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 2. สร้าง config.php

```php
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'qrcheckin');

define('ADMIN_PASSWORD',  'your_admin_password');   // รหัสผ่านผู้สอน
define('REPORT_PASSWORD', 'your_report_password');  // รหัสผ่านดูรายงาน

define('WINDOW_SEC',  3);   // QR เปลี่ยนทุกกี่วินาที
define('SESSION_MIN', 1);   // ค่าคงไว้เพื่อ compatibility (qr_display ใช้ 3 นาที fixed)
```

### 3. สร้าง connect.php

```php
<?php
require_once __DIR__ . '/config.php';

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    array(
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    )
);
```

### 4. ตั้งค่า Permission

```bash
chmod -R 777 /path/to/checkin/
```

---

## วิธีใช้งาน

### ฝั่งอาจารย์

1. เปิด `index.php` หรือ `session_create.php`
2. ใส่รหัสผ่านผู้สอน (`ADMIN_PASSWORD`)
3. กรอก วิชา / ตอนเรียน / วันที่ แล้วกด **เปิดเช็คชื่อ**
4. หน้า `session_ready.php` แสดงข้อมูล session — กด **เปิด QR เช็คชื่อบน Projector**
5. ฉาย QR บน Projector รอ นศ. สแกน
6. กด **ต่อเวลา +3 นาที** ถ้า นศ. ยังเช็คชื่อไม่ครบ

### ฝั่งนักศึกษา

1. สแกน QR บน Projector ด้วยกล้องมือถือ **ครั้งเดียว**
2. Browser เปิดฟอร์มเช็คชื่ออัตโนมัติ
3. กรอก รหัสนักศึกษา / ชื่อ / นามสกุล แล้วกด **ยืนยันการเช็คชื่อ**
4. หน้าแสดงผล ✓ เช็คชื่อสำเร็จ พร้อมรหัสและชื่อให้ตรวจสอบ

### ดูรายงาน

1. เปิด `report.php`
2. ใส่รหัสผ่านรายงาน (`REPORT_PASSWORD`)
3. เลือก session ที่ต้องการแล้วกด **ดูรายชื่อ**
4. กด **Export CSV** เพื่อนำไปใช้ต่อ

---

## หลักการรักษาความปลอดภัย

### QR Dynamic
- QR encode URL เต็ม: `checkin_form.php?id=<db_id>&s=<sig>&t=<timestamp>`
- `id` = Primary Key จาก `ckn_sessions` (สั้นกว่า sid มาก → QR เล็กลง สแกนง่ายขึ้น)
- `sig` = `HMAC-SHA256(sid + t + seq, secret_key)` ตัดเหลือ 8 ตัว
- QR เปลี่ยนทุก `WINDOW_SEC` วินาที — sig ที่หมดอายุจะ verify ไม่ผ่าน

### การตรวจ sig ฝั่ง Server
- `checkin_process.php` คำนวณ sig ใหม่จาก `t_qr` ที่ส่งมา แล้วเปรียบเทียบ
- ถ้า sig ไม่ตรง → reject ทันที

### การวิเคราะห์ elapsed หลังบ้าน

| elapsed_scan | ความหมาย |
|-------------|----------|
| ≤ 5 วิ | ปกติ (สีเขียว) |
| 6–7 วิ | เฝ้าระวัง (สีเหลือง) |
| > 7 วิ | น่าสงสัย (สีแดง) — อาจได้ QR มาจากคนอื่น |

- **elapsed_scan** = เวลาตั้งแต่ QR generate ถึงหน้าโหลด → บอกว่าสแกนจากในห้องจริงมั๊ย
- **elapsed_fill** = เวลากรอกชื่อ → แค่ข้อมูลเพิ่มเติม ไม่ใช้ตรวจทุจริต
- IP Address ซ้ำ → แสดงเป็น **สีเหลือง** ในตาราง

---

## Requirements

- PHP 5.4+ (รองรับ PHP 5 และ PHP 8)
- MySQL 5.6+
- PHP extension: `GD`, `OpenSSL`, `PDO`, `PDO_MySQL`
- Apache หรือ Nginx
- HTTPS (จำเป็นสำหรับกล้องบนมือถือ — localhost ใช้ HTTP ได้)

---

## ไฟล์ที่ต้องสร้างเองและไม่มีใน GitHub

| ไฟล์ | เหตุผล |
|------|--------|
| `config.php` | มี password และ DB credentials |
| `connect.php` | ใช้ค่าจาก config.php |

## ไฟล์ที่ระบบสร้างให้อัตโนมัติ

| ไฟล์ | สร้างเมื่อ |
|------|-----------|
| `qr_tmp.png` | ทุกครั้งที่ QR Dynamic refresh |
