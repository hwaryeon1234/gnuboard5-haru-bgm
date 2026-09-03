<?php
include_once('./_common.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') alert('잘못된 요청입니다.', HB_URL.'/admin/operation.php');
hb_check_csrf();
hb_check_admin_token_if_present();
$enabled_raw = isset($_POST['enabled']) ? hb_scalar_string($_POST['enabled'], '') : '';
if ($enabled_raw !== '0' && $enabled_raw !== '1') alert('잘못된 방송 설정 요청입니다.', HB_URL.'/admin/operation.php');
$enabled = $enabled_raw === '1' ? 1 : 0;
if ($enabled) {
    $hook = hb_sync_sitewide_hook();
    if (empty($hook['ok'])) alert('사이트 전체 방송을 켤 수 없습니다. '.$hook['message'], HB_URL.'/admin/operation.php');
}
if (!hb_db_begin()) alert('DB 작업을 시작하지 못했습니다. 잠시 후 다시 시도해주세요.', HB_URL.'/admin/operation.php');
$ok_setting = hb_update_setting('sitewide_broadcast_enabled', $enabled);
$state = $ok_setting ? hb_broadcast_set_state($enabled ? 'auto' : 'stop') : array('ok'=>false);
if (!$ok_setting || empty($state['ok'])) { hb_db_rollback(); alert('사이트 전체 방송 상태를 저장하지 못했습니다.', HB_URL.'/admin/operation.php'); }
if (!hb_db_commit()) { hb_db_rollback(); alert('사이트 전체 방송 설정을 완료하지 못했습니다.', HB_URL.'/admin/operation.php'); }
alert($enabled ? '사이트 전체 방송을 켰습니다.' : '사이트 전체 방송을 껐습니다.', HB_URL.'/admin/operation.php');
