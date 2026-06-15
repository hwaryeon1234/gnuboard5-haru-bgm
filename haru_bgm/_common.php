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
hb_ensure_tables();
hb_ensure_data_dir();

if (!$is_member) {
    if (defined('HB_JSON_MODE') && HB_JSON_MODE) {
        hb_json_exit(array('ok' => false, 'message' => 'login_required'));
    }
    $url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : HB_URL.'/index.php';
    alert('로그인 후 이용할 수 있습니다.', G5_BBS_URL.'/login.php?url='.urlencode($url));
}
?>
