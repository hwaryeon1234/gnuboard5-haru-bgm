<?php
include_once('./_common.php');

$mf_id = isset($_POST['mf_id']) ? (int)$_POST['mf_id'] : 0;
$source = (isset($_POST['mf_source']) && $_POST['mf_source'] === 'youtube') ? 'youtube' : 'file';
$title = isset($_POST['mf_title']) ? trim($_POST['mf_title']) : '';
$volume = isset($_POST['mf_volume']) ? max(0, min(100, (int)$_POST['mf_volume'])) : 80;
$type = isset($_POST['mf_type']) && $_POST['mf_type'] === 'bell' ? 'bell' : 'music';
$memo = isset($_POST['mf_memo']) ? trim($_POST['mf_memo']) : '';
$use = isset($_POST['mf_use']) ? 1 : 0;
$music = hb_table('music');
$mb_id = hb_escape($member['mb_id']);

function hb_delete_old_file_if_needed($row) {
    if ($row && (!isset($row['mf_source']) || $row['mf_source'] === 'file') && !empty($row['mf_file'])) {
        $oldfile = hb_safe_file($row['mf_file']);
        if ($oldfile && file_exists(HB_DATA_PATH.'/'.$oldfile)) @unlink(HB_DATA_PATH.'/'.$oldfile);
    }
}

if ($source === 'youtube') {
    $url = isset($_POST['mf_youtube_url']) ? trim($_POST['mf_youtube_url']) : '';
    if ($mf_id) {
        $yt_id = hb_extract_youtube_id($url);
        if (!$yt_id) alert('올바른 YouTube 링크를 입력해주세요.');
        if (!$title) $title = 'YouTube BGM '.$yt_id;
        $title_sql = hb_escape($title);
        $type_sql = hb_escape($type);
        $memo_sql = hb_escape($memo);
        $url_sql = hb_escape($url);
        $yt_sql = hb_escape($yt_id);
        $old = sql_fetch("SELECT * FROM `{$music}` WHERE mf_id='{$mf_id}'");
        hb_delete_old_file_if_needed($old);
        sql_query("UPDATE `{$music}` SET mf_title='{$title_sql}', mf_source='youtube', mf_file='', mf_org_name='', mf_mime='', mf_size='0', mf_youtube_url='{$url_sql}', mf_youtube_id='{$yt_sql}', mf_volume='{$volume}', mf_type='{$type_sql}', mf_memo='{$memo_sql}', mf_use='{$use}', mf_updated_at=NOW() WHERE mf_id='{$mf_id}'");
        alert('저장되었습니다.', HB_URL.'/admin/music_list.php');
    }

    $bulk = $url."\n".(isset($_POST['bulk_youtube_urls']) ? $_POST['bulk_youtube_urls'] : '');
    $ids = hb_create_youtube_musics_from_text($bulk, $title, $volume, $type, $memo !== '' ? $memo : 'YouTube 대량 등록', isset($member['mb_id']) ? $member['mb_id'] : '');
    if (!$ids) alert('YouTube 링크를 1개 이상 입력해주세요. 일반 영상 URL, youtu.be 링크, 영상 ID를 사용할 수 있습니다.');
    alert(count($ids).'개 YouTube 음악을 등록했습니다.', HB_URL.'/admin/music_list.php');
}

$files = isset($_FILES['music_files']) ? hb_files_rearray($_FILES['music_files']) : array();
if ($mf_id) {
    $file_sql = '';
    if (isset($files[0]) && isset($files[0]['tmp_name']) && is_uploaded_file($files[0]['tmp_name'])) {
        $error = '';
        $up = hb_upload_music_file($files[0], $error);
        if (!$up) alert($error ? $error : '파일 업로드에 실패했습니다.');
        $old = sql_fetch("SELECT * FROM `{$music}` WHERE mf_id='{$mf_id}'");
        hb_delete_old_file_if_needed($old);
        $file_sql = ", mf_file='".hb_escape($up['save'])."', mf_org_name='".hb_escape($up['org'])."', mf_mime='".hb_escape($up['mime'])."', mf_size='".(int)$up['size']."'";
    }
    if (!$title) {
        $old = sql_fetch("SELECT mf_title FROM `{$music}` WHERE mf_id='{$mf_id}'");
        $title = $old && $old['mf_title'] ? $old['mf_title'] : '하루브금 음악';
    }
    $title_sql = hb_escape($title);
    $type_sql = hb_escape($type);
    $memo_sql = hb_escape($memo);
    sql_query("UPDATE `{$music}` SET mf_title='{$title_sql}', mf_source='file', mf_youtube_url='', mf_youtube_id='', mf_volume='{$volume}', mf_type='{$type_sql}', mf_memo='{$memo_sql}', mf_use='{$use}', mf_updated_at=NOW() {$file_sql} WHERE mf_id='{$mf_id}'");
    alert('저장되었습니다.', HB_URL.'/admin/music_list.php');
}

if (!$files) alert('음악 파일을 업로드해주세요.');
$ok = 0;
$fail = array();
foreach ($files as $file) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) continue;
    $error = '';
    $up = hb_upload_music_file($file, $error);
    if (!$up) { $fail[] = ($file['name'] ? $file['name'].': ' : '').$error; continue; }
    $use_title = $title && count($files) === 1 ? $title : hb_guess_title_from_filename($up['org']);
    $title_sql = hb_escape($use_title);
    $type_sql = hb_escape($type);
    $memo_sql = hb_escape($memo);
    sql_query("INSERT INTO `{$music}` SET mf_title='{$title_sql}', mf_source='file', mf_file='".hb_escape($up['save'])."', mf_org_name='".hb_escape($up['org'])."', mf_mime='".hb_escape($up['mime'])."', mf_size='".(int)$up['size']."', mf_youtube_url='', mf_youtube_id='', mf_volume='{$volume}', mf_type='{$type_sql}', mf_memo='{$memo_sql}', mf_use='{$use}', mb_id='{$mb_id}', mf_created_at=NOW()");
    $ok++;
}
if ($ok < 1) alert($fail ? implode("\n", $fail) : '업로드된 파일이 없습니다.');
$msg = $ok.'개 음악을 등록했습니다.';
if ($fail) $msg .= '\n실패: '.count($fail).'개';
alert($msg, HB_URL.'/admin/music_list.php');
?>
