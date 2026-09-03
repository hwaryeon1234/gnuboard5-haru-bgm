<?php
include_once('./_common.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') alert('잘못된 요청입니다.', HB_URL.'/admin/index.php');
hb_check_csrf();
hb_check_admin_token_if_present();

$mf_id = isset($_POST['mf_id']) ? hb_int_value($_POST['mf_id'], 0) : 0;
$source_raw = isset($_POST['mf_source']) ? hb_scalar_string($_POST['mf_source'], '') : '';
$source = $source_raw === 'youtube' ? 'youtube' : 'file';
$title = isset($_POST['mf_title']) ? hb_text_limit($_POST['mf_title'], 255) : '';
$volume = isset($_POST['mf_volume']) ? max(0, min(100, hb_int_value($_POST['mf_volume'], 80))) : 80;
$type_raw = isset($_POST['mf_type']) ? hb_scalar_string($_POST['mf_type'], '') : '';
$type = $type_raw === 'bell' ? 'bell' : 'music';
$memo_raw = isset($_POST['mf_memo']) && is_scalar($_POST['mf_memo']) ? trim((string)$_POST['mf_memo']) : '';
if (!hb_text_fits($memo_raw)) alert('메모는 '.HB_MAX_MEMO_CHARS.'자 / '.hb_human_bytes(HB_MAX_MEMO_BYTES).' 이내로 입력해주세요.');
$memo = hb_text_limit($memo_raw, HB_MAX_MEMO_CHARS);
$use = isset($_POST['mf_use']) ? 1 : 0;
$music = hb_table('music');
$editing_row = null;
if ($mf_id) {
    $editing_row = sql_fetch("SELECT * FROM `{$music}` WHERE mf_id='{$mf_id}' LIMIT 1", false);
    if (!$editing_row) alert('수정할 음악을 찾을 수 없습니다.', HB_URL.'/admin/music_list.php');
}

// YouTube 생성뿐 아니라 YouTube→파일/다른 영상으로 바꾸는 편집도 같은 registry lock 아래에서 처리합니다.
// 그렇지 않으면 다른 요청이 커밋 전의 옛 YouTube ID를 읽고 같은 mf_id를 재사용할 수 있습니다.
$hb_needs_youtube_registry = ($source === 'youtube') || ($mf_id > 0);
if ($hb_needs_youtube_registry && !hb_acquire_youtube_registry_lock(5)) {
    alert('YouTube 음악 정보를 다른 관리자 작업에서 처리 중입니다. 잠시 후 다시 시도해주세요.', HB_URL.'/admin/music_list.php');
}
unset($hb_needs_youtube_registry);

if ($source === 'youtube') {
    $url = isset($_POST['mf_youtube_url']) && is_scalar($_POST['mf_youtube_url']) ? trim((string)$_POST['mf_youtube_url']) : '';
    if ($mf_id) {
        $yt_id = hb_extract_youtube_id($url);
        if (!$yt_id) alert('올바른 YouTube 링크를 입력해주세요.');
        if (!$title) $title = 'YouTube BGM '.$yt_id;
        $title_sql = hb_escape($title);
        $type_sql = hb_escape($type);
        $memo_sql = hb_escape($memo);
        $url = hb_youtube_watch_url($yt_id);
        $url_sql = hb_escape($url);
        $yt_sql = hb_escape($yt_id);
        if (!hb_acquire_youtube_lock($yt_id, 5)) alert('같은 YouTube 영상을 다른 관리자 작업에서 처리 중입니다. 잠시 후 다시 시도해주세요.', HB_URL.'/admin/music_form.php?mf_id='.$mf_id);
        if (!hb_db_begin()) alert('DB 작업을 시작하지 못했습니다. 잠시 후 다시 시도해주세요.', HB_URL.'/admin/music_list.php');
        // 삭제/다른 수정과 동시에 실행되어도 이미 사라진 행을 성공으로 오인하지 않도록
        // 수정 대상 음악 행을 트랜잭션 안에서 잠그고 최신 값을 다시 읽습니다.
        $old = sql_fetch("SELECT * FROM `{$music}` WHERE mf_id='{$mf_id}' LIMIT 1 FOR UPDATE", false);
        if (!$old) { hb_db_rollback(); alert('수정할 음악을 찾을 수 없습니다.', HB_URL.'/admin/music_list.php'); }
        $duplicate = sql_fetch("SELECT mf_id FROM `{$music}` WHERE mf_source='youtube' AND mf_youtube_id='{$yt_sql}' AND mf_id<>'{$mf_id}' LIMIT 1 FOR UPDATE", false);
        if ($duplicate && !empty($duplicate['mf_id'])) { hb_db_rollback(); alert('이미 등록된 YouTube 영상입니다.', HB_URL.'/admin/music_form.php?mf_id='.$mf_id); }
        $ok = sql_query("UPDATE `{$music}` SET mf_title='{$title_sql}', mf_source='youtube', mf_file='', mf_org_name='', mf_mime='', mf_size='0', mf_youtube_url='{$url_sql}', mf_youtube_id='{$yt_sql}', mf_volume='{$volume}', mf_type='{$type_sql}', mf_memo='{$memo_sql}', mf_use='{$use}', mf_updated_at=NOW() WHERE mf_id='{$mf_id}'", false) ? true : false;
        if ($ok) $ok = hb_broadcast_reset_if_music($mf_id);
        if ($ok && !$use) $ok = hb_repair_schedule_primary_music($mf_id);
        if (!$ok) { hb_db_rollback(); alert('음악 정보를 저장하지 못했습니다. 기존 파일과 정보는 유지되었습니다.', HB_URL.'/admin/music_list.php'); }
        if (!hb_db_commit()) { hb_db_rollback(); alert('음악 정보 저장을 완료하지 못했습니다.', HB_URL.'/admin/music_list.php'); }
        $cleanup_ok = hb_unlink_music_file_row($old);
        alert($cleanup_ok ? '저장되었습니다.' : '저장은 완료되었지만 이전 음악 파일을 삭제하지 못했습니다. 시스템 점검의 미사용 음악 파일 항목을 확인해주세요.', HB_URL.'/admin/music_list.php');
    }

    $bulk = $url."\n".(isset($_POST['bulk_youtube_urls']) ? hb_scalar_string($_POST['bulk_youtube_urls'], '') : '');
    if (hb_youtube_text_too_large($bulk)) alert('YouTube 입력 데이터가 너무 큽니다. 링크 수를 줄여주세요.');
    $links = hb_parse_youtube_bulk($bulk, HB_MAX_YOUTUBE_BULK_ITEMS + 1);
    if (count($links) > HB_MAX_YOUTUBE_BULK_ITEMS) alert('YouTube 링크는 한 번에 최대 '.HB_MAX_YOUTUBE_BULK_ITEMS.'개까지 등록할 수 있습니다.');
    if (!$links) alert('YouTube 링크를 1개 이상 입력해주세요. 일반 영상 URL, youtu.be 링크, 영상 ID를 사용할 수 있습니다.');
    if (!hb_db_begin()) alert('DB 작업을 시작하지 못했습니다. 잠시 후 다시 시도해주세요.', HB_URL.'/admin/music_list.php');
    $ids = hb_create_youtube_musics_from_text($bulk, $title, $volume, $type, $memo !== '' ? $memo : 'YouTube 대량 등록', $links);
    if (count($ids) !== count($links)) {
        hb_db_rollback();
        alert('YouTube 음악 등록 중 DB 오류가 발생했습니다. 이번 등록은 모두 취소했습니다.', HB_URL.'/admin/music_list.php');
    }
    if (!hb_db_commit()) { hb_db_rollback(); alert('YouTube 음악 등록을 완료하지 못했습니다.', HB_URL.'/admin/music_list.php'); }
    alert(count($ids).'개 YouTube 음악을 등록했습니다.', HB_URL.'/admin/music_list.php');
}

$files = isset($_FILES['music_files']) ? hb_files_rearray($_FILES['music_files']) : array();
if (count($files) > HB_MAX_UPLOAD_FILES) alert('음악 파일은 한 번에 최대 '.HB_MAX_UPLOAD_FILES.'개까지 업로드할 수 있습니다.');
if ($mf_id) {
    $file_sql = '';
    $new_upload = null;
    $old_file_row = null;
    if ((!isset($files[0]) || (int)($files[0]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) && $editing_row && isset($editing_row['mf_source']) && $editing_row['mf_source'] === 'youtube') {
        alert('YouTube 음악을 파일 음악으로 바꾸려면 새 음악 파일을 업로드해주세요.');
    }
    if (isset($files[0]) && (int)($files[0]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $error = '';
        $up = hb_upload_music_file($files[0], $error);
        if (!$up) alert($error ? $error : '파일 업로드에 실패했습니다.');
        $new_upload = $up;
        $file_sql = ", mf_file='".hb_escape($up['save'])."', mf_org_name='".hb_escape($up['org'])."', mf_mime='".hb_escape($up['mime'])."', mf_size='".(int)$up['size']."'";
    }
    if (!hb_db_begin()) {
        $cleanup_ok = $new_upload ? hb_unlink_music_file_row(array('mf_source'=>'file','mf_file'=>$new_upload['save'])) : true;
        $msg = 'DB 작업을 시작하지 못했습니다. 잠시 후 다시 시도해주세요.';
        if (!$cleanup_ok) $msg .= "\n업로드된 임시 음악 파일도 정리하지 못했습니다. 시스템 점검의 미사용 음악 파일 항목을 확인해주세요.";
        alert($msg, HB_URL.'/admin/music_list.php');
    }
    $old_file_row = sql_fetch("SELECT * FROM `{$music}` WHERE mf_id='{$mf_id}' LIMIT 1 FOR UPDATE", false);
    if (!$old_file_row) {
        hb_db_rollback();
        $cleanup_ok = $new_upload ? hb_unlink_music_file_row(array('mf_source'=>'file','mf_file'=>$new_upload['save'])) : true;
        $msg = '수정할 음악을 찾을 수 없습니다.';
        if (!$cleanup_ok) $msg .= "\n새로 업로드된 파일도 정리하지 못했습니다. 시스템 점검의 미사용 음악 파일 항목을 확인해주세요.";
        alert($msg, HB_URL.'/admin/music_list.php');
    }
    // 최초 화면 조회 뒤 다른 관리자 요청이 파일→YouTube로 바꿨을 수 있으므로
    // 잠근 최신 행을 기준으로도 새 파일 필요 여부를 다시 확인합니다.
    if (!$new_upload && isset($old_file_row['mf_source']) && $old_file_row['mf_source'] === 'youtube') {
        hb_db_rollback();
        alert('YouTube 음악을 파일 음악으로 바꾸려면 새 음악 파일을 업로드해주세요.', HB_URL.'/admin/music_form.php?mf_id='.$mf_id);
    }
    if (!$title) $title = !empty($old_file_row['mf_title']) ? hb_text_limit($old_file_row['mf_title'], 255) : '하루브금 음악';
    $title_sql = hb_escape($title);
    $type_sql = hb_escape($type);
    $memo_sql = hb_escape($memo);
    $ok = sql_query("UPDATE `{$music}` SET mf_title='{$title_sql}', mf_source='file', mf_youtube_url='', mf_youtube_id=NULL, mf_volume='{$volume}', mf_type='{$type_sql}', mf_memo='{$memo_sql}', mf_use='{$use}', mf_updated_at=NOW() {$file_sql} WHERE mf_id='{$mf_id}'", false) ? true : false;
    if ($ok) $ok = hb_broadcast_reset_if_music($mf_id);
    if ($ok && !$use) $ok = hb_repair_schedule_primary_music($mf_id);
    if (!$ok) {
        hb_db_rollback();
        $cleanup_ok = $new_upload ? hb_unlink_music_file_row(array('mf_source'=>'file','mf_file'=>$new_upload['save'])) : true;
        $msg = '음악 정보를 저장하지 못했습니다. 기존 파일과 정보는 유지되었습니다.';
        if (!$cleanup_ok) $msg .= "\n새로 업로드된 파일을 정리하지 못했습니다. 시스템 점검의 미사용 음악 파일 항목을 확인해주세요.";
        alert($msg, HB_URL.'/admin/music_list.php');
    }
    if (!hb_db_commit()) {
        hb_db_rollback();
        $cleanup_ok = $new_upload ? hb_unlink_music_file_row(array('mf_source'=>'file','mf_file'=>$new_upload['save'])) : true;
        $msg = '음악 정보 저장을 완료하지 못했습니다.';
        if (!$cleanup_ok) $msg .= "\n새로 업로드된 파일을 정리하지 못했습니다. 시스템 점검의 미사용 음악 파일 항목을 확인해주세요.";
        alert($msg, HB_URL.'/admin/music_list.php');
    }
    $cleanup_ok = true;
    if ($new_upload) $cleanup_ok = hb_unlink_music_file_row($old_file_row);
    alert($cleanup_ok ? '저장되었습니다.' : '저장은 완료되었지만 이전 음악 파일을 삭제하지 못했습니다. 시스템 점검의 미사용 음악 파일 항목을 확인해주세요.', HB_URL.'/admin/music_list.php');
}

if (!$files) alert('음악 파일을 업로드해주세요.');
$ok = 0;
$fail = array();
foreach ($files as $file) {
    $error = '';
    $up = hb_upload_music_file($file, $error);
    if (!$up) { $fail[] = ($file['name'] ? $file['name'].': ' : '').$error; continue; }
    $use_title = $title && count($files) === 1 ? $title : hb_guess_title_from_filename($up['org']);
    $title_sql = hb_escape($use_title);
    $type_sql = hb_escape($type);
    $memo_sql = hb_escape($memo);
    // 파일 1개당 INSERT 1건을 짧은 트랜잭션으로 묶어, DB 커넥션이 도중에 끊기는 등의
    // 이유로 INSERT가 절반만 반영되는 경우(원자성 깨짐)를 막습니다. 여러 파일 중 일부가
    // 실패해도 나머지는 계속 처리하는 기존 "부분 성공" 동작은 그대로 유지합니다.
    if (!hb_db_begin()) {
        $cleanup_ok = hb_unlink_music_file_row(array('mf_source'=>'file','mf_file'=>$up['save']));
        $fail[] = $up['org'].': DB 작업을 시작하지 못했습니다.'.($cleanup_ok ? '' : ' · 업로드 파일 정리 실패(시스템 점검 확인 필요)');
        continue;
    }
    $inserted = sql_query("INSERT INTO `{$music}` SET mf_title='{$title_sql}', mf_source='file', mf_file='".hb_escape($up['save'])."', mf_org_name='".hb_escape($up['org'])."', mf_mime='".hb_escape($up['mime'])."', mf_size='".(int)$up['size']."', mf_youtube_url='', mf_youtube_id=NULL, mf_volume='{$volume}', mf_type='{$type_sql}', mf_memo='{$memo_sql}', mf_use='{$use}', mf_created_at=NOW()", false);
    if (!$inserted) {
        hb_db_rollback();
        $cleanup_ok = hb_unlink_music_file_row(array('mf_source'=>'file','mf_file'=>$up['save']));
        $fail[] = $up['org'].': DB 저장 실패'.($cleanup_ok ? '' : ' · 업로드 파일 정리 실패(시스템 점검 확인 필요)');
        continue;
    }
    if (!hb_db_commit()) {
        hb_db_rollback();
        $cleanup_ok = hb_unlink_music_file_row(array('mf_source'=>'file','mf_file'=>$up['save']));
        $fail[] = $up['org'].': DB 저장을 완료하지 못했습니다.'.($cleanup_ok ? '' : ' · 업로드 파일 정리 실패(시스템 점검 확인 필요)');
        continue;
    }
    $ok++;
}
if ($ok < 1) alert($fail ? implode("\n", $fail) : '업로드된 파일이 없습니다.');
$msg = $ok.'개 음악을 등록했습니다.';
if ($fail) $msg .= "\n실패: ".count($fail).'개';
alert($msg, HB_URL.'/admin/music_list.php');
