<?php
include_once('./_common.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') alert('잘못된 요청입니다.', HB_URL.'/admin/block_global.php');
hb_check_csrf();
hb_check_admin_token_if_present();
$bl_id = isset($_POST['bl_id']) ? hb_int_value($_POST['bl_id'], 0) : 0;
if ($bl_id < 1) alert('잘못된 요청입니다.', HB_URL.'/admin/block_global.php');
$block = hb_table('block');
$block_item = hb_table('block_item');
if ($bl_id) {
    if (!hb_db_begin()) alert('DB 작업을 시작하지 못했습니다. 잠시 후 다시 시도해주세요.', HB_URL.'/admin/block_global.php');
    $row = sql_fetch("SELECT bl_id FROM `{$block}` WHERE bl_id='{$bl_id}' AND bl_scope='global' LIMIT 1 FOR UPDATE", false);
    if (!$row) { hb_db_rollback(); alert('삭제할 수 없는 공통 시간대입니다.', HB_URL.'/admin/block_global.php'); }
    $ok = sql_query("DELETE FROM `{$block_item}` WHERE bl_id='{$bl_id}'", false) ? true : false;
    if ($ok) $ok = sql_query("DELETE FROM `{$block}` WHERE bl_id='{$bl_id}' AND bl_scope='global'", false) ? true : false;
    if (!$ok) { hb_db_rollback(); alert('공통 시간대 삭제 중 DB 오류가 발생했습니다. 기존 데이터는 유지되었습니다.', HB_URL.'/admin/block_global.php'); }
    if (!hb_db_commit()) { hb_db_rollback(); alert('공통 시간대 삭제를 완료하지 못했습니다.', HB_URL.'/admin/block_global.php'); }
}
alert('삭제되었습니다.', HB_URL.'/admin/block_global.php');
