<?php
include_once('./_common.php');
$g5['title'] = '하루브금 음악 등록';
$mf_id = isset($_GET['mf_id']) ? (int)$_GET['mf_id'] : 0;
$row = array('mf_id'=>0, 'mf_title'=>'', 'mf_source'=>'file', 'mf_file'=>'', 'mf_youtube_url'=>'', 'mf_youtube_id'=>'', 'mf_volume'=>80, 'mf_type'=>'music', 'mf_memo'=>'', 'mf_use'=>1, 'mf_org_name'=>'');
if ($mf_id) {
    $music = hb_table('music');
    $found = sql_fetch("SELECT * FROM `{$music}` WHERE mf_id='{$mf_id}'");
    if (!$found) alert('음악을 찾을 수 없습니다.');
    $row = array_merge($row, $found);
}
include_once(G5_PATH.'/head.php');
?>
<link rel="stylesheet" href="<?php echo HB_URL; ?>/assets/haru_bgm.css?ver=20260616e">
<div class="hb-wrap">
    <section class="hb-page-head"><div><p class="hb-kicker">ADMIN</p><h1><?php echo $mf_id ? '음악 수정' : '음악 업로드 / YouTube 등록'; ?></h1><p>파일은 드래그앤드롭으로 여러 개, YouTube 링크도 여러 줄로 한 번에 등록할 수 있습니다.</p></div><a class="hb-btn" href="<?php echo HB_URL; ?>/admin/music_list.php">목록으로</a></section>
    <?php echo hb_nav_admin(); ?>
    <form class="hb-card hb-form" method="post" action="<?php echo HB_URL; ?>/admin/music_update.php" enctype="multipart/form-data">
        <input type="hidden" name="mf_id" value="<?php echo (int)$row['mf_id']; ?>">
        <label>등록 방식
            <select name="mf_source" id="hbMusicSource">
                <option value="file" <?php echo $row['mf_source']==='file'?'selected':''; ?>>파일 업로드</option>
                <option value="youtube" <?php echo $row['mf_source']==='youtube'?'selected':''; ?>>YouTube 링크</option>
            </select>
        </label>
        <label>음악 제목 <span class="hb-muted-mini">여러 파일/링크 등록 시 비워두면 파일명 또는 영상 ID 기준으로 자동 등록됩니다.</span><input type="text" name="mf_title" value="<?php echo hb_e($row['mf_title']); ?>" placeholder="예: 밤 작업용 재즈 / 예배 전 묵상 BGM"></label>
        <div class="hb-source-box hb-source-file">
            <label>음악 파일 <?php if ($mf_id && $row['mf_source']==='file') echo '<span class="hb-muted-mini">현재 파일: '.hb_e($row['mf_org_name']).'</span>'; ?>
                <input type="file" id="hbMusicFiles" name="music_files[]" multiple accept="audio/mpeg,audio/mp3,audio/wav,audio/ogg,audio/mp4,.mp3,.wav,.ogg,.m4a">
            </label>
            <div class="hb-dropzone" id="hbFileDropzone">
                <strong>여기로 음악 파일을 끌어다 놓기</strong>
                <span>mp3, wav, ogg, m4a · 여러 개 가능 · 클릭해도 선택됩니다.</span>
                <em id="hbFileDropStatus">선택된 파일 없음</em>
            </div>
            <p class="hb-muted-mini">새 등록은 여러 파일 가능, 수정은 첫 번째 파일만 교체됩니다.</p>
        </div>
        <div class="hb-source-box hb-source-youtube">
            <label>YouTube 링크 <?php echo $mf_id ? '<span class="hb-muted-mini">수정 시에는 1개 링크만 사용됩니다.</span>' : '<span class="hb-muted-mini">여러 링크를 줄바꿈으로 넣으면 한 번에 등록됩니다.</span>'; ?>
                <input type="text" name="mf_youtube_url" value="<?php echo hb_e($row['mf_youtube_url']); ?>" placeholder="https://www.youtube.com/watch?v=...">
            </label>
            <?php if (!$mf_id) { ?>
            <label>YouTube 링크 여러 개 붙여넣기 / 드롭
                <textarea id="hbYoutubeBulk" name="bulk_youtube_urls" rows="7" placeholder="https://youtu.be/...
https://www.youtube.com/watch?v=...
영상 ID만 넣어도 됩니다."></textarea>
            </label>
            <div class="hb-dropzone hb-dropzone-youtube" id="hbYoutubeDropzone">
                <strong>YouTube 링크를 여기로 끌어오거나 붙여넣기</strong>
                <span>브라우저 주소창/영상 링크/텍스트 여러 줄을 넣을 수 있습니다.</span>
                <em id="hbYoutubeDropStatus">대기 중</em>
            </div>
            <?php } ?>
            <?php if ($row['mf_youtube_id']) { ?><p class="hb-muted-mini">현재 영상 ID: <?php echo hb_e($row['mf_youtube_id']); ?></p><?php } ?>
        </div>
        <label>기본 볼륨<input type="range" name="mf_volume" min="0" max="100" value="<?php echo (int)$row['mf_volume']; ?>" oninput="this.nextElementSibling.textContent=this.value+'%'" ><span><?php echo (int)$row['mf_volume']; ?>%</span></label>
        <label>분류<select name="mf_type"><option value="music" <?php echo $row['mf_type']==='music'?'selected':''; ?>>음악</option><option value="bell" <?php echo $row['mf_type']==='bell'?'selected':''; ?>>알림음</option></select></label>
        <label>메모<textarea name="mf_memo" rows="4"><?php echo hb_e($row['mf_memo']); ?></textarea></label>
        <label class="hb-inline"><input type="checkbox" name="mf_use" value="1" <?php echo $row['mf_use'] ? 'checked' : ''; ?>> 사용하기</label>
        <div class="hb-actions"><button class="hb-btn hb-btn-primary" type="submit">저장하기</button></div>
    </form>
</div>
<script>
(function(){
    const sel = document.getElementById('hbMusicSource');
    function sync(){
        const v = sel ? sel.value : 'file';
        document.querySelectorAll('.hb-source-file').forEach(el => el.style.display = v === 'file' ? '' : 'none');
        document.querySelectorAll('.hb-source-youtube').forEach(el => el.style.display = v === 'youtube' ? '' : 'none');
    }
    if(sel){ sel.addEventListener('change', sync); sync(); }

    const fileInput = document.getElementById('hbMusicFiles');
    const fileDrop = document.getElementById('hbFileDropzone');
    const fileStatus = document.getElementById('hbFileDropStatus');
    function setFileStatus(){
        if(!fileStatus || !fileInput) return;
        const files = Array.from(fileInput.files || []);
        fileStatus.textContent = files.length ? files.length + '개 선택됨 · ' + files.slice(0,3).map(f => f.name).join(', ') + (files.length > 3 ? ' 외' : '') : '선택된 파일 없음';
    }
    if(fileDrop && fileInput){
        fileDrop.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', setFileStatus);
        ['dragenter','dragover'].forEach(ev => fileDrop.addEventListener(ev, e => { e.preventDefault(); fileDrop.classList.add('is-drag'); }));
        ['dragleave','drop'].forEach(ev => fileDrop.addEventListener(ev, e => { e.preventDefault(); fileDrop.classList.remove('is-drag'); }));
        fileDrop.addEventListener('drop', e => { if(e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length){ fileInput.files = e.dataTransfer.files; setFileStatus(); } });
        setFileStatus();
    }

    const ytArea = document.getElementById('hbYoutubeBulk');
    const ytDrop = document.getElementById('hbYoutubeDropzone');
    const ytStatus = document.getElementById('hbYoutubeDropStatus');
    function appendYoutubeText(text){
        if(!ytArea || !text) return;
        const old = ytArea.value.trim();
        ytArea.value = old ? old + '\n' + text.trim() : text.trim();
        if(ytStatus) ytStatus.textContent = '링크가 추가되었습니다.';
    }
    if(ytDrop && ytArea){
        ytDrop.addEventListener('click', () => ytArea.focus());
        ['dragenter','dragover'].forEach(ev => ytDrop.addEventListener(ev, e => { e.preventDefault(); ytDrop.classList.add('is-drag'); }));
        ['dragleave','drop'].forEach(ev => ytDrop.addEventListener(ev, e => { e.preventDefault(); ytDrop.classList.remove('is-drag'); }));
        ytDrop.addEventListener('drop', e => {
            const txt = (e.dataTransfer && (e.dataTransfer.getData('text/uri-list') || e.dataTransfer.getData('text/plain'))) || '';
            appendYoutubeText(txt);
        });
    }
})();
</script>
<?php include_once(G5_PATH.'/tail.php'); ?>
