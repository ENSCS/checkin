<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/qrlib/qrlib.php';
require_once __DIR__ . '/connect.php';

// รับค่าจาก session_create.php
$course     = isset($_GET['course'])     ? trim($_GET['course'])     : '';
$section    = isset($_GET['section'])    ? trim($_GET['section'])    : '';
$class_date = isset($_GET['class_date']) ? trim($_GET['class_date']) : date('Y-m-d');

// ถ้าไม่มีข้อมูลให้กลับไปหน้าแรก
if (!$course || !$section || !$class_date) {
    header('Location: session_create.php');
    exit;
}

// สร้าง sid
$sid = $course . '-' . $section . '-' . $class_date;

// secret_key สำหรับ session นี้
//$secret_key = bin2hex(random_bytes(16));
$secret_key = bin2hex(openssl_random_pseudo_bytes(16));

// บันทึกลง DB (ถ้า sid ซ้ำให้ update secret_key ใหม่)
$stmt = $pdo->prepare('
    INSERT INTO ckn_sessions (sid, secret_key)
    VALUES (:sid, :secret_key)
    ON DUPLICATE KEY UPDATE secret_key = :secret_key2
');
$stmt->execute(array(
    ':sid'         => $sid,
    ':secret_key'  => $secret_key,
    ':secret_key2' => $secret_key,
));

// เก็บใน PHP session
session_start();
$_SESSION['sid']        = $sid;
$_SESSION['course']     = $course;
$_SESSION['section']    = $section;
$_SESSION['class_date'] = $class_date;
$_SESSION['secret_key'] = $secret_key;

// สร้าง QR static → พานักเรียนไปหน้ากรอกชื่อ
$protocol    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host        = $_SERVER['HTTP_HOST'];
$base_path   = dirname($_SERVER['PHP_SELF']);
$checkin_url = $protocol . '://' . $host . $base_path . '/checkin_form.php?sid=' . urlencode($sid);

$qr_file   = __DIR__ . '/qr_static.png';
QRcode::png($checkin_url, $qr_file, QR_ECLEVEL_M, 8, 2);
$qr_base64 = 'data:image/png;base64,' . base64_encode(file_get_contents($qr_file));

$display_date = date('d/m/Y', strtotime($class_date));
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>เตรียมเช็คชื่อ</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: sans-serif;
    background: #0f1117;
    color: #f0f0f0;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 24px;
    gap: 24px;
}
h1 { font-size: 1.4rem; font-weight: 600; text-align: center; }
.meta { font-size: 0.85rem; color: #666; text-align: center; margin-top: 4px; }
.card {
    background: #1a1d27;
    border-radius: 16px;
    padding: 28px;
    width: 100%;
    max-width: 420px;
    border: 1px solid #2a2d3a;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
}
.card-label { font-size: 0.8rem; color: #666; text-align: center; }
.qr-wrap { background: #fff; border-radius: 12px; padding: 16px; }
.qr-wrap img { display: block; width: 220px; height: 220px; }
.info-box {
    width: 100%;
    background: #0f1117;
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 0.85rem;
    color: #aaa;
    line-height: 1.8;
}
.info-box span { color: #f0f0f0; font-weight: 600; }
.url-box {
    width: 100%;
    background: #0f1117;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 0.75rem;
    color: #555;
    font-family: monospace;
    word-break: break-all;
    text-align: center;
}
.btn-open {
    width: 100%;
    max-width: 420px;
    padding: 16px;
    background: #639922;
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
    text-align: center;
    text-decoration: none;
    display: block;
}
.btn-open:hover { background: #4d7a18; }
.btn-back {
    font-size: 0.8rem;
    color: #555;
    text-decoration: none;
    margin-top: -8px;
}
.btn-back:hover { color: #aaa; }
</style>
</head>
<body>

<div>
    <h1><?php echo htmlspecialchars($course); ?></h1>
    <p class="meta">ตอน <?php echo htmlspecialchars($section); ?> &nbsp;|&nbsp; <?php echo $display_date; ?></p>
</div>

<div class="card">
    <div class="card-label">ให้นักเรียนสแกน QR นี้เพื่อเปิดฟอร์มกรอกชื่อ</div>
    <div class="qr-wrap">
        <img src="<?php echo $qr_base64; ?>" alt="QR สำหรับนักเรียน">
    </div>
    <div class="info-box">
        วิชา: <span><?php echo htmlspecialchars($course); ?></span><br>
        ตอน: <span><?php echo htmlspecialchars($section); ?></span><br>
        วันที่: <span><?php echo $display_date; ?></span><br>
        Session ID: <span><?php echo htmlspecialchars($sid); ?></span>
    </div>
    <div class="url-box"><?php echo htmlspecialchars($checkin_url); ?></div>
</div>

<a href="qr_display.php" class="btn-open">เปิด QR เช็คชื่อบน Projector</a>
<a href="session_create.php" class="btn-back">ย้อนกลับ</a>

</body>
</html>
