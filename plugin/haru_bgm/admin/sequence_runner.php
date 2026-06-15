<?php
include_once('./_common.php');
$seq_id = isset($_GET['seq_id']) ? (int)$_GET['seq_id'] : 0;
$sequence = hb_table('sequence');
$row = sql_fetch("SELECT * FROM `{$sequence}` WHERE seq_id='{$seq_id}'", false);
if (!$row) alert('순서표를 찾을 수 없습니다.', HB_URL.'/admin/sequence_list.php');
$items = hb_sequence_items($seq_id);
$g5['title'] = '순서표 진행판';
include_once(G5_PATH.'/head.php');
?>
<link rel="stylesheet" href="<?php echo HB_URL; ?>/assets/haru_bgm.css?ver=20260616g">
<div class="hb-wrap hb-runner" data-hb-url="<?php echo HB_URL; ?>" data-hb-mode="sequence-runner">
<section class="hb-page-head"><div><p class="hb-kicker">SEQUENCE RUNNER</p><h1><?php echo hb_e($row['seq_title']); ?></h1><p><?php echo hb_sequence_type_label($row['seq_type']); ?> · 담당자가 순서대로 눌러 진행하는 관리자 전용 화면입니다.</p></div><div class="hb-actions"><a class="hb-btn" href="<?php echo HB_URL; ?>/admin/sequence_list.php">순서표 목록</a><a class="hb-btn" href="<?php echo HB_URL; ?>/admin/operation.php">공용 운영판</a></div></section>
<?php echo hb_nav_admin(); ?>
<section class="hb-hero hb-hero-admin"><div><p class="hb-kicker">MANUAL CONTROL</p><h1>현장 진행 모드</h1><p class="hb-sub">소리는 이 브라우저에서만 나옵니다. 실수 방지 잠금을 켜면 재생 전 확인창이 뜹니다.</p><div class="hb-actions hb-operation-controls"><button type="button" class="hb-btn hb-btn-primary" id="hbEnableSound">🔊 진행판 소리 켜기</button><button type="button" class="hb-btn" id="hbStopSound">■ 전체 정지</button><label class="hb-btn hb-lock-toggle"><input type="checkbox" id="hbConfirmPlay" checked> 실수방지 확인</label></div></div><div class="hb-clock-card"><div class="hb-clock" id="hbClock">--:--:--</div><div class="hb-next-label">현재 상태</div><div class="hb-countdown" id="hbCountdown">수동 진행</div></div></section>
<section class="hb-now-grid"><article class="hb-card hb-player-card"><div class="hb-card-head"><div><p class="hb-kicker">NOW</p><h2 id="hbNowTitle">순서 대기 중</h2></div><span class="hb-pill" id="hbSoundState">대기</span></div><p class="hb-muted" id="hbNowDesc">항목의 재생 버튼을 누르면 시작합니다.</p><p class="hb-status-text" id="hbStatusText">처음 한 번은 소리 켜기를 눌러주세요.</p><audio id="hbAudio" preload="auto" controls></audio><div class="hb-youtube-wrap" id="hbYoutubeWrap" style="display:none"><div id="hbYouTubePlayer"></div></div><div class="hb-volume-row"><label>진행 볼륨</label><input type="range" min="0" max="100" value="80" id="hbVolume"><span id="hbVolumeText">80%</span></div></article>
<article class="hb-card"><div class="hb-card-head"><div><p class="hb-kicker">STEPS</p><h2>진행 순서</h2></div><button type="button" class="hb-mini-play hb-btn-primary" data-items="<?php echo hb_sequence_preview_items_attr($seq_id); ?>" data-title="<?php echo hb_e($row['seq_title']); ?>" data-confirm="1">전체 순서 테스트</button></div><div class="hb-sequence-steps">
<?php foreach($items as $idx=>$it){ $payload=hb_music_item_payload($it); $title=$it['siq_title'] ? $it['siq_title'] : $it['mf_title']; $one=hb_media_items_attr(array($it)); ?>
<div class="hb-sequence-step"><div class="hb-step-no"><?php echo $idx+1; ?></div><div class="hb-item-main"><strong><?php echo hb_e($title); ?></strong><span><?php echo hb_music_source_label($it); ?> · <?php echo hb_e($it['mf_title']); ?></span><?php if($it['siq_memo']){ ?><em><?php echo hb_e($it['siq_memo']); ?></em><?php } ?></div><button type="button" class="hb-mini-play hb-btn-primary" data-items="<?php echo $one; ?>" data-source="<?php echo hb_e($payload['source']); ?>" data-src="<?php echo hb_e($payload['url']); ?>" data-youtube-id="<?php echo hb_e($payload['youtube_id']); ?>" data-title="<?php echo hb_e($title); ?>" data-volume="<?php echo (int)$it['mf_volume']; ?>" data-confirm="1">재생</button></div>
<?php } if(!count($items)){ ?><div class="hb-empty"><strong>순서 항목이 없습니다</strong></div><?php } ?>
</div></article></section>
</div>
<script>
window.HARU_BGM={apiLog:'<?php echo HB_URL; ?>/api_log.php',memberId:'<?php echo hb_e($member['mb_id']); ?>',mode:'sequence_runner',storagePrefix:'haru_bgm_sequence_<?php echo (int)$seq_id; ?>_'};
(function(){document.addEventListener('click',function(e){var b=e.target.closest('.hb-mini-play'); if(!b) return; var c=document.getElementById('hbConfirmPlay'); if(c && !c.checked) b.dataset.confirm='0'; else b.dataset.confirm='1';},true);})();
</script>
<script src="<?php echo HB_URL; ?>/assets/haru_bgm.js?ver=20260616g"></script>
<?php include_once(G5_PATH.'/tail.php'); ?>
