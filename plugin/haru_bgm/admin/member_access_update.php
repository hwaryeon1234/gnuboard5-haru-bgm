<?php
include_once('./_common.php');
$mode = isset($_POST['mode']) ? $_POST['mode'] : 'member';
$return_url = isset($_POST['return_url']) ? $_POST['return_url'] : HB_URL.'/admin/member_access.php';
if (strpos($return_url, HB_URL) === false && strpos($return_url, '/plugin/haru_bgm/') === false) $return_url = HB_URL.'/admin/member_access.php';

if ($mode === 'default') {
    $enabled = isset($_POST['member_default_enabled']) && $_POST['member_default_enabled'] === '0' ? '0' : '1';
    hb_update_setting('member_default_enabled', $enabled);
    alert('기본 사용값을 저장했습니다.', $return_url);
}

$member_table = hb_member_table_name();
$admin_id = isset($member['mb_id']) ? $member['mb_id'] : '';
if ($mode === 'bulk') {
    $ids = array();
    if (isset($_POST['one_on'])) { $ids[] = trim($_POST['one_on']); $enabled = 1; }
    elseif (isset($_POST['one_off'])) { $ids[] = trim($_POST['one_off']); $enabled = 0; }
    else { $ids = isset($_POST['mb_ids']) && is_array($_POST['mb_ids']) ? $_POST['mb_ids'] : array(); $enabled = isset($_POST['ma_enabled']) && (int)$_POST['ma_enabled'] === 1 ? 1 : 0; }
    $count = 0;
    foreach ($ids as $mb_id) {
        $mb_id = trim((string)$mb_id); if ($mb_id === '') continue;
        $mb_id_sql = hb_escape($mb_id);
        $exists = sql_fetch("SELECT mb_id FROM `{$member_table}` WHERE mb_id='{$mb_id_sql}' LIMIT 1", false);
        if (!$exists) continue;
        hb_set_member_bgm_enabled($mb_id, $enabled, $enabled ? '일괄 ON' : '일괄 OFF', $admin_id);
        $count++;
    }
    alert($count.'명의 하루브금 사용설정을 변경했습니다.', $return_url);
}

$mb_id = isset($_POST['mb_id']) ? trim($_POST['mb_id']) : '';
$enabled = isset($_POST['ma_enabled']) && (int)$_POST['ma_enabled'] === 1 ? 1 : 0;
$memo = isset($_POST['ma_memo']) ? trim($_POST['ma_memo']) : '';
if ($mb_id === '') alert('회원 아이디가 없습니다.');
$mb_id_sql = hb_escape($mb_id);
$exists = sql_fetch("SELECT mb_id FROM `{$member_table}` WHERE mb_id='{$mb_id_sql}' LIMIT 1", false);
if (!$exists) alert('존재하지 않는 회원입니다.');
hb_set_member_bgm_enabled($mb_id, $enabled, $memo, $admin_id);
alert($enabled ? '하루브금 사용을 ON 처리했습니다.' : '하루브금 사용을 OFF 처리했습니다.', $return_url);
?>
