<?php
include_once('./_common.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') alert('잘못된 요청입니다.', HB_URL.'/admin/index.php');
hb_check_csrf();
hb_check_admin_token_if_present();
if (hb_request_body_too_large()) alert('요청 데이터가 너무 큽니다. 시간표 항목과 YouTube 링크 수를 줄여주세요.');
if (isset($_POST['mf_ids']) && is_array($_POST['mf_ids']) && count($_POST['mf_ids']) > HB_MAX_SCHEDULE_ITEMS) alert('시간표 음악은 최대 '.HB_MAX_SCHEDULE_ITEMS.'곡까지 저장할 수 있습니다.');

$sc_id = isset($_POST['sc_id']) ? hb_int_value($_POST['sc_id'], 0) : 0;
$mf_id = isset($_POST['mf_id']) ? hb_int_value($_POST['mf_id'], 0) : 0;
$mf_ids = hb_clean_music_ids(isset($_POST['mf_ids']) ? $_POST['mf_ids'] : array());
$title = isset($_POST['sc_title']) ? hb_text_limit($_POST['sc_title'], 255) : '';
$time = isset($_POST['sc_time']) ? trim(hb_scalar_string($_POST['sc_time'], '')) : '';
$play_mode_raw = isset($_POST['sc_play_mode']) ? hb_scalar_string($_POST['sc_play_mode'], '') : '';
$play_mode = $play_mode_raw === 'range' ? 'range' : 'once';
$end_time = isset($_POST['sc_end_time']) ? trim(hb_scalar_string($_POST['sc_end_time'], '')) : '';
$repeat = isset($_POST['sc_repeat']) ? 1 : 0;
$days = hb_clean_days(isset($_POST['sc_days']) ? $_POST['sc_days'] : array());
$start_date_raw = isset($_POST['sc_start_date']) ? trim(hb_scalar_string($_POST['sc_start_date'], '')) : '';
$end_date_raw = isset($_POST['sc_end_date']) ? trim(hb_scalar_string($_POST['sc_end_date'], '')) : '';
$use = isset($_POST['sc_use']) ? 1 : 0;
$sort = isset($_POST['sc_sort']) ? hb_int_value($_POST['sc_sort'], 0) : 0;
$quick_youtube_url = isset($_POST['quick_youtube_url']) ? trim(hb_scalar_string($_POST['quick_youtube_url'], '')) : '';
$quick_youtube_urls = isset($_POST['quick_youtube_urls']) ? trim(hb_scalar_string($_POST['quick_youtube_urls'], '')) : '';
$quick_youtube_title = isset($_POST['quick_youtube_title']) ? hb_text_limit($_POST['quick_youtube_title'], 255) : '';
$quick_text = trim($quick_youtube_url."\n".$quick_youtube_urls);
if (hb_youtube_text_too_large($quick_text)) alert('YouTube 입력 데이터가 너무 큽니다. 링크 수를 줄여주세요.');
$quick_links = $quick_text !== '' ? hb_parse_youtube_bulk($quick_text, HB_MAX_YOUTUBE_BULK_ITEMS + 1) : array();
if (count($quick_links) > HB_MAX_YOUTUBE_BULK_ITEMS) alert('YouTube 링크는 한 번에 최대 '.HB_MAX_YOUTUBE_BULK_ITEMS.'개까지 처리할 수 있습니다.');

if ($days === '') alert('요일을 1개 이상 선택해주세요.');
$date_error = hb_validate_date_range($start_date_raw, $end_date_raw);
if ($date_error !== '') alert($date_error);
if ($title === '' || !hb_valid_hm($time)) alert('필수 항목을 확인해주세요.');
if ($quick_text !== '' && !$quick_links) alert('YouTube 링크를 확인해주세요. 일반 영상 URL, youtu.be 링크, 영상 ID를 사용할 수 있습니다.');
if ($play_mode === 'range') {
    if (!hb_valid_hm($end_time)) alert('특정 시간 동안 재생하려면 종료 시간을 입력해주세요.');
    if ($time === $end_time) alert('시작 시간과 종료 시간은 다르게 설정해주세요.');
    $end_time_sql = "'".hb_hm_to_sql($end_time)."'";
} else {
    $end_time_sql = 'NULL';
    $repeat = 0;
}

$start = $start_date_raw !== '' ? "'".hb_escape($start_date_raw)."'" : 'NULL';
$end = $end_date_raw !== '' ? "'".hb_escape($end_date_raw)."'" : 'NULL';
$title_sql = hb_escape($title);
$time_sql = hb_hm_to_sql($time);
$play_mode_sql = hb_escape($play_mode);
$schedule = hb_table('schedule');

if (!hb_db_begin()) alert('DB 작업을 시작하지 못했습니다. 잠시 후 다시 시도해주세요.', HB_URL.'/admin/schedule_global.php');

$all_ids = array();
if ($mf_id > 0) $all_ids[] = $mf_id;
foreach ($mf_ids as $id) if ((int)$id > 0) $all_ids[] = (int)$id;

if ($quick_links) {
    $yt_title = $quick_youtube_title !== '' ? $quick_youtube_title : $title;
    $yt_ids = hb_create_youtube_musics_from_text($quick_text, $yt_title, 80, 'music', '공통 시간표에서 바로 등록', $quick_links);
    if (count($yt_ids) !== count($quick_links)) {
        hb_db_rollback();
        alert('YouTube 음악 등록 중 DB 오류가 발생했습니다. 편성과 음악 등록을 모두 취소했습니다.', HB_URL.'/admin/schedule_global.php');
    }
    foreach ($yt_ids as $id) $all_ids[] = (int)$id;
}

$all_ids = array_values(array_unique(array_filter(array_map('intval', $all_ids))));
if (count($all_ids) > HB_MAX_SCHEDULE_ITEMS) {
    hb_db_rollback();
    alert('시간표 음악은 대표 음악과 YouTube 추가분을 포함해 최대 '.HB_MAX_SCHEDULE_ITEMS.'곡까지 저장할 수 있습니다.', HB_URL.'/admin/schedule_global.php');
}
$all_ids = hb_filter_active_music_ids($all_ids, true);
$mf_id = $all_ids ? (int)$all_ids[0] : 0;
if (!$mf_id) {
    hb_db_rollback();
    alert('파일 음악/YouTube 중 하나 이상을 선택해주세요.', HB_URL.'/admin/schedule_global.php');
}

$write_ok = false;
if ($sc_id) {
    $old = sql_fetch("SELECT sc_id FROM `{$schedule}` WHERE sc_id='{$sc_id}' AND sc_scope='global' LIMIT 1 FOR UPDATE", false);
    if (!$old) { hb_db_rollback(); alert('수정할 수 없는 공통 시간표입니다.', HB_URL.'/admin/schedule_global.php'); }
    $write_ok = sql_query("UPDATE `{$schedule}` SET mf_id='{$mf_id}', sc_title='{$title_sql}', sc_time='{$time_sql}', sc_play_mode='{$play_mode_sql}', sc_end_time={$end_time_sql}, sc_repeat='{$repeat}', sc_days='{$days}', sc_start_date={$start}, sc_end_date={$end}, sc_sort='{$sort}', sc_use='{$use}', sc_updated_at=NOW() WHERE sc_id='{$sc_id}' AND sc_scope='global'", false) ? true : false;
} else {
    $write_ok = sql_query("INSERT INTO `{$schedule}` SET sc_scope='global', mf_id='{$mf_id}', sc_title='{$title_sql}', sc_time='{$time_sql}', sc_play_mode='{$play_mode_sql}', sc_end_time={$end_time_sql}, sc_repeat='{$repeat}', sc_days='{$days}', sc_start_date={$start}, sc_end_date={$end}, sc_sort='{$sort}', sc_use='{$use}', sc_created_at=NOW()", false) ? true : false;
    if ($write_ok && function_exists('sql_insert_id')) $sc_id = (int)sql_insert_id();
    if ($write_ok && !$sc_id) { $last = sql_fetch("SELECT LAST_INSERT_ID() AS id", false); $sc_id = $last && isset($last['id']) ? (int)$last['id'] : 0; }
    if (!$sc_id) $write_ok = false;
}

if (!$write_ok || !hb_save_schedule_items($sc_id, $all_ids) || !hb_sync_schedule_days($sc_id, $days)) {
    hb_db_rollback();
    alert('공통 시간표 저장 중 DB 오류가 발생했습니다. 기존 데이터는 유지되었습니다.', HB_URL.'/admin/schedule_global.php');
}
if (!hb_db_commit()) { hb_db_rollback(); alert('공통 시간표 저장을 완료하지 못했습니다.', HB_URL.'/admin/schedule_global.php'); }

alert('저장되었습니다.', HB_URL.'/admin/schedule_global.php');
