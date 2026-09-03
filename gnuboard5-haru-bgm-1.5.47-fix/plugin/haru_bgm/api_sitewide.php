<?php
define('HB_JSON_MODE', true);
define('HB_SKIP_MEMBER_GATE', true);
include_once('./_common.php');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string)$_SERVER['REQUEST_METHOD']) : 'GET';
if ($method !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    hb_json_exit(array('ok'=>false, 'message'=>'method_not_allowed'));
}
if (!hb_schema_runtime_ready()) {
    http_response_code(503);
    header('Retry-After: 5');
    hb_json_exit(array('ok'=>false, 'message'=>'schema_not_ready'));
}
if (!hb_sitewide_enabled()) hb_json_exit(array('ok'=>false, 'message'=>'sitewide_disabled'));

$snapshot = hb_runtime_schedule_snapshot();
if (empty($snapshot['ok'])) {
    http_response_code(503);
    header('Retry-After: 5');
    hb_json_exit(array('ok'=>false, 'message'=>isset($snapshot['message']) ? $snapshot['message'] : 'schedule_query_failed'));
}
$response = array(
    'ok'=>true,
    'server_date'=>G5_TIME_YMD,
    'server_time'=>G5_TIME_YMDHIS,
    'server_epoch_ms'=>(int)round(microtime(true) * 1000),
    'broadcast'=>hb_broadcast_payload(),
    'settings'=>$snapshot['settings'],
    'items'=>$snapshot['items'],
    'blocks'=>$snapshot['blocks']
);
hb_json_bounded_output($response);
exit;
