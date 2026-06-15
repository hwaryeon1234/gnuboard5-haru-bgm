<?php
include_once('./_common.php');
$g5['title'] = '하루브금 관리자';
include_once(G5_PATH.'/head.php');
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
?>
<link rel="stylesheet" href="<?php echo HB_URL; ?>/assets/haru_bgm.css?ver=20260616g">
<div class="hb-wrap">
    <section class="hb-page-head">
        <div><p class="hb-kicker">ADMIN</p><h1>하루브금 관리자</h1><p>운영/음악/시간표/회원/시스템을 나눠 관리합니다. 처음 온 담당자도 여기서 바로 들어가면 됩니다.</p></div>
        <div class="hb-actions"><a class="hb-btn hb-btn-primary" href="<?php echo HB_URL; ?>/admin/operation.php">공용 운영판</a><a class="hb-btn" href="<?php echo HB_URL; ?>/admin/today.php">오늘 운영표</a><a class="hb-btn" href="<?php echo HB_URL; ?>/index.php">모드 선택</a></div>
    </section>
    <?php echo hb_nav_admin(); ?>
    <section class="hb-admin-section-grid">
        <article class="hb-admin-section"><p class="hb-kicker">OPERATION</p><h2>운영</h2><a href="<?php echo HB_URL; ?>/admin/operation.php">공용 운영판</a><a href="<?php echo HB_URL; ?>/admin/today.php">오늘 운영표</a><a href="<?php echo HB_URL; ?>/admin/sequence_list.php">순서표 모드</a></article>
        <article class="hb-admin-section"><p class="hb-kicker">MUSIC</p><h2>음악</h2><a href="<?php echo HB_URL; ?>/admin/music_list.php">음악 보관함 <?php echo (int)$music_cnt['cnt']; ?>개</a><a href="<?php echo HB_URL; ?>/admin/music_form.php">파일/YouTube 등록</a></article>
        <article class="hb-admin-section"><p class="hb-kicker">SCHEDULE</p><h2>시간표</h2><a href="<?php echo HB_URL; ?>/admin/schedule_global.php">공통 시간표 <?php echo (int)$global_cnt['cnt']; ?>개</a><a href="<?php echo HB_URL; ?>/admin/block_global.php">공통 시간대 <?php echo (int)$block_cnt['cnt']; ?>개</a><a href="<?php echo HB_URL; ?>/admin/user_schedule.php">개인 시간표 <?php echo (int)$user_cnt['cnt']; ?>개</a></article>
        <article class="hb-admin-section"><p class="hb-kicker">MEMBER</p><h2>회원</h2><a href="<?php echo HB_URL; ?>/admin/member_access.php">회원 사용설정 · 차단 <?php echo (int)($disabled_cnt['cnt'] ?? 0); ?>명</a></article>
        <article class="hb-admin-section"><p class="hb-kicker">SYSTEM</p><h2>시스템</h2><a href="<?php echo HB_URL; ?>/admin/logs.php">오늘 로그 <?php echo (int)($today_log['cnt'] ?? 0); ?>건 / 실패 <?php echo (int)($fail_log['cnt'] ?? 0); ?>건</a><a href="<?php echo HB_URL; ?>/admin/settings.php">환경설정</a><a href="<?php echo HB_URL; ?>/admin/health.php">설치 점검</a></article>
    </section>
</div>
<?php include_once(G5_PATH.'/tail.php'); ?>
