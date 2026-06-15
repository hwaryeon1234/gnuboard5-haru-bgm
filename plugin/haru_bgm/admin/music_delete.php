<?php
include_once('./_common.php');
$mf_id = isset($_GET['mf_id']) ? (int)$_GET['mf_id'] : 0;
$music = hb_table('music');
if ($mf_id) {
    $row = sql_fetch("SELECT * FROM `{$music}` WHERE mf_id='{$mf_id}'");
    if ($row) {
        $oldfile = hb_safe_file($row['mf_file']);
        if ($oldfile && file_exists(HB_DATA_PATH.'/'.$oldfile)) @unlink(HB_DATA_PATH.'/'.$oldfile);
        $schedule = hb_table('schedule');
        $block_item = hb_table('block_item');
        sql_query("DELETE FROM `{$schedule}` WHERE mf_id='{$mf_id}'");
        sql_query("DELETE FROM `{$block_item}` WHERE mf_id='{$mf_id}'");
        sql_query("DELETE FROM `{$music}` WHERE mf_id='{$mf_id}'");
    }
}
alert('삭제되었습니다.', HB_URL.'/admin/music_list.php');
?>
