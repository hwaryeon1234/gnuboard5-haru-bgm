<?php
include_once('./_common.php');
$g5['title'] = '하루브금 공용 운영판';
include_once(HB_PATH.'/admin/_head.php');

$today = hb_today_operation_entries();
$single_count = 0;
$range_count = 0;
$block_count = 0;
foreach ($today as $entry) {
    if ($entry['kind'] === 'single') $single_count++;
    elseif ($entry['kind'] === 'range') $range_count++;
    else $block_count++;
}
$sitewide_enabled = hb_sitewide_enabled();
$sitewide_hook = hb_sitewide_hook_installed();
$broadcast = hb_broadcast_payload();
$can_sitewide_control = hb_user_has_admin_auth('990110', 'w');
$can_schedule_write = hb_user_has_admin_auth('990140', 'w');
$can_block_write = hb_user_has_admin_auth('990150', 'w');
$broadcast_mode_label = $broadcast['mode'] === 'manual' ? '수동 전체 재생' : ($broadcast['mode'] === 'stop' ? '전체 정지' : '자동 편성');
$broadcast_position = 0.0;
if ($broadcast['mode'] === 'manual' && !empty($broadcast['item'])) {
    $broadcast_position = max(0.0, (float)$broadcast['seek_seconds']);
    if (!empty($broadcast['started_epoch_ms'])) $broadcast_position += max(0.0, (microtime(true) * 1000 - (float)$broadcast['started_epoch_ms']) / 1000);
}
?>

<div id="haruBgmAdminApp" class="hb-app"><?php echo hb_nav_admin(); ?><main class="hb-app-main"><div class="hb-wrap hb-operation-page" data-hb-url="<?php echo HB_URL; ?>" data-hb-mode="admin-operation">
    <section class="hb-page-head hb-operation-head">
        <div>
            <p class="hb-kicker">ADMIN OPERATION</p>
            <h1>공용 운영판</h1>
            <p>방송실 느낌으로 더 크게 정리한 운영 전용 화면입니다. 현재 재생 상태, 즉시 제어, 오늘 공통 운영표를 한눈에 보면서 바로 조작할 수 있습니다.</p>
        </div>
    </section>
    <section class="hb-operation-banner">
        <div class="hb-operation-banner-copy">
            <p class="hb-kicker">BROADCAST ROOM PANEL</p>
            <h2>운영 중 필요한 것만 크게</h2>
            <p>왼쪽은 현재 재생과 직접 제어, 오른쪽은 시계/카운트다운/오늘 요약으로 묶었습니다. 현장에서 작은 버튼 찾지 않도록 전체적으로 크게 키운 구성입니다.</p>
        </div>
        <div class="hb-operation-banner-stats">
            <span><b><?php echo count($today); ?></b> 오늘 운영표</span>
            <span><b><?php echo $single_count; ?></b> 정각</span>
            <span><b><?php echo $range_count; ?></b> 시간 재생</span>
            <span><b><?php echo $block_count; ?></b> 시간대 묶음</span>
        </div>
    </section>

    <section class="hb-operation-stage">
        <article class="hb-card hb-player-card hb-operation-main-card">
            <div class="hb-card-head">
                <div>
                    <p class="hb-kicker">ON AIR</p>
                    <h2 id="hbNowTitle">공용 운영 대기 중</h2>
                </div>
                <span class="hb-pill" id="hbSoundState">대기</span>
            </div>
            <div class="hb-operation-sitewide-control">
                <div class="hb-operation-sitewide-copy">
                    <strong>사이트 전체 송출</strong>
                    <span><?php echo $sitewide_enabled ? ($sitewide_hook ? '연결 정상 · 현재 모드: '.hb_e($broadcast_mode_label).' · 페이지 이동 중에도 방문자 송출 상태는 서버에서 유지됩니다' : '활성화됨 · extend 연결 확인 필요') : '현재 꺼져 있음'; ?></span>
                </div>
                <div class="hb-operation-global-actions">
                    <?php if ($can_sitewide_control && $sitewide_enabled) { ?>
                    <form method="post" action="<?php echo HB_URL; ?>/admin/sitewide_control.php"><?php echo hb_csrf_field(); ?><input type="hidden" name="action" value="auto"><button type="submit" class="hb-btn hb-btn-small">자동 편성</button></form>
                    <form method="post" action="<?php echo HB_URL; ?>/admin/sitewide_control.php" onsubmit="return confirm('사이트 전체 재생을 정지할까요?');"><?php echo hb_csrf_field(); ?><input type="hidden" name="action" value="stop"><button type="submit" class="hb-btn hb-btn-small hb-danger">전체 정지</button></form>
                    <?php } ?>
                    <?php if ($can_sitewide_control) { ?><form method="post" action="<?php echo HB_URL; ?>/admin/sitewide_toggle.php"><?php echo hb_csrf_field(); ?>
                        <input type="hidden" name="enabled" value="<?php echo $sitewide_enabled ? '0' : '1'; ?>">
                        <button type="submit" class="hb-btn hb-btn-small <?php echo $sitewide_enabled ? 'hb-danger' : 'hb-btn-primary'; ?>"><?php echo $sitewide_enabled ? '전체 송출 끄기' : '전체 송출 켜기'; ?></button>
                    </form><?php } ?>
                </div>
            </div>
            <?php if ($can_sitewide_control && $sitewide_enabled && $broadcast['mode'] === 'manual' && !empty($broadcast['item'])) { ?>
            <div class="hb-broadcast-position">
                <div>
                    <strong>현재 전체 재생 · <?php echo hb_e($broadcast['item']['music_title']); ?></strong>
                    <span>페이지를 연 시점 기준 약 <?php echo number_format($broadcast_position, 1); ?>초 위치입니다.</span>
                </div>
                <form class="hb-inline-form hb-broadcast-seek-form" method="post" action="<?php echo HB_URL; ?>/admin/sitewide_control.php">
                    <?php echo hb_csrf_field(); ?>
                    <input type="hidden" name="action" value="play">
                    <input type="hidden" name="mf_id" value="<?php echo (int)$broadcast['item']['music_id']; ?>">
                    <label>재생 위치(초)<input type="number" name="seek_seconds" min="0" step="0.1" value="<?php echo hb_e(number_format($broadcast_position, 1, '.', '')); ?>"></label>
                    <button type="submit" class="hb-btn hb-btn-small hb-btn-primary">전체 재생 위치 이동</button>
                </form>
            </div>
            <?php } ?>
            <p class="hb-muted hb-operation-desc" id="hbNowDesc">공통 시간표와 공통 시간대 묶음만 불러옵니다.</p>
            <p class="hb-status-text hb-operation-status" id="hbStatusText">처음 한 번은 운영판 소리 켜기를 눌러주세요.</p>
            <p class="hb-policy-text hb-operation-policy" id="hbPolicyText">공용 운영판 정보를 불러오는 중입니다.</p>
            <div class="hb-operation-controls-panel">
                <button type="button" class="hb-btn hb-btn-primary" id="hbEnableSound">▶ 운영판 소리 켜기</button>
                <button type="button" class="hb-btn" id="hbTodayOff">× 오늘만 끄기</button>
                <button type="button" class="hb-btn" id="hbStopSound">■ 운영판만 정지</button>
            </div>
            <audio id="hbAudio" preload="auto" controls></audio>
            <div class="hb-youtube-wrap" id="hbYoutubeWrap" style="display:none"><div id="hbYouTubePlayer"></div></div>
            <div class="hb-volume-row hb-operation-volume-row">
                <label for="hbVolume">운영판 볼륨</label>
                <input type="range" min="0" max="100" value="80" id="hbVolume">
                <span id="hbVolumeText">80%</span>
            </div>
        </article>

        <aside class="hb-operation-side">
            <div class="hb-clock-card hb-operation-clock-card">
                <div class="hb-clock" id="hbClock">--:--:--</div>
                <div class="hb-next-label">다음 공용 음악까지</div>
                <div class="hb-countdown" id="hbCountdown">계산 중</div>
            </div>
            <article class="hb-card hb-operation-sidepanel">
                <div class="hb-card-head">
                    <div>
                        <p class="hb-kicker">CONTROL GUIDE</p>
                        <h2>현장 메모</h2>
                    </div>
                </div>
                <ul class="hb-operation-guide">
                    <li><b>전체 재생</b>은 서버 방송 상태를 바꿔 BGM을 허용한 방문자 기기에 같은 시작 시각으로 전달합니다.</li>
                    <li><b>운영판만 정지</b>와 <b>오늘만 끄기</b>는 현재 관리자 기기에만 적용됩니다.</li>
                    <li>브라우저 자동재생 정책 때문에 방문자는 최초 1회 BGM 켜기가 필요할 수 있습니다.</li>
                </ul>
            </article>
        </aside>
    </section>

    <section class="hb-card hb-operation-rundown-card">
        <div class="hb-card-head hb-operation-rundown-head">
            <div>
                <p class="hb-kicker">COMMON TODAY</p>
                <h2>오늘 공통 운영표</h2>
                <p class="hb-sub">오늘 실제 실행될 공통 시간표 / 시간대 묶음을 순서대로 보여줍니다.</p>
            </div>
            <div class="hb-actions">
                <span class="hb-pill"><?php echo count($today); ?>개</span>
                <?php if ($can_schedule_write) { ?><a class="hb-btn hb-btn-small" href="<?php echo HB_URL; ?>/admin/schedule_form.php">공통 시간 추가</a><?php } ?>
                <?php if ($can_block_write) { ?><a class="hb-btn hb-btn-small" href="<?php echo HB_URL; ?>/admin/block_form.php">공통 시간대 추가</a><?php } ?>
            </div>
        </div>
        <?php if (!$today) { ?>
            <div class="hb-empty">
                <div class="hb-empty-icon">■</div>
                <strong>오늘 등록된 공통 운영표가 없습니다</strong>
                <p>공통 시간표 또는 공통 시간대 묶음을 등록하면 관리자 운영판에 동일하게 표시됩니다.</p>
                <div class="hb-actions hb-actions-center">
                    <?php if ($can_schedule_write) { ?><a class="hb-btn hb-btn-small" href="<?php echo HB_URL; ?>/admin/schedule_form.php">공통 시간표 추가</a><?php } ?>
                    <?php if ($can_block_write) { ?><a class="hb-btn hb-btn-small" href="<?php echo HB_URL; ?>/admin/block_form.php">공통 시간대 추가</a><?php } ?>
                </div>
            </div>
        <?php } else { ?>
            <div class="hb-list hb-operation-rundown-list" id="hbTodayList">
                <?php foreach ($today as $entry) { $row = $entry['row']; ?>
                    <?php if ($entry['kind'] === 'single') { $hb_first = hb_schedule_effective_first_item($row['sc_id'], $row); $payload = $hb_first ? hb_music_item_payload($hb_first) : array('source'=>'file','url'=>'','youtube_id'=>''); $preview_items = hb_schedule_preview_items_attr($row['sc_id'], $row); ?>
                        <div class="hb-schedule-item" data-service-date="<?php echo hb_e($entry['service_date']); ?>" data-time="<?php echo hb_time_hm($row['sc_time']); ?>">
                            <div class="hb-time"><?php echo hb_time_hm($row['sc_time']); ?></div>
                            <div class="hb-item-main">
                                <strong><?php echo hb_e($row['sc_title']); ?></strong>
                                <span>공통 운영 · <?php echo hb_e($hb_first ? $hb_first['mf_title'] : '재생 가능한 음악 없음'); ?> · <?php echo $hb_first ? hb_music_source_label($hb_first) : '-'; ?> · 단일 재생</span>
                            </div>
                            <button type="button" class="hb-mini-play" data-items="<?php echo $preview_items; ?>" data-source="<?php echo hb_e($payload['source']); ?>" data-src="<?php echo hb_e($payload['url']); ?>" data-youtube-id="<?php echo hb_e($payload['youtube_id']); ?>" data-title="<?php echo hb_e($row['sc_title']); ?>" data-volume="<?php echo $hb_first ? (int)$hb_first['mf_volume'] : 80; ?>">미리듣기</button>
                            <?php if ($can_sitewide_control && $sitewide_enabled && $hb_first) { ?><form class="hb-inline-form" method="post" action="<?php echo HB_URL; ?>/admin/sitewide_control.php"><?php echo hb_csrf_field(); ?><input type="hidden" name="action" value="play"><input type="hidden" name="mf_id" value="<?php echo (int)$hb_first['mf_id']; ?>"><button type="submit" class="hb-btn hb-btn-small hb-btn-primary">전체 재생</button></form><?php } ?>
                        </div>
                    <?php } elseif ($entry['kind'] === 'range') { $hb_first = hb_schedule_effective_first_item($row['sc_id'], $row); $payload = $hb_first ? hb_music_item_payload($hb_first) : array('source'=>'file','url'=>'','youtube_id'=>''); $preview_items = hb_schedule_preview_items_attr($row['sc_id'], $row); ?>
                        <div class="hb-schedule-item hb-block-schedule" data-service-date="<?php echo hb_e($entry['service_date']); ?>" data-block-id="range_<?php echo (int)$row['sc_id']; ?>" data-block-kind="range" data-start="<?php echo hb_time_hm($row['sc_time']); ?>" data-end="<?php echo hb_time_hm($row['sc_end_time']); ?>">
                            <div class="hb-time"><?php echo hb_time_hm($row['sc_time']); ?><small>~<?php echo hb_time_hm($row['sc_end_time']); ?></small></div>
                            <div class="hb-item-main">
                                <strong><?php echo hb_e($row['sc_title']); ?></strong>
                                <span>공통 운영 · <?php echo hb_e($hb_first ? $hb_first['mf_title'] : '재생 가능한 음악 없음'); ?> · <?php echo $hb_first ? hb_music_source_label($hb_first) : '-'; ?> · 특정 시간 재생<?php echo $row['sc_repeat'] ? ' · 반복' : ' · 1회'; ?></span>
                                <em>시작~종료 시간 안에서만 재생됩니다.</em>
                            </div>
                            <button type="button" class="hb-mini-play" data-items="<?php echo $preview_items; ?>" data-source="<?php echo hb_e($payload['source']); ?>" data-src="<?php echo hb_e($payload['url']); ?>" data-youtube-id="<?php echo hb_e($payload['youtube_id']); ?>" data-title="<?php echo hb_e($row['sc_title']); ?>" data-volume="<?php echo $hb_first ? (int)$hb_first['mf_volume'] : 80; ?>">미리듣기</button>
                            <?php if ($can_sitewide_control && $sitewide_enabled && $hb_first) { ?><form class="hb-inline-form" method="post" action="<?php echo HB_URL; ?>/admin/sitewide_control.php"><?php echo hb_csrf_field(); ?><input type="hidden" name="action" value="play"><input type="hidden" name="mf_id" value="<?php echo (int)$hb_first['mf_id']; ?>"><button type="submit" class="hb-btn hb-btn-small hb-btn-primary">전체 재생</button></form><?php } ?>
                        </div>
                    <?php } else { $cnt = hb_block_item_count($row['bl_id']); ?>
                        <div class="hb-schedule-item hb-block-schedule" data-service-date="<?php echo hb_e($entry['service_date']); ?>" data-block-id="<?php echo (int)$row['bl_id']; ?>" data-block-kind="block" data-start="<?php echo hb_time_hm($row['bl_start_time']); ?>" data-end="<?php echo hb_time_hm($row['bl_end_time']); ?>">
                            <div class="hb-time"><?php echo hb_time_hm($row['bl_start_time']); ?><small>~<?php echo hb_time_hm($row['bl_end_time']); ?></small></div>
                            <div class="hb-item-main">
                                <strong><?php echo hb_e($row['bl_title']); ?></strong>
                                <span>공통 운영 · 시간대 묶음 · <?php echo $cnt; ?>곡 · <?php echo hb_play_mode_label($row['bl_play_mode']); ?><?php echo $row['bl_repeat'] ? ' · 반복' : ' · 1회'; ?></span>
                                <em><?php echo hb_e(hb_block_item_titles($row['bl_id'])); ?></em>
                            </div>
                            <button type="button" class="hb-mini-play" data-items="<?php echo hb_block_preview_items_attr($row['bl_id']); ?>" data-title="<?php echo hb_e($row['bl_title']); ?>" data-volume="80">미리듣기</button>
                            <?php if ($can_sitewide_control && $sitewide_enabled) { $hb_block_items_for_broadcast = hb_block_items($row['bl_id']); $hb_first_broadcast = $hb_block_items_for_broadcast ? $hb_block_items_for_broadcast[0] : null; if ($hb_first_broadcast) { ?><form class="hb-inline-form" method="post" action="<?php echo HB_URL; ?>/admin/sitewide_control.php"><?php echo hb_csrf_field(); ?><input type="hidden" name="action" value="play"><input type="hidden" name="mf_id" value="<?php echo (int)$hb_first_broadcast['mf_id']; ?>"><button type="submit" class="hb-btn hb-btn-small hb-btn-primary">첫 곡 전체 재생</button></form><?php } } ?>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>
        <?php } ?>
    </section>
</div></main></div>

<script>
window.HARU_BGM = {
    apiSchedule: <?php echo hb_json_encode(HB_URL.'/admin/api_operation_schedule.php'); ?>,
    apiBroadcast: <?php echo hb_json_encode(HB_URL.'/api_broadcast.php'); ?>,
    broadcastPollMs: 1500,
    apiLog: <?php echo hb_json_encode(HB_URL.'/api_log.php'); ?>,
    csrfToken: <?php echo hb_json_encode(hb_csrf_token()); ?>,
    standardToken: '',
    mode: 'admin_operation',
    persistAcrossPages: false,
    storagePrefix: 'haru_bgm_sitewide_'
};
</script>
<script src="<?php echo HB_URL; ?>/assets/haru_bgm.js?ver=<?php echo rawurlencode(HB_ASSET_VERSION); ?>"></script>
<?php
include_once(HB_PATH.'/admin/_tail.php');

