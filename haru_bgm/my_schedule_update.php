<?php
include_once('./_common.php');
hb_require_member_bgm_enabled();

$sc_id = isset($_POST['sc_id']) ? (int)$_POST['sc_id'] : 0;
$mf_id = isset($_POST['mf_id']) ? (int)$_POST['mf_id'] : 0;
$mf_ids = hb_clean_music_ids(isset($_POST['mf_ids']) ? $_POST['mf_ids'] : array());
$title = isset($_POST['sc_title']) ? trim($_POST['sc_title']) : '';
$time = isset($_POST['sc_time']) ? trim($_POST['sc_time']) : '';
$play_mode = isset($_POST['sc_play_mode']) && $_POST['sc_play_mode'] === 'range' ? 'range' : 'once';
$end_time = isset($_POST['sc_end_time']) ? trim($_POST['sc_end_time']) : '';
$repeat = isset($_POST['sc_repeat']) ? 1 : 0;
$days = hb_clean_days(isset($_POST['sc_days']) ? $_POST['sc_days'] : array());
$start = isset($_POST['sc_start_date']) && $_POST['sc_start_date'] ? "'".hb_escape($_POST['sc_start_date'])."'" : 'NULL';
$end = isset($_POST['sc_end_date']) && $_POST['sc_end_date'] ? "'".hb_escape($_POST['sc_end_date'])."'" : 'NULL';
$use = isset($_POST['sc_use']) ? 1 : 0;
$mb_id = hb_escape($member['mb_id']);
$quick_youtube_url = isset($_POST['quick_youtube_url']) ? trim($_POST['quick_youtube_url']) : '';
$quick_youtube_urls = isset($_POST['quick_youtube_urls']) ? trim($_POST['quick_youtube_urls']) : '';
$quick_youtube_title = isset($_POST['quick_youtube_title']) ? trim($_POST['quick_youtube_title']) : '';
$all_ids = array();
if ($mf_id > 0) $all_ids[] = $mf_id;
foreach ($mf_ids as $id) { if ($id > 0) $all_ids[] = $id; }
if ($quick_youtube_url !== '' || $quick_youtube_urls !== '') {
    $yt_title = $quick_youtube_title !== '' ? $quick_youtube_title : $title;
    $ids = hb_create_youtube_musics_from_text($quick_youtube_url."
".$quick_youtube_urls, $yt_title, 80, 'music', '개인 시간표에서 바로 등록', isset($member['mb_id']) ? $member['mb_id'] : '');
    if (!$ids) alert('YouTube 링크를 확인해주세요.');
    foreach ($ids as $id) $all_ids[] = (int)$id;
}
$all_ids = array_values(array_unique(array_filter(array_map('intval', $all_ids))));
if ($all_ids) $mf_id = (int)$all_ids[0];
if (!$mf_id || !$title || !hb_valid_hm($time)) alert('필수 항목을 확인해주세요. 파일 음악/YouTube 중 하나 이상을 선택해야 합니다.');
if ($play_mode === 'range') {
    if (!hb_valid_hm($end_time)) alert('특정 시간 동안 재생하려면 종료 시간을 입력해주세요.');
    if ($time === $end_time) alert('시작 시간과 종료 시간은 다르게 설정해주세요.');
    $end_time_sql = "'".hb_hm_to_sql($end_time)."'";
} else {
    $end_time_sql = 'NULL';
    $repeat = 0;
}
$title_sql = hb_escape($title);
$time_sql = hb_hm_to_sql($time);
$play_mode_sql = hb_escape($play_mode);
$schedule = hb_table('schedule');

if ($sc_id) {
    $old = sql_fetch("SELECT sc_id FROM `{$schedule}` WHERE sc_id='{$sc_id}' AND sc_scope='user' AND mb_id='{$mb_id}'");
    if (!$old) alert('수정할 수 없는 시간표입니다.');
    sql_query("UPDATE `{$schedule}` SET mf_id='{$mf_id}', sc_title='{$title_sql}', sc_time='{$time_sql}', sc_play_mode='{$play_mode_sql}', sc_end_time={$end_time_sql}, sc_repeat='{$repeat}', sc_days='{$days}', sc_start_date={$start}, sc_end_date={$end}, sc_use='{$use}', sc_updated_at=NOW() WHERE sc_id='{$sc_id}' AND mb_id='{$mb_id}'");
} else {
    sql_query("INSERT INTO `{$schedule}` SET sc_scope='user', mb_id='{$mb_id}', mf_id='{$mf_id}', sc_title='{$title_sql}', sc_time='{$time_sql}', sc_play_mode='{$play_mode_sql}', sc_end_time={$end_time_sql}, sc_repeat='{$repeat}', sc_days='{$days}', sc_start_date={$start}, sc_end_date={$end}, sc_use='{$use}', sc_created_at=NOW()");
    if (function_exists('sql_insert_id')) $sc_id = (int)sql_insert_id();
    if (!$sc_id) { $last = sql_fetch("SELECT LAST_INSERT_ID() AS id", false); $sc_id = $last && isset($last['id']) ? (int)$last['id'] : 0; }
}
hb_save_schedule_items($sc_id, $all_ids ? $all_ids : array($mf_id));

alert('저장되었습니다.', HB_URL.'/my_schedule.php');
?>
