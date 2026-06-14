<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Bangkok');
require_once __DIR__ . '/connect.php';

$input = json_decode(file_get_contents('php://input'), true);

$sig           = isset($input['sig'])           ? trim($input['sig'])           : '';
$sid           = isset($input['sid'])           ? trim($input['sid'])           : '';
$student_id    = isset($input['student_id'])    ? trim($input['student_id'])    : '';
$student_fname = isset($input['student_fname']) ? trim($input['student_fname']) : '';
$student_lname = isset($input['student_lname']) ? trim($input['student_lname']) : '';

if (!$sig || !$sid || !$student_id || !$student_fname || !$student_lname) {
    echo json_encode(array('success' => false, 'message' => 'ข้อมูลไม่ครบ'));
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM ckn_sessions WHERE sid = :sid LIMIT 1');
$stmt->execute(array(':sid' => $sid));
$session = $stmt->fetch();

if (!$session) {
    echo json_encode(array('success' => false, 'message' => 'ไม่พบ session นี้'));
    exit;
}

// เก็บเวลาและ IP ก่อน INSERT
$t_receive  = date('Y-m-d H:i:s');
$elapsed    = isset($input['elapsed']) ? (int)$input['elapsed'] : null;
$ip_address = isset($_SERVER['HTTP_X_FORWARDED_FOR'])
    ? trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0])
    : $_SERVER['REMOTE_ADDR'];

$stmt = $pdo->prepare('
    INSERT INTO ckn_checkins (sid, student_id, student_fname, student_lname, sig, ip_address, elapsed)
    VALUES (:sid, :student_id, :student_fname, :student_lname, :sig, :ip_address, :elapsed)
');
$stmt->execute(array(
    ':sid'           => $sid,
    ':student_id'    => $student_id,
    ':student_fname' => $student_fname,
    ':student_lname' => $student_lname,
    ':sig'           => $sig,
    ':ip_address'    => $ip_address,
    ':elapsed'       => $elapsed,
));

// แยก sid เพื่อแสดงผล
$prefix  = substr($sid, 0, -11);
$dash    = strrpos($prefix, '-');
$course  = $dash !== false ? substr($prefix, 0, $dash) : $prefix;
$section = $dash !== false ? substr($prefix, $dash + 1) : '';

echo json_encode(array(
    'success'   => true,
    'course'    => $course,
    'section'   => $section,
    't_receive' => $t_receive,
    'elapsed'   => $elapsed,
));
