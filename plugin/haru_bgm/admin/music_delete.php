<?php
include_once('./_common.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') alert('잘못된 요청입니다.', HB_URL.'/admin/music_list.php');
hb_check_csrf();
hb_check_admin_token_if_present();
$mf_id = isset($_POST['mf_id']) ? hb_int_value($_POST['mf_id'], 0) : 0;
if ($mf_id < 1) alert('잘못된 요청입니다.', HB_URL.'/admin/music_list.php');
$music = hb_table('music');
$schedule = hb_table('schedule');
$schedule_item = hb_table('schedule_item');
$block = hb_table('block');
$block_item = hb_table('block_item');
$sequence_item = hb_table('sequence_item');

// 기존 음악의 소스가 동시 요청에서 file↔YouTube로 바뀌는 경우까지 포함해
// YouTube 등록/편집과 같은 lock 순서를 사용합니다.
if (!hb_acquire_youtube_registry_lock(5)) {
    alert('음악 정보를 다른 관리자 작업에서 처리 중입니다. 잠시 후 다시 시도해주세요.', HB_URL.'/admin/music_list.php');
}

if (!hb_db_begin()) alert('DB 작업을 시작하지 못했습니다. 잠시 후 다시 시도해주세요.', HB_URL.'/admin/music_list.php');
// 편성 저장과 음악 삭제가 동시에 실행되어도 삭제 직전/직후에 자식 행이 다시 생기지 않도록
// 음악 행을 먼저 잠근 뒤 연관 편성을 정리합니다.
$row = sql_fetch("SELECT * FROM `{$music}` WHERE mf_id='{$mf_id}' LIMIT 1 FOR UPDATE", false);
if (!$row) { hb_db_rollback(); alert('삭제할 음악을 찾을 수 없습니다.', HB_URL.'/admin/music_list.php'); }

$ok = sql_query("DELETE si FROM `{$schedule_item}` si INNER JOIN `{$schedule}` s ON s.sc_id=si.sc_id WHERE s.sc_scope='global' AND si.mf_id='{$mf_id}'", false) ? true : false;
if ($ok) {
    $res = sql_query("SELECT sc_id FROM `{$schedule}` WHERE sc_scope='global' AND mf_id='{$mf_id}'", false);
    if (!$res) $ok = false;
    while ($ok && ($sc = sql_fetch_array($res))) {
        $sc_id = (int)$sc['sc_id'];
        $next = sql_fetch("SELECT si.mf_id FROM `{$schedule_item}` si INNER JOIN `{$music}` m ON si.mf_id=m.mf_id WHERE si.sc_id='{$sc_id}' AND m.mf_use=1 AND si.mf_id<>'{$mf_id}' ORDER BY si.si_sort ASC, si.si_id ASC LIMIT 1", false);
        if ($next && (int)$next['mf_id'] > 0) {
            $next_id = (int)$next['mf_id'];
            $ok = sql_query("UPDATE `{$schedule}` SET mf_id='{$next_id}', sc_updated_at=NOW() WHERE sc_id='{$sc_id}' AND sc_scope='global'", false) ? true : false;
        } else {
            // 이 시간표에 재생 가능한 대체곡이 없으면 시간표 자체를 제거합니다.
            // 비활성 곡 등 다른 schedule_item 행이 남아 있을 수 있으므로 부모 삭제 전에 자식도 모두 정리합니다.
            $ok = sql_query("DELETE FROM `{$schedule_item}` WHERE sc_id='{$sc_id}'", false) ? true : false;
            if ($ok) $ok = sql_query("DELETE FROM `{$schedule}` WHERE sc_id='{$sc_id}' AND sc_scope='global'", false) ? true : false;
        }
    }
}
if ($ok) $ok = sql_query("DELETE bi FROM `{$block_item}` bi INNER JOIN `{$block}` b ON b.bl_id=bi.bl_id WHERE b.bl_scope='global' AND bi.mf_id='{$mf_id}'", false) ? true : false;
if ($ok) $ok = sql_query("DELETE FROM `{$sequence_item}` WHERE mf_id='{$mf_id}'", false) ? true : false;
if ($ok) $ok = sql_query("DELETE FROM `{$music}` WHERE mf_id='{$mf_id}'", false) ? true : false;
if ($ok) $ok = hb_broadcast_reset_if_music($mf_id);
if (!$ok) { hb_db_rollback(); alert('음악 삭제 중 DB 오류가 발생했습니다. 기존 데이터와 파일은 유지되었습니다.', HB_URL.'/admin/music_list.php'); }
if (!hb_db_commit()) { hb_db_rollback(); alert('음악 삭제를 완료하지 못했습니다.', HB_URL.'/admin/music_list.php'); }
$cleanup_ok = hb_unlink_music_file_row($row);
if (!$cleanup_ok) alert('DB에서는 삭제되었지만 음악 파일을 삭제하지 못했습니다. 시스템 점검의 미사용 음악 파일 항목을 확인해주세요.', HB_URL.'/admin/music_list.php');
alert('삭제되었습니다.', HB_URL.'/admin/music_list.php');
