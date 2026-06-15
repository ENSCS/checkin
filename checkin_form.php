<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
date_default_timezone_set('Asia/Bangkok');

// รับค่าจาก QR URL: ?id=7&s=sig&t=timestamp
$db_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$sig   = isset($_GET['s'])  ? trim($_GET['s'])  : '';
$t_qr  = isset($_GET['t'])  ? (int)$_GET['t']  : 0;

if (!$db_id || !$sig || !$t_qr) {
    header('Location: session_create.php');
    exit;
}

// lookup sid จาก DB
require_once __DIR__ . '/connect.php';
$stmt = $pdo->prepare('SELECT id, sid FROM ckn_sessions WHERE id = :id LIMIT 1');
$stmt->execute(array(':id' => $db_id));
$session_row = $stmt->fetch();

if (!$session_row) {
    header('Location: session_create.php');
    exit;
}

$sid        = $session_row['sid'];
$class_date = substr($sid, -10);
$display_date = date('d/m/Y', strtotime($class_date));

// แยก course/section เพื่อแสดงผล
$prefix  = substr($sid, 0, -11);
$dash    = strrpos($prefix, '-');
$course  = $dash !== false ? substr($prefix, 0, $dash) : $prefix;
$section = $dash !== false ? substr($prefix, $dash + 1) : '';
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
.card.hide { display: none; }

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
.result-id    { font-size: 1.4rem; color: #fff; font-weight: 700; letter-spacing: 1px; }
.result-name  { font-size: 1.4rem; color: #fff; font-weight: 700; }
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
    <p class="meta"><?php echo htmlspecialchars($course); ?> ตอน <?php echo htmlspecialchars($section); ?> &nbsp;|&nbsp; <?php echo $display_date; ?></p>

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

    <button class="btn" id="btn-confirm">ยืนยันการเช็คชื่อ</button>
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

<script>
var db_id         = <?php echo $db_id; ?>;
var sid           = '<?php echo addslashes($sid); ?>';
var t_qr          = <?php echo $t_qr; ?>;
var sig_from_url  = '<?php echo addslashes($sig); ?>';
var t_scan        = Math.floor(Date.now() / 1000); // บันทึกเวลาที่หน้าโหลด = เวลาสแกน QR ติด

document.getElementById('student_id').addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '');
});

document.getElementById('btn-confirm').addEventListener('click', function() {
    var student_id    = document.getElementById('student_id').value.trim();
    var student_fname = document.getElementById('student_fname').value.trim();
    var student_lname = document.getElementById('student_lname').value.trim();
    var errorBox      = document.getElementById('error-box');

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
    document.getElementById('btn-confirm').disabled = true;

    var t_submit     = Math.floor(Date.now() / 1000);
    var elapsed_scan = t_scan - t_qr;     // เวลาตั้งแต่ QR gen ถึงสแกนติด (สำคัญ)
    var elapsed_fill = t_submit - t_scan; // เวลากรอกชื่อ (ข้อมูลเพิ่มเติม)

    submitCheckin(student_id, student_fname, student_lname, elapsed_scan, elapsed_fill);
});

function submitCheckin(student_id, student_fname, student_lname, elapsed_scan, elapsed_fill) {
    fetch('checkin_process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            db_id:         db_id,
            sig:           sig_from_url,
            t_qr:          t_qr,
            student_id:    student_id,
            student_fname: student_fname,
            student_lname: student_lname,
            elapsed_scan:  elapsed_scan,
            elapsed_fill:  elapsed_fill,
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        document.getElementById('form-card').classList.add('hide');
        if (d.success) {
            var elapsed_text = d.elapsed_scan !== null ? d.elapsed_scan + ' วินาที' : '-';
            var detail = 'วิชา: <span>' + d.course + '</span><br>'
                       + 'ตอน: <span>' + d.section + '</span><br>'
                       + 'วันเวลา: <span>' + d.t_receive + '</span><br>'
                       + 'ระยะเวลาสแกน: <span>' + elapsed_text + '</span>';
            showResult(true, 'เช็คชื่อสำเร็จ', student_fname + ' ' + student_lname, student_id, detail);
        } else {
            showResult(false, d.message || 'เกิดข้อผิดพลาด', '', '', '');
        }
    })
    .catch(function() {
        document.getElementById('btn-confirm').disabled = false;
        showResult(false, 'ไม่สามารถเชื่อมต่อได้', '', '', 'กรุณาลองใหม่อีกครั้ง');
    });
}

function showResult(success, title, name, stid, detail) {
    document.getElementById('result-screen').classList.add('show');

    var icon = document.getElementById('res-icon');
    var ttl  = document.getElementById('res-title');
    icon.textContent = success ? '✓' : '✗';
    icon.className   = 'result-icon ' + (success ? 'success' : 'error');
    ttl.textContent  = title;
    ttl.className    = 'result-title ' + (success ? 'success' : 'error');

    if (name) {
        document.getElementById('res-id').textContent   = stid;
        document.getElementById('res-id').style.display = 'block';
        document.getElementById('res-name').textContent = name;
        document.getElementById('res-name').style.display = 'block';
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
    document.getElementById('btn-confirm').disabled = false;
    document.getElementById('res-name').style.display = 'none';
    document.getElementById('res-id').style.display   = 'none';
    document.getElementById('res-box').style.display  = 'none';
    document.getElementById('btn-retry').style.display = 'none';
    // รีเซ็ต t_scan ใหม่เมื่อกรอกใหม่
    t_scan = Math.floor(Date.now() / 1000);
}
</script>
</body>
</html>
