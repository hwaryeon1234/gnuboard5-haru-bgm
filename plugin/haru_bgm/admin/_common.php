<?php
if (!defined('G5_IS_ADMIN')) define('G5_IS_ADMIN', true);
include_once(dirname(__FILE__).'/../_common.php');
require_once G5_ADMIN_PATH.'/admin.lib.php';

$sub_menu = hb_admin_menu_for_script();
$hb_admin_required_auth = hb_admin_required_auth_for_script();
$hb_schema_script = isset($_SERVER['SCRIPT_NAME']) ? basename(str_replace('\\', '/', (string)$_SERVER['SCRIPT_NAME'])) : '';

$hb_schema_bootstrap_attempted = !empty($GLOBALS['hb_schema_auto_install_attempted']);
$hb_schema_bootstrap_ok = !empty($GLOBALS['hb_schema_auto_install_ok']);
if (!$hb_schema_bootstrap_ok && (hb_is_super_admin() || hb_is_plugin_admin())) $hb_schema_bootstrap_ok = hb_schema_is_current();
$GLOBALS['hb_schema_bootstrap_attempted'] = $hb_schema_bootstrap_attempted;
$GLOBALS['hb_schema_bootstrap_ok'] = $hb_schema_bootstrap_ok;

auth_check_menu($auth, $sub_menu, $hb_admin_required_auth);

$hb_schema_gate_exempt = in_array($hb_schema_script, array('health.php', 'schema_update.php'), true);
if (!$hb_schema_bootstrap_ok && !$hb_schema_gate_exempt) {
    if (defined('HB_JSON_MODE') && HB_JSON_MODE) {
        http_response_code(503);
        hb_json_exit(array('ok'=>false, 'message'=>'schema_not_ready', 'install_attempted'=>$hb_schema_bootstrap_attempted ? 1 : 0));
    }
    alert(
        $hb_schema_bootstrap_attempted
            ? '하루BGM DB 자동 점검/복구를 완료하지 못했습니다. 시스템 점검에서 확인이 필요한 항목을 확인해주세요.'
            : '하루BGM DB 구성이 준비되지 않았습니다. 최고관리자가 시스템 점검에서 DB 점검 및 복구를 실행해주세요.',
        HB_URL.'/admin/health.php'
    );
}

if (function_exists('g5_check_data_htaccess')) {
    g5_check_data_htaccess();
}
run_event('admin_common');
