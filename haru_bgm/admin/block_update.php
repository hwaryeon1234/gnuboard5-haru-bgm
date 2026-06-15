<?php
include_once('./_common.php');
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
$sort = isset($_POST['bl_sort']) ? (int)$_POST['bl_sort'] : 0;
$music_ids = hb_clean_music_ids(isset($_POST['mf_ids']) ? $_POST['mf_ids'] : array());
$quick_youtube_urls = isset($_POST['quick_youtube_urls']) ? trim($_POST['quick_youtube_urls']) : '';
$quick_youtube_title = isset($_POST['quick_youtube_title']) ? trim($_POST['quick_youtube_title']) : '';
if ($quick_youtube_urls !== '') {
    $yt_title = $quick_youtube_title !== '' ? $quick_youtube_title : $title;
    $yt_ids = hb_create_youtube_musics_from_text($quick_youtube_urls, $yt_title, 80, 'music', '공통 시간대 묶음에서 바로 등록', isset($member['mb_id']) ? $member['mb_id'] : '');
    if (!$yt_ids) alert('YouTube 링크를 확인해주세요. 일반 영상 URL, youtu.be 링크, 영상 ID를 사용할 수 있습니다.');
    $music_ids = array_values(array_unique(array_merge($music_ids, $yt_ids)));
}
if (!$title || !hb_valid_hm($start_time) || !hb_valid_hm($end_time)) alert('필수 항목을 확인해주세요.');
if ($start_time === $end_time) alert('시작 시간과 끝 시간은 다르게 설정해주세요.');
if (!$music_ids) alert('시간대 안에 넣을 음악을 1개 이상 선택해주세요.');
$title = hb_escape($title);
$start_sql = hb_hm_to_sql($start_time);
$end_sql = hb_hm_to_sql($end_time);
$block = hb_table('block');
if ($bl_id) {
    sql_query("UPDATE `{$block}` SET bl_title='{$title}', bl_start_time='{$start_sql}', bl_end_time='{$end_sql}', bl_days='{$days}', bl_start_date={$start}, bl_end_date={$end}, bl_play_mode='{$mode}', bl_repeat='{$repeat}', bl_sort='{$sort}', bl_use='{$use}', bl_updated_at=NOW() WHERE bl_id='{$bl_id}' AND bl_scope='global'");
} else {
    sql_query("INSERT INTO `{$block}` SET bl_scope='global', mb_id='', bl_title='{$title}', bl_start_time='{$start_sql}', bl_end_time='{$end_sql}', bl_days='{$days}', bl_start_date={$start}, bl_end_date={$end}, bl_play_mode='{$mode}', bl_repeat='{$repeat}', bl_sort='{$sort}', bl_use='{$use}', bl_created_at=NOW()");
    $bl_id = sql_insert_id();
}
hb_save_block_items($bl_id, $music_ids);
alert('저장되었습니다.', HB_URL.'/admin/block_global.php');
?>
