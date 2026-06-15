<?php
include_once('./_common.php');
$seq_id = isset($_POST['seq_id']) ? (int)$_POST['seq_id'] : 0;
$title = isset($_POST['seq_title']) ? trim($_POST['seq_title']) : '';
$type = isset($_POST['seq_type']) ? trim($_POST['seq_type']) : 'general';
if (!in_array($type, array('church','broadcast','event','store','general'), true)) $type='general';
$memo = isset($_POST['seq_memo']) ? trim($_POST['seq_memo']) : '';
$sort = isset($_POST['seq_sort']) ? (int)$_POST['seq_sort'] : 0;
$use = isset($_POST['seq_use']) ? 1 : 0;
$ids = hb_clean_music_ids(isset($_POST['mf_ids']) ? $_POST['mf_ids'] : array());
$step_titles = isset($_POST['step_titles']) && is_array($_POST['step_titles']) ? $_POST['step_titles'] : array();
$step_memos = isset($_POST['step_memos']) && is_array($_POST['step_memos']) ? $_POST['step_memos'] : array();
$yt_text = isset($_POST['quick_youtube_urls']) ? trim($_POST['quick_youtube_urls']) : '';
$yt_title = isset($_POST['quick_youtube_title']) && trim($_POST['quick_youtube_title']) !== '' ? trim($_POST['quick_youtube_title']) : $title;
if ($yt_text !== '') {
    $yt_ids = hb_create_youtube_musics_from_text($yt_text, $yt_title, 80, 'music', '순서표에서 바로 등록', isset($member['mb_id']) ? $member['mb_id'] : '');
    foreach ($yt_ids as $id) { $ids[]=(int)$id; $step_titles[]=''; $step_memos[]='YouTube'; }
}
$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
if ($title === '') alert('순서표 이름을 입력해주세요.');
if (!$ids) alert('순서 항목을 하나 이상 선택하거나 YouTube 링크를 넣어주세요.');
$sequence = hb_table('sequence');
$title_sql=hb_escape($title); $type_sql=hb_escape($type); $memo_sql=hb_escape($memo);
if ($seq_id) {
    sql_query("UPDATE `{$sequence}` SET seq_title='{$title_sql}', seq_type='{$type_sql}', seq_memo='{$memo_sql}', seq_use='{$use}', seq_sort='{$sort}', seq_updated_at=NOW() WHERE seq_id='{$seq_id}'");
} else {
    sql_query("INSERT INTO `{$sequence}` SET seq_title='{$title_sql}', seq_type='{$type_sql}', seq_memo='{$memo_sql}', seq_use='{$use}', seq_sort='{$sort}', seq_created_at=NOW()", false);
    if (function_exists('sql_insert_id')) $seq_id = (int)sql_insert_id();
    if (!$seq_id) { $last = sql_fetch("SELECT LAST_INSERT_ID() AS id", false); $seq_id = $last && isset($last['id']) ? (int)$last['id'] : 0; }
}
hb_save_sequence_items($seq_id, $ids, $step_titles, $step_memos);
alert('저장되었습니다.', HB_URL.'/admin/sequence_list.php');
?>
