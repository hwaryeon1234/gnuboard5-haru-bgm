<?php
include_once('./_common.php');
$g5['title'] = '하루브금 공용 운영판';
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
$single_count = 0;
$range_count = 0;
$block_count = 0;
foreach ($today as $entry) {
    if ($entry['kind'] === 'single') $single_count++;
    elseif ($entry['kind'] === 'range') $range_count++;
    else $block_count++;
}
?>
<link rel="stylesheet" href="<?php echo HB_URL; ?>/assets/haru_bgm.css?ver=20260625-radiov2">

<div class="hb-app"><?php echo hb_nav_admin(); ?><main class="hb-app-main"><div class="hb-wrap hb-operation-page" data-hb-url="<?php echo HB_URL; ?>" data-hb-mode="admin-operation">
    <section class="hb-page-head hb-operation-head">
        <div>
            <p class="hb-kicker">ADMIN OPERATION</p>
            <h1>공용 운영판</h1>
            <p>방송실 느낌으로 더 크게 정리한 운영 전용 화면입니다. 현재 재생 상태, 즉시 제어, 오늘 공통 운영표를 한눈에 보면서 바로 조작할 수 있습니다.</p>
        </div>
        <div class="hb-actions">
            <a class="hb-btn" href="<?php echo HB_URL; ?>/admin/index.php">관리자 홈</a>
            <a class="hb-btn" href="<?php echo HB_URL; ?>/admin/today.php">오늘 운영표</a>
            <a class="hb-btn" href="<?php echo HB_URL; ?>/admin/sequence_list.php">순서표 모드</a>
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
            <p class="hb-muted hb-operation-desc" id="hbNowDesc">공통 시간표와 공통 시간대 묶음만 불러옵니다.</p>
            <p class="hb-status-text hb-operation-status" id="hbStatusText">처음 한 번은 운영판 소리 켜기를 눌러주세요.</p>
            <p class="hb-policy-text hb-operation-policy" id="hbPolicyText">공용 운영판 정보를 불러오는 중입니다.</p>
            <div class="hb-operation-controls-panel">
                <button type="button" class="hb-btn hb-btn-primary" id="hbEnableSound">🔊 운영판 소리 켜기</button>
                <button type="button" class="hb-btn" id="hbTodayOff">🌙 오늘만 끄기</button>
                <button type="button" class="hb-btn" id="hbStopSound">■ 정지</button>
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
                    <li>소리는 이 브라우저에서만 나옵니다.</li>
                    <li>오늘만 끄기는 현재 기기에서만 적용됩니다.</li>
                    <li>미리듣기는 아래 오늘 운영표에서 바로 테스트할 수 있습니다.</li>
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
                <a class="hb-btn hb-btn-small" href="<?php echo HB_URL; ?>/admin/schedule_form.php">공통 시간 추가</a>
                <a class="hb-btn hb-btn-small" href="<?php echo HB_URL; ?>/admin/block_form.php">공통 시간대 추가</a>
            </div>
        </div>
        <?php if (!$today) { ?>
            <div class="hb-empty">
                <div class="hb-empty-icon">🎚️</div>
                <strong>오늘 등록된 공통 운영표가 없습니다</strong>
                <p>공통 시간표 또는 공통 시간대 묶음을 등록하면 관리자 운영판에 동일하게 표시됩니다.</p>
                <div class="hb-actions hb-actions-center">
                    <a class="hb-btn hb-btn-small" href="<?php echo HB_URL; ?>/admin/schedule_form.php">공통 시간표 추가</a>
                    <a class="hb-btn hb-btn-small" href="<?php echo HB_URL; ?>/admin/block_form.php">공통 시간대 추가</a>
                </div>
            </div>
        <?php } else { ?>
            <div class="hb-list hb-operation-rundown-list" id="hbTodayList">
                <?php foreach ($today as $entry) { $row = $entry['row']; ?>
                    <?php if ($entry['kind'] === 'single') { ?>
                        <div class="hb-schedule-item" data-time="<?php echo hb_time_hm($row['sc_time']); ?>">
                            <div class="hb-time"><?php echo hb_time_hm($row['sc_time']); ?></div>
                            <div class="hb-item-main">
                                <strong><?php echo hb_e($row['sc_title']); ?></strong>
                                <span>공통 운영 · <?php echo hb_e($row['mf_title']); ?> · <?php echo hb_music_source_label($row); ?> · 단일 재생</span>
                            </div>
                            <?php $payload = hb_music_item_payload($row); $preview_items = hb_schedule_preview_items_attr($row['sc_id'], $row); ?>
                            <button type="button" class="hb-mini-play" data-items="<?php echo $preview_items; ?>" data-source="<?php echo hb_e($payload['source']); ?>" data-src="<?php echo hb_e($payload['url']); ?>" data-youtube-id="<?php echo hb_e($payload['youtube_id']); ?>" data-title="<?php echo hb_e($row['sc_title']); ?>" data-volume="<?php echo (int)$row['mf_volume']; ?>">미리듣기</button>
                        </div>
                    <?php } elseif ($entry['kind'] === 'range') { ?>
                        <div class="hb-schedule-item hb-block-schedule" data-start="<?php echo hb_time_hm($row['sc_time']); ?>" data-end="<?php echo hb_time_hm($row['sc_end_time']); ?>">
                            <div class="hb-time"><?php echo hb_time_hm($row['sc_time']); ?><small>~<?php echo hb_time_hm($row['sc_end_time']); ?></small></div>
                            <div class="hb-item-main">
                                <strong><?php echo hb_e($row['sc_title']); ?></strong>
                                <span>공통 운영 · <?php echo hb_e($row['mf_title']); ?> · <?php echo hb_music_source_label($row); ?> · 특정 시간 재생<?php echo $row['sc_repeat'] ? ' · 반복' : ' · 1회'; ?></span>
                                <em>시작~종료 시간 안에서만 재생됩니다.</em>
                            </div>
                            <?php $payload = hb_music_item_payload($row); $preview_items = hb_schedule_preview_items_attr($row['sc_id'], $row); ?>
                            <button type="button" class="hb-mini-play" data-items="<?php echo $preview_items; ?>" data-source="<?php echo hb_e($payload['source']); ?>" data-src="<?php echo hb_e($payload['url']); ?>" data-youtube-id="<?php echo hb_e($payload['youtube_id']); ?>" data-title="<?php echo hb_e($row['sc_title']); ?>" data-volume="<?php echo (int)$row['mf_volume']; ?>">미리듣기</button>
                        </div>
                    <?php } else { $cnt = hb_block_item_count($row['bl_id']); ?>
                        <div class="hb-schedule-item hb-block-schedule" data-start="<?php echo hb_time_hm($row['bl_start_time']); ?>" data-end="<?php echo hb_time_hm($row['bl_end_time']); ?>">
                            <div class="hb-time"><?php echo hb_time_hm($row['bl_start_time']); ?><small>~<?php echo hb_time_hm($row['bl_end_time']); ?></small></div>
                            <div class="hb-item-main">
                                <strong><?php echo hb_e($row['bl_title']); ?></strong>
                                <span>공통 운영 · 시간대 묶음 · <?php echo $cnt; ?>곡 · <?php echo hb_play_mode_label($row['bl_play_mode']); ?><?php echo $row['bl_repeat'] ? ' · 반복' : ' · 1회'; ?></span>
                                <em><?php echo hb_e(hb_block_item_titles($row['bl_id'])); ?></em>
                            </div>
                            <button type="button" class="hb-mini-play" data-items="<?php echo hb_block_preview_items_attr($row['bl_id']); ?>" data-title="<?php echo hb_e($row['bl_title']); ?>" data-volume="80">미리듣기</button>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>
        <?php } ?>
    </section>
</div></main></div>

<script>
window.HARU_BGM = {
    apiSchedule: '<?php echo HB_URL; ?>/admin/api_operation_schedule.php',
    apiLog: '<?php echo HB_URL; ?>/api_log.php',
    memberId: '<?php echo hb_e($member['mb_id']); ?>',
    mode: 'admin_operation',
    storagePrefix: 'haru_bgm_admin_'
};
</script>
<script src="<?php echo HB_URL; ?>/assets/haru_bgm.js?ver=20260625-radiov2"></script>
<?php
include_once(G5_PATH.'/tail.php');
?>
