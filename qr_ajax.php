<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/qrlib/qrlib.php';

if (empty($_SESSION['session_db_id']) || empty($_SESSION['sid']) || empty($_SESSION['secret_key'])) {
    echo json_encode(array('error' => 'no session'));
    exit;
}

$session_db_id = (int)$_SESSION['session_db_id'];
$sid           = $_SESSION['sid'];
$secret_key    = $_SESSION['secret_key'];

$t   = (int) floor(time() / WINDOW_SEC) * WINDOW_SEC;
$seq = (int) floor(time() / WINDOW_SEC);
$sig = substr(hash_hmac('sha256', $sid . $t . $seq, $secret_key), 0, 8);

$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'];
$base_path = dirname($_SERVER['PHP_SELF']);
$qr_url    = $protocol . '://' . $host . $base_path . '/checkin_form.php?id=' . $session_db_id . '&s=' . $sig . '&t=' . $t;

$qr_file   = __DIR__ . '/qr_tmp.png';
QRcode::png($qr_url, $qr_file, QR_ECLEVEL_M, 8, 2);
$qr_base64 = 'data:image/png;base64,' . base64_encode(file_get_contents($qr_file));

echo json_encode(array(
    'sig' => $sig,
    'qr'  => $qr_base64,
    't'   => $t,
    'seq' => $seq,
));
