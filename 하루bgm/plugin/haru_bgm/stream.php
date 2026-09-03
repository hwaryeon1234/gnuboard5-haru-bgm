<?php
define('HB_SKIP_MEMBER_GATE', true);
include_once('./_common.php');
$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string)$_SERVER['REQUEST_METHOD']) : 'GET';
if ($method !== 'GET' && $method !== 'HEAD') {
    header('HTTP/1.1 405 Method Not Allowed');
    header('Allow: GET, HEAD');
    exit;
}
if (!hb_schema_runtime_ready()) {
    header('HTTP/1.1 503 Service Unavailable');
    exit;
}
$mf_id = isset($_GET['id']) ? max(0, hb_int_value($_GET['id'], 0)) : 0;
$file = isset($_GET['file']) ? hb_safe_file($_GET['file']) : '';
if ($mf_id > 0) {
    $music = hb_table('music');
    $row = sql_fetch("SELECT mf_id, mf_use, mf_source, mf_file FROM `{$music}` WHERE mf_id='{$mf_id}' LIMIT 1", false);
    if (!$row || (isset($row['mf_source']) && $row['mf_source'] !== 'file')) {
        header('HTTP/1.1 404 Not Found');
        exit;
    }
    $file = hb_safe_file(isset($row['mf_file']) ? $row['mf_file'] : '');
    $allowed = false;
    // 공개 중인 활성 음원은 로그인/관리자 여부와 무관하게 동일한 public 판정을 먼저 적용합니다.
    if ((int)$row['mf_use'] === 1 && hb_sitewide_enabled() && hb_music_is_global_broadcast_used((int)$row['mf_id'])) {
        $allowed = true;
    } elseif (hb_is_plugin_admin()) {
        // 비활성·미편성 음원은 음악 관리 읽기 권한이 있는 관리자만 점검할 수 있습니다.
        $allowed = hb_user_has_admin_auth('990130', 'r');
    }
    if (!$allowed) {
        header('HTTP/1.1 403 Forbidden');
        exit;
    }
} else {
    // v1.5.12부터 실제 파일명 기반 URL은 관리자 호환에만 허용합니다.
    // 공개 API는 mf_id URL을 사용하므로 데이터 폴더의 랜덤 파일명을 노출하지 않습니다.
    if (!$file || !hb_is_plugin_admin() || !hb_stream_file_allowed($file)) {
        header($file ? 'HTTP/1.1 403 Forbidden' : 'HTTP/1.1 404 Not Found');
        exit;
    }
}
if (!$file) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

$path = HB_DATA_PATH.'/'.$file;
if (!is_file($path) || !is_readable($path)) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

// 바이너리 응답 앞에 그누보드/확장 플러그인의 출력 버퍼가 섞이지 않도록 비웁니다.
if (function_exists('ob_get_level')) {
    while (ob_get_level() > 0) @ob_end_clean();
}

// Audio delivery can run for a long time. Release the PHP session lock after
// all access checks so concurrent page/API requests from the same session do not block.
if (function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$types = array(
    'mp3' => 'audio/mpeg',
    'wav' => 'audio/wav',
    'ogg' => 'audio/ogg',
    'm4a' => 'audio/mp4'
);
$mime = isset($types[$ext]) ? $types[$ext] : 'application/octet-stream';
$size = (int)filesize($path);
if ($size <= 0) {
    header('HTTP/1.1 416 Range Not Satisfiable');
    header('Content-Range: bytes */0');
    exit;
}
$start = 0;
$end = $size - 1;

header('Content-Type: '.$mime);
header('X-Content-Type-Options: nosniff');
header('Accept-Ranges: bytes');
header('Cache-Control: private, no-store, max-age=0');
$public_name = 'haru-bgm'.($mf_id > 0 ? '-'.$mf_id : '').'.'.$ext;
header('Content-Disposition: inline; filename="'.rawurlencode($public_name).'"');

if (isset($_SERVER['HTTP_RANGE'])) {
    $range = trim((string)$_SERVER['HTTP_RANGE']);
    if (!preg_match('/^bytes=(\d*)-(\d*)$/', $range, $m) || ($m[1] === '' && $m[2] === '')) {
        header('HTTP/1.1 416 Range Not Satisfiable');
        header('Content-Range: bytes */'.$size);
        exit;
    }
    if ($m[1] === '') {
        $suffix = (int)$m[2];
        if ($suffix <= 0) {
            header('HTTP/1.1 416 Range Not Satisfiable');
            header('Content-Range: bytes */'.$size);
            exit;
        }
        $start = max(0, $size - $suffix);
        $end = $size - 1;
    } else {
        $start = (int)$m[1];
        if ($m[2] !== '') $end = min((int)$m[2], $size - 1);
    }
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
if (!$fp) {
    header('HTTP/1.1 500 Internal Server Error');
    exit;
}
if ($start > 0 && fseek($fp, $start, SEEK_SET) !== 0) {
    fclose($fp);
    header('HTTP/1.1 500 Internal Server Error');
    exit;
}
if ($method === 'HEAD') {
    fclose($fp);
    exit;
}
$buffer = 8192;
$sent = 0;
while (!feof($fp) && $sent < $length) {
    $read = min($buffer, $length - $sent);
    $chunk = fread($fp, $read);
    if ($chunk === false || $chunk === '') break;
    $actual = strlen($chunk);
    echo $chunk;
    $sent += $actual;
    if (function_exists('flush')) @flush();
}
fclose($fp);
exit;
