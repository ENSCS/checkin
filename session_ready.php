<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/qrlib/qrlib.php';
require_once __DIR__ . '/connect.php';

$course     = isset($_GET['course'])     ? trim($_GET['course'])     : '';
$section    = isset($_GET['section'])    ? trim($_GET['section'])    : '';
$class_date = isset($_GET['class_date']) ? trim($_GET['class_date']) : date('Y-m-d');

if (!$course || !$section || !$class_date) {
    header('Location: session_create.php');
    exit;
}

$sid = $course . '-' . $section . '-' . $class_date;

// SELECT ก่อน — ถ้ามีอยู่แล้วเอาค่าเดิม ไม่สร้างใหม่
$stmt = $pdo->prepare('SELECT id, secret_key FROM ckn_sessions WHERE sid = :sid LIMIT 1');
$stmt->execute(array(':sid' => $sid));
$existing = $stmt->fetch();

if ($existing) {
    $session_db_id = $existing['id'];
    $secret_key    = $existing['secret_key'];
} else {
    $secret_key = bin2hex(openssl_random_pseudo_bytes(16));
    $stmt = $pdo->prepare('INSERT INTO ckn_sessions (sid, secret_key) VALUES (:sid, :secret_key)');
    $stmt->execute(array(':sid' => $sid, ':secret_key' => $secret_key));
    $session_db_id = (int)$pdo->lastInsertId();
}

session_start();
$_SESSION['sid']            = $sid;
$_SESSION['session_db_id']  = $session_db_id;
$_SESSION['course']         = $course;
$_SESSION['section']        = $section;
$_SESSION['class_date']     = $class_date;
$_SESSION['secret_key']     = $secret_key;

// QR Static ยังเก็บไว้ให้ แต่หน้านี้ไม่ได้ใช้แล้ว (นศ. สแกน Dynamic QR ครั้งเดียว)
$protocol    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host        = $_SERVER['HTTP_HOST'];
$base_path   = dirname($_SERVER['PHP_SELF']);
$checkin_url = $protocol . '://' . $host . $base_path . '/checkin_form.php?id=' . $session_db_id;

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
.notice {
    width: 100%;
    background: #1a2d0f;
    border: 1px solid #2a4a1a;
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 0.85rem;
    color: #639922;
    text-align: center;
    line-height: 1.6;
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
    <div class="info-box">
        วิชา: <span><?php echo htmlspecialchars($course); ?></span><br>
        ตอน: <span><?php echo htmlspecialchars($section); ?></span><br>
        วันที่: <span><?php echo $display_date; ?></span><br>
        Session ID: <span><?php echo htmlspecialchars($sid); ?></span><br>
        DB ID: <span>#<?php echo $session_db_id; ?></span>
    </div>
    <div class="notice">
        นศ. สแกน QR บน Projector <strong>ครั้งเดียว</strong><br>
        แล้วกรอกชื่อในหน้าที่เปิดขึ้นมาเลย
    </div>
</div>

<a href="qr_display.php" class="btn-open">เปิด QR เช็คชื่อบน Projector</a>
<a href="session_create.php" class="btn-back">ย้อนกลับ</a>

</body>
</html>
