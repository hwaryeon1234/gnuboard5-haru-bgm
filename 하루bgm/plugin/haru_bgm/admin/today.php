<?php
include_once('./_common.php');
$g5['title'] = '하루브금 오늘 운영표';
include_once(HB_PATH.'/admin/_head.php');
$today = hb_today_operation_entries();
$sitewide_enabled = hb_sitewide_enabled();
?>
<div id="haruBgmAdminApp" class="hb-app"><?php echo hb_nav_admin(); ?><main class="hb-app-main"><div class="hb-wrap" data-hb-url="<?php echo HB_URL; ?>" data-hb-mode="admin-today">
    <section class="hb-page-head"><div><p class="hb-kicker">TODAY</p><h1>오늘 운영표</h1><p>오늘 실제 실행될 공통 시간표/시간대 묶음만 모아 봅니다. 현장 전 테스트용으로 쓰기 좋습니다.</p></div></section>
    <section class="hb-card hb-today-board">
        <div class="hb-card-head"><div><p class="hb-kicker"><?php echo G5_TIME_YMD; ?></p><h2>오늘 실행 예정</h2></div><span class="hb-pill"><?php echo count($today); ?>개</span></div>
        <?php if ($sitewide_enabled) { ?>
        <div class="hb-inline-broadcast-note"><strong>사이트 전체 방송 유지 중</strong><span>아래 테스트 버튼은 관리자 브라우저에서만 미리듣기하며, 현재 사이트 전체 송출 상태에는 영향을 주지 않습니다.</span></div>
        <?php } ?>
        <audio id="hbAudio" preload="auto" controls></audio><div class="hb-youtube-wrap" id="hbYoutubeWrap" style="display:none"><div id="hbYouTubePlayer"></div></div><div class="hb-volume-row"><label>테스트 볼륨</label><input type="range" min="0" max="100" value="80" id="hbVolume"><span id="hbVolumeText">80%</span></div>
        <p class="hb-status-text" id="hbStatusText">미리듣기 버튼으로 지금 바로 테스트할 수 있습니다.</p>
        <?php if (!$today) { ?><div class="hb-empty"><div class="hb-empty-icon">■</div><strong>오늘 운영표가 없습니다</strong><p>공통 시간표 또는 공통 시간대 묶음을 등록해주세요.</p></div><?php } else { ?>
        <div class="hb-timeline" id="hbTodayList">
            <?php foreach ($today as $entry) { $row = $entry['row']; ?>
                <?php if ($entry['kind'] === 'block') { $preview = hb_block_preview_items_attr($row['bl_id']); $title=$row['bl_title']; $meta='시간대 묶음 · '.hb_block_item_count($row['bl_id']).'개 · '.hb_play_mode_label($row['bl_play_mode']); ?>
                    <div class="hb-schedule-item hb-block-schedule" data-service-date="<?php echo hb_e($entry['service_date']); ?>" data-block-id="<?php echo (int)$row['bl_id']; ?>" data-block-kind="block" data-start="<?php echo hb_time_hm($row['bl_start_time']); ?>" data-end="<?php echo hb_time_hm($row['bl_end_time']); ?>"><div class="hb-time"><?php echo hb_time_hm($row['bl_start_time']); ?><small>~<?php echo hb_time_hm($row['bl_end_time']); ?></small></div><div class="hb-item-main"><strong><?php echo hb_e($title); ?></strong><span><?php echo hb_e($meta); ?></span><em><?php echo hb_e(hb_block_item_titles($row['bl_id'])); ?></em></div><div class="hb-item-actions"><button type="button" class="hb-mini-play hb-btn-primary" data-items="<?php echo $preview; ?>" data-title="<?php echo hb_e($title); ?>" data-volume="80">세트 테스트</button><button type="button" class="hb-mini-play hb-mini-play-live" data-live="1" data-items="<?php echo $preview; ?>" data-title="<?php echo hb_e($title); ?>" data-volume="80" title="지금 실제로 방송 중이면 그 위치에서 이어서 들어봅니다">◉ 실시간 듣기</button></div></div>
                <?php } else { $preview = hb_schedule_preview_items_attr($row['sc_id'], $row); $hb_first = hb_schedule_effective_first_item($row['sc_id'], $row); $payload=$hb_first ? hb_music_item_payload($hb_first) : array('source'=>'file','url'=>'','youtube_id'=>''); $cnt=hb_schedule_item_count($row['sc_id']); ?>
                    <div class="hb-schedule-item <?php echo $entry['kind']==='range'?'hb-block-schedule':''; ?>" <?php echo $entry['kind']==='range' ? 'data-service-date="'.hb_e($entry['service_date']).'" data-block-id="range_'.(int)$row['sc_id'].'" data-block-kind="range" data-start="'.hb_time_hm($row['sc_time']).'" data-end="'.hb_time_hm($row['sc_end_time']).'"' : 'data-service-date="'.hb_e($entry['service_date']).'" data-time="'.hb_time_hm($row['sc_time']).'"'; ?>><div class="hb-time"><?php echo hb_schedule_time_label($row); ?></div><div class="hb-item-main"><strong><?php echo hb_e($row['sc_title']); ?></strong><span><?php echo $entry['kind']==='range' ? '특정 시간 재생' : '정각 재생'; ?> · <?php echo $cnt > 1 ? '혼합 세트 '.$cnt.'개' : ($hb_first ? hb_music_source_label($hb_first) : '재생 가능 음악 없음'); ?></span><em><?php echo hb_e(hb_schedule_item_titles($row['sc_id']) ?: ($hb_first ? $hb_first['mf_title'] : '')); ?></em></div><div class="hb-item-actions"><button type="button" class="hb-mini-play hb-btn-primary" data-items="<?php echo $preview; ?>" data-source="<?php echo hb_e($payload['source']); ?>" data-src="<?php echo hb_e($payload['url']); ?>" data-youtube-id="<?php echo hb_e($payload['youtube_id']); ?>" data-title="<?php echo hb_e($row['sc_title']); ?>" data-volume="<?php echo $hb_first ? (int)$hb_first['mf_volume'] : 80; ?>">테스트</button><button type="button" class="hb-mini-play hb-mini-play-live" data-live="1" data-items="<?php echo $preview; ?>" data-source="<?php echo hb_e($payload['source']); ?>" data-src="<?php echo hb_e($payload['url']); ?>" data-youtube-id="<?php echo hb_e($payload['youtube_id']); ?>" data-title="<?php echo hb_e($row['sc_title']); ?>" data-volume="<?php echo $hb_first ? (int)$hb_first['mf_volume'] : 80; ?>" title="지금 실제로 방송 중이면 그 위치에서 이어서 들어봅니다">◉ 실시간 듣기</button></div></div>
                <?php } ?>
            <?php } ?>
        </div><?php } ?>
    </section>
</div></main></div>
<script>window.HARU_BGM={apiLog:<?php echo hb_json_encode(HB_URL.'/api_log.php'); ?>,csrfToken:<?php echo hb_json_encode(hb_csrf_token()); ?>,standardToken:'',
apiBroadcastStatus:<?php echo hb_json_encode(HB_URL.'/api_broadcast.php'); ?>,
serverTime:<?php echo hb_json_encode(G5_TIME_YMDHIS); ?>,serverEpochMs:<?php echo (int)round(microtime(true)*1000); ?>,
mode:'admin_today',storagePrefix:<?php echo hb_json_encode('haru_bgm_admin_today_'.(string)$member['mb_id'].'_'); ?>};</script>
<script src="<?php echo HB_URL; ?>/assets/haru_bgm.js?ver=<?php echo rawurlencode(HB_ASSET_VERSION); ?>"></script>
<?php include_once(HB_PATH.'/admin/_tail.php'); 
