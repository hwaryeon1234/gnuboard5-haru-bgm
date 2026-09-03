<?php
define('HB_SKIP_MEMBER_GATE', true);
include_once('./_common.php');

if (hb_is_plugin_admin()) {
    $targets = array(
        '990100'=>'index.php', '990110'=>'operation.php', '990120'=>'today.php',
        '990130'=>'music_list.php', '990140'=>'schedule_global.php', '990150'=>'block_global.php',
        '990160'=>'sequence_list.php', '990180'=>'logs.php', '990190'=>'settings.php'
    );
    foreach ($targets as $menu_id => $script) {
        if (hb_user_has_admin_auth($menu_id, 'r')) {
            goto_url(HB_URL.'/admin/'.$script);
            exit;
        }
    }
}

header('HTTP/1.1 404 Not Found');
exit;
