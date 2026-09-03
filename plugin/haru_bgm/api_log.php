<?php
define('HB_JSON_MODE', true);
include_once('./_common.php');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    hb_json_exit(array('ok'=>false, 'message'=>'method_not_allowed'));
}
hb_check_csrf(true);
if (!hb_is_plugin_admin()) {
    http_response_code(403);
    hb_json_exit(array('ok'=>false, 'message'=>'forbidden'));
}
if (!hb_schema_runtime_ready()) {
    http_response_code(503);
    hb_json_exit(array('ok'=>false, 'message'=>'schema_not_ready'));
}

$sc_id = isset($_POST['sc_id']) ? max(0, hb_int_value($_POST['sc_id'], 0)) : 0;
$mf_id = isset($_POST['mf_id']) ? max(0, hb_int_value($_POST['mf_id'], 0)) : 0;
$scope = isset($_POST['scope']) ? trim(hb_scalar_string($_POST['scope'], '')) : 'global';
$action = isset($_POST['action']) ? trim(hb_scalar_string($_POST['action'], '')) : 'auto';
$status = isset($_POST['status']) ? trim(hb_scalar_string($_POST['status'], '')) : 'success';
$message = isset($_POST['message']) ? hb_text_limit($_POST['message'], 250) : '';

$allowed_scopes = array('global','global_block','preview','preview_block','sequence','broadcast');
$allowed_actions = array('auto','preview','manual');
$allowed_statuses = array('success','fail');
if (!in_array($scope, $allowed_scopes, true)) {
    http_response_code(400);
    hb_json_exit(array('ok'=>false, 'message'=>'invalid_scope'));
}
if (!in_array($action, $allowed_actions, true)) {
    http_response_code(400);
    hb_json_exit(array('ok'=>false, 'message'=>'invalid_action'));
}
if (!in_array($status, $allowed_statuses, true)) {
    http_response_code(400);
    hb_json_exit(array('ok'=>false, 'message'=>'invalid_status'));
}

$scope_auth_ok = false;
if (in_array($scope, array('global','global_block','broadcast'), true)) {
    $scope_auth_ok = hb_user_has_admin_auth('990110', 'r');
} elseif (in_array($scope, array('preview','preview_block'), true)) {
    $scope_auth_ok = hb_user_has_any_admin_auth(array('990110','990120'), 'r');
} elseif ($scope === 'sequence') {
    $scope_auth_ok = hb_user_has_admin_auth('990160', 'r');
}
if (!$scope_auth_ok) {
    http_response_code(403);
    hb_json_exit(array('ok'=>false, 'message'=>'forbidden_scope'));
}
if (($scope === 'global' || $scope === 'global_block') && $action !== 'auto') {
    http_response_code(400);
    hb_json_exit(array('ok'=>false, 'message'=>'invalid_scope_action'));
}
if (($scope === 'preview' || $scope === 'preview_block') && $action !== 'preview') {
    http_response_code(400);
    hb_json_exit(array('ok'=>false, 'message'=>'invalid_scope_action'));
}
if ($scope === 'sequence' && $action !== 'manual') {
    http_response_code(400);
    hb_json_exit(array('ok'=>false, 'message'=>'invalid_scope_action'));
}
if ($scope === 'broadcast' && $action !== 'manual') {
    http_response_code(400);
    hb_json_exit(array('ok'=>false, 'message'=>'invalid_scope_action'));
}

if ($mf_id < 1) {
    http_response_code(400);
    hb_json_exit(array('ok'=>false, 'message'=>'invalid_music'));
}
$music = hb_table('music');
$music_row = sql_fetch("SELECT mf_id, mf_use FROM `{$music}` WHERE mf_id='{$mf_id}' LIMIT 1", false);
if (!$music_row || (int)$music_row['mf_use'] !== 1) {
    http_response_code(400);
    hb_json_exit(array('ok'=>false, 'message'=>'invalid_music'));
}
if (!hb_log_target_is_valid($scope, $sc_id, $mf_id)) {
    http_response_code(400);
    hb_json_exit(array('ok'=>false, 'message'=>'invalid_target'));
}

$mb_id = isset($member['mb_id']) ? hb_escape((string)$member['mb_id']) : '';
$ip = hb_escape(hb_ip());
$ua = isset($_SERVER['HTTP_USER_AGENT']) ? hb_escape(hb_text_limit($_SERVER['HTTP_USER_AGENT'], 250)) : '';
$log = hb_table('play_log');
$scope_sql = hb_escape($scope);
$action_sql = hb_escape($action);
$status_sql = hb_escape($status);
$message_sql = hb_escape($message);
$played_at = hb_escape(hb_server_now_sql());
$ok = sql_query("INSERT INTO `{$log}` SET sc_id='{$sc_id}', mf_id='{$mf_id}', sc_scope='{$scope_sql}', pl_action='{$action_sql}', pl_status='{$status_sql}', pl_message='{$message_sql}', mb_id='{$mb_id}', pl_ip='{$ip}', pl_user_agent='{$ua}', pl_played_at='{$played_at}'", false);
if (!$ok) {
    http_response_code(500);
    hb_json_exit(array('ok'=>false, 'message'=>'log_write_failed'));
}

// 재생 로그는 최근 90일만 유지하고, 정리는 하루 한 번만 수행합니다.
hb_cleanup_old_play_logs();

hb_json_exit(array('ok'=>true));
