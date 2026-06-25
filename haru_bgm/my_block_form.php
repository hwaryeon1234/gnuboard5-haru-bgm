<?php
include_once('./_common.php');
hb_require_member_bgm_enabled();
$g5['title'] = '내 시간대 묶음 등록';
$bl_id = isset($_GET['bl_id']) ? (int)$_GET['bl_id'] : 0;
$row = array('bl_id'=>0, 'bl_title'=>'', 'bl_start_time'=>'10:00:00', 'bl_end_time'=>'11:00:00', 'bl_days'=>'0,1,2,3,4,5,6', 'bl_start_date'=>'', 'bl_end_date'=>'', 'bl_play_mode'=>'sequence', 'bl_repeat'=>1, 'bl_use'=>1);
$selected_ids = array();
if ($bl_id) {
    $block = hb_table('block');
    $mb_id = hb_escape($member['mb_id']);
    $found = sql_fetch("SELECT * FROM `{$block}` WHERE bl_id='{$bl_id}' AND bl_scope='user' AND mb_id='{$mb_id}'");
    if (!$found) alert('시간대 묶음을 찾을 수 없습니다.');
    $row = array_merge($row, $found);
    foreach (hb_block_items($bl_id) as $item) $selected_ids[] = (int)$item['mf_id'];
}
$days_selected = array_filter(explode(',', $row['bl_days']), 'strlen');
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
<div class="hb-wrap hb-radio">
    <section class="hb-page-head"><div><p class="hb-kicker">MY PLAYLIST</p><h1><?php echo $bl_id ? '내 시간대 수정' : '내 시간대 추가'; ?></h1><p>이 설정은 내 계정에서만 재생됩니다.</p></div><a class="hb-btn" href="<?php echo HB_URL; ?>/my_blocks.php">목록으로</a></section>
    <form class="hb-card hb-form" method="post" action="<?php echo HB_URL; ?>/my_block_update.php">
        <input type="hidden" name="bl_id" value="<?php echo (int)$row['bl_id']; ?>">
        <label>시간대 제목<input type="text" name="bl_title" value="<?php echo hb_e($row['bl_title']); ?>" placeholder="예: 집중 작업 BGM" required></label>
        <div class="hb-two"><label>시작 시간<input type="time" name="bl_start_time" value="<?php echo hb_time_hm($row['bl_start_time']); ?>" required></label><label>끝 시간<input type="time" name="bl_end_time" value="<?php echo hb_time_hm($row['bl_end_time']); ?>" required></label></div>
        <label>재생 방식<select name="bl_play_mode"><option value="sequence" <?php echo $row['bl_play_mode']==='sequence'?'selected':''; ?>>순서대로</option><option value="random" <?php echo $row['bl_play_mode']==='random'?'selected':''; ?>>랜덤</option></select></label>
        <label class="hb-inline"><input type="checkbox" name="bl_repeat" value="1" <?php echo $row['bl_repeat'] ? 'checked' : ''; ?>> 시간대가 끝날 때까지 반복 재생</label>
        <div class="hb-fieldset"><strong>음악 목록</strong><p class="hb-muted">위에서 아래 순서대로 재생됩니다. 최대 100곡까지 넣을 수 있고, 빈 줄은 자동으로 무시됩니다.</p><div class="hb-track-list"><?php echo hb_get_block_music_select_rows($selected_ids, 100); ?></div></div>
        <div class="hb-fieldset"><strong>요일</strong><div class="hb-checks"><?php foreach (hb_days_all() as $d => $label) { ?><label><input type="checkbox" name="bl_days[]" value="<?php echo $d; ?>" <?php echo in_array((string)$d, $days_selected, true) ? 'checked' : ''; ?>> <?php echo $label; ?></label><?php } ?></div></div>
        <div class="hb-two"><label>시작일 선택<input type="date" name="bl_start_date" value="<?php echo hb_e($row['bl_start_date']); ?>"></label><label>종료일 선택<input type="date" name="bl_end_date" value="<?php echo hb_e($row['bl_end_date']); ?>"></label></div>
        <label class="hb-inline"><input type="checkbox" name="bl_use" value="1" <?php echo $row['bl_use'] ? 'checked' : ''; ?>> 사용하기</label>
        <div class="hb-actions"><button class="hb-btn hb-btn-primary" type="submit">저장하기</button></div>
    </form>
</div>
<script>
(function(){

    function renumberTracks(){
        document.querySelectorAll('.hb-track-list').forEach(function(list){
            list.querySelectorAll('.hb-track-row').forEach(function(row, idx){
                const no = row.querySelector('.hb-track-no');
                if(no) no.textContent = idx + 1;
            });
        });
    }
    document.addEventListener('click', function(e){
        const btn = e.target.closest('.hb-track-up,.hb-track-down,.hb-track-clear');
        if(!btn) return;
        const row = btn.closest('.hb-track-row');
        if(!row) return;
        e.preventDefault();
        if(btn.classList.contains('hb-track-clear')){
            const sel = row.querySelector('select');
            if(sel) sel.value = '';
            renumberTracks();
            return;
        }
        if(btn.classList.contains('hb-track-up') && row.previousElementSibling){
            row.parentNode.insertBefore(row, row.previousElementSibling);
        }
        if(btn.classList.contains('hb-track-down') && row.nextElementSibling){
            row.parentNode.insertBefore(row.nextElementSibling, row);
        }
        renumberTracks();
    });
    document.addEventListener('change', function(e){
        if(e.target && e.target.closest('.hb-track-list')) renumberTracks();
    });
    renumberTracks();

})();
</script>
<?php include_once(G5_PATH.'/tail.php'); ?>
