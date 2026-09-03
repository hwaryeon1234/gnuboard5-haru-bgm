<?php
if (!defined('_GNUBOARD_')) exit;

$hb_admin_base = G5_PLUGIN_URL.'/haru_bgm/admin';

$menu['menu990'] = array(
    array('990000', '■ 하루BGM', $hb_admin_base.'/index.php', 'haru_bgm'),
    array('990100', '▶ 하루BGM · 대시보드', $hb_admin_base.'/index.php', 'haru_bgm_dashboard'),
    array('990110', '공용 운영판', $hb_admin_base.'/operation.php', 'haru_bgm_operation'),
    array('990120', '오늘 운영표', $hb_admin_base.'/today.php', 'haru_bgm_today'),
    array('990130', '음악 관리', $hb_admin_base.'/music_list.php', 'haru_bgm_music'),
    array('990140', '공통 시간표', $hb_admin_base.'/schedule_global.php', 'haru_bgm_schedule'),
    array('990150', '시간대 묶음', $hb_admin_base.'/block_global.php', 'haru_bgm_block'),
    array('990160', '순서표', $hb_admin_base.'/sequence_list.php', 'haru_bgm_sequence'),
    array('990180', '재생 로그', $hb_admin_base.'/logs.php', 'haru_bgm_logs'),
    array('990190', '환경설정', $hb_admin_base.'/settings.php', 'haru_bgm_settings')
);
unset($hb_admin_base);
