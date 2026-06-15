<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
date_default_timezone_set('Asia/Bangkok');

if (!isset($_SESSION['is_report'])) {
    header('Location: report.php');
    exit;
}

$sid = isset($_GET['sid']) ? trim($_GET['sid']) : '';
if (!$sid) {
    header('Location: report.php');
    exit;
}

require_once __DIR__ . '/connect.php';

$stmt = $pdo->prepare('
    SELECT id, student_id, student_fname, student_lname, sig, t_receive, ip_address, elapsed_scan, elapsed_fill
    FROM ckn_checkins
    WHERE sid = :sid
    ORDER BY t_receive ASC
');
$stmt->execute(array(':sid' => $sid));
$checkins = $stmt->fetchAll();

$date    = substr($sid, -10);
$prefix  = substr($sid, 0, -11);
$dash    = strrpos($prefix, '-');
$course  = $dash !== false ? substr($prefix, 0, $dash) : $prefix;
$section = $dash !== false ? substr($prefix, $dash + 1) : '';

// export CSV
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="checkin_' . $sid . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($out, array('ลำดับ', 'รหัสนักศึกษา', 'ชื่อ', 'นามสกุล', 'วันเวลาเช็คชื่อ', 'ระยะเวลาสแกน (วิ)', 'ระยะเวลากรอก (วิ)', 'IP Address'));
    $i = 1;
    foreach ($checkins as $c) {
        $es = ($c['elapsed_scan'] !== null) ? (int)$c['elapsed_scan'] : '-';
        $ef = ($c['elapsed_fill'] !== null) ? (int)$c['elapsed_fill'] : '-';
        fputcsv($out, array(
            $i++,
            $c['student_id'],
            $c['student_fname'],
            $c['student_lname'],
            $c['t_receive'],
            $es,
            $ef,
            $c['ip_address'],
        ));
    }
    fclose($out);
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>รายชื่อเช็คชื่อ — <?php echo htmlspecialchars($sid); ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: sans-serif;
    background: #0f1117;
    color: #f0f0f0;
    min-height: 100vh;
    padding: 24px;
}
.wrap { max-width: 1000px; margin: 0 auto; }
.page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 12px;
}
.page-title { font-size: 1.3rem; font-weight: 600; }
.page-meta { font-size: 0.85rem; color: #555; margin-top: 4px; }
.header-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.btn {
    padding: 10px 18px;
    background: #639922;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: background 0.2s;
}
.btn:hover { background: #4d7a18; }
.btn-outline {
    padding: 10px 18px;
    background: transparent;
    color: #aaa;
    border: 1px solid #2a2d3a;
    border-radius: 8px;
    font-size: 0.9rem;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: all 0.2s;
}
.btn-outline:hover { border-color: #639922; color: #639922; }

.summary-row {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.summary-box {
    background: #1a1d27;
    border-radius: 10px;
    padding: 14px 20px;
    border: 1px solid #2a2d3a;
    font-size: 0.85rem;
    color: #666;
}
.summary-box strong { display: block; font-size: 1.4rem; color: #f0f0f0; font-weight: 600; margin-bottom: 2px; }

.search-box {
    background: #1a1d27;
    border-radius: 10px;
    padding: 14px 16px;
    border: 1px solid #2a2d3a;
    margin-bottom: 16px;
}
.search-box input {
    width: 100%;
    padding: 10px 12px;
    background: #0f1117;
    border: 1px solid #2a2d3a;
    border-radius: 8px;
    color: #f0f0f0;
    font-size: 0.9rem;
    outline: none;
}
.search-box input:focus { border-color: #639922; }
.search-box input::placeholder { color: #444; }

.info-row { font-size: 0.85rem; color: #555; margin-bottom: 10px; }
.table-wrap {
    background: #1a1d27;
    border-radius: 12px;
    border: 1px solid #2a2d3a;
    overflow: hidden;
}
table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
thead { background: #0f1117; }
th {
    padding: 11px 14px;
    text-align: left;
    font-size: 0.78rem;
    color: #666;
    font-weight: 500;
    cursor: pointer;
    user-select: none;
    white-space: nowrap;
}
th:hover { color: #aaa; }
th .sort-icon { margin-left: 4px; opacity: 0.4; }
th.active .sort-icon { opacity: 1; color: #639922; }
td {
    padding: 11px 14px;
    border-top: 1px solid #2a2d3a;
    color: #ccc;
}
tr:hover td { background: #1f2333; }
.elapsed {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 600;
}
.elapsed.ok      { background: #1a2d0f; color: #639922; border: 1px solid #2a4a1a; }
.elapsed.warning { background: #2a1a0f; color: #EF9F27; border: 1px solid #4a3a1a; }
.elapsed.danger  { background: #2a1a1a; color: #E24B4A; border: 1px solid #4a2a2a; }
.no-data { padding: 40px; text-align: center; color: #444; }
</style>
</head>
<body>
<div class="wrap">

    <div class="page-header">
        <div>
            <div class="page-title"><?php echo htmlspecialchars($course); ?> ตอน <?php echo htmlspecialchars($section); ?></div>
            <div class="page-meta"><?php echo htmlspecialchars($date); ?> &nbsp;|&nbsp; Session: <?php echo htmlspecialchars($sid); ?></div>
        </div>
        <div class="header-actions">
            <a href="report_detail.php?sid=<?php echo urlencode($sid); ?>&export=1" class="btn">Export CSV</a>
            <a href="report.php" class="btn-outline">ย้อนกลับ</a>
        </div>
    </div>

    <div class="summary-row">
        <div class="summary-box">
            <strong><?php echo count($checkins); ?></strong>
            จำนวนที่เช็คชื่อ
        </div>
        <div class="summary-box">
            <strong id="avg-scan">—</strong>
            เฉลี่ยเวลาสแกน (วิ)
        </div>
        <div class="summary-box">
            <strong id="suspicious-count">—</strong>
            น่าสงสัย elapsed_scan &gt;7 วิ
        </div>
        <div class="summary-box">
            <strong id="avg-fill">—</strong>
            เฉลี่ยเวลากรอก (วิ)
        </div>
    </div>

    <div class="search-box">
        <input type="text" id="search" placeholder="ค้นหา รหัส / ชื่อ / นามสกุล..." onkeyup="applyFilter()">
    </div>

    <div class="info-row" id="info-text"></div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th onclick="sortBy('no')"            id="th-no">ลำดับ <span class="sort-icon">↕</span></th>
                    <th onclick="sortBy('student_id')"    id="th-student_id">รหัส <span class="sort-icon">↕</span></th>
                    <th onclick="sortBy('student_fname')" id="th-student_fname">ชื่อ <span class="sort-icon">↕</span></th>
                    <th onclick="sortBy('student_lname')" id="th-student_lname">นามสกุล <span class="sort-icon">↕</span></th>
                    <th onclick="sortBy('t_receive')"     id="th-t_receive">วันเวลาเช็คชื่อ <span class="sort-icon">↕</span></th>
                    <th onclick="sortBy('ip_address')"    id="th-ip_address">IP Address <span class="sort-icon">↕</span></th>
                    <th onclick="sortBy('elapsed_scan')"  id="th-elapsed_scan">สแกน (วิ) <span class="sort-icon">↕</span></th>
                    <th onclick="sortBy('elapsed_fill')"  id="th-elapsed_fill">กรอก (วิ) <span class="sort-icon">↕</span></th>
                </tr>
            </thead>
            <tbody id="table-body"></tbody>
        </table>
    </div>

</div>

<script>
var RAW = <?php
    $out = array();
    $i   = 1;
    foreach ($checkins as $c) {
        $out[] = array(
            'no'            => $i++,
            'student_id'    => $c['student_id'],
            'student_fname' => $c['student_fname'],
            'student_lname' => $c['student_lname'],
            't_receive'     => $c['t_receive'],
            'ip_address'    => $c['ip_address'] ? $c['ip_address'] : '-',
            'elapsed_scan'  => ($c['elapsed_scan'] !== null) ? (int)$c['elapsed_scan'] : null,
            'elapsed_fill'  => ($c['elapsed_fill'] !== null) ? (int)$c['elapsed_fill'] : null,
        );
    }
    echo json_encode($out);
?>;

var sortCol  = 'no';
var sortDir  = 'asc';
var filtered = RAW.slice();

// summary
(function() {
    if (RAW.length === 0) return;
    var hasScan = RAW.filter(function(r) { return r.elapsed_scan !== null; });
    var hasFill = RAW.filter(function(r) { return r.elapsed_fill !== null; });
    var sumScan = hasScan.reduce(function(s, r) { return s + r.elapsed_scan; }, 0);
    var sumFill = hasFill.reduce(function(s, r) { return s + r.elapsed_fill; }, 0);
    document.getElementById('avg-scan').textContent         = hasScan.length ? Math.round(sumScan / hasScan.length) : '-';
    document.getElementById('avg-fill').textContent         = hasFill.length ? Math.round(sumFill / hasFill.length) : '-';
    document.getElementById('suspicious-count').textContent = hasScan.filter(function(r) { return r.elapsed_scan > 7; }).length;
})();

function applyFilter() {
    var search = document.getElementById('search').value.trim().toLowerCase();
    filtered = RAW.filter(function(r) {
        if (!search) return true;
        return r.student_id.toLowerCase().indexOf(search) >= 0
            || r.student_fname.toLowerCase().indexOf(search) >= 0
            || r.student_lname.toLowerCase().indexOf(search) >= 0;
    });
    render();
}

function sortBy(col) {
    if (sortCol === col) {
        sortDir = sortDir === 'asc' ? 'desc' : 'asc';
    } else {
        sortCol = col;
        sortDir = 'asc';
    }
    render();
}

function getDupIPs() {
    var count = {};
    RAW.forEach(function(r) { count[r.ip_address] = (count[r.ip_address] || 0) + 1; });
    var dup = {};
    Object.keys(count).forEach(function(ip) { if (count[ip] > 1) dup[ip] = true; });
    return dup;
}

function render() {
    var dir = sortDir === 'asc' ? 1 : -1;
    filtered.sort(function(a, b) {
        var av = a[sortCol];
        var bv = b[sortCol];
        if (av === null) return 1;
        if (bv === null) return -1;
        if (typeof av === 'number') return (av - bv) * dir;
        return String(av).localeCompare(String(bv)) * dir;
    });

    ['no','student_id','student_fname','student_lname','t_receive','ip_address','elapsed_scan','elapsed_fill'].forEach(function(c) {
        var th = document.getElementById('th-' + c);
        if (!th) return;
        th.classList.remove('active');
        th.querySelector('.sort-icon').textContent = '↕';
    });
    var activeTh = document.getElementById('th-' + sortCol);
    if (activeTh) {
        activeTh.classList.add('active');
        activeTh.querySelector('.sort-icon').textContent = sortDir === 'asc' ? '↑' : '↓';
    }

    document.getElementById('info-text').textContent = 'ทั้งหมด ' + filtered.length + ' รายการ';

    var dupIPs = getDupIPs();
    var tbody = document.getElementById('table-body');
    if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="no-data">ไม่พบข้อมูล</td></tr>';
    } else {
        tbody.innerHTML = filtered.map(function(r) {
            var cls_scan  = r.elapsed_scan === null ? 'ok' : r.elapsed_scan <= 5 ? 'ok' : r.elapsed_scan <= 7 ? 'warning' : 'danger';
            var text_scan = r.elapsed_scan === null ? '-' : r.elapsed_scan + ' วิ';
            var text_fill = r.elapsed_fill === null ? '-' : r.elapsed_fill + ' วิ';
            var ip_style  = dupIPs[r.ip_address] ? ' style="color:#EF9F27;font-weight:600;"' : '';
            return '<tr>' +
                '<td>' + r.no + '</td>' +
                '<td>' + r.student_id + '</td>' +
                '<td>' + r.student_fname + '</td>' +
                '<td>' + r.student_lname + '</td>' +
                '<td>' + r.t_receive + '</td>' +
                '<td' + ip_style + '>' + r.ip_address + '</td>' +
                '<td><span class="elapsed ' + cls_scan + '">' + text_scan + '</span></td>' +
                '<td>' + text_fill + '</td>' +
            '</tr>';
        }).join('');
    }
}

render();
</script>
</body>
</html>
