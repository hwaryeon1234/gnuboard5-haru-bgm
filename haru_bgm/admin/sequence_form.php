<?php
include_once('./_common.php');
$g5['title'] = '순서표 등록';
$seq_id = isset($_GET['seq_id']) ? (int)$_GET['seq_id'] : 0;
$sequence = hb_table('sequence');
$row = array('seq_id'=>0,'seq_title'=>'','seq_type'=>'church','seq_memo'=>'','seq_use'=>1,'seq_sort'=>0);
$ids = array(); $titles=array(); $memos=array();
if ($seq_id) {
    $found = sql_fetch("SELECT * FROM `{$sequence}` WHERE seq_id='{$seq_id}'", false);
    if (!$found) alert('순서표를 찾을 수 없습니다.');
    $row = array_merge($row, $found);
    foreach (hb_sequence_items($seq_id) as $it) { $ids[]=(int)$it['mf_id']; $titles[]=$it['siq_title']; $memos[]=$it['siq_memo']; }
}
$hb_haru_form_row_backup = $row;
$hb_haru_head_row_was_set = array_key_exists('row', get_defined_vars());
$hb_haru_head_row_backup = $hb_haru_head_row_was_set ? $row : null;
include_once(G5_PATH.'/head.php');
if ($hb_haru_head_row_was_set) {
    $row = $hb_haru_head_row_backup;
} else {
    unset($row);
}
unset($hb_haru_head_row_was_set, $hb_haru_head_row_backup);
$row = $hb_haru_form_row_backup;
unset($hb_haru_form_row_backup);
?>
<link rel="stylesheet" href="<?php echo HB_URL; ?>/assets/haru_bgm.css?ver=20260625-radiov2">
<div class="hb-app"><?php echo hb_nav_admin(); ?><main class="hb-app-main"><div class="hb-wrap">
<section class="hb-page-head"><div><p class="hb-kicker">SEQUENCE</p><h1><?php echo $seq_id ? '순서표 수정' : '순서표 추가'; ?></h1><p>순서대로 눌러 진행하는 현장용 플레이리스트입니다. 파일 음악과 YouTube를 섞어 넣을 수 있습니다.</p></div><a class="hb-btn" href="<?php echo HB_URL; ?>/admin/sequence_list.php">목록으로</a></section>
<form class="hb-card hb-form" method="post" action="<?php echo HB_URL; ?>/admin/sequence_update.php">
<input type="hidden" name="seq_id" value="<?php echo (int)$row['seq_id']; ?>">
<div class="hb-two"><label>순서표 이름<input type="text" name="seq_title" value="<?php echo hb_e($row['seq_title']); ?>" required placeholder="예: 주일 오전예배"></label><label>구분<select name="seq_type"><option value="church" <?php echo $row['seq_type']==='church'?'selected':''; ?>>교회</option><option value="broadcast" <?php echo $row['seq_type']==='broadcast'?'selected':''; ?>>방송</option><option value="event" <?php echo $row['seq_type']==='event'?'selected':''; ?>>행사</option><option value="store" <?php echo $row['seq_type']==='store'?'selected':''; ?>>매장/학교</option><option value="general" <?php echo $row['seq_type']==='general'?'selected':''; ?>>기타</option></select></label></div>
<label>메모<textarea name="seq_memo" rows="3" placeholder="예: 예배 전 묵상 → 입례송 → 찬양 → 헌금송 → 폐회송"><?php echo hb_e($row['seq_memo']); ?></textarea></label>
<div class="hb-source-box"><strong>순서 항목</strong><p class="hb-muted-mini">위에서부터 순서대로 진행됩니다. 순서명은 운영판에 크게 표시됩니다.</p><div class="hb-track-list"><?php echo hb_get_sequence_music_select_rows($ids, $titles, $memos, 30); ?></div></div>
<div class="hb-source-box"><strong>YouTube 링크 추가 등록</strong><p class="hb-muted-mini">여러 줄로 붙여넣으면 음악 보관함에 등록되고 순서표 뒤에 자동 추가됩니다.</p><label>YouTube 링크/영상 ID<textarea name="quick_youtube_urls" rows="5" placeholder="https://youtu.be/...\nhttps://www.youtube.com/watch?v=..."></textarea></label><label>기본 제목<input type="text" name="quick_youtube_title" placeholder="비우면 순서표 이름으로 등록"></label></div>
<div class="hb-two"><label>정렬값<input type="number" name="seq_sort" value="<?php echo (int)$row['seq_sort']; ?>"></label><label class="hb-inline"><input type="checkbox" name="seq_use" value="1" <?php echo $row['seq_use']?'checked':''; ?>> 사용하기</label></div>
<div class="hb-actions"><button class="hb-btn hb-btn-primary" type="submit">저장하기</button></div>
</form>
</div></main></div>
<?php include_once(G5_PATH.'/tail.php'); ?>
