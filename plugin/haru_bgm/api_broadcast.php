<?php
define('HB_JSON_MODE', true);
define('HB_SKIP_MEMBER_GATE', true);
include_once('./_common.php');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
$hb_method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string)$_SERVER['REQUEST_METHOD']) : 'GET';
if ($hb_method !== 'GET') {
    header('HTTP/1.1 405 Method Not Allowed');
    header('Allow: GET');
    hb_json_exit(array('ok'=>false, 'message'=>'method_not_allowed'));
}
unset($hb_method);

// 로그인 상태의 요청도 설정/방송 상태를 읽기 전에 전체 스키마를 확인합니다.
if (!hb_schema_runtime_ready()) hb_json_exit(array('ok'=>false, 'message'=>'schema_not_ready'));
if (!hb_sitewide_enabled()) {
    $disabled = hb_broadcast_payload();
    $disabled['mode'] = 'stop';
    $disabled['started_epoch_ms'] = 0;
    $disabled['seek_seconds'] = 0.0;
    $disabled['item'] = null;
    hb_json_exit(array(
        'ok' => true,
        'sitewide_enabled' => false,
        'server_time' => defined('G5_TIME_YMDHIS') ? G5_TIME_YMDHIS : date('Y-m-d H:i:s'),
        'server_epoch_ms' => (int)round(microtime(true) * 1000),
        'broadcast' => $disabled
    ));
}

hb_json_exit(array(
    'ok' => true,
    'sitewide_enabled' => true,
    'server_time' => defined('G5_TIME_YMDHIS') ? G5_TIME_YMDHIS : date('Y-m-d H:i:s'),
    'server_epoch_ms' => (int)round(microtime(true) * 1000),
    'broadcast' => hb_broadcast_payload()
));
