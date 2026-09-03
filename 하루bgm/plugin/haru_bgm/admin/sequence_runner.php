<?php
include_once('./_common.php');
$seq_id = isset($_GET['seq_id']) ? hb_int_value($_GET['seq_id'], 0) : 0;
$sequence = hb_table('sequence');
$row = sql_fetch("SELECT * FROM `{$sequence}` WHERE seq_id='{$seq_id}' AND seq_use='1'", false);
if (!$row) alert('사용 중인 순서표를 찾을 수 없습니다.', HB_URL.'/admin/sequence_list.php');
$items = hb_sequence_items($seq_id);
if (!$items) alert('재생 가능한 순서 항목이 없습니다. 순서표를 수정해주세요.', HB_URL.'/admin/sequence_list.php');
$sitewide_enabled = hb_sitewide_enabled();
$sitewide_control_allowed = $sitewide_enabled && hb_user_has_admin_auth('990110', 'w');
$g5['title'] = '순서표 진행판';
$hb_haru_form_row_backup = $row;
include_once(HB_PATH.'/admin/_head.php');
$row = $hb_haru_form_row_backup;
unset($hb_haru_form_row_backup);
?>
<div id="haruBgmAdminApp" class="hb-app"><?php echo hb_nav_admin(); ?><main class="hb-app-main"><div class="hb-wrap hb-runner" data-hb-url="<?php echo HB_URL; ?>" data-hb-mode="sequence-runner">
<section class="hb-page-head"><div><p class="hb-kicker">SEQUENCE RUNNER</p><h1><?php echo hb_e($row['seq_title']); ?></h1><p><?php echo hb_sequence_type_label($row['seq_type']); ?> · 담당자가 순서대로 눌러 진행하는 관리자 전용 화면입니다.</p></div><div class="hb-actions"><a class="hb-btn" href="<?php echo HB_URL; ?>/admin/sequence_list.php">순서표 목록</a></div></section>
<section class="hb-hero hb-hero-admin"><div><p class="hb-kicker">MANUAL CONTROL</p><h1>현장 진행 모드</h1><p class="hb-sub">이 화면의 미리듣기는 관리자 기기에서 확인하고, 사이트 전체 방송이 켜져 있으면 각 항목의 <b>전체 재생</b>으로 방문자에게 즉시 송출할 수 있습니다. 실수 방지 잠금을 켜면 미리듣기 전에 확인창이 뜹니다.</p><div class="hb-actions hb-operation-controls"><button type="button" class="hb-btn hb-btn-primary" id="hbEnableSound">▶ 진행판 소리 켜기</button><button type="button" class="hb-btn" id="hbStopSound">■ 진행판만 정지</button><?php if ($sitewide_control_allowed) { ?><form class="hb-inline-form" method="post" action="<?php echo HB_URL; ?>/admin/sitewide_control.php"><?php echo hb_csrf_field(); ?><input type="hidden" name="return_url" value="<?php echo hb_e(HB_URL.'/admin/sequence_runner.php?seq_id='.(int)$seq_id); ?>"><input type="hidden" name="action" value="auto"><button type="submit" class="hb-btn">자동 편성 복귀</button></form><form class="hb-inline-form" method="post" action="<?php echo HB_URL; ?>/admin/sitewide_control.php" onsubmit="return confirm('사이트 전체 방송을 정지할까요?');"><?php echo hb_csrf_field(); ?><input type="hidden" name="return_url" value="<?php echo hb_e(HB_URL.'/admin/sequence_runner.php?seq_id='.(int)$seq_id); ?>"><input type="hidden" name="action" value="stop"><button type="submit" class="hb-btn hb-danger">사이트 전체 정지</button></form><?php } ?><label class="hb-btn hb-lock-toggle"><input type="checkbox" id="hbConfirmPlay" checked> 실수방지 확인</label></div></div><div class="hb-clock-card"><div class="hb-clock" id="hbClock">--:--:--</div><div class="hb-next-label">현재 상태</div><div class="hb-countdown" id="hbCountdown"><?php echo $sitewide_enabled ? ($sitewide_control_allowed ? '사이트 전체 송출 사용 가능' : '전체 송출 제어 권한 없음') : '수동 진행'; ?></div></div></section>
<section class="hb-now-grid"><article class="hb-card hb-player-card"><div class="hb-card-head"><div><p class="hb-kicker">NOW</p><h2 id="hbNowTitle">순서 대기 중</h2></div><span class="hb-pill" id="hbSoundState">대기</span></div><p class="hb-muted" id="hbNowDesc">항목의 미리듣기 버튼을 누르면 이 관리자 기기에서 확인합니다.</p><p class="hb-status-text" id="hbStatusText">처음 한 번은 진행판 소리 켜기를 눌러주세요.</p><audio id="hbAudio" preload="auto" controls></audio><div class="hb-youtube-wrap" id="hbYoutubeWrap" style="display:none"><div id="hbYouTubePlayer"></div></div><div class="hb-volume-row"><label>진행 볼륨</label><input type="range" min="0" max="100" value="80" id="hbVolume"><span id="hbVolumeText">80%</span></div></article>
<article class="hb-card"><div class="hb-card-head"><div><p class="hb-kicker">STEPS</p><h2>진행 순서</h2></div><button type="button" class="hb-mini-play hb-btn-primary" data-items="<?php echo hb_sequence_preview_items_attr($seq_id); ?>" data-title="<?php echo hb_e($row['seq_title']); ?>" data-confirm="1">전체 순서 테스트</button></div><div class="hb-sequence-steps">
<?php foreach($items as $idx=>$it){ $payload=hb_music_item_payload($it); $title=$it['siq_title'] ? $it['siq_title'] : $it['mf_title']; $one=hb_media_items_attr(array($it)); ?>
<div class="hb-sequence-step"><div class="hb-step-no"><?php echo $idx+1; ?></div><div class="hb-item-main"><strong><?php echo hb_e($title); ?></strong><span><?php echo hb_music_source_label($it); ?> · <?php echo hb_e($it['mf_title']); ?></span><?php if($it['siq_memo']){ ?><em><?php echo hb_e($it['siq_memo']); ?></em><?php } ?></div><button type="button" class="hb-mini-play hb-btn-primary" data-items="<?php echo $one; ?>" data-source="<?php echo hb_e($payload['source']); ?>" data-src="<?php echo hb_e($payload['url']); ?>" data-youtube-id="<?php echo hb_e($payload['youtube_id']); ?>" data-title="<?php echo hb_e($title); ?>" data-volume="<?php echo (int)$it['mf_volume']; ?>" data-confirm="1">미리듣기</button><?php if ($sitewide_control_allowed) { ?><form class="hb-inline-form" method="post" action="<?php echo HB_URL; ?>/admin/sitewide_control.php"><?php echo hb_csrf_field(); ?><input type="hidden" name="return_url" value="<?php echo hb_e(HB_URL.'/admin/sequence_runner.php?seq_id='.(int)$seq_id); ?>"><input type="hidden" name="action" value="play"><input type="hidden" name="mf_id" value="<?php echo (int)$it['mf_id']; ?>"><button type="submit" class="hb-btn hb-btn-small hb-btn-primary">전체 재생</button></form><?php } ?></div>
<?php } if(!count($items)){ ?><div class="hb-empty"><strong>순서 항목이 없습니다</strong></div><?php } ?>
</div></article></section>
</div></main></div>
<script>
(function(){
    document.addEventListener('click',function(e){
        var b=e.target.closest('.hb-mini-play');
        if(b){ var c=document.getElementById('hbConfirmPlay'); b.dataset.confirm=(c && !c.checked)?'0':'1'; }
    },true);
})();
</script>
<script>window.HARU_BGM={apiLog:<?php echo hb_json_encode(HB_URL.'/api_log.php'); ?>,csrfToken:<?php echo hb_json_encode(hb_csrf_token()); ?>,standardToken:'',
apiBroadcastStatus:<?php echo hb_json_encode(HB_URL.'/api_broadcast.php'); ?>,
serverTime:<?php echo hb_json_encode(G5_TIME_YMDHIS); ?>,serverEpochMs:<?php echo (int)round(microtime(true)*1000); ?>,
mode:'sequence_runner',sequenceId:<?php echo (int)$seq_id; ?>,storagePrefix:<?php echo hb_json_encode('haru_bgm_sequence_'.(string)$member['mb_id'].'_'.(int)$seq_id.'_'); ?>};</script>
<script src="<?php echo HB_URL; ?>/assets/haru_bgm.js?ver=<?php echo rawurlencode(HB_ASSET_VERSION); ?>"></script>
<?php include_once(HB_PATH.'/admin/_tail.php'); 
