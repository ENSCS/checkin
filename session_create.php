<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
date_default_timezone_set('Asia/Bangkok');
require_once __DIR__ . '/config.php';

$today      = date('Y-m-d');
$auth_error = false;

if (!isset($_SESSION['is_admin'])) {
    if (isset($_POST['password'])) {
        if ($_POST['password'] === ADMIN_PASSWORD) {
            $_SESSION['is_admin'] = true;
        } else {
            $auth_error = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เปิดเช็คชื่อ</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: sans-serif;
            background: #0f1117;
            color: #f0f0f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .card {
            background: #1a1d27;
            border-radius: 16px;
            padding: 36px;
            width: 100%;
            max-width: 420px;
            border: 1px solid #2a2d3a;
        }

        h1 {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 6px;
            color: #fff;
        }

        .subtitle {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 28px;
        }

        .field {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-size: 0.85rem;
            color: #aaa;
            margin-bottom: 6px;
        }

        input {
            width: 100%;
            padding: 12px 14px;
            background: #0f1117;
            border: 1px solid #2a2d3a;
            border-radius: 8px;
            color: #f0f0f0;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.2s;
        }

        input:focus {
            border-color: #639922;
        }

        input::placeholder {
            color: #444;
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: #639922;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
            transition: background 0.2s;
        }

        .btn:hover {
            background: #4d7a18;
        }

        .error {
            background: #2a1a1a;
            border: 1px solid #E24B4A;
            border-radius: 8px;
            padding: 12px 14px;
            color: #E24B4A;
            font-size: 0.85rem;
            margin-bottom: 18px;
            display: none;
        }

        .error.show {
            display: block;
        }
    </style>
</head>

<body>

    <?php if (!isset($_SESSION['is_admin'])): ?>

        <!-- หน้าใส่ password -->
        <div class="card">
            <h1>เข้าสู่ระบบ</h1>
            <p class="subtitle">สำหรับผู้สอนเท่านั้น</p>

            <?php if ($auth_error): ?>
                <div class="error show">รหัสผ่านไม่ถูกต้อง</div>
            <?php endif; ?>

            <form method="POST">
                <div class="field">
                    <label>รหัสผ่าน</label>
                    <input type="password" name="password" placeholder="กรอกรหัสผ่าน" autofocus>
                </div>
                <button type="submit" class="btn">เข้าสู่ระบบ</button>
            </form>
        </div>

    <?php else: ?>

        <!-- หน้าเปิดเช็คชื่อ -->
        <div class="card">
            <h1>เปิดเช็คชื่อ</h1>
            <p class="subtitle">กรอกรายละเอียดแล้วกดเปิด</p>

            <div class="error" id="error-box"></div>

            <form id="form">
                <div class="field">
                    <label>วิชา</label>
                    <input type="text" id="course" placeholder="เช่น CS101 Introduction to Programming" maxlength="80">
                </div>

                <div class="field">
                    <label>ตอนเรียน</label>
                    <input type="text" id="section" placeholder="เช่น 1" maxlength="40">
                </div>

                <div class="field">
                    <label>วันที่</label>
                    <input type="date" id="class_date" value="<?php echo $today; ?>">
                </div>

                <button type="submit" class="btn">เปิดเช็คชื่อ</button>
            </form>
            <div style="text-align:center; margin-top:16px;">
                <a href="report.php" style="font-size:0.8rem; color:#444; text-decoration:none;">ดูรายงานการเช็คชื่อ</a>
            </div>
        </div>

        <script>
            document.getElementById('form').addEventListener('submit', function(e) {
                e.preventDefault();

                var course = document.getElementById('course').value.trim();
                var section = document.getElementById('section').value.trim();
                var class_date = document.getElementById('class_date').value;
                var errorBox = document.getElementById('error-box');

                if (!course || !section || !class_date) {
                    errorBox.textContent = 'กรุณากรอกข้อมูลให้ครบทุกช่อง';
                    errorBox.classList.add('show');
                    return;
                }

                errorBox.classList.remove('show');

                var params = new URLSearchParams({
                    course: course,
                    section: section,
                    class_date: class_date,
                });

                window.location.href = 'session_ready.php?' + params.toString();
            });
        </script>

    <?php endif; ?>

</body>

</html>