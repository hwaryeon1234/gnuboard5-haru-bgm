<?php
include_once('./_common.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') alert('잘못된 요청입니다.', HB_URL.'/admin/index.php');
hb_check_csrf();
hb_check_admin_token_if_present();
if (hb_request_body_too_large()) alert('요청 데이터가 너무 큽니다. 시간대 음악과 YouTube 링크 수를 줄여주세요.');
if (isset($_POST['mf_ids']) && is_array($_POST['mf_ids']) && count($_POST['mf_ids']) > HB_MAX_BLOCK_ITEMS) alert('시간대 음악은 최대 '.HB_MAX_BLOCK_ITEMS.'곡까지 저장할 수 있습니다.');

$bl_id = isset($_POST['bl_id']) ? hb_int_value($_POST['bl_id'], 0) : 0;
$title = isset($_POST['bl_title']) ? hb_text_limit($_POST['bl_title'], 255) : '';
$start_time = isset($_POST['bl_start_time']) ? trim(hb_scalar_string($_POST['bl_start_time'], '')) : '';
$end_time = isset($_POST['bl_end_time']) ? trim(hb_scalar_string($_POST['bl_end_time'], '')) : '';
$days = hb_clean_days(isset($_POST['bl_days']) ? $_POST['bl_days'] : array());
$start_date_raw = isset($_POST['bl_start_date']) ? trim(hb_scalar_string($_POST['bl_start_date'], '')) : '';
$end_date_raw = isset($_POST['bl_end_date']) ? trim(hb_scalar_string($_POST['bl_end_date'], '')) : '';
$mode_raw = isset($_POST['bl_play_mode']) ? hb_scalar_string($_POST['bl_play_mode'], '') : '';
$mode = $mode_raw === 'random' ? 'random' : 'sequence';
$repeat = isset($_POST['bl_repeat']) ? 1 : 0;
$use = isset($_POST['bl_use']) ? 1 : 0;
$sort = isset($_POST['bl_sort']) ? hb_int_value($_POST['bl_sort'], 0) : 0;
$music_ids = hb_clean_music_ids(isset($_POST['mf_ids']) ? $_POST['mf_ids'] : array());
$quick_youtube_urls = isset($_POST['quick_youtube_urls']) ? trim(hb_scalar_string($_POST['quick_youtube_urls'], '')) : '';
$quick_youtube_title = isset($_POST['quick_youtube_title']) ? hb_text_limit($_POST['quick_youtube_title'], 255) : '';
if (hb_youtube_text_too_large($quick_youtube_urls)) alert('YouTube 입력 데이터가 너무 큽니다. 링크 수를 줄여주세요.');
$quick_links = $quick_youtube_urls !== '' ? hb_parse_youtube_bulk($quick_youtube_urls, HB_MAX_YOUTUBE_BULK_ITEMS + 1) : array();
if (count($quick_links) > HB_MAX_YOUTUBE_BULK_ITEMS) alert('YouTube 링크는 한 번에 최대 '.HB_MAX_YOUTUBE_BULK_ITEMS.'개까지 처리할 수 있습니다.');

if ($days === '') alert('요일을 1개 이상 선택해주세요.');
$date_error = hb_validate_date_range($start_date_raw, $end_date_raw);
if ($date_error !== '') alert($date_error);
if ($title === '' || !hb_valid_hm($start_time) || !hb_valid_hm($end_time)) alert('필수 항목을 확인해주세요.');
if ($start_time === $end_time) alert('시작 시간과 끝 시간은 다르게 설정해주세요.');
if ($quick_youtube_urls !== '' && !$quick_links) alert('YouTube 링크를 확인해주세요. 일반 영상 URL, youtu.be 링크, 영상 ID를 사용할 수 있습니다.');

$start = $start_date_raw !== '' ? "'".hb_escape($start_date_raw)."'" : 'NULL';
$end = $end_date_raw !== '' ? "'".hb_escape($end_date_raw)."'" : 'NULL';
$title_sql = hb_escape($title);
$start_sql = hb_hm_to_sql($start_time);
$end_sql = hb_hm_to_sql($end_time);
$block = hb_table('block');

if (!hb_db_begin()) alert('DB 작업을 시작하지 못했습니다. 잠시 후 다시 시도해주세요.', HB_URL.'/admin/block_global.php');

if ($quick_links) {
    $yt_title = $quick_youtube_title !== '' ? $quick_youtube_title : $title;
    $yt_ids = hb_create_youtube_musics_from_text($quick_youtube_urls, $yt_title, 80, 'music', '공통 시간대 묶음에서 바로 등록', $quick_links);
    if (count($yt_ids) !== count($quick_links)) {
        hb_db_rollback();
        alert('YouTube 음악 등록 중 DB 오류가 발생했습니다. 시간대와 음악 등록을 모두 취소했습니다.', HB_URL.'/admin/block_global.php');
    }
    $music_ids = array_merge($music_ids, $yt_ids);
}

$music_ids = array_values(array_unique(array_map('intval', $music_ids)));
if (count($music_ids) > HB_MAX_BLOCK_ITEMS) {
    hb_db_rollback();
    alert('시간대 음악은 YouTube 추가분을 포함해 최대 '.HB_MAX_BLOCK_ITEMS.'곡까지 저장할 수 있습니다.', HB_URL.'/admin/block_global.php');
}
$music_ids = hb_filter_active_music_ids($music_ids, true);
if (!$music_ids) {
    hb_db_rollback();
    alert('시간대 안에 넣을 음악을 1개 이상 선택해주세요.', HB_URL.'/admin/block_global.php');
}

$write_ok = false;
if ($bl_id) {
    $old = sql_fetch("SELECT bl_id FROM `{$block}` WHERE bl_id='{$bl_id}' AND bl_scope='global' LIMIT 1 FOR UPDATE", false);
    if (!$old) { hb_db_rollback(); alert('수정할 수 없는 공통 시간대입니다.', HB_URL.'/admin/block_global.php'); }
    $write_ok = sql_query("UPDATE `{$block}` SET bl_title='{$title_sql}', bl_start_time='{$start_sql}', bl_end_time='{$end_sql}', bl_days='{$days}', bl_start_date={$start}, bl_end_date={$end}, bl_play_mode='{$mode}', bl_repeat='{$repeat}', bl_sort='{$sort}', bl_use='{$use}', bl_updated_at=NOW() WHERE bl_id='{$bl_id}' AND bl_scope='global'", false) ? true : false;
} else {
    $write_ok = sql_query("INSERT INTO `{$block}` SET bl_scope='global', bl_title='{$title_sql}', bl_start_time='{$start_sql}', bl_end_time='{$end_sql}', bl_days='{$days}', bl_start_date={$start}, bl_end_date={$end}, bl_play_mode='{$mode}', bl_repeat='{$repeat}', bl_sort='{$sort}', bl_use='{$use}', bl_created_at=NOW()", false) ? true : false;
    if ($write_ok && function_exists('sql_insert_id')) $bl_id = (int)sql_insert_id();
    if ($write_ok && !$bl_id) { $last = sql_fetch("SELECT LAST_INSERT_ID() AS id", false); $bl_id = $last && isset($last['id']) ? (int)$last['id'] : 0; }
    if (!$bl_id) $write_ok = false;
}

if (!$write_ok || !hb_save_block_items($bl_id, $music_ids) || !hb_sync_block_days($bl_id, $days)) {
    hb_db_rollback();
    alert('공통 시간대 저장 중 DB 오류가 발생했습니다. 기존 데이터는 유지되었습니다.', HB_URL.'/admin/block_global.php');
}
if (!hb_db_commit()) { hb_db_rollback(); alert('공통 시간대 저장을 완료하지 못했습니다.', HB_URL.'/admin/block_global.php'); }

alert('저장되었습니다.', HB_URL.'/admin/block_global.php');
