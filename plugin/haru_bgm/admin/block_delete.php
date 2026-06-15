<?php
include_once('./_common.php');
$bl_id = isset($_GET['bl_id']) ? (int)$_GET['bl_id'] : 0;
if ($bl_id) {
    $block = hb_table('block');
    $block_item = hb_table('block_item');
    sql_query("DELETE FROM `{$block_item}` WHERE bl_id='{$bl_id}'");
    sql_query("DELETE FROM `{$block}` WHERE bl_id='{$bl_id}' AND bl_scope='global'");
}
alert('삭제되었습니다.', HB_URL.'/admin/block_global.php');
?>
