<?php
include_once('./_common.php');
hb_require_member_bgm_enabled();
$bl_id = isset($_GET['bl_id']) ? (int)$_GET['bl_id'] : 0;
$mb_id = hb_escape($member['mb_id']);
if ($bl_id) {
    $block = hb_table('block');
    $block_item = hb_table('block_item');
    $old = sql_fetch("SELECT bl_id FROM `{$block}` WHERE bl_id='{$bl_id}' AND bl_scope='user' AND mb_id='{$mb_id}'");
    if ($old) {
        sql_query("DELETE FROM `{$block_item}` WHERE bl_id='{$bl_id}'");
        sql_query("DELETE FROM `{$block}` WHERE bl_id='{$bl_id}' AND mb_id='{$mb_id}'");
    }
}
alert('삭제되었습니다.', HB_URL.'/my_blocks.php');
?>
