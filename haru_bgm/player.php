<?php
include_once('./_common.php');
hb_require_member_bgm_enabled();
$g5['title'] = '하루브금';
$hb_haru_head_row_was_set = array_key_exists('row', get_defined_vars());
$hb_haru_head_row_backup = $hb_haru_head_row_was_set ? $row : null;
include_once(G5_PATH.'/head.php');
if ($hb_haru_head_row_was_set) {
    $row = $hb_haru_head_row_backup;
} else {
    unset($row);
}
unset($hb_haru_head_row_was_set, $hb_haru_head_row_backup);

$today = array();
$res = sql_query(hb_schedule_query($member['mb_id'], true));
while ($row = sql_fetch_array($res)) {
    $today[] = array('kind'=>hb_schedule_is_range($row) ? 'range' : 'single', 'start'=>hb_time_hm($row['sc_time']), 'row'=>$row);
}
$bres = sql_query(hb_block_query($member['mb_id'], true));
while ($row = sql_fetch_array($bres)) {
    if (hb_block_item_count($row['bl_id']) < 1) continue;
    $today[] = array('kind'=>'block', 'start'=>hb_time_hm($row['bl_start_time']), 'row'=>$row);
}
usort($today, function($a, $b) {
    if ($a['start'] === $b['start']) return 0;
    return $a['start'] < $b['start'] ? -1 : 1;
});
?>
<link rel="stylesheet" href="<?php echo HB_URL; ?>/assets/haru_bgm.css?ver=20260625-radiov2">

<div class="hb-wrap hb-radio" data-hb-url="<?php echo HB_URL; ?>">
    <section class="hb-radio-station">
        <div class="hb-radio-station-info">
            <p class="hb-radio-eyebrow">하루BGM 방송중</p>
            <h1>하루BGM</h1>
            <p class="hb-radio-station-desc">정해둔 시간마다 오늘의 BGM이 톡 하고 재생돼요. 파일 음악과 YouTube 링크, 시간대 묶음까지 내 브라우저에서만 재생됩니다.</p>
        </div>
        <div class="hb-clock-card hb-radio-clock">
            <div class="hb-clock" id="hbClock">--:--:--</div>
            <div class="hb-next-label">다음 음악까지</div>
            <div class="hb-countdown" id="hbCountdown">계산 중</div>
        </div>
    </section>

    <section class="hb-radio-onair">
        <div class="hb-radio-onair-badge"><span class="hb-radio-dot"></span>ON AIR</div>
        <h2 id="hbNowTitle">아직 재생 전이에요</h2>
        <p class="hb-muted" id="hbNowDesc">오늘 시간표를 확인하고, 시간이 되면 자동으로 재생합니다.</p>
        <p class="hb-status-text" id="hbStatusText">처음 한 번은 음악 알림 켜기를 눌러주세요.</p>
        <p class="hb-policy-text" id="hbPolicyText">우선순위 정보를 불러오는 중입니다.</p>

        <div class="hb-radio-controls">
            <button type="button" class="hb-btn hb-btn-primary" id="hbEnableSound">🔊 음악 알림 켜기</button>
            <button type="button" class="hb-btn" id="hbTodayOff">🌙 오늘만 끄기</button>
            <button type="button" class="hb-btn" id="hbStopSound">■ 정지</button>
        </div>
        <audio id="hbAudio" preload="auto" controls></audio>
        <div class="hb-youtube-wrap" id="hbYoutubeWrap" style="display:none"><div id="hbYouTubePlayer"></div></div>
        <div class="hb-volume-row">
            <label for="hbVolume">기본 볼륨</label>
            <input type="range" min="0" max="100" value="80" id="hbVolume">
            <span id="hbVolumeText">80%</span>
        </div>

        <div class="hb-radio-links">
            <a href="<?php echo HB_URL; ?>/my_schedule.php">내 시간표</a>
            <a href="<?php echo HB_URL; ?>/my_blocks.php">내 시간대</a>
            <a href="<?php echo HB_URL; ?>/index.php">모드 선택</a>
            <?php if ($is_admin) { ?><a href="<?php echo HB_URL; ?>/admin/index.php">관리자</a><?php } ?>
        </div>
    </section>

    <section class="hb-card hb-radio-guide-card">
        <div class="hb-card-head">
            <div>
                <p class="hb-kicker">TODAY ON AIR</p>
                <h2>오늘 편성표</h2>
            </div>
            <span class="hb-pill"><?php echo count($today); ?>개</span>
        </div>
        <?php if (!$today) { ?>
            <div class="hb-empty">
                <div class="hb-empty-icon">🎧</div>
                <strong>오늘 재생될 음악이 없어요</strong>
                <p>관리자 공통 시간표나 내 개인 시간표를 등록하면 여기에 표시됩니다.</p>
                <a class="hb-btn hb-btn-small" href="<?php echo HB_URL; ?>/my_schedule.php">내 시간표 만들기</a>
            </div>
        <?php } else { ?>
            <div class="hb-list hb-radio-guide-list" id="hbTodayList">
                <?php foreach ($today as $entry) { $row = $entry['row']; ?>
                    <?php if ($entry['kind'] === 'single') { ?>
                        <div class="hb-schedule-item" data-time="<?php echo hb_time_hm($row['sc_time']); ?>">
                            <div class="hb-time"><?php echo hb_time_hm($row['sc_time']); ?></div>
                            <div class="hb-item-main">
                                <strong><?php echo hb_e($row['sc_title']); ?></strong>
                                <span><?php echo hb_e($row['mf_title']); ?> · <?php echo hb_scope_label($row['sc_scope']); ?> · <?php echo hb_music_source_label($row); ?> · 단일 재생</span>
                            </div>
                            <?php $payload = hb_music_item_payload($row); $preview_items = hb_schedule_preview_items_attr($row['sc_id'], $row); ?>
                            <button type="button" class="hb-mini-play" data-items="<?php echo $preview_items; ?>" data-source="<?php echo hb_e($payload['source']); ?>" data-src="<?php echo hb_e($payload['url']); ?>" data-youtube-id="<?php echo hb_e($payload['youtube_id']); ?>" data-title="<?php echo hb_e($row['sc_title']); ?>" data-volume="<?php echo (int)$row['mf_volume']; ?>">미리듣기</button>
                        </div>
                    <?php } elseif ($entry['kind'] === 'range') { ?>
                        <div class="hb-schedule-item hb-block-schedule" data-start="<?php echo hb_time_hm($row['sc_time']); ?>" data-end="<?php echo hb_time_hm($row['sc_end_time']); ?>">
                            <div class="hb-time"><?php echo hb_time_hm($row['sc_time']); ?><small>~<?php echo hb_time_hm($row['sc_end_time']); ?></small></div>
                            <div class="hb-item-main">
                                <strong><?php echo hb_e($row['sc_title']); ?></strong>
                                <span><?php echo hb_e($row['mf_title']); ?> · <?php echo hb_scope_label($row['sc_scope']); ?> · <?php echo hb_music_source_label($row); ?> · 특정 시간 재생<?php echo $row['sc_repeat'] ? ' · 반복' : ' · 1회'; ?></span>
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
                                <span><?php echo hb_scope_label($row['bl_scope']); ?> · 시간대 묶음 · <?php echo $cnt; ?>곡 · <?php echo hb_play_mode_label($row['bl_play_mode']); ?><?php echo $row['bl_repeat'] ? ' · 반복' : ' · 1회'; ?></span>
                                <em><?php echo hb_e(hb_block_item_titles($row['bl_id'])); ?></em>
                            </div>
                            <button type="button" class="hb-mini-play" data-items="<?php echo hb_block_preview_items_attr($row['bl_id']); ?>" data-title="<?php echo hb_e($row['bl_title']); ?>" data-volume="80">미리듣기</button>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>
        <?php } ?>
    </section>
</div>

<script>
window.HARU_BGM = {
    apiSchedule: '<?php echo HB_URL; ?>/api_schedule.php',
    apiLog: '<?php echo HB_URL; ?>/api_log.php',
    memberId: '<?php echo hb_e($member['mb_id']); ?>'
};
</script>
<script src="<?php echo HB_URL; ?>/assets/haru_bgm.js?ver=20260625-radiov2"></script>
<?php
include_once(G5_PATH.'/tail.php');
?>
