<?php
include_once('./_common.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') alert('잘못된 요청입니다.', HB_URL.'/admin/operation.php');
hb_check_csrf();
hb_check_admin_token_if_present();

$return_url = isset($_POST['return_url']) ? trim(hb_scalar_string($_POST['return_url'], '')) : '';
$default_return = HB_URL.'/admin/operation.php';
$sequence_return_pattern = '#^'.preg_quote(HB_URL.'/admin/sequence_runner.php', '#').'\\?seq_id=[1-9][0-9]*$#';
$return_ok = ($return_url === $default_return) || (bool)preg_match($sequence_return_pattern, $return_url);
if (!$return_ok || preg_match('/[\r\n]/', $return_url)) $return_url = $default_return;
unset($sequence_return_pattern, $return_ok);

if (!hb_sitewide_enabled()) {
    alert('사이트 전체 방송이 꺼져 있습니다. 먼저 전체 송출을 켜주세요.', $return_url);
}

$action = isset($_POST['action']) ? hb_scalar_string($_POST['action'], '') : 'auto';
if (!in_array($action, array('auto','play','stop'), true)) alert('잘못된 방송 제어 요청입니다.', $return_url);
$mf_id = isset($_POST['mf_id']) ? hb_int_value($_POST['mf_id'], 0) : 0;
$seek_raw = isset($_POST['seek_seconds']) ? trim(hb_scalar_string($_POST['seek_seconds'], '')) : '0';
if ($seek_raw === '') $seek_raw = '0';
if (!is_numeric($seek_raw)) alert('재생 시작 위치가 올바르지 않습니다.', $return_url);
$seek_seconds = (float)$seek_raw;
if (!is_finite($seek_seconds) || $seek_seconds < 0 || $seek_seconds > 9999999.999) alert('재생 시작 위치가 허용 범위를 벗어났습니다.', $return_url);

if ($action === 'play') {
    $result = hb_broadcast_set_state('manual', $mf_id, $seek_seconds);
    if (empty($result['ok'])) alert($result['message'] ?? '전체 재생을 시작하지 못했습니다.', $return_url);
    alert('선택한 음악을 사이트 전체에 재생하도록 전환했습니다.', $return_url);
}

if ($action === 'stop') {
    $result = hb_broadcast_set_state('stop');
    if (empty($result['ok'])) alert($result['message'] ?? '사이트 전체 방송을 정지하지 못했습니다.', $return_url);
    alert('사이트 전체 방송을 정지했습니다.', $return_url);
}

$result = hb_broadcast_set_state('auto');
if (empty($result['ok'])) alert($result['message'] ?? '자동 편성으로 되돌리지 못했습니다.', $return_url);
alert('사이트 전체 방송을 자동 편성으로 되돌렸습니다.', $return_url);
