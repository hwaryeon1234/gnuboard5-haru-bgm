<?php
include_once('./_common.php');
$g5['title'] = '하루브금 오늘 운영표';
$hb_haru_head_row_was_set = array_key_exists('row', get_defined_vars());
$hb_haru_head_row_backup = $hb_haru_head_row_was_set ? $row : null;
include_once(G5_PATH.'/head.php');
if ($hb_haru_head_row_was_set) {
    $row = $hb_haru_head_row_backup;
} else {
    unset($row);
}
unset($hb_haru_head_row_was_set, $hb_haru_head_row_backup);
$today = hb_today_operation_entries();
?>
<link rel="stylesheet" href="<?php echo HB_URL; ?>/assets/haru_bgm.css?ver=20260625-radiov5">
<div class="hb-app"><?php echo hb_nav_admin(); ?><main class="hb-app-main"><div class="hb-wrap" data-hb-url="<?php echo HB_URL; ?>" data-hb-mode="admin-today">
    <section class="hb-page-head"><div><p class="hb-kicker">TODAY</p><h1>오늘 운영표</h1><p>오늘 실제 실행될 공통 시간표/시간대 묶음만 모아 봅니다. 현장 전 테스트용으로 쓰기 좋습니다.</p></div><div class="hb-actions"><a class="hb-btn hb-btn-primary" href="<?php echo HB_URL; ?>/admin/operation.php">공용 운영판</a><a class="hb-btn" href="<?php echo HB_URL; ?>/admin/index.php">관리자 홈</a></div></section>
    <section class="hb-card hb-today-board">
        <div class="hb-card-head"><div><p class="hb-kicker"><?php echo date('Y-m-d'); ?></p><h2>오늘 실행 예정</h2></div><span class="hb-pill"><?php echo count($today); ?>개</span></div>
        <audio id="hbAudio" preload="auto" controls></audio><div class="hb-youtube-wrap" id="hbYoutubeWrap" style="display:none"><div id="hbYouTubePlayer"></div></div><div class="hb-volume-row"><label>테스트 볼륨</label><input type="range" min="0" max="100" value="80" id="hbVolume"><span id="hbVolumeText">80%</span></div>
        <p class="hb-status-text" id="hbStatusText">미리듣기 버튼으로 지금 바로 테스트할 수 있습니다.</p>
        <?php if (!$today) { ?><div class="hb-empty"><div class="hb-empty-icon">🗓️</div><strong>오늘 운영표가 없습니다</strong><p>공통 시간표 또는 공통 시간대 묶음을 등록해주세요.</p></div><?php } else { ?>
        <div class="hb-timeline">
            <?php foreach ($today as $entry) { $row = $entry['row']; ?>
                <?php if ($entry['kind'] === 'block') { $preview = hb_block_preview_items_attr($row['bl_id']); $title=$row['bl_title']; $meta='시간대 묶음 · '.hb_block_item_count($row['bl_id']).'개 · '.hb_play_mode_label($row['bl_play_mode']); ?>
                    <div class="hb-schedule-item hb-block-schedule" data-start="<?php echo hb_time_hm($row['bl_start_time']); ?>" data-end="<?php echo hb_time_hm($row['bl_end_time']); ?>"><div class="hb-time"><?php echo hb_time_hm($row['bl_start_time']); ?><small>~<?php echo hb_time_hm($row['bl_end_time']); ?></small></div><div class="hb-item-main"><strong><?php echo hb_e($title); ?></strong><span><?php echo hb_e($meta); ?></span><em><?php echo hb_e(hb_block_item_titles($row['bl_id'])); ?></em></div><button type="button" class="hb-mini-play hb-btn-primary" data-items="<?php echo $preview; ?>" data-title="<?php echo hb_e($title); ?>" data-volume="80">세트 테스트</button></div>
                <?php } else { $preview = hb_schedule_preview_items_attr($row['sc_id'], $row); $payload=hb_music_item_payload($row); $cnt=hb_schedule_item_count($row['sc_id']); ?>
                    <div class="hb-schedule-item <?php echo $entry['kind']==='range'?'hb-block-schedule':''; ?>" <?php echo $entry['kind']==='range' ? 'data-start="'.hb_time_hm($row['sc_time']).'" data-end="'.hb_time_hm($row['sc_end_time']).'"' : 'data-time="'.hb_time_hm($row['sc_time']).'"'; ?>><div class="hb-time"><?php echo hb_schedule_time_label($row); ?></div><div class="hb-item-main"><strong><?php echo hb_e($row['sc_title']); ?></strong><span><?php echo $entry['kind']==='range' ? '특정 시간 재생' : '정각 재생'; ?> · <?php echo $cnt > 1 ? '혼합 세트 '.$cnt.'개' : hb_music_source_label($row); ?></span><em><?php echo hb_e(hb_schedule_item_titles($row['sc_id']) ?: $row['mf_title']); ?></em></div><button type="button" class="hb-mini-play hb-btn-primary" data-items="<?php echo $preview; ?>" data-source="<?php echo hb_e($payload['source']); ?>" data-src="<?php echo hb_e($payload['url']); ?>" data-youtube-id="<?php echo hb_e($payload['youtube_id']); ?>" data-title="<?php echo hb_e($row['sc_title']); ?>" data-volume="<?php echo (int)$row['mf_volume']; ?>">테스트</button></div>
                <?php } ?>
            <?php } ?>
        </div><?php } ?>
    </section>
</div></main></div>
<script>window.HARU_BGM={apiLog:'<?php echo HB_URL; ?>/api_log.php',memberId:'<?php echo hb_e($member['mb_id']); ?>',mode:'admin_today',storagePrefix:'haru_bgm_admin_today_'};</script>
<script src="<?php echo HB_URL; ?>/assets/haru_bgm.js?ver=20260625-radiov5"></script>
<?php include_once(G5_PATH.'/tail.php'); ?>
