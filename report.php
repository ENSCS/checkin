<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
date_default_timezone_set('Asia/Bangkok');
require_once __DIR__ . '/config.php';

$auth_error = false;

if (!isset($_SESSION['is_report'])) {
    if (isset($_POST['password'])) {
        if ($_POST['password'] === REPORT_PASSWORD) {
            $_SESSION['is_report'] = true;
        } else {
            $auth_error = true;
        }
    }
}

// ดึงข้อมูลจาก DB
$sessions = array();
if (isset($_SESSION['is_report'])) {
    require_once __DIR__ . '/connect.php';
    $stmt = $pdo->query('
        SELECT s.sid, s.created_at,
               COUNT(c.id) as total
        FROM ckn_sessions s
        LEFT JOIN ckn_checkins c ON c.sid = s.sid
        GROUP BY s.sid, s.created_at
        ORDER BY s.created_at DESC
    ');
    $sessions = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>รายงานการเช็คชื่อ</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: sans-serif;
    background: #0f1117;
    color: #f0f0f0;
    min-height: 100vh;
    padding: 24px;
}
.login-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
}
.card {
    background: #1a1d27;
    border-radius: 16px;
    padding: 32px;
    width: 100%;
    max-width: 420px;
    border: 1px solid #2a2d3a;
}
h1 { font-size: 1.4rem; font-weight: 600; margin-bottom: 6px; color: #fff; }
.subtitle { font-size: 0.85rem; color: #666; margin-bottom: 24px; }
.field { margin-bottom: 16px; }
label { display: block; font-size: 0.85rem; color: #aaa; margin-bottom: 6px; }
input, select {
    width: 100%;
    padding: 10px 12px;
    background: #0f1117;
    border: 1px solid #2a2d3a;
    border-radius: 8px;
    color: #f0f0f0;
    font-size: 0.9rem;
    outline: none;
    transition: border-color 0.2s;
}
input:focus, select:focus { border-color: #639922; }
input::placeholder { color: #444; }
.btn {
    padding: 10px 18px;
    background: #639922;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}
.btn:hover { background: #4d7a18; }
.btn-full { width: 100%; margin-top: 8px; padding: 13px; }
.btn-outline {
    padding: 8px 14px;
    background: transparent;
    color: #aaa;
    border: 1px solid #2a2d3a;
    border-radius: 8px;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-outline:hover { border-color: #639922; color: #639922; }
.btn-outline.active { background: #639922; color: #fff; border-color: #639922; }
.error {
    background: #2a1a1a;
    border: 1px solid #E24B4A;
    border-radius: 8px;
    padding: 12px;
    color: #E24B4A;
    font-size: 0.85rem;
    margin-bottom: 16px;
}

/* Report layout */
.report-wrap { max-width: 1100px; margin: 0 auto; }
.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 12px;
}
.page-title { font-size: 1.4rem; font-weight: 600; }
.filter-box {
    background: #1a1d27;
    border-radius: 12px;
    padding: 20px;
    border: 1px solid #2a2d3a;
    margin-bottom: 20px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.filter-row {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 12px;
    align-items: end;
}
.filter-box .field { margin-bottom: 0; }
.btn-search {
    padding: 10px 32px;
    font-size: 0.95rem;
}
.table-wrap {
    background: #1a1d27;
    border-radius: 12px;
    border: 1px solid #2a2d3a;
    overflow: hidden;
}
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
}
thead { background: #0f1117; }
th {
    padding: 12px 16px;
    text-align: left;
    font-size: 0.8rem;
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
    padding: 12px 16px;
    border-top: 1px solid #2a2d3a;
    color: #ccc;
    vertical-align: middle;
}
tr:hover td { background: #1f2333; }
.badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 600;
    background: #1a2d0f;
    color: #639922;
    border: 1px solid #2a4a1a;
}
.btn-view {
    padding: 6px 14px;
    background: transparent;
    color: #639922;
    border: 1px solid #639922;
    border-radius: 6px;
    font-size: 0.8rem;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: all 0.2s;
}
.btn-view:hover { background: #639922; color: #fff; }
.pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    border-top: 1px solid #2a2d3a;
    font-size: 0.85rem;
    color: #555;
    flex-wrap: wrap;
    gap: 8px;
}
.page-btns { display: flex; gap: 6px; }
.page-btns button {
    padding: 6px 12px;
    background: #0f1117;
    color: #aaa;
    border: 1px solid #2a2d3a;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.8rem;
    transition: all 0.2s;
}
.page-btns button:hover { border-color: #639922; color: #639922; }
.page-btns button.active { background: #639922; color: #fff; border-color: #639922; }
.page-btns button:disabled { opacity: 0.3; cursor: not-allowed; }
.no-data { padding: 40px; text-align: center; color: #444; }
.info-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
    font-size: 0.85rem;
    color: #555;
    flex-wrap: wrap;
    gap: 8px;
}
</style>
</head>
<body>

<?php if (!isset($_SESSION['is_report'])): ?>

<div class="login-wrap">
<div class="card">
    <h1>รายงานการเช็คชื่อ</h1>
    <p class="subtitle">สำหรับผู้สอนเท่านั้น</p>

    <?php if ($auth_error): ?>
    <div class="error">รหัสผ่านไม่ถูกต้อง</div>
    <?php endif; ?>

    <form method="POST">
        <div class="field">
            <label>รหัสผ่าน</label>
            <input type="password" name="password" placeholder="กรอกรหัสผ่าน" autofocus>
        </div>
        <button type="submit" class="btn btn-full">เข้าสู่ระบบ</button>
    </form>
</div>
</div>

<?php else: ?>

<div class="report-wrap">
    <div class="page-header">
        <div class="page-title">รายงานการเช็คชื่อ</div>
        <a href="?logout=1" class="btn-outline" onclick="<?php
            if (isset($_GET['logout'])) {
                unset($_SESSION['is_report']);
                header('Location: report.php');
                exit;
            }
        ?>">ออกจากระบบ</a>
    </div>

    <!-- Filter -->
    <div class="filter-box">
        <!-- แถวบน: ค้นหา วิชา ตอนเรียน เดือน -->
        <div class="filter-row">
            <div class="field">
                <label>ค้นหา</label>
                <input type="text" id="search" placeholder="วิชา / ตอน...">
            </div>
            <div class="field">
                <label>วิชา</label>
                <select id="filter-course">
                    <option value="">ทั้งหมด</option>
                </select>
            </div>
            <div class="field">
                <label>ตอนเรียน</label>
                <select id="filter-section">
                    <option value="">ทั้งหมด</option>
                </select>
            </div>
            <div class="field">
                <label>เดือน</label>
                <select id="filter-month">
                    <option value="">ทั้งหมด</option>
                </select>
            </div>
        </div>
        <!-- แถวล่าง: วันที่เริ่ม วันที่สิ้นสุด ปุ่ม -->
        <div class="filter-row">
            <div class="field">
                <label>วันที่เริ่ม</label>
                <input type="date" id="filter-date-from">
            </div>
            <div class="field">
                <label>วันที่สิ้นสุด</label>
                <input type="date" id="filter-date-to">
            </div>
            <div class="field" style="display:flex; align-items:flex-end; gap:8px;">
                <button class="btn btn-search" onclick="applyFilter()">ค้นหา</button>
                <button class="btn-outline" onclick="resetFilter()">รีเซ็ต</button>
            </div>
        </div>
    </div>

    <div class="info-row">
        <span id="info-text"></span>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th onclick="sortBy('course')" id="th-course">วิชา <span class="sort-icon">↕</span></th>
                    <th onclick="sortBy('section')" id="th-section">ตอน <span class="sort-icon">↕</span></th>
                    <th onclick="sortBy('date')" id="th-date">วันที่ <span class="sort-icon">↕</span></th>
                    <th onclick="sortBy('total')" id="th-total">จำนวน นร. <span class="sort-icon">↕</span></th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="table-body"></tbody>
        </table>
        <div class="pagination">
            <span id="page-info"></span>
            <div class="page-btns" id="page-btns"></div>
        </div>
    </div>
</div>

<script>
var RAW = <?php
    $out = array();
    foreach ($sessions as $s) {
        $parts      = explode('-', $s['sid']);
        $course     = isset($parts[0]) ? $parts[0] : '';
        $section    = isset($parts[1]) ? $parts[1] : '';
        $created_at = $s['created_at'];
        $month      = strlen($created_at) >= 7 ? substr($created_at, 0, 7) : '';
        $date_only  = strlen($created_at) >= 10 ? substr($created_at, 0, 10) : '';
        $out[] = array(
            'sid'     => $s['sid'],
            'course'  => $course,
            'section' => $section,
            'date'    => $created_at,
            'month'   => $month,
            'date_only' => $date_only,
            'total'   => (int)$s['total'],
        );
    }
    echo json_encode($out);
?>;

var PER_PAGE   = 30;
var currentPage = 1;
var sortCol    = 'date';
var sortDir    = 'desc';
var filtered   = RAW.slice();

// populate dropdowns
(function() {
    var courses  = {};
    var sections = {};
    var months   = {};
    RAW.forEach(function(r) {
        courses[r.course]   = true;
        sections[r.section] = true;
        months[r.month]     = true;
    });
    var sel = document.getElementById('filter-course');
    Object.keys(courses).sort().forEach(function(c) {
        sel.innerHTML += '<option value="' + c + '">' + c + '</option>';
    });
    sel = document.getElementById('filter-section');
    Object.keys(sections).sort().forEach(function(s) {
        sel.innerHTML += '<option value="' + s + '">' + s + '</option>';
    });
    sel = document.getElementById('filter-month');
    Object.keys(months).sort().reverse().forEach(function(m) {
        sel.innerHTML += '<option value="' + m + '">' + m + '</option>';
    });
})();

function applyFilter() {
    var search  = document.getElementById('search').value.trim().toLowerCase();
    var course  = document.getElementById('filter-course').value;
    var section = document.getElementById('filter-section').value;
    var month   = document.getElementById('filter-month').value;
    var dfrom   = document.getElementById('filter-date-from').value;
    var dto     = document.getElementById('filter-date-to').value;

    filtered = RAW.filter(function(r) {
        if (search  && r.course.toLowerCase().indexOf(search) < 0
                    && r.section.toLowerCase().indexOf(search) < 0) return false;
        if (course  && r.course   !== course)  return false;
        if (section && r.section  !== section) return false;
        if (month   && r.month    !== month)   return false;
        if (dfrom   && r.date_only < dfrom)     return false;
        if (dto     && r.date_only > dto)       return false;
        return true;
    });

    currentPage = 1;
    render();
}

function resetFilter() {
    document.getElementById('search').value           = '';
    document.getElementById('filter-course').value    = '';
    document.getElementById('filter-section').value   = '';
    document.getElementById('filter-month').value     = '';
    document.getElementById('filter-date-from').value = '';
    document.getElementById('filter-date-to').value   = '';
    filtered    = RAW.slice();
    currentPage = 1;
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

function render() {
    // sort
    var dir = sortDir === 'asc' ? 1 : -1;
    filtered.sort(function(a, b) {
        var av = a[sortCol];
        var bv = b[sortCol];
        if (typeof av === 'number') return (av - bv) * dir;
        return av.localeCompare(bv) * dir;
    });

    // update sort icons
    ['course','section','date','total'].forEach(function(c) {
        var th = document.getElementById('th-' + c);
        th.classList.remove('active');
        th.querySelector('.sort-icon').textContent = '↕';
    });
    var activeTh = document.getElementById('th-' + sortCol);
    if (activeTh) {
        activeTh.classList.add('active');
        activeTh.querySelector('.sort-icon').textContent = sortDir === 'asc' ? '↑' : '↓';
    }

    // paginate
    var total    = filtered.length;
    var pages    = Math.max(1, Math.ceil(total / PER_PAGE));
    currentPage  = Math.min(currentPage, pages);
    var start    = (currentPage - 1) * PER_PAGE;
    var pageData = filtered.slice(start, start + PER_PAGE);

    // info
    document.getElementById('info-text').textContent =
        'แสดง ' + (total === 0 ? 0 : start + 1) + '-' + Math.min(start + PER_PAGE, total) +
        ' จาก ' + total + ' รายการ';

    // rows
    var tbody = document.getElementById('table-body');
    if (pageData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="no-data">ไม่พบข้อมูล</td></tr>';
    } else {
        tbody.innerHTML = pageData.map(function(r) {
            return '<tr>' +
                '<td>' + r.course + '</td>' +
                '<td>' + r.section + '</td>' +
                '<td>' + r.date + '</td>' +
                '<td><span class="badge">' + r.total + ' คน</span></td>' +
                '<td><a href="report_detail.php?sid=' + encodeURIComponent(r.sid) + '" class="btn-view">ดูรายชื่อ</a></td>' +
            '</tr>';
        }).join('');
    }

    // page buttons
    var btns = document.getElementById('page-btns');
    var html = '';
    html += '<button onclick="goPage(' + (currentPage-1) + ')" ' + (currentPage<=1?'disabled':'') + '>&larr;</button>';
    var start_p = Math.max(1, currentPage - 2);
    var end_p   = Math.min(pages, start_p + 4);
    for (var p = start_p; p <= end_p; p++) {
        html += '<button onclick="goPage(' + p + ')" class="' + (p===currentPage?'active':'') + '">' + p + '</button>';
    }
    html += '<button onclick="goPage(' + (currentPage+1) + ')" ' + (currentPage>=pages?'disabled':'') + '>&rarr;</button>';
    btns.innerHTML = html;

    document.getElementById('page-info').textContent = 'หน้า ' + currentPage + ' / ' + pages;
}

function goPage(p) {
    currentPage = p;
    render();
}

// enter to search
document.getElementById('search').addEventListener('keyup', function(e) {
    if (e.key === 'Enter') applyFilter();
});

// logout
<?php if (isset($_GET['logout'])): ?>
    <?php unset($_SESSION['is_report']); ?>
    window.location.href = 'report.php';
<?php endif; ?>

render();
</script>

<?php endif; ?>
</body>
</html>
