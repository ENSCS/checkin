<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/qrlib/qrlib.php';

if (empty($_SESSION['sid']) || empty($_SESSION['secret_key'])) {
    header('Location: session_create.php');
    exit;
}

$sid        = $_SESSION['sid'];
$secret_key = $_SESSION['secret_key'];
$course     = isset($_SESSION['course'])     ? $_SESSION['course']     : '';
$section    = isset($_SESSION['section'])    ? $_SESSION['section']    : '';
$class_name = $course . ' ตอน ' . $section;

$t       = (int) floor(time() / WINDOW_SEC) * WINDOW_SEC;
$seq     = (int) floor(time() / WINDOW_SEC);
$sig_val = substr(hash_hmac('sha256', $sid . $t . $seq, $secret_key), 0, 8);

$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'];
$base_path = dirname($_SERVER['PHP_SELF']);
$qr_url = 's=' . $sig_val . '&t=' . $t;

$qr_file   = __DIR__ . '/qr_tmp.png';
QRcode::png($qr_url, $qr_file, QR_ECLEVEL_M, 8, 2);
$qr_base64 = 'data:image/png;base64,' . base64_encode(file_get_contents($qr_file));

$expires_at  = time() + (SESSION_MIN * 60);
$session_sec = SESSION_MIN * 60;
$window_sec  = WINDOW_SEC;
$class_name  = htmlspecialchars($class_name);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QR Check-in</title>
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
    gap: 0;
}
h1 { font-size: 1.8rem; margin-bottom: 6px; text-align: center; }
.sub { color: #888; font-size: 0.9rem; margin-bottom: 28px; text-align: center; }
.card {
    background: #fff;
    border-radius: 20px;
    padding: 28px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
}
#qr-img { width: 260px; height: 260px; display: block; }
.sig { font-size: 0.7rem; color: #999; font-family: monospace; letter-spacing: 1px; }
.countdown { color: #0f1117; font-size: 2rem; font-weight: 700; }
.countdown span { color: #639922; }
.bar-wrap { width: 100%; max-width: 340px; margin-top: 24px; text-align: center; }
.bar-label { font-size: 0.8rem; color: #666; margin-bottom: 6px; }
.bar-track { width: 100%; height: 8px; background: #2a2a2a; border-radius: 4px; overflow: hidden; }
.bar-fill { height: 100%; background: #639922; border-radius: 4px; transition: width 1s linear; }
.bar-time { margin-top: 6px; font-size: 0.9rem; color: #aaa; }
.btn-row {
    display: flex;
    gap: 10px;
    width: 100%;
    max-width: 340px;
    margin-top: 16px;
}
.btn-ready {
    flex: 1;
    padding: 14px;
    background: #2a2d3a;
    color: #aaa;
    border: 1px solid #3a3d4a;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}
.btn-ready:hover { background: #3a3d4a; color: #fff; }
.btn-extend {
    flex: 1;
    padding: 14px;
    background: #639922;
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}
.btn-extend:hover { background: #4d7a18; }
.expired {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.9);
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 16px;
}
.expired.show { display: flex; }
.expired h2 { font-size: 2rem; color: #E24B4A; }
.expired p { color: #888; }
.btn-expired-extend {
    padding: 14px 28px;
    background: #639922;
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}
.btn-expired-extend:hover { background: #4d7a18; }
.btn-new-session {
    padding: 14px 28px;
    background: #1a6fa8;
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
    text-decoration: none;
    display: inline-block;
}
.btn-new-session:hover { background: #155a8a; }
.btn-back-ready {
    padding: 14px 28px;
    background: #2a2d3a;
    color: #aaa;
    border: 1px solid #3a3d4a;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-block;
}
.btn-back-ready:hover { background: #3a3d4a; color: #fff; }
</style>
</head>
<body>

<h1><?php echo $class_name; ?></h1>
<p class="sub">เปิดเว็บแอพแล้วสแกน QR เพื่อเช็คชื่อ</p>

<div class="card">
    <img id="qr-img" src="<?php echo $qr_base64; ?>" alt="QR Code">
    <div class="sig" id="sig-label">sig: <?php echo $sig_val; ?> &nbsp;|&nbsp; t: <?php echo substr($t, -4); ?></div>
    <div class="countdown">เปลี่ยนใน <span id="cd"><?php echo $window_sec; ?></span> วิ</div>
</div>

<div class="bar-wrap">
    <div class="bar-label">เวลาเช็คชื่อที่เหลือ</div>
    <div class="bar-track">
        <div class="bar-fill" id="bar"></div>
    </div>
    <div class="bar-time" id="bar-time"></div>
</div>

<div class="btn-row">
    <a href="session_ready.php?course=<?php echo urlencode($course); ?>&section=<?php echo urlencode($section); ?>&class_date=<?php echo urlencode(isset($_SESSION['class_date']) ? $_SESSION['class_date'] : ''); ?>" class="btn-ready">QR ฟอร์ม นศ.</a>
    <button class="btn-extend" onclick="extendTime()">ต่อเวลา +1 นาที</button>
</div>

<div class="expired" id="expired">
    <h2>หมดเวลาเช็คชื่อ</h2>
    <p>อาจารย์กรุณาปิดหน้านี้</p>
    <button class="btn-expired-extend" onclick="extendTime()">ต่อเวลา +1 นาที</button>
    <a href="session_create.php" class="btn-new-session">เปิดเช็คชื่อใหม่</a>
    <a href="session_ready.php?course=<?php echo urlencode($course); ?>&section=<?php echo urlencode($section); ?>&class_date=<?php echo urlencode(isset($_SESSION['class_date']) ? $_SESSION['class_date'] : ''); ?>" class="btn-back-ready">แสดง QR ฟอร์มให้นักเรียน</a>
</div>

<script>
var WINDOW_SEC  = <?php echo $window_sec; ?>;
var SESSION_END = <?php echo $expires_at; ?>;
var SESSION_SEC = <?php echo $session_sec; ?>;
var lastWindow  = -1;

function pad(n) { return n < 10 ? '0' + n : '' + n; }

function extendTime() {
    SESSION_END = Math.floor(Date.now() / 1000) + SESSION_SEC;
    document.getElementById('expired').classList.remove('show');
    refreshQr();
}

function refreshQr() {
    fetch('qr_ajax.php')
        .then(function(r) { return r.json(); })
        .then(function(d) {
            document.getElementById('qr-img').src = d.qr;
            document.getElementById('sig-label').textContent = 'sig: ' + d.sig + '  |  t: ' + String(d.t).slice(-4);
        });
}

function tick() {
    var now      = Math.floor(Date.now() / 1000);
    var secsLeft = SESSION_END - now;

    if (secsLeft <= 0) {
        document.getElementById('expired').classList.add('show');
        return;
    }

    var m = Math.floor(secsLeft / 60);
    var s = secsLeft % 60;
    document.getElementById('bar-time').textContent = m + ':' + pad(s) + ' นาที';
    document.getElementById('bar').style.width = ((secsLeft / SESSION_SEC) * 100) + '%';

    var winLeft = WINDOW_SEC - (now % WINDOW_SEC);
    document.getElementById('cd').textContent = winLeft;

    var curWindow = Math.floor(now / WINDOW_SEC);
    if (lastWindow !== -1 && curWindow !== lastWindow) {
        refreshQr();
    }
    lastWindow = curWindow;
}

tick();
setInterval(tick, 1000);
</script>
</body>
</html>
