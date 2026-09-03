<?php
if (!defined('_GNUBOARD_')) exit;

$hb_admin_base = G5_PLUGIN_URL.'/haru_bgm/admin';

// 1.5.17까지 사용한 390번대 권한을 990번대 메뉴로 옮긴 뒤에도
// 서브관리자가 즉시 접근할 수 있도록 현재 요청의 권한 배열에 호환 별칭을 제공합니다.
if (isset($auth) && is_array($auth)) {
    $hb_legacy_auth_map = array(
        '990100'=>'390100', '990110'=>'390110', '990120'=>'390120', '990130'=>'390130',
        '990140'=>'390140', '990150'=>'390150', '990160'=>'390160', '990180'=>'390180', '990190'=>'390190'
    );
    foreach ($hb_legacy_auth_map as $hb_new_menu => $hb_old_menu) {
        if (empty($auth[$hb_new_menu]) && !empty($auth[$hb_old_menu])) {
            $auth[$hb_new_menu] = $auth[$hb_old_menu];
        }
    }
    unset($hb_legacy_auth_map, $hb_new_menu, $hb_old_menu);
}
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
