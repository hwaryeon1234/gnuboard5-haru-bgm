<?php
include_once('./_common.php');
hb_require_member_bgm_enabled();
$bl_id = isset($_POST['bl_id']) ? (int)$_POST['bl_id'] : 0;
$title = isset($_POST['bl_title']) ? trim($_POST['bl_title']) : '';
$start_time = isset($_POST['bl_start_time']) ? trim($_POST['bl_start_time']) : '';
$end_time = isset($_POST['bl_end_time']) ? trim($_POST['bl_end_time']) : '';
$days = hb_clean_days(isset($_POST['bl_days']) ? $_POST['bl_days'] : array());
$start = isset($_POST['bl_start_date']) && $_POST['bl_start_date'] ? "'".hb_escape($_POST['bl_start_date'])."'" : 'NULL';
$end = isset($_POST['bl_end_date']) && $_POST['bl_end_date'] ? "'".hb_escape($_POST['bl_end_date'])."'" : 'NULL';
$mode = isset($_POST['bl_play_mode']) && $_POST['bl_play_mode'] === 'random' ? 'random' : 'sequence';
$repeat = isset($_POST['bl_repeat']) ? 1 : 0;
$use = isset($_POST['bl_use']) ? 1 : 0;
$music_ids = hb_clean_music_ids(isset($_POST['mf_ids']) ? $_POST['mf_ids'] : array());
$mb_id = hb_escape($member['mb_id']);
if (!$title || !hb_valid_hm($start_time) || !hb_valid_hm($end_time)) alert('필수 항목을 확인해주세요.');
if ($start_time === $end_time) alert('시작 시간과 끝 시간은 다르게 설정해주세요.');
if (!$music_ids) alert('시간대 안에 넣을 음악을 1개 이상 선택해주세요.');
$title = hb_escape($title);
$start_sql = hb_hm_to_sql($start_time);
$end_sql = hb_hm_to_sql($end_time);
$block = hb_table('block');
if ($bl_id) {
    $old = sql_fetch("SELECT bl_id FROM `{$block}` WHERE bl_id='{$bl_id}' AND bl_scope='user' AND mb_id='{$mb_id}'");
    if (!$old) alert('수정할 수 없는 시간대입니다.');
    sql_query("UPDATE `{$block}` SET bl_title='{$title}', bl_start_time='{$start_sql}', bl_end_time='{$end_sql}', bl_days='{$days}', bl_start_date={$start}, bl_end_date={$end}, bl_play_mode='{$mode}', bl_repeat='{$repeat}', bl_use='{$use}', bl_updated_at=NOW() WHERE bl_id='{$bl_id}' AND mb_id='{$mb_id}'");
} else {
    sql_query("INSERT INTO `{$block}` SET bl_scope='user', mb_id='{$mb_id}', bl_title='{$title}', bl_start_time='{$start_sql}', bl_end_time='{$end_sql}', bl_days='{$days}', bl_start_date={$start}, bl_end_date={$end}, bl_play_mode='{$mode}', bl_repeat='{$repeat}', bl_use='{$use}', bl_created_at=NOW()");
    $bl_id = sql_insert_id();
}
hb_save_block_items($bl_id, $music_ids);
alert('저장되었습니다.', HB_URL.'/my_blocks.php');
?>
