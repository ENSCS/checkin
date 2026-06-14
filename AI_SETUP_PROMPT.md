# AI Setup Prompt — QR Check-in System

## ภารกิจ

คุณคือ AI Assistant ที่จะช่วย setup ระบบ **QR Check-in System** จาก GitHub repository นี้ให้รันได้บน server ของผู้ใช้

Repository: https://github.com/ENSCS/checkin

---

## ความเข้าใจระบบ

ระบบนี้คือ **ระบบเช็คชื่อนักเรียนด้วย QR Code แบบ Dynamic** เขียนด้วย PHP + MySQL

### หลักการทำงาน
- ผู้สอนเปิด session แล้วฉาย QR บน Projector
- QR เปลี่ยนทุก N วินาที (default 3 วิ) ป้องกันส่งต่อออกนอกห้อง
- นักเรียนสแกน QR Static ก่อนเพื่อเปิดฟอร์มกรอกชื่อ
- จากนั้นสแกน QR Dynamic บน Projector ผ่านกล้องในหน้าเดียวกัน
- ระบบบันทึกเวลา elapsed ตั้งแต่ QR generate ถึงสแกนติด

### flow การทำงาน
```
session_create.php → session_ready.php → qr_display.php
                          ↓
                    checkin_form.php → checkin_process.php → Database
```

---

## สิ่งที่ต้องมีก่อน

### Server Requirements
- PHP 5.4+ (รองรับทั้ง PHP 5 และ PHP 8)
- MySQL 5.6+
- PHP extensions ที่ต้องการ: `GD`, `OpenSSL`, `PDO`, `PDO_MySQL`
- Apache หรือ Nginx
- permission เขียนไฟล์ในโฟลเดอร์ได้ (สำหรับ generate QR image)

### ตรวจสอบ PHP extensions
```bash
php -m | grep -E "gd|openssl|pdo"
```

---

## ขั้นตอน Setup

### ขั้นที่ 1 — Clone repository
```bash
git clone https://github.com/ENSCS/checkin.git
cd checkin
```

### ขั้นที่ 2 — ตั้งค่า permission
```bash
chmod -R 777 /path/to/checkin/
```

### ขั้นที่ 3 — สร้าง Database

รัน SQL นี้ใน MySQL (ผ่าน phpMyAdmin หรือ Terminal)

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

### ขั้นที่ 4 — สร้าง config.php

สร้างไฟล์ `config.php` ในโฟลเดอร์ checkin

```php
<?php
// =============================
// Database
// =============================
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_username');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'qrcheckin');

// =============================
// Passwords
// =============================
define('ADMIN_PASSWORD',  'your_admin_password');   // รหัสผ่านผู้สอน
define('REPORT_PASSWORD', 'your_report_password');  // รหัสผ่านดูรายงาน

// =============================
// QR Settings
// =============================
define('WINDOW_SEC',  3);   // QR เปลี่ยนทุกกี่วินาที
define('SESSION_MIN', 1);   // เปิด session กี่นาที
```

### ขั้นที่ 5 — สร้าง connect.php

สร้างไฟล์ `connect.php` ในโฟลเดอร์ checkin

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

### ขั้นที่ 6 — ทดสอบ

เปิด browser ไปที่ URL ของโฟลเดอร์ checkin เช่น

```
http://localhost/checkin/
```

---

## โครงสร้างไฟล์หลังติดตั้ง

```
checkin/
├── .gitignore
├── README.md
├── AI_SETUP_PROMPT.md
├── config.php            ← สร้างเอง (ไม่มีใน GitHub)
├── connect.php           ← สร้างเอง (ไม่มีใน GitHub)
├── index.php
├── session_create.php
├── session_ready.php
├── qr_display.php
├── qr_ajax.php
├── checkin_form.php
├── checkin_process.php
├── report.php
├── report_detail.php
├── qr_static.png         ← สร้างอัตโนมัติตอนใช้งาน
├── qr_tmp.png            ← สร้างอัตโนมัติตอนใช้งาน
└── qrlib/
    ├── qrlib.php
    ├── qrconst.php
    ├── qrconfig.php
    ├── qrtools.php
    ├── qrspec.php
    ├── qrimage.php
    ├── qrinput.php
    ├── qrbitstream.php
    ├── qrsplit.php
    ├── qrrscode.php
    ├── qrmask.php
    └── qrencode.php
```

---

## Database Schema

### ckn_sessions — เก็บข้อมูลการเปิดเช็คชื่อ
| Column | Type | Description |
|--------|------|-------------|
| id | INT AUTO_INCREMENT | Primary Key |
| sid | VARCHAR(100) UNIQUE | Session ID เช่น `CS101-1-2026-06-13` |
| secret_key | VARCHAR(64) | คีย์ลับสำหรับ HMAC |
| created_at | TIMESTAMP | เวลาที่เปิด session |

### ckn_checkins — เก็บข้อมูลการเช็คชื่อนักเรียน
| Column | Type | Description |
|--------|------|-------------|
| id | INT AUTO_INCREMENT | Primary Key |
| sid | VARCHAR(100) | อ้างอิง session |
| student_id | VARCHAR(20) | รหัสนักศึกษา |
| student_fname | VARCHAR(80) | ชื่อ |
| student_lname | VARCHAR(80) | นามสกุล |
| sig | VARCHAR(20) | Signature ของ QR |
| ip_address | VARCHAR(45) | IP Address รองรับ IPv6 |
| elapsed | INT | เวลาตั้งแต่ QR generate ถึงสแกนติด (วินาที) |
| t_receive | TIMESTAMP | เวลาที่ server รับ request |

---

## ไฟล์ที่ต้องสร้างเองและไม่มีใน GitHub

| ไฟล์ | เหตุผล |
|------|--------|
| `config.php` | มี password และ DB credentials |
| `connect.php` | ใช้ค่าจาก config.php |

---

## ปัญหาที่พบบ่อย

### HTTP ERROR 500
- เช็ค PHP error log
- เพิ่ม `ini_set('display_errors', 1);` บนสุดของไฟล์ที่มีปัญหา

### QR ไม่แสดง
- ตรวจสอบว่า PHP extension `GD` เปิดอยู่
- ตรวจสอบ permission ของโฟลเดอร์ว่าเขียนไฟล์ได้

### เวลาไม่ตรง
- ตรวจสอบว่าทุกไฟล์มี `date_default_timezone_set('Asia/Bangkok');`

### กล้องไม่ขึ้นบนมือถือ
- ต้องใช้ HTTPS เท่านั้น (localhost ใช้ HTTP ได้)
- ต้องอนุญาต permission กล้องในบราวเซอร์

### elapsed เป็น null
- ตรวจสอบว่า column `elapsed` มีอยู่ใน table `ckn_checkins`
- รัน: `ALTER TABLE ckn_checkins ADD COLUMN elapsed INT DEFAULT NULL;`

---

## การ Customize

ทุกค่าตั้งได้ใน `config.php`

```php
define('WINDOW_SEC',  3);   // ลดให้ QR เปลี่ยนเร็วขึ้น หรือเพิ่มให้ช้าลง
define('SESSION_MIN', 1);   // เพิ่มเวลาเปิด session
define('ADMIN_PASSWORD',  'xxx');  // เปลี่ยนรหัสผ่านผู้สอน
define('REPORT_PASSWORD', 'xxx');  // เปลี่ยนรหัสผ่านรายงาน
```

---

## หมายเหตุสำหรับ AI

- `config.php` และ `connect.php` ไม่มีใน GitHub ต้องสร้างเอง
- `qr_static.png` และ `qr_tmp.png` ระบบสร้างให้อัตโนมัติ ไม่ต้องสร้างเอง
- PHP 5 ให้ใช้ `openssl_random_pseudo_bytes()` แทน `random_bytes()`
- ระบบใช้ `session_start()` ทุกไฟล์ ต้องไม่มี output ก่อน header
- timezone ใช้ `Asia/Bangkok` ทุกไฟล์ที่เกี่ยวกับเวลา
