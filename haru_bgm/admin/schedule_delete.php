<?php
include_once('./_common.php');
$sc_id = isset($_GET['sc_id']) ? (int)$_GET['sc_id'] : 0;
$schedule = hb_table('schedule');
$schedule_item = hb_table('schedule_item');
if ($sc_id) sql_query("DELETE FROM `{$schedule_item}` WHERE sc_id='{$sc_id}'");
sql_query("DELETE FROM `{$schedule}` WHERE sc_id='{$sc_id}' AND sc_scope='global'");
alert('삭제되었습니다.', HB_URL.'/admin/schedule_global.php');
?>
