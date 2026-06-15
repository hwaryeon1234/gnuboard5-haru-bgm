<?php
define('HB_JSON_MODE', true);
include_once('./_common.php');
hb_require_member_bgm_enabled();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$sc_id = isset($_POST['sc_id']) ? (int)$_POST['sc_id'] : 0;
$mf_id = isset($_POST['mf_id']) ? (int)$_POST['mf_id'] : 0;
$scope = isset($_POST['scope']) ? hb_escape(substr($_POST['scope'], 0, 30)) : '';
$action = isset($_POST['action']) ? hb_escape(substr($_POST['action'], 0, 30)) : 'auto';
$status = isset($_POST['status']) && $_POST['status'] === 'fail' ? 'fail' : 'success';
$message = isset($_POST['message']) ? hb_escape(substr($_POST['message'], 0, 250)) : '';
$mb_id = hb_escape($member['mb_id']);
$ip = hb_escape(hb_ip());
$ua = isset($_SERVER['HTTP_USER_AGENT']) ? hb_escape(substr($_SERVER['HTTP_USER_AGENT'], 0, 250)) : '';
$log = hb_table('play_log');

if ($mf_id > 0) {
    sql_query("INSERT INTO `{$log}` SET sc_id='{$sc_id}', mf_id='{$mf_id}', sc_scope='{$scope}', pl_action='{$action}', pl_status='{$status}', pl_message='{$message}', mb_id='{$mb_id}', pl_ip='{$ip}', pl_user_agent='{$ua}', pl_played_at=NOW()", false);
}

echo json_encode(array('ok' => true), JSON_UNESCAPED_UNICODE);
?>
