# QR Check-in System

ระบบเช็คชื่อนักเรียนด้วย QR Code แบบ Dynamic สำหรับห้องเรียน

---

## โครงสร้างไฟล์

```
checkin_form.php      หน้านักเรียนกรอกชื่อและสแกน QR
checkin_process.php   บันทึกข้อมูลการเช็คชื่อลง Database
config.php            ตั้งค่าระบบ (ไม่ขึ้น GitHub)
connect.php           เชื่อมต่อ Database (ไม่ขึ้น GitHub)
index.php             redirect ไปหน้า session_create.php
qr_ajax.php           API สร้าง QR ใหม่ทุก N วินาที
qr_display.php        หน้าฉาย QR บน Projector
qr_static.png         QR พานักเรียนไปหน้ากรอกชื่อ (สร้างอัตโนมัติ)
qr_tmp.png            QR Dynamic สำหรับเช็คชื่อ (สร้างอัตโนมัติ)
qrlib/                Library สร้าง QR Code (phpqrcode)
report.php            หน้ารายงาน session ทั้งหมด
report_detail.php     หน้ารายชื่อนักเรียนใน session พร้อม Export CSV
session_create.php    หน้า Login และกรอกข้อมูลวิชา
session_ready.php     หน้าแสดง QR Static สำหรับนักเรียน
```

---

## Database

ใช้ MySQL มี 2 ตาราง

### `ckn_sessions`
| Column | Type | Description |
|--------|------|-------------|
| id | INT AUTO_INCREMENT | Primary Key |
| sid | VARCHAR(100) UNIQUE | Session ID เช่น `CS101-1-2026-06-13` |
| secret_key | VARCHAR(64) | คีย์ลับสำหรับสร้าง HMAC |
| created_at | TIMESTAMP | เวลาที่เปิด session |

### `ckn_checkins`
| Column | Type | Description |
|--------|------|-------------|
| id | INT AUTO_INCREMENT | Primary Key |
| sid | VARCHAR(100) | อ้างอิง session |
| student_id | VARCHAR(20) | รหัสนักศึกษา |
| student_fname | VARCHAR(80) | ชื่อ |
| student_lname | VARCHAR(80) | นามสกุล |
| sig | VARCHAR(20) | Signature ของ QR ที่ใช้สแกน |
| ip_address | VARCHAR(45) | IP Address ของนักเรียน |
| elapsed | INT | ระยะเวลาตั้งแต่ QR generate ถึงสแกนติด (วินาที) |
| t_receive | TIMESTAMP | เวลาที่ server รับ request |

---

## การติดตั้ง

1. วางไฟล์ทั้งหมดในโฟลเดอร์บน server
2. สร้าง Database และ import SQL

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
    elapsed         INT          DEFAULT NULL,
    t_receive       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sid        (sid),
    INDEX idx_student_id (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

3. สร้างไฟล์ `config.php` จาก `config.example.php` แล้วแก้ค่าให้ตรงกับ server

```php
// Database
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'qrcheckin');

// Passwords
define('ADMIN_PASSWORD',  'your_admin_password');
define('REPORT_PASSWORD', 'your_report_password');

// QR Settings
define('WINDOW_SEC',  3);   // QR เปลี่ยนทุกกี่วินาที
define('SESSION_MIN', 1);   // เปิด session กี่นาที
```

4. สร้างไฟล์ `connect.php`

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

5. ตั้งค่า permission ให้ Apache เขียนไฟล์ได้

```bash
chmod -R 777 /path/to/checkin/
```

---

## วิธีใช้งาน

### ฝั่งผู้สอน

1. เปิด `index.php` หรือ `session_create.php`
2. ใส่รหัสผ่านผู้สอน (`ADMIN_PASSWORD`)
3. กรอก วิชา / ตอนเรียน / วันที่ แล้วกด **เปิดเช็คชื่อ**
4. หน้า `session_ready.php` จะแสดง QR Static ให้นักเรียนสแกนไปกรอกชื่อก่อน
5. เมื่อพร้อมกด **เปิด QR เช็คชื่อบน Projector** เพื่อฉาย QR Dynamic
6. กด **ต่อเวลา +1 นาที** หากนักเรียนยังเช็คชื่อไม่ครบ

### ฝั่งนักเรียน

1. สแกน QR Static จากหน้าจออาจารย์เพื่อเปิดฟอร์ม
2. กรอก รหัสนักศึกษา / ชื่อ / นามสกุล แล้วกด **ยืนยันและสแกน QR**
3. จ่อกล้องไปที่ QR Dynamic บน Projector
4. ระบบเช็คชื่ออัตโนมัติเมื่อสแกนติด

### ดูรายงาน

1. เปิด `report.php`
2. ใส่รหัสผ่านรายงาน (`REPORT_PASSWORD`)
3. เลือก session ที่ต้องการแล้วกด **ดูรายชื่อ**
4. กด **Export CSV** เพื่อนำไปใช้ต่อ

---

## หลักการทำงาน

### QR Dynamic
- QR เปลี่ยนทุก **N วินาที** (ตั้งค่าได้ใน `config.php`)
- ข้อมูลใน QR คือ `s=<sig>&t=<timestamp>`
- `sig` คำนวณจาก `HMAC-SHA256(sid + t + seq, secret_key)`

### การวัด elapsed
- `t` ใน QR คือเวลาที่ QR generate (Unix timestamp)
- นักเรียนสแกนติด JS บันทึก `t_scan = Date.now()/1000`
- `elapsed = t_scan - t`
- ถ้า elapsed สูง (>5 วิ) อาจหมายความว่าได้รับ QR มาจากคนอื่น

### การป้องกันการทุจริต
| elapsed | สถานะ |
|---------|-------|
| ≤ 3 วิ | ปกติ (สีเขียว) |
| 4-5 วิ | เฝ้าระวัง (สีเหลือง) |
| > 5 วิ | น่าสงสัย (สีแดง) |

---

## Requirements

- PHP 5.4+ (รองรับ PHP 5 และ PHP 8)
- MySQL 5.6+
- PHP extension: GD, OpenSSL, PDO, PDO_MySQL
- Apache
