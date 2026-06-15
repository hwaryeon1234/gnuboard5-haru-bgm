<?php
include_once('./_common.php');
$g5['title'] = '하루브금 공용 운영판';
include_once(G5_PATH.'/head.php');

$today = hb_today_operation_entries();
?>
<link rel="stylesheet" href="<?php echo HB_URL; ?>/assets/haru_bgm.css?ver=20260616e">

<div class="hb-wrap" data-hb-url="<?php echo HB_URL; ?>" data-hb-mode="admin-operation">
    <section class="hb-page-head">
        <div>
            <p class="hb-kicker">ADMIN OPERATION</p>
            <h1>공용 운영판</h1>
            <p>교회 · 방송 · 행사 진행자가 함께 보는 관리자 전용 화면입니다. 관리자 계정은 모두 같은 공통 시간표와 공통 시간대 묶음을 봅니다.</p>
        </div>
        <div class="hb-actions">
            <a class="hb-btn" href="<?php echo HB_URL; ?>/index.php">모드 선택</a>
            <a class="hb-btn" href="<?php echo HB_URL; ?>/admin/index.php">관리자 홈</a>
            <a class="hb-btn" href="<?php echo HB_URL; ?>/admin/today.php">오늘 운영표</a>
            <a class="hb-btn" href="<?php echo HB_URL; ?>/admin/sequence_list.php">순서표 모드</a>
            <a class="hb-btn" href="<?php echo HB_URL; ?>/admin/schedule_global.php">공통 시간표</a>
            <a class="hb-btn" href="<?php echo HB_URL; ?>/admin/block_global.php">공통 시간대</a>
        </div>
    </section>
    <?php echo hb_nav_admin(); ?>

    <section class="hb-hero hb-hero-admin">
        <div>
            <p class="hb-kicker">공용 방송/진행 모드</p>
            <h1>하루브금 운영판</h1>
            <p class="hb-sub">개인 시간표는 섞지 않고, 관리자 공통 데이터만 재생합니다. 소리는 각 담당자의 브라우저에서만 나며, 볼륨/오늘만 끄기는 기기별로 저장됩니다.</p>
            <div class="hb-actions hb-operation-controls">
                <button type="button" class="hb-btn hb-btn-primary" id="hbEnableSound">🔊 운영판 소리 켜기</button>
                <button type="button" class="hb-btn" id="hbTodayOff">🌙 오늘만 끄기</button>
                <button type="button" class="hb-btn" id="hbStopSound">■ 정지</button>
            </div>
        </div>
        <div class="hb-clock-card">
            <div class="hb-clock" id="hbClock">--:--:--</div>
            <div class="hb-next-label">다음 공용 음악까지</div>
            <div class="hb-countdown" id="hbCountdown">계산 중</div>
        </div>
    </section>

    <section class="hb-now-grid">
        <article class="hb-card hb-player-card">
            <div class="hb-card-head">
                <div>
                    <p class="hb-kicker">ON AIR</p>
                    <h2 id="hbNowTitle">공용 운영 대기 중</h2>
                </div>
                <span class="hb-pill" id="hbSoundState">대기</span>
            </div>
            <p class="hb-muted" id="hbNowDesc">공통 시간표와 공통 시간대 묶음만 불러옵니다.</p>
            <p class="hb-status-text" id="hbStatusText">처음 한 번은 운영판 소리 켜기를 눌러주세요.</p>
            <p class="hb-policy-text" id="hbPolicyText">공용 운영판 정보를 불러오는 중입니다.</p>
            <audio id="hbAudio" preload="auto" controls></audio>
            <div class="hb-youtube-wrap" id="hbYoutubeWrap" style="display:none"><div id="hbYouTubePlayer"></div></div>
            <div class="hb-volume-row">
                <label for="hbVolume">운영판 볼륨</label>
                <input type="range" min="0" max="100" value="80" id="hbVolume">
                <span id="hbVolumeText">80%</span>
            </div>
        </article>

        <article class="hb-card">
            <div class="hb-card-head">
                <div>
                    <p class="hb-kicker">COMMON TODAY</p>
                    <h2>오늘 공통 운영표</h2>
                </div>
                <span class="hb-pill"><?php echo count($today); ?>개</span>
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
                <div class="hb-list" id="hbTodayList">
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
        </article>
    </section>
</div>

<script>
window.HARU_BGM = {
    apiSchedule: '<?php echo HB_URL; ?>/admin/api_operation_schedule.php',
    apiLog: '<?php echo HB_URL; ?>/api_log.php',
    memberId: '<?php echo hb_e($member['mb_id']); ?>',
    mode: 'admin_operation',
    storagePrefix: 'haru_bgm_admin_'
};
</script>
<script src="<?php echo HB_URL; ?>/assets/haru_bgm.js?ver=20260616e"></script>
<?php
include_once(G5_PATH.'/tail.php');
?>
