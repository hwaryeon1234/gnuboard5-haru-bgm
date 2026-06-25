<?php
include_once('./_common.php');
$g5['title'] = '하루브금 관리자';
$hb_haru_head_row_was_set = array_key_exists('row', get_defined_vars());
$hb_haru_head_row_backup = $hb_haru_head_row_was_set ? $row : null;
include_once(G5_PATH.'/head.php');
if ($hb_haru_head_row_was_set) {
    $row = $hb_haru_head_row_backup;
} else {
    unset($row);
}
unset($hb_haru_head_row_was_set, $hb_haru_head_row_backup);
$music = hb_table('music');
$schedule = hb_table('schedule');
$log = hb_table('play_log');
$block = hb_table('block');
$sequence = hb_table('sequence');
$member_access = hb_table('member_access');
$music_cnt = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$music}`");
$global_cnt = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$schedule}` WHERE sc_scope='global'");
$user_cnt = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$schedule}` WHERE sc_scope='user'");
$today_log = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$log}` WHERE DATE(pl_played_at)=CURDATE()", false);
$fail_log = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$log}` WHERE pl_status='fail' AND DATE(pl_played_at)=CURDATE()", false);
$block_cnt = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$block}` WHERE bl_scope='global'");
$seq_cnt = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$sequence}` WHERE seq_use=1", false);
$disabled_cnt = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$member_access}` WHERE ma_enabled=0", false);
$today_items = hb_today_operation_entries();
?>
<link rel="stylesheet" href="<?php echo HB_URL; ?>/assets/haru_bgm.css?ver=20260625-radiov3">
<div class="hb-app"><?php echo hb_nav_admin(); ?><main class="hb-app-main"><div class="hb-wrap hb-admin-dashboard-wrap">
    <section class="hb-page-head hb-dashboard-hero">
        <div>
            <p class="hb-kicker">ADMIN DASHBOARD</p>
            <h1>하루브금 관리자 대시보드</h1>
            <p>오늘 운영 현황, 전체 데이터 수, 자주 쓰는 작업 버튼을 한 화면에 모았습니다. 처음 들어와도 어디를 눌러야 하는지 바로 보이게 정리한 관리자 홈입니다.</p>
        </div>
        <div class="hb-actions">
            <a class="hb-btn hb-btn-primary" href="<?php echo HB_URL; ?>/admin/operation.php">공용 운영판 열기</a>
            <a class="hb-btn" href="<?php echo HB_URL; ?>/admin/today.php">오늘 운영표</a>
            <a class="hb-btn" href="<?php echo HB_URL; ?>/index.php">모드 선택</a>
        </div>
    </section>
    <section class="hb-dashboard-stats">
        <article class="hb-dashboard-stat is-primary"><span class="hb-kicker">TODAY</span><strong><?php echo count($today_items); ?></strong><em>오늘 공통 운영표</em></article>
        <article class="hb-dashboard-stat"><span class="hb-kicker">MUSIC</span><strong><?php echo (int)$music_cnt['cnt']; ?></strong><em>등록된 음악</em></article>
        <article class="hb-dashboard-stat"><span class="hb-kicker">GLOBAL</span><strong><?php echo (int)$global_cnt['cnt']; ?></strong><em>공통 시간표</em></article>
        <article class="hb-dashboard-stat"><span class="hb-kicker">BLOCK</span><strong><?php echo (int)$block_cnt['cnt']; ?></strong><em>공통 시간대 묶음</em></article>
        <article class="hb-dashboard-stat"><span class="hb-kicker">SEQUENCE</span><strong><?php echo (int)($seq_cnt['cnt'] ?? 0); ?></strong><em>사용중 순서표</em></article>
        <article class="hb-dashboard-stat"><span class="hb-kicker">LOG</span><strong><?php echo (int)($today_log['cnt'] ?? 0); ?></strong><em>오늘 재생 로그</em></article>
    </section>

    <section class="hb-dashboard-main">
        <div class="hb-dashboard-left">
            <article class="hb-card hb-dashboard-panel hb-dashboard-panel-hero">
                <div class="hb-card-head">
                    <div>
                        <p class="hb-kicker">QUICK START</p>
                        <h2>자주 쓰는 바로가기</h2>
                        <p class="hb-sub">관리자가 가장 자주 쓰는 작업만 큰 버튼으로 뽑았습니다.</p>
                    </div>
                </div>
                <div class="hb-dashboard-quick-grid">
                    <a class="hb-dashboard-quick is-strong" href="<?php echo HB_URL; ?>/admin/operation.php"><strong>공용 운영판</strong><span>현재 방송/재생 상태 확인 및 즉시 제어</span></a>
                    <a class="hb-dashboard-quick" href="<?php echo HB_URL; ?>/admin/today.php"><strong>오늘 운영표</strong><span>오늘 실행될 시간표만 빠르게 확인</span></a>
                    <a class="hb-dashboard-quick" href="<?php echo HB_URL; ?>/admin/music_form.php"><strong>음악 등록</strong><span>파일 업로드 또는 YouTube 링크 등록</span></a>
                    <a class="hb-dashboard-quick" href="<?php echo HB_URL; ?>/admin/schedule_form.php"><strong>공통 시간 추가</strong><span>정각 재생 / 특정 시간 재생 등록</span></a>
                    <a class="hb-dashboard-quick" href="<?php echo HB_URL; ?>/admin/block_form.php"><strong>공통 시간대 추가</strong><span>여러 곡을 시간대 단위로 묶어서 운영</span></a>
                    <a class="hb-dashboard-quick" href="<?php echo HB_URL; ?>/admin/sequence_form.php"><strong>순서표 추가</strong><span>예배·행사·방송용 수동 진행표 만들기</span></a>
                </div>
            </article>

            <div class="hb-admin-section-grid hb-dashboard-section-grid">
                <article class="hb-admin-section">
                    <p class="hb-kicker">OPERATION</p>
                    <h2>운영</h2>
                    <a href="<?php echo HB_URL; ?>/admin/operation.php">공용 운영판</a>
                    <a href="<?php echo HB_URL; ?>/admin/today.php">오늘 운영표</a>
                    <a href="<?php echo HB_URL; ?>/admin/sequence_list.php">순서표 모드 <?php echo (int)($seq_cnt['cnt'] ?? 0); ?>개</a>
                </article>
                <article class="hb-admin-section">
                    <p class="hb-kicker">MUSIC</p>
                    <h2>음악</h2>
                    <a href="<?php echo HB_URL; ?>/admin/music_list.php">음악 보관함 <?php echo (int)$music_cnt['cnt']; ?>개</a>
                    <a href="<?php echo HB_URL; ?>/admin/music_form.php">파일/YouTube 등록</a>
                </article>
                <article class="hb-admin-section">
                    <p class="hb-kicker">SCHEDULE</p>
                    <h2>시간표</h2>
                    <a href="<?php echo HB_URL; ?>/admin/schedule_global.php">공통 시간표 <?php echo (int)$global_cnt['cnt']; ?>개</a>
                    <a href="<?php echo HB_URL; ?>/admin/block_global.php">공통 시간대 <?php echo (int)$block_cnt['cnt']; ?>개</a>
                    <a href="<?php echo HB_URL; ?>/admin/user_schedule.php">개인 시간표 <?php echo (int)$user_cnt['cnt']; ?>개</a>
                </article>
                <article class="hb-admin-section">
                    <p class="hb-kicker">MEMBER</p>
                    <h2>회원</h2>
                    <a href="<?php echo HB_URL; ?>/admin/member_access.php">회원 사용설정 · 차단 <?php echo (int)($disabled_cnt['cnt'] ?? 0); ?>명</a>
                </article>
                <article class="hb-admin-section">
                    <p class="hb-kicker">SYSTEM</p>
                    <h2>시스템</h2>
                    <a href="<?php echo HB_URL; ?>/admin/logs.php">오늘 로그 <?php echo (int)($today_log['cnt'] ?? 0); ?>건 / 실패 <?php echo (int)($fail_log['cnt'] ?? 0); ?>건</a>
                    <a href="<?php echo HB_URL; ?>/admin/settings.php">환경설정</a>
                    <a href="<?php echo HB_URL; ?>/admin/health.php">설치 점검</a>
                </article>
            </div>
        </div>

        <aside class="hb-dashboard-right">
            <article class="hb-card hb-dashboard-sidepanel">
                <div class="hb-card-head">
                    <div>
                        <p class="hb-kicker">AT A GLANCE</p>
                        <h2>지금 한눈에</h2>
                    </div>
                </div>
                <ul class="hb-dashboard-glance">
                    <li><span>오늘 운영표</span><strong><?php echo count($today_items); ?>개</strong></li>
                    <li><span>오늘 재생 로그</span><strong><?php echo (int)($today_log['cnt'] ?? 0); ?>건</strong></li>
                    <li><span>오늘 실패 로그</span><strong><?php echo (int)($fail_log['cnt'] ?? 0); ?>건</strong></li>
                    <li><span>차단 회원</span><strong><?php echo (int)($disabled_cnt['cnt'] ?? 0); ?>명</strong></li>
                    <li><span>개인 시간표</span><strong><?php echo (int)$user_cnt['cnt']; ?>개</strong></li>
                </ul>
            </article>
            <article class="hb-card hb-dashboard-sidepanel">
                <div class="hb-card-head">
                    <div>
                        <p class="hb-kicker">RECOMMENDED FLOW</p>
                        <h2>추천 작업 순서</h2>
                    </div>
                </div>
                <ol class="hb-dashboard-flow">
                    <li>음악을 등록합니다.</li>
                    <li>공통 시간표 또는 공통 시간대를 만듭니다.</li>
                    <li>오늘 운영표에서 목록을 확인합니다.</li>
                    <li>공용 운영판에서 실제 재생 상태를 점검합니다.</li>
                </ol>
            </article>
        </aside>
    </section>
</div></main></div>
<?php include_once(G5_PATH.'/tail.php'); ?>
