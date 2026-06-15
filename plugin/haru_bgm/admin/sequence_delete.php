<?php
include_once('./_common.php');
$seq_id = isset($_GET['seq_id']) ? (int)$_GET['seq_id'] : 0;
$sequence = hb_table('sequence');
$sequence_item = hb_table('sequence_item');
if ($seq_id) {
    sql_query("DELETE FROM `{$sequence_item}` WHERE seq_id='{$seq_id}'");
    sql_query("DELETE FROM `{$sequence}` WHERE seq_id='{$seq_id}'");
}
alert('삭제되었습니다.', HB_URL.'/admin/sequence_list.php');
?>
