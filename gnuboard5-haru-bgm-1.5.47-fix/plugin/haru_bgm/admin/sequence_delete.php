<?php
include_once('./_common.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') alert('잘못된 요청입니다.', HB_URL.'/admin/sequence_list.php');
hb_check_csrf();
hb_check_admin_token_if_present();
$seq_id = isset($_POST['seq_id']) ? hb_int_value($_POST['seq_id'], 0) : 0;
if ($seq_id < 1) alert('잘못된 요청입니다.', HB_URL.'/admin/sequence_list.php');
$sequence = hb_table('sequence');
$sequence_item = hb_table('sequence_item');
if ($seq_id) {
    if (!hb_db_begin()) alert('DB 작업을 시작하지 못했습니다. 잠시 후 다시 시도해주세요.', HB_URL.'/admin/sequence_list.php');
    $row = sql_fetch("SELECT seq_id FROM `{$sequence}` WHERE seq_id='{$seq_id}' LIMIT 1 FOR UPDATE", false);
    if (!$row) { hb_db_rollback(); alert('삭제할 수 없는 순서표입니다.', HB_URL.'/admin/sequence_list.php'); }
    $ok = sql_query("DELETE FROM `{$sequence_item}` WHERE seq_id='{$seq_id}'", false) ? true : false;
    if ($ok) $ok = sql_query("DELETE FROM `{$sequence}` WHERE seq_id='{$seq_id}'", false) ? true : false;
    if (!$ok) { hb_db_rollback(); alert('순서표 삭제 중 DB 오류가 발생했습니다. 기존 데이터는 유지되었습니다.', HB_URL.'/admin/sequence_list.php'); }
    if (!hb_db_commit()) { hb_db_rollback(); alert('순서표 삭제를 완료하지 못했습니다.', HB_URL.'/admin/sequence_list.php'); }
}
alert('삭제되었습니다.', HB_URL.'/admin/sequence_list.php');
