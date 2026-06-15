<?php
include_once('./_common.php');
$g5['title'] = '공통 시간대 등록';
$bl_id = isset($_GET['bl_id']) ? (int)$_GET['bl_id'] : 0;
$row = array('bl_id'=>0, 'bl_title'=>'', 'bl_start_time'=>'10:00:00', 'bl_end_time'=>'11:00:00', 'bl_days'=>'0,1,2,3,4,5,6', 'bl_start_date'=>'', 'bl_end_date'=>'', 'bl_play_mode'=>'sequence', 'bl_repeat'=>1, 'bl_use'=>1, 'bl_sort'=>0);
$selected_ids = array();
if ($bl_id) {
    $block = hb_table('block');
    $found = sql_fetch("SELECT * FROM `{$block}` WHERE bl_id='{$bl_id}' AND bl_scope='global'");
    if (!$found) alert('시간대 묶음을 찾을 수 없습니다.');
    $row = array_merge($row, $found);
    foreach (hb_block_items($bl_id) as $item) $selected_ids[] = (int)$item['mf_id'];
}
$days_selected = array_filter(explode(',', $row['bl_days']), 'strlen');
include_once(G5_PATH.'/head.php');
?>
<link rel="stylesheet" href="<?php echo HB_URL; ?>/assets/haru_bgm.css?ver=20260616e">
<div class="hb-wrap">
    <section class="hb-page-head"><div><p class="hb-kicker">ADMIN</p><h1><?php echo $bl_id ? '공통 시간대 수정' : '공통 시간대 추가'; ?></h1><p>정해진 시간 안에서 여러 곡을 이어서 재생합니다.</p></div><a class="hb-btn" href="<?php echo HB_URL; ?>/admin/block_global.php">목록으로</a></section>
    <form class="hb-card hb-form" method="post" action="<?php echo HB_URL; ?>/admin/block_update.php">
        <input type="hidden" name="bl_id" value="<?php echo (int)$row['bl_id']; ?>">
        <label>시간대 제목<input type="text" name="bl_title" value="<?php echo hb_e($row['bl_title']); ?>" placeholder="예: 예배 전 묵상 BGM / 오전 매장 음악" required></label>
        <div class="hb-two"><label>시작 시간<input type="time" name="bl_start_time" value="<?php echo hb_time_hm($row['bl_start_time']); ?>" required></label><label>끝 시간<input type="time" name="bl_end_time" value="<?php echo hb_time_hm($row['bl_end_time']); ?>" required></label></div>
        <div class="hb-two"><label>재생 방식<select name="bl_play_mode"><option value="sequence" <?php echo $row['bl_play_mode']==='sequence'?'selected':''; ?>>순서대로</option><option value="random" <?php echo $row['bl_play_mode']==='random'?'selected':''; ?>>랜덤</option></select></label><label>정렬값<input type="number" name="bl_sort" value="<?php echo (int)$row['bl_sort']; ?>"></label></div>
        <label class="hb-inline"><input type="checkbox" name="bl_repeat" value="1" <?php echo $row['bl_repeat'] ? 'checked' : ''; ?>> 시간대가 끝날 때까지 반복 재생</label>
        <div class="hb-fieldset"><strong>음악 목록</strong><p class="hb-muted">위에서 아래 순서대로 재생됩니다. 최대 100곡까지 넣을 수 있고, 빈 줄은 자동으로 무시됩니다.</p><div class="hb-track-list"><?php echo hb_get_block_music_select_rows($selected_ids, 100); ?></div></div>
        <div class="hb-source-box">
            <strong>YouTube 링크 여러 개 바로 추가</strong>
            <p class="hb-muted-mini">음악 보관함에 미리 등록하지 않아도, 여기에 YouTube 링크를 줄바꿈으로 넣으면 저장할 때 자동 등록되어 이 시간대 목록 뒤에 붙습니다.</p>
            <label>YouTube 링크 붙여넣기 / 드롭
                <textarea id="hbBlockYoutubeBulk" name="quick_youtube_urls" rows="6" placeholder="https://youtu.be/...
https://www.youtube.com/watch?v=..."></textarea>
            </label>
            <label>YouTube 제목 접두어
                <input type="text" name="quick_youtube_title" value="" placeholder="비우면 시간대 제목으로 자동 등록">
            </label>
            <div class="hb-dropzone hb-dropzone-youtube" id="hbBlockYoutubeDropzone"><strong>YouTube 링크를 여기로 끌어오거나 붙여넣기</strong><span>여러 링크를 한 번에 넣을 수 있습니다.</span><em id="hbBlockYoutubeStatus">대기 중</em></div>
        </div>
        <div class="hb-fieldset"><strong>요일</strong><div class="hb-checks"><?php foreach (hb_days_all() as $d => $label) { ?><label><input type="checkbox" name="bl_days[]" value="<?php echo $d; ?>" <?php echo in_array((string)$d, $days_selected, true) ? 'checked' : ''; ?>> <?php echo $label; ?></label><?php } ?></div></div>
        <div class="hb-two"><label>시작일 선택<input type="date" name="bl_start_date" value="<?php echo hb_e($row['bl_start_date']); ?>"></label><label>종료일 선택<input type="date" name="bl_end_date" value="<?php echo hb_e($row['bl_end_date']); ?>"></label></div>
        <label class="hb-inline"><input type="checkbox" name="bl_use" value="1" <?php echo $row['bl_use'] ? 'checked' : ''; ?>> 사용하기</label>
        <div class="hb-actions"><button class="hb-btn hb-btn-primary" type="submit">저장하기</button></div>
    </form>
</div>
<script>
(function(){
    const area = document.getElementById('hbBlockYoutubeBulk');
    const drop = document.getElementById('hbBlockYoutubeDropzone');
    const status = document.getElementById('hbBlockYoutubeStatus');
    function appendText(text){ if(!area || !text) return; const old = area.value.trim(); area.value = old ? old + '\n' + text.trim() : text.trim(); if(status) status.textContent = '링크가 추가되었습니다.'; }
    if(drop && area){
        drop.addEventListener('click', () => area.focus());
        ['dragenter','dragover'].forEach(ev => drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.add('is-drag'); }));
        ['dragleave','drop'].forEach(ev => drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.remove('is-drag'); }));
        drop.addEventListener('drop', e => { const txt = (e.dataTransfer && (e.dataTransfer.getData('text/uri-list') || e.dataTransfer.getData('text/plain'))) || ''; appendText(txt); });
    }

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
