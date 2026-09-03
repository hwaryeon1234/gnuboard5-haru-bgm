<?php
include_once('./_common.php');
$g5['title'] = '하루BGM 관리자';
$music = hb_table('music');
$schedule = hb_table('schedule');
$log = hb_table('play_log');
$block = hb_table('block');
$sequence = hb_table('sequence');
$hb_clock = hb_site_clock_parts();
$hb_today_start = hb_escape($hb_clock['today'].' 00:00:00');
$hb_tomorrow_start = hb_escape(hb_server_date_add($hb_clock['today'], 1).' 00:00:00');
$music_cnt = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$music}`", false) ?: array('cnt'=>0);
$global_cnt = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$schedule}` WHERE sc_scope='global'", false) ?: array('cnt'=>0);
$today_log = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$log}` WHERE sc_scope IN ('global','global_block','preview','preview_block','sequence','broadcast') AND pl_played_at>='{$hb_today_start}' AND pl_played_at<'{$hb_tomorrow_start}'", false);
$fail_log = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$log}` WHERE sc_scope IN ('global','global_block','preview','preview_block','sequence','broadcast') AND pl_status='fail' AND pl_played_at>='{$hb_today_start}' AND pl_played_at<'{$hb_tomorrow_start}'", false);
$block_cnt = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$block}` WHERE bl_scope='global'", false) ?: array('cnt'=>0);
$seq_cnt = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$sequence}` WHERE seq_use=1", false);
$today_items = hb_today_operation_entries();
$sitewide_enabled = hb_sitewide_enabled();
$sitewide_hook = hb_sitewide_hook_installed();
$can_operation_read = hb_user_has_admin_auth('990110', 'r');
$can_music_write = hb_user_has_admin_auth('990130', 'w');
$can_schedule_write = hb_user_has_admin_auth('990140', 'w');
$can_block_write = hb_user_has_admin_auth('990150', 'w');
$can_settings_read = hb_user_has_admin_auth('990190', 'r');
include_once(HB_PATH.'/admin/_head.php');
?>
<div id="haruBgmAdminApp" class="hb-app"><?php echo hb_nav_admin(); ?><main class="hb-app-main"><div class="hb-wrap hb-admin-dashboard-wrap">
    <section class="hb-dashboard-top">
        <div>
            <p class="hb-kicker">HARU BGM</p>
            <h1>오늘의 방송</h1>
            <p>편성 확인부터 즉시 재생까지 여기서 시작합니다.</p>
        </div>
        <div class="hb-dashboard-status <?php echo $sitewide_enabled && $sitewide_hook ? 'is-live' : ''; ?>">
            <span class="hb-live-dot"></span>
            <div><strong>사이트 전체 방송</strong><small><?php echo $sitewide_enabled ? ($sitewide_hook ? '활성화됨' : '연결 확인 필요') : '꺼짐'; ?></small></div>
            <?php if ($can_settings_read) { ?><a href="<?php echo HB_URL; ?>/admin/settings.php">설정</a><?php } ?>
        </div>
    </section>

    <section class="hb-dashboard-stats hb-dashboard-stats-clean">
        <article class="hb-dashboard-stat"><i class="hb-stat-badge"><?php echo hb_nav_icon_svg_tag('calendar'); ?></i><div class="hb-dashboard-stat-body"><span>오늘 편성</span><strong><?php echo count($today_items); ?></strong><em>개</em></div></article>
        <article class="hb-dashboard-stat"><i class="hb-stat-badge"><?php echo hb_nav_icon_svg_tag('music'); ?></i><div class="hb-dashboard-stat-body"><span>등록 음악</span><strong><?php echo (int)$music_cnt['cnt']; ?></strong><em>곡</em></div></article>
        <article class="hb-dashboard-stat"><i class="hb-stat-badge"><?php echo hb_nav_icon_svg_tag('layers'); ?></i><div class="hb-dashboard-stat-body"><span>공통 시간표</span><strong><?php echo (int)$global_cnt['cnt']; ?></strong><em>개</em></div></article>
        <article class="hb-dashboard-stat"><i class="hb-stat-badge"><?php echo hb_nav_icon_svg_tag('broadcast'); ?></i><div class="hb-dashboard-stat-body"><span>오늘 재생</span><strong><?php echo (int)($today_log['cnt'] ?? 0); ?></strong><em>건</em></div></article>
    </section>

    <section class="hb-dashboard-clean-grid">
        <article class="hb-card hb-dashboard-command">
            <div class="hb-card-head"><div><p class="hb-kicker">QUICK ACTION</p><h2>바로 실행</h2></div><?php if ($can_operation_read) { ?><a class="hb-text-link" href="<?php echo HB_URL; ?>/admin/operation.php">운영판 열기 →</a><?php } ?></div>
            <div class="hb-dashboard-command-grid">
                <?php if ($can_operation_read) { ?><a href="<?php echo HB_URL; ?>/admin/operation.php"><span class="hb-command-icon hb-command-icon-pink"><?php echo hb_nav_icon_svg_tag('broadcast'); ?></span><strong>공용 운영판</strong><small>현재 방송 확인 · 즉시 재생</small></a><?php } ?>
                <?php if ($can_music_write) { ?><a href="<?php echo HB_URL; ?>/admin/music_form.php"><span class="hb-command-icon hb-command-icon-blue"><?php echo hb_nav_icon_svg_tag('music'); ?></span><strong>음악 등록</strong><small>파일 · YouTube</small></a><?php } ?>
                <?php if ($can_schedule_write) { ?><a href="<?php echo HB_URL; ?>/admin/schedule_form.php"><span class="hb-command-icon hb-command-icon-green"><?php echo hb_nav_icon_svg_tag('plus'); ?></span><strong>시간표 추가</strong><small>정각 · 시간 범위</small></a><?php } ?>
                <?php if ($can_block_write) { ?><a href="<?php echo HB_URL; ?>/admin/block_form.php"><span class="hb-command-icon hb-command-icon-peach"><?php echo hb_nav_icon_svg_tag('layers'); ?></span><strong>시간대 추가</strong><small>여러 곡 묶음 방송</small></a><?php } ?>
            </div>
        </article>

        <aside class="hb-card hb-dashboard-summary">
            <div class="hb-card-head"><div><p class="hb-kicker">STATUS</p><h2>운영 상태</h2></div></div>
            <ul>
                <li><span>공통 시간대</span><strong><?php echo (int)$block_cnt['cnt']; ?>개</strong></li>
                <li><span>순서표</span><strong><?php echo (int)($seq_cnt['cnt'] ?? 0); ?>개</strong></li>
                <li><span>오늘 실패</span><strong class="<?php echo (int)($fail_log['cnt'] ?? 0) > 0 ? 'is-danger' : ''; ?>"><?php echo (int)($fail_log['cnt'] ?? 0); ?>건</strong></li>
                <li><span>전체 방송</span><strong><?php echo $sitewide_enabled ? 'ON' : 'OFF'; ?></strong></li>
            </ul>
        </aside>
    </section>

</div></main></div>
<?php include_once(HB_PATH.'/admin/_tail.php'); 
