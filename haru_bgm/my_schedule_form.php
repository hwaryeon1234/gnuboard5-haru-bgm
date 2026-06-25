<?php
include_once('./_common.php');
hb_require_member_bgm_enabled();
$g5['title'] = '개인 시간표 등록';

$sc_id = isset($_GET['sc_id']) ? (int)$_GET['sc_id'] : 0;
$row = array('sc_id'=>0, 'mf_id'=>0, 'sc_title'=>'', 'sc_time'=>'09:00:00', 'sc_play_mode'=>'once', 'sc_end_time'=>'10:00:00', 'sc_repeat'=>0, 'sc_days'=>'0,1,2,3,4,5,6', 'sc_start_date'=>'', 'sc_end_date'=>'', 'sc_use'=>1);
if ($sc_id) {
    $schedule = hb_table('schedule');
    $mb_id = hb_escape($member['mb_id']);
    $found = sql_fetch("SELECT * FROM `{$schedule}` WHERE sc_id='{$sc_id}' AND sc_scope='user' AND mb_id='{$mb_id}'");
    if (!$found) alert('시간표를 찾을 수 없습니다.');
    $row = array_merge($row, $found);
}
$days_selected = array_filter(explode(',', $row['sc_days']), 'strlen');
$schedule_item_ids = $sc_id ? hb_schedule_item_ids($sc_id) : array();
if (!$schedule_item_ids && (int)$row['mf_id'] > 0) $schedule_item_ids = array((int)$row['mf_id']);
$extra_ids = $schedule_item_ids;
if ($extra_ids) array_shift($extra_ids);
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
<link rel="stylesheet" href="<?php echo HB_URL; ?>/assets/haru_bgm.css?ver=20260625-radiov5">
<div class="hb-wrap hb-radio">
    <section class="hb-page-head">
        <div>
            <p class="hb-kicker">MY ROUTINE</p>
            <h1><?php echo $sc_id ? '개인 시간 수정' : '개인 시간 추가'; ?></h1>
            <p>파일 음악과 YouTube를 같이 넣어 나만의 시간 세트를 만들 수 있습니다.</p>
        </div>
        <div class="hb-actions">
            <a class="hb-btn" href="<?php echo HB_URL; ?>/my_music_list.php">내 음악 보관함</a>
            <a class="hb-btn" href="<?php echo HB_URL; ?>/my_schedule.php">목록으로</a>
        </div>
    </section>

    <form class="hb-card hb-form" method="post" action="<?php echo HB_URL; ?>/my_schedule_update.php">
        <input type="hidden" name="sc_id" value="<?php echo (int)$row['sc_id']; ?>">
        <label>제목
            <input type="text" name="sc_title" value="<?php echo hb_e($row['sc_title']); ?>" placeholder="예: 아침 작업 시작송" required>
        </label>
        <div class="hb-two"><label>재생 방식<select name="sc_play_mode" id="hbSchedulePlayMode"><option value="once" <?php echo $row['sc_play_mode']!=='range'?'selected':''; ?>>정각에 한 번 재생</option><option value="range" <?php echo $row['sc_play_mode']==='range'?'selected':''; ?>>특정 시간 동안 재생</option></select></label><label>시작 시간<input type="time" name="sc_time" value="<?php echo hb_time_hm($row['sc_time']); ?>" required></label></div>
        <div class="hb-source-box hb-range-options" id="hbRangeOptions">
            <strong>특정 시간 동안만 나오게 하기</strong>
            <p class="hb-muted-mini">예: 22:00~23:00 작업용 BGM처럼 시간 안에서만 재생됩니다.</p>
            <div class="hb-two"><label>종료 시간<input type="time" name="sc_end_time" value="<?php echo hb_time_hm($row['sc_end_time'] ?: '10:00:00'); ?>"></label><label>반복 옵션<span class="hb-inline"><input type="checkbox" name="sc_repeat" value="1" <?php echo $row['sc_repeat'] ? 'checked' : ''; ?>> 종료 시간까지 반복 재생</span></label></div>
        </div>
        <label>첫 번째(대표) 음악
            <select name="mf_id">
                <option value="">음악 선택</option>
                <?php echo hb_get_music_options((int)$row['mf_id']); ?>
            </select>
        </label>
        <div class="hb-source-box">
            <strong>추가 음악 함께 넣기</strong>
            <div class="hb-track-list"><?php echo hb_get_schedule_music_select_rows($extra_ids, 15); ?></div>
        </div>
        <div class="hb-source-box">
            <strong>YouTube 링크 추가 등록</strong>
            <label>YouTube URL 또는 영상 ID
                <input type="text" name="quick_youtube_url" value="" placeholder="https://youtu.be/... 또는 YouTube 영상 ID">
            </label>
            <label>YouTube 링크 여러 개 붙여넣기 / 드롭
                <textarea id="hbScheduleYoutubeBulk" name="quick_youtube_urls" rows="5" placeholder="https://youtu.be/...
https://www.youtube.com/watch?v=..."></textarea>
            </label>
            <label>YouTube 음악 제목
                <input type="text" name="quick_youtube_title" value="" placeholder="비우면 시간표 제목으로 자동 등록">
            </label>
            <div class="hb-dropzone hb-dropzone-youtube" id="hbScheduleYoutubeDropzone"><strong>YouTube 링크를 여기로 끌어오거나 붙여넣기</strong><span>여러 링크를 줄바꿈으로 넣을 수 있습니다.</span><em id="hbScheduleYoutubeStatus">대기 중</em></div>
        </div>
        <div class="hb-fieldset">
            <strong>요일</strong>
            <div class="hb-checks">
                <?php foreach (hb_days_all() as $d => $label) { ?>
                    <label><input type="checkbox" name="sc_days[]" value="<?php echo $d; ?>" <?php echo in_array((string)$d, $days_selected, true) ? 'checked' : ''; ?>> <?php echo $label; ?></label>
                <?php } ?>
            </div>
        </div>
        <div class="hb-two">
            <label>시작일 선택
                <input type="date" name="sc_start_date" value="<?php echo hb_e($row['sc_start_date']); ?>">
            </label>
            <label>종료일 선택
                <input type="date" name="sc_end_date" value="<?php echo hb_e($row['sc_end_date']); ?>">
            </label>
        </div>
        <label class="hb-inline"><input type="checkbox" name="sc_use" value="1" <?php echo $row['sc_use'] ? 'checked' : ''; ?>> 사용하기</label>
        <div class="hb-actions"><button class="hb-btn hb-btn-primary" type="submit">저장하기</button></div>
    </form>
</div>
<script>
(function(){
    const mode = document.getElementById('hbSchedulePlayMode');
    const rangeBox = document.getElementById('hbRangeOptions');
    function syncRange(){ if(rangeBox && mode) rangeBox.style.display = mode.value === 'range' ? '' : 'none'; }
    if(mode){ mode.addEventListener('change', syncRange); syncRange(); }
    const area = document.getElementById('hbScheduleYoutubeBulk');
    const drop = document.getElementById('hbScheduleYoutubeDropzone');
    const status = document.getElementById('hbScheduleYoutubeStatus');
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
