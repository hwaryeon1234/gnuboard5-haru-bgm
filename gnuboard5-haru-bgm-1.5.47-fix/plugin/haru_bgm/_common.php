<?php
if (!defined('_GNUBOARD_')) {
    include_once(dirname(__FILE__).'/../../common.php');
}

if (!defined('HB_DIR')) {
    define('HB_DIR', 'haru_bgm');
    define('HB_PATH', G5_PLUGIN_PATH.'/'.HB_DIR);
    define('HB_URL', G5_PLUGIN_URL.'/'.HB_DIR);
    define('HB_DATA_PATH', G5_DATA_PATH.'/'.HB_DIR);
    define('HB_DATA_URL', G5_DATA_URL.'/'.HB_DIR);
}

include_once(HB_PATH.'/lib.php');

// 신규 설치에서만 DB 테이블을 자동 생성합니다. 기존/부분 설치는 관리자 점검 절차로 처리합니다.
$GLOBALS['hb_schema_auto_install_attempted'] = true;
$GLOBALS['hb_schema_auto_install_ok'] = hb_schema_auto_install_if_missing();

// v1.5.5부터 제거된 구형 회원 기능 엔드포인트입니다.
// 기존 설치에 옛 PHP 파일이 물리적으로 남아 있어도 덮어쓰기 업그레이드만으로 다시 실행되지 않게 차단합니다.
$hb_legacy_member_endpoints = array_map('basename', hb_legacy_member_relative_files());
$hb_current_script = isset($_SERVER['SCRIPT_NAME']) ? basename(str_replace('\\', '/', (string)$_SERVER['SCRIPT_NAME'])) : '';
if (in_array($hb_current_script, $hb_legacy_member_endpoints, true)) {
    header('HTTP/1.1 404 Not Found');
    exit;
}
unset($hb_legacy_member_endpoints, $hb_current_script);

$hb_skip_member_gate = defined('HB_SKIP_MEMBER_GATE') && HB_SKIP_MEMBER_GATE;
if (!$hb_skip_member_gate && !$is_member) {
    if (defined('HB_JSON_MODE') && HB_JSON_MODE) {
        hb_json_exit(array('ok' => false, 'message' => 'login_required'));
    }
    $url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : HB_URL.'/index.php';
    alert('로그인 후 이용할 수 있습니다.', G5_BBS_URL.'/login.php?url='.urlencode($url));
}
