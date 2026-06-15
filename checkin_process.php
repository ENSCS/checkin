<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Bangkok');
require_once __DIR__ . '/connect.php';

$input = json_decode(file_get_contents('php://input'), true);

$db_id         = isset($input['db_id'])         ? (int)$input['db_id']             : 0;
$sig           = isset($input['sig'])           ? trim($input['sig'])               : '';
$t_qr          = isset($input['t_qr'])          ? (int)$input['t_qr']              : 0;
$student_id    = isset($input['student_id'])    ? trim($input['student_id'])        : '';
$student_fname = isset($input['student_fname']) ? trim($input['student_fname'])     : '';
$student_lname = isset($input['student_lname']) ? trim($input['student_lname'])     : '';
$elapsed_scan  = isset($input['elapsed_scan'])  ? (int)$input['elapsed_scan']       : null;
$elapsed_fill  = isset($input['elapsed_fill'])  ? (int)$input['elapsed_fill']       : null;

if (!$db_id || !$sig || !$t_qr || !$student_id || !$student_fname || !$student_lname) {
    echo json_encode(array('success' => false, 'message' => 'ข้อมูลไม่ครบ'));
    exit;
}

// lookup session จาก id
$stmt = $pdo->prepare('SELECT id, sid, secret_key FROM ckn_sessions WHERE id = :id LIMIT 1');
$stmt->execute(array(':id' => $db_id));
$session = $stmt->fetch();

if (!$session) {
    echo json_encode(array('success' => false, 'message' => 'ไม่พบ session นี้'));
    exit;
}

$sid        = $session['sid'];
$secret_key = $session['secret_key'];

// ตรวจ sig — คำนวณใหม่จาก t_qr และ secret_key
require_once __DIR__ . '/config.php';
$seq          = (int) floor($t_qr / WINDOW_SEC);
$expected_sig = substr(hash_hmac('sha256', $sid . $t_qr . $seq, $secret_key), 0, 8);

if ($sig !== $expected_sig) {
    echo json_encode(array('success' => false, 'message' => 'QR ไม่ถูกต้องหรือหมดอายุ'));
    exit;
}


$t_receive  = date('Y-m-d H:i:s');
$ip_address = isset($_SERVER['HTTP_X_FORWARDED_FOR'])
    ? trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0])
    : $_SERVER['REMOTE_ADDR'];

$stmt = $pdo->prepare('
    INSERT INTO ckn_checkins (sid, student_id, student_fname, student_lname, sig, ip_address, elapsed_scan, elapsed_fill)
    VALUES (:sid, :student_id, :student_fname, :student_lname, :sig, :ip_address, :elapsed_scan, :elapsed_fill)
');
$stmt->execute(array(
    ':sid'           => $sid,
    ':student_id'    => $student_id,
    ':student_fname' => $student_fname,
    ':student_lname' => $student_lname,
    ':sig'           => $sig,
    ':ip_address'    => $ip_address,
    ':elapsed_scan'  => $elapsed_scan,
    ':elapsed_fill'  => $elapsed_fill,
));

// แยก sid เพื่อแสดงผล
$prefix  = substr($sid, 0, -11);
$dash    = strrpos($prefix, '-');
$course  = $dash !== false ? substr($prefix, 0, $dash) : $prefix;
$section = $dash !== false ? substr($prefix, $dash + 1) : '';

echo json_encode(array(
    'success'      => true,
    'course'       => $course,
    'section'      => $section,
    't_receive'    => $t_receive,
    'elapsed_scan' => $elapsed_scan,
    'elapsed_fill' => $elapsed_fill,
));
