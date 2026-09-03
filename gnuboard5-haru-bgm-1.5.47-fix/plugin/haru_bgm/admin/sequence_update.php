<?php
include_once('./_common.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') alert('잘못된 요청입니다.', HB_URL.'/admin/index.php');
hb_check_csrf();
hb_check_admin_token_if_present();
if (hb_request_body_too_large()) alert('요청 데이터가 너무 큽니다. 순서 항목과 YouTube 링크 수를 줄여주세요.');
if (isset($_POST['mf_ids']) && is_array($_POST['mf_ids']) && count($_POST['mf_ids']) > HB_MAX_SEQUENCE_ITEMS) alert('순서표는 최대 '.HB_MAX_SEQUENCE_ITEMS.'개 항목까지 저장할 수 있습니다.');

$seq_id = isset($_POST['seq_id']) ? hb_int_value($_POST['seq_id'], 0) : 0;
$title = isset($_POST['seq_title']) ? hb_text_limit($_POST['seq_title'], 255) : '';
$type = isset($_POST['seq_type']) ? trim(hb_scalar_string($_POST['seq_type'], '')) : 'general';
if (!in_array($type, array('church','broadcast','event','store','general'), true)) $type = 'general';
$memo_raw = isset($_POST['seq_memo']) ? trim(hb_scalar_string($_POST['seq_memo'], '')) : '';
if (!hb_text_fits($memo_raw)) alert('메모는 '.HB_MAX_MEMO_CHARS.'자 / '.hb_human_bytes(HB_MAX_MEMO_BYTES).' 이내로 입력해주세요.');
$memo = hb_text_limit($memo_raw, HB_MAX_MEMO_CHARS);
$sort = isset($_POST['seq_sort']) ? hb_int_value($_POST['seq_sort'], 0) : 0;
$use = isset($_POST['seq_use']) ? 1 : 0;
$posted_ids = isset($_POST['mf_ids']) && is_array($_POST['mf_ids']) ? $_POST['mf_ids'] : array();
$posted_titles = isset($_POST['step_titles']) && is_array($_POST['step_titles']) ? $_POST['step_titles'] : array();
$posted_memos = isset($_POST['step_memos']) && is_array($_POST['step_memos']) ? $_POST['step_memos'] : array();
$yt_text = isset($_POST['quick_youtube_urls']) ? trim(hb_scalar_string($_POST['quick_youtube_urls'], '')) : '';
$yt_title = isset($_POST['quick_youtube_title']) && hb_text_limit($_POST['quick_youtube_title'], 255) !== '' ? hb_text_limit($_POST['quick_youtube_title'], 255) : $title;
if (hb_youtube_text_too_large($yt_text)) alert('YouTube 입력 데이터가 너무 큽니다. 링크 수를 줄여주세요.');
$quick_links = $yt_text !== '' ? hb_parse_youtube_bulk($yt_text, HB_MAX_YOUTUBE_BULK_ITEMS + 1) : array();
if (count($quick_links) > HB_MAX_YOUTUBE_BULK_ITEMS) alert('YouTube 링크는 한 번에 최대 '.HB_MAX_YOUTUBE_BULK_ITEMS.'개까지 처리할 수 있습니다.');

if ($title === '') alert('순서표 이름을 입력해주세요.');
if ($yt_text !== '' && !$quick_links) alert('YouTube 링크를 확인해주세요. 일반 영상 URL, youtu.be 링크, 영상 ID를 사용할 수 있습니다.');

$ids = array();
$step_titles = array();
$step_memos = array();
foreach ($posted_ids as $idx => $raw_id) {
    $id = hb_int_value($raw_id, 0);
    if ($id <= 0) continue;
    $ids[] = $id;
    $step_titles[] = isset($posted_titles[$idx]) ? hb_text_limit($posted_titles[$idx], 255) : '';
    $step_memos[] = isset($posted_memos[$idx]) ? hb_text_limit($posted_memos[$idx], 255) : '';
}

$sequence = hb_table('sequence');
$title_sql = hb_escape($title);
$type_sql = hb_escape($type);
$memo_sql = hb_escape($memo);
if (!hb_db_begin()) alert('DB 작업을 시작하지 못했습니다. 잠시 후 다시 시도해주세요.', HB_URL.'/admin/sequence_list.php');

if ($quick_links) {
    $yt_ids = hb_create_youtube_musics_from_text($yt_text, $yt_title, 80, 'music', '순서표에서 바로 등록', $quick_links);
    if (count($yt_ids) !== count($quick_links)) {
        hb_db_rollback();
        alert('YouTube 음악 등록 중 DB 오류가 발생했습니다. 순서표와 음악 등록을 모두 취소했습니다.', HB_URL.'/admin/sequence_list.php');
    }
    foreach ($yt_ids as $id) { $ids[] = (int)$id; $step_titles[] = ''; $step_memos[] = 'YouTube'; }
}

if (count($ids) > HB_MAX_SEQUENCE_ITEMS) {
    hb_db_rollback();
    alert('순서표는 YouTube 추가분을 포함해 최대 '.HB_MAX_SEQUENCE_ITEMS.'개 항목까지 저장할 수 있습니다.', HB_URL.'/admin/sequence_list.php');
}

if ($ids) {
    $active_unique = hb_filter_active_music_ids($ids, true);
    $active_map = array_fill_keys(array_map('intval', $active_unique), true);
    $filtered_ids = array(); $filtered_titles = array(); $filtered_memos = array();
    foreach ($ids as $idx => $id) {
        $id = (int)$id;
        if (!isset($active_map[$id])) continue;
        $filtered_ids[] = $id;
        $filtered_titles[] = isset($step_titles[$idx]) ? $step_titles[$idx] : '';
        $filtered_memos[] = isset($step_memos[$idx]) ? $step_memos[$idx] : '';
    }
    $ids = $filtered_ids; $step_titles = $filtered_titles; $step_memos = $filtered_memos;
}
if (!$ids) {
    hb_db_rollback();
    alert('순서 항목을 하나 이상 선택하거나 YouTube 링크를 넣어주세요.', HB_URL.'/admin/sequence_list.php');
}

$write_ok = false;
if ($seq_id) {
    $old = sql_fetch("SELECT seq_id FROM `{$sequence}` WHERE seq_id='{$seq_id}' LIMIT 1 FOR UPDATE", false);
    if (!$old) { hb_db_rollback(); alert('수정할 순서표를 찾을 수 없습니다.', HB_URL.'/admin/sequence_list.php'); }
    $write_ok = sql_query("UPDATE `{$sequence}` SET seq_title='{$title_sql}', seq_type='{$type_sql}', seq_memo='{$memo_sql}', seq_use='{$use}', seq_sort='{$sort}', seq_updated_at=NOW() WHERE seq_id='{$seq_id}'", false) ? true : false;
} else {
    $write_ok = sql_query("INSERT INTO `{$sequence}` SET seq_title='{$title_sql}', seq_type='{$type_sql}', seq_memo='{$memo_sql}', seq_use='{$use}', seq_sort='{$sort}', seq_created_at=NOW()", false) ? true : false;
    if ($write_ok && function_exists('sql_insert_id')) $seq_id = (int)sql_insert_id();
    if ($write_ok && !$seq_id) { $last = sql_fetch("SELECT LAST_INSERT_ID() AS id", false); $seq_id = $last && isset($last['id']) ? (int)$last['id'] : 0; }
    if (!$seq_id) $write_ok = false;
}

if (!$write_ok || !hb_save_sequence_items($seq_id, $ids, $step_titles, $step_memos)) {
    hb_db_rollback();
    alert('순서표 저장 중 DB 오류가 발생했습니다. 기존 데이터는 유지되었습니다.', HB_URL.'/admin/sequence_list.php');
}
if (!hb_db_commit()) { hb_db_rollback(); alert('순서표 저장을 완료하지 못했습니다.', HB_URL.'/admin/sequence_list.php'); }

alert('저장되었습니다.', HB_URL.'/admin/sequence_list.php');
