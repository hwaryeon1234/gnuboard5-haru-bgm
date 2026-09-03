<?php
include_once('./_common.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') alert('잘못된 요청입니다.', HB_URL.'/admin/schedule_global.php');
hb_check_csrf();
hb_check_admin_token_if_present();
$sc_id = isset($_POST['sc_id']) ? hb_int_value($_POST['sc_id'], 0) : 0;
if ($sc_id < 1) alert('잘못된 요청입니다.', HB_URL.'/admin/schedule_global.php');
$schedule = hb_table('schedule');
$schedule_item = hb_table('schedule_item');
if ($sc_id) {
    if (!hb_db_begin()) alert('DB 작업을 시작하지 못했습니다. 잠시 후 다시 시도해주세요.', HB_URL.'/admin/schedule_global.php');
    $row = sql_fetch("SELECT sc_id FROM `{$schedule}` WHERE sc_id='{$sc_id}' AND sc_scope='global' LIMIT 1 FOR UPDATE", false);
    if (!$row) { hb_db_rollback(); alert('삭제할 수 없는 공통 시간표입니다.', HB_URL.'/admin/schedule_global.php'); }
    $ok = sql_query("DELETE FROM `{$schedule_item}` WHERE sc_id='{$sc_id}'", false) ? true : false;
    if ($ok) $ok = sql_query("DELETE FROM `{$schedule}` WHERE sc_id='{$sc_id}' AND sc_scope='global'", false) ? true : false;
    if (!$ok) { hb_db_rollback(); alert('공통 시간표 삭제 중 DB 오류가 발생했습니다. 기존 데이터는 유지되었습니다.', HB_URL.'/admin/schedule_global.php'); }
    if (!hb_db_commit()) { hb_db_rollback(); alert('공통 시간표 삭제를 완료하지 못했습니다.', HB_URL.'/admin/schedule_global.php'); }
}
alert('삭제되었습니다.', HB_URL.'/admin/schedule_global.php');
