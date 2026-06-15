<?php
include_once('./_common.php');
hb_require_member_bgm_enabled();

$file = isset($_GET['file']) ? hb_safe_file($_GET['file']) : '';
if (!$file) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

$path = HB_DATA_PATH.'/'.$file;
if (!is_file($path) || !is_readable($path)) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$types = array(
    'mp3' => 'audio/mpeg',
    'wav' => 'audio/wav',
    'ogg' => 'audio/ogg',
    'm4a' => 'audio/mp4'
);
$mime = isset($types[$ext]) ? $types[$ext] : 'application/octet-stream';
$size = filesize($path);
$start = 0;
$end = $size - 1;

header('Content-Type: '.$mime);
header('X-Content-Type-Options: nosniff');
header('Accept-Ranges: bytes');
header('Cache-Control: private, max-age=86400');
header('Content-Disposition: inline; filename="'.rawurlencode($file).'"');

if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $m)) {
    if ($m[1] !== '') $start = (int)$m[1];
    if ($m[2] !== '') $end = min((int)$m[2], $size - 1);
    if ($start > $end || $start >= $size) {
        header('HTTP/1.1 416 Range Not Satisfiable');
        header('Content-Range: bytes */'.$size);
        exit;
    }
    header('HTTP/1.1 206 Partial Content');
    header('Content-Range: bytes '.$start.'-'.$end.'/'.$size);
}

$length = $end - $start + 1;
header('Content-Length: '.$length);

$fp = fopen($path, 'rb');
if (!$fp) exit;
fseek($fp, $start);
$buffer = 8192;
$sent = 0;
while (!feof($fp) && $sent < $length) {
    $read = min($buffer, $length - $sent);
    echo fread($fp, $read);
    $sent += $read;
    if (function_exists('flush')) @flush();
}
fclose($fp);
exit;
?>
