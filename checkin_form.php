<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

$sid = isset($_GET['sid']) ? trim($_GET['sid']) : '';

if (!$sid) {
    header('Location: session_create.php');
    exit;
}

$class_date   = substr($sid, -10);
$display_date = date('d/m/Y', strtotime($class_date));
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>เช็คชื่อ</title>
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
}
.card {
    background: #1a1d27;
    border-radius: 16px;
    padding: 28px 24px;
    width: 100%;
    max-width: 400px;
    border: 1px solid #2a2d3a;
}
h1 { font-size: 1.2rem; font-weight: 600; margin-bottom: 4px; color: #fff; }
.meta { font-size: 0.8rem; color: #555; margin-bottom: 24px; }
.field { margin-bottom: 16px; }
label { display: block; font-size: 0.85rem; color: #aaa; margin-bottom: 6px; }
input {
    width: 100%;
    padding: 14px;
    background: #0f1117;
    border: 1px solid #2a2d3a;
    border-radius: 10px;
    color: #f0f0f0;
    font-size: 1rem;
    outline: none;
    transition: border-color 0.2s;
    -webkit-appearance: none;
}
input:focus { border-color: #639922; }
input::placeholder { color: #444; }
.btn {
    width: 100%;
    padding: 16px;
    background: #639922;
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    margin-top: 8px;
    -webkit-appearance: none;
}
.btn:active { background: #4d7a18; }
.error {
    background: #2a1a1a;
    border: 1px solid #E24B4A;
    border-radius: 8px;
    padding: 12px;
    color: #E24B4A;
    font-size: 0.85rem;
    margin-bottom: 16px;
    display: none;
}
.error.show { display: block; }

/* หน้าสแกน QR */
.scan-screen {
    display: none;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    width: 100%;
    max-width: 400px;
    text-align: center;
}
.scan-screen.show { display: flex; }
.card.hide { display: none; }

.scan-name { font-size: 1rem; font-weight: 600; color: #fff; }
.scan-sub  { font-size: 0.8rem; color: #555; margin-top: -8px; }

.camera-wrap {
    width: 100%;
    position: relative;
    border-radius: 16px;
    overflow: hidden;
    background: #000;
    aspect-ratio: 1;
}
#preview {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.scan-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}
.scan-box {
    width: 60%;
    aspect-ratio: 1;
    border: 2px solid #639922;
    border-radius: 12px;
    box-shadow: 0 0 0 1000px rgba(0,0,0,0.4);
}
.scan-line {
    position: absolute;
    width: 56%;
    height: 2px;
    background: #639922;
    animation: scanline 2s ease-in-out infinite;
    opacity: 0.8;
}
@keyframes scanline {
    0%   { top: 22%; }
    50%  { top: 74%; }
    100% { top: 22%; }
}
.scan-label {
    font-size: 0.85rem;
    color: #aaa;
}

/* หน้าผลลัพธ์ */
.result-screen {
    display: none;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    width: 100%;
    max-width: 400px;
    text-align: center;
}
.result-screen.show { display: flex; }
.result-icon {
    width: 80px; height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2rem;
}
.result-icon.success { background: #1a2d0f; border: 2px solid #639922; }
.result-icon.error   { background: #2a1a1a; border: 2px solid #E24B4A; }
.result-title { font-size: 1.3rem; font-weight: 600; }
.result-title.success { color: #639922; }
.result-title.error   { color: #E24B4A; }
.result-name  { font-size: 1rem; color: #fff; font-weight: 600; }
.result-id    { font-size: 0.85rem; color: #555; }
.result-box {
    background: #1a1d27;
    border-radius: 12px;
    padding: 16px 20px;
    width: 100%;
    font-size: 0.85rem;
    color: #aaa;
    line-height: 2;
    border: 1px solid #2a2d3a;
}
.result-box span { color: #f0f0f0; }
.btn-retry {
    width: 100%;
    padding: 14px;
    background: #2a2d3a;
    color: #aaa;
    border: none;
    border-radius: 10px;
    font-size: 0.9rem;
    cursor: pointer;
    margin-top: 4px;
}
</style>
</head>
<body>

<!-- หน้ากรอกข้อมูล -->
<div class="card" id="form-card">
    <h1>เช็คชื่อเข้าเรียน</h1>
    <p class="meta"><?php echo htmlspecialchars($sid); ?> &nbsp;|&nbsp; <?php echo $display_date; ?></p>

    <div class="error" id="error-box"></div>

    <div class="field">
        <label>รหัสนักศึกษา</label>
        <input type="text" id="student_id" placeholder="เช่น 6401001234"
               inputmode="numeric" maxlength="20" autocomplete="off">
    </div>

    <div class="field">
        <label>ชื่อ</label>
        <input type="text" id="student_fname" placeholder="เช่น สมชาย"
               maxlength="40" autocomplete="off">
    </div>

    <div class="field">
        <label>นามสกุล</label>
        <input type="text" id="student_lname" placeholder="เช่น ใจดี"
               maxlength="40" autocomplete="off">
    </div>

    <button class="btn" id="btn-confirm">ยืนยันและสแกน QR</button>
</div>

<!-- หน้าสแกน QR -->
<div class="scan-screen" id="scan-screen">
    <div class="scan-name" id="scan-name"></div>
    <div class="scan-sub"  id="scan-id"></div>
    <div class="camera-wrap">
        <video id="preview" autoplay playsinline muted></video>
        <div class="scan-overlay">
            <div class="scan-box"></div>
            <div class="scan-line"></div>
        </div>
    </div>
    <div class="scan-label">จ่อกล้องไปที่ QR บนจอหน้าห้อง</div>
    <canvas id="canvas" style="display:none"></canvas>
</div>

<!-- หน้าผลลัพธ์ -->
<div class="result-screen" id="result-screen">
    <div class="result-icon" id="res-icon"></div>
    <div class="result-title" id="res-title"></div>
    <div class="result-name"  id="res-name" style="display:none"></div>
    <div class="result-id"    id="res-id"   style="display:none"></div>
    <div class="result-box"   id="res-box"  style="display:none"></div>
    <button class="btn-retry" id="btn-retry" style="display:none" onclick="retryForm()">กรอกใหม่อีกครั้ง</button>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script>
var sid          = '<?php echo addslashes($sid); ?>';
var student_id    = '';
var student_fname = '';
var student_lname = '';
var videoStream   = null;
var scanInterval  = null;

document.getElementById('student_id').addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '');
});

document.getElementById('btn-confirm').addEventListener('click', function() {
    student_id    = document.getElementById('student_id').value.trim();
    student_fname = document.getElementById('student_fname').value.trim();
    student_lname = document.getElementById('student_lname').value.trim();
    var errorBox  = document.getElementById('error-box');

    if (!student_id || !student_fname || !student_lname) {
        errorBox.textContent = 'กรุณากรอกข้อมูลให้ครบทุกช่อง';
        errorBox.classList.add('show');
        return;
    }
    if (!/^[0-9]+$/.test(student_id)) {
        errorBox.textContent = 'รหัสนักศึกษาต้องเป็นตัวเลขเท่านั้น';
        errorBox.classList.add('show');
        return;
    }

    errorBox.classList.remove('show');

    document.getElementById('scan-name').textContent = student_fname + ' ' + student_lname;
    document.getElementById('scan-id').textContent   = student_id;
    document.getElementById('form-card').classList.add('hide');
    document.getElementById('scan-screen').classList.add('show');

    startCamera();
});

function startCamera() {
    navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'environment' }
    })
    .then(function(stream) {
        videoStream = stream;
        var video = document.getElementById('preview');
        video.srcObject = stream;
        video.play();
        scanInterval = setInterval(scanFrame, 300);
    })
    .catch(function() {
        showResult(false, 'ไม่สามารถเปิดกล้องได้', '', '', 'กรุณาอนุญาตการใช้งานกล้องแล้วลองใหม่');
    });
}

function stopCamera() {
    if (videoStream) {
        videoStream.getTracks().forEach(function(t) { t.stop(); });
        videoStream = null;
    }
    if (scanInterval) {
        clearInterval(scanInterval);
        scanInterval = null;
    }
}

function scanFrame() {
    var video  = document.getElementById('preview');
    var canvas = document.getElementById('canvas');
    if (video.readyState !== video.HAVE_ENOUGH_DATA) return;

    canvas.width  = video.videoWidth;
    canvas.height = video.videoHeight;
    var ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    var imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    var code = jsQR(imageData.data, imageData.width, imageData.height);

    if (code && code.data) {
        var url   = code.data;
        var matchS = url.match(/(?:^|[?&])s=([^&]+)/);
        var matchT = url.match(/(?:^|[?&])t=([^&]+)/);
        if (matchS && matchT) {
            var sig     = matchS[1];
            var t_qr    = parseInt(matchT[1]);
            var t_scan  = Math.floor(Date.now() / 1000);
            var elapsed = t_scan - t_qr;
            stopCamera();
            submitCheckin(sig, elapsed);
        }
    }
}

function submitCheckin(sig, elapsed) {
    document.getElementById('scan-screen').classList.remove('show');

    fetch('checkin_process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            sig:           sig,
            sid:           sid,
            student_id:    student_id,
            student_fname: student_fname,
            student_lname: student_lname,
            elapsed:       elapsed,
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success) {
            var elapsed_text = d.elapsed !== null ? d.elapsed + ' วินาที' : '-';
            var detail = 'วิชา: <span>' + d.course + '</span><br>'
                       + 'ตอน: <span>' + d.section + '</span><br>'
                       + 'วันเวลา: <span>' + d.t_receive + '</span><br>'
                       + 'ระยะเวลา: <span>' + elapsed_text + '</span>';
            showResult(true, 'เช็คชื่อสำเร็จ', student_fname + ' ' + student_lname, student_id, detail);
        } else {
            showResult(false, d.message || 'เกิดข้อผิดพลาด', '', '', '');
        }
    })
    .catch(function() {
        showResult(false, 'ไม่สามารถเชื่อมต่อได้', '', '', 'กรุณาลองใหม่อีกครั้ง');
    });
}

function showResult(success, title, name, stid, detail) {
    document.getElementById('scan-screen').classList.remove('show');
    var res = document.getElementById('result-screen');
    res.classList.add('show');

    var icon  = document.getElementById('res-icon');
    var ttl   = document.getElementById('res-title');
    icon.textContent = success ? '✓' : '✗';
    icon.className   = 'result-icon ' + (success ? 'success' : 'error');
    ttl.textContent  = title;
    ttl.className    = 'result-title ' + (success ? 'success' : 'error');

    if (name) {
        document.getElementById('res-name').textContent = name;
        document.getElementById('res-name').style.display = 'block';
        document.getElementById('res-id').textContent   = stid;
        document.getElementById('res-id').style.display = 'block';
    }
    if (detail) {
        document.getElementById('res-box').innerHTML    = detail;
        document.getElementById('res-box').style.display = 'block';
    }
    if (!success) {
        document.getElementById('btn-retry').style.display = 'block';
    }
}

function retryForm() {
    document.getElementById('result-screen').classList.remove('show');
    document.getElementById('form-card').classList.remove('hide');
    document.getElementById('res-name').style.display = 'none';
    document.getElementById('res-id').style.display   = 'none';
    document.getElementById('res-box').style.display  = 'none';
    document.getElementById('btn-retry').style.display = 'none';
}
</script>
</body>
</html>
