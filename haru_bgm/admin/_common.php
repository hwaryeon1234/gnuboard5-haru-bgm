<?php
include_once(dirname(__FILE__).'/../_common.php');
if (!$is_admin) {
    if (defined('HB_JSON_MODE') && HB_JSON_MODE) {
        hb_json_exit(array('ok' => false, 'message' => 'admin_required'));
    }
    alert('관리자만 접근할 수 있습니다.', G5_URL);
}
?>
