<?php
include_once('./_common.php');
hb_require_member_bgm_enabled();
$g5['title'] = '하루브금 모드 선택';
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
$block = hb_table('block');
$music_cnt = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$music}` WHERE mf_use=1", false);
$global_cnt = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$schedule}` WHERE sc_scope='global' AND sc_use=1", false);
$user_cnt = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$schedule}` WHERE sc_scope='user' AND mb_id='".hb_escape($member['mb_id'])."' AND sc_use=1", false);
$block_global_cnt = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$block}` WHERE bl_scope='global' AND bl_use=1", false);
$block_user_cnt = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$block}` WHERE bl_scope='user' AND mb_id='".hb_escape($member['mb_id'])."' AND bl_use=1", false);
?>
<link rel="stylesheet" href="<?php echo HB_URL; ?>/assets/haru_bgm.css?ver=20260625-radiov5">

<div class="hb-wrap hb-radio hb-mode-wrap">
    <section class="hb-hero hb-mode-hero">
        <div>
            <p class="hb-kicker">MODE SELECT</p>
            <h1>하루브금</h1>
            <p class="hb-sub">어떤 화면으로 들어갈지 선택하세요. 관리자 계정은 공용 운영판으로 바로 들어갈 수 있고, 일반 회원은 내 BGM 화면을 사용할 수 있습니다.</p>
            <div class="hb-mode-user">
                <strong><?php echo hb_e($member['mb_nick'] ? $member['mb_nick'] : $member['mb_id']); ?>님</strong>
                <span><?php echo $is_admin ? '관리자 권한으로 접속 중' : '회원 전용 화면 이용 가능'; ?></span>
            </div>
        </div>
        <div class="hb-clock-card">
            <div class="hb-clock" id="hbModeClock">--:--:--</div>
            <div class="hb-next-label">바로가기</div>
            <div class="hb-countdown">모드 선택</div>
        </div>
    </section>

    <?php if ($is_admin) { ?>
    <section class="hb-mode-section">
        <div class="hb-card-head">
            <div><p class="hb-kicker">ADMIN</p><h2>관리자 공용 모드</h2></div>
            <span class="hb-pill">모든 관리자 동일 화면</span>
        </div>
        <div class="hb-mode-grid hb-mode-grid-admin">
            <a class="hb-mode-card hb-mode-card-primary" href="<?php echo HB_URL; ?>/admin/operation.php">
                <span class="hb-mode-icon">🎚️</span>
                <strong>공용 운영판</strong>
                <em>교회 · 방송 · 행사 진행용</em>
                <small>공통 시간표 <?php echo (int)($global_cnt['cnt'] ?? 0); ?>개 · 공통 시간대 <?php echo (int)($block_global_cnt['cnt'] ?? 0); ?>개</small>
            </a>
            <a class="hb-mode-card" href="<?php echo HB_URL; ?>/admin/index.php">
                <span class="hb-mode-icon">⚙️</span>
                <strong>관리자 홈</strong>
                <em>음악/시간표/설정 관리</em>
                <small>음악 <?php echo (int)($music_cnt['cnt'] ?? 0); ?>개 등록됨</small>
            </a>
            <a class="hb-mode-card" href="<?php echo HB_URL; ?>/admin/schedule_global.php">
                <span class="hb-mode-icon">📅</span>
                <strong>공통 시간표</strong>
                <em>특정 시간에 한 곡 재생</em>
                <small>정각/특정 시간 자동재생</small>
            </a>
            <a class="hb-mode-card" href="<?php echo HB_URL; ?>/admin/block_global.php">
                <span class="hb-mode-icon">🧩</span>
                <strong>공통 시간대 묶음</strong>
                <em>시간대 안에서 여러 곡 재생</em>
                <small>순서/랜덤/반복 재생</small>
            </a>
        </div>
    </section>
    <?php } ?>

    <section class="hb-mode-section">
        <div class="hb-card-head">
            <div><p class="hb-kicker">MEMBER</p><h2>회원용 개인 모드</h2></div>
            <span class="hb-pill">내 브라우저에서만 재생</span>
        </div>
        <div class="hb-mode-grid">
            <a class="hb-mode-card hb-mode-card-member" href="<?php echo HB_URL; ?>/player.php">
                <span class="hb-mode-icon">🎧</span>
                <strong>내 BGM 화면</strong>
                <em>공통 + 내 개인 시간표 재생</em>
                <small>내 시간표 <?php echo (int)($user_cnt['cnt'] ?? 0); ?>개 · 내 시간대 <?php echo (int)($block_user_cnt['cnt'] ?? 0); ?>개</small>
            </a>
            <a class="hb-mode-card" href="<?php echo HB_URL; ?>/my_schedule.php">
                <span class="hb-mode-icon">🕘</span>
                <strong>내 시간표</strong>
                <em>내 계정 전용 정각 재생</em>
                <small>개인 루틴 BGM 등록</small>
            </a>
            <a class="hb-mode-card" href="<?php echo HB_URL; ?>/my_blocks.php">
                <span class="hb-mode-icon">🔁</span>
                <strong>내 시간대 묶음</strong>
                <em>정해진 시간대에 여러 곡 재생</em>
                <small>작업/공부/휴식용</small>
            </a>
            <a class="hb-mode-card" href="<?php echo HB_URL; ?>/my_music_list.php">
                <span class="hb-mode-icon">🎵</span>
                <strong>내 음악 보관함</strong>
                <em>내가 등록한 파일/YouTube 링크 관리</em>
                <small>내가 등록한 음악만 보여요</small>
            </a>
            <?php if ($is_admin) { ?>
            <a class="hb-mode-card" href="<?php echo HB_URL; ?>/admin/music_list.php">
                <span class="hb-mode-icon">🎼</span>
                <strong>전체 음악 보관함 (관리자)</strong>
                <em>모든 회원의 음악 파일/YouTube 링크 관리</em>
                <small>최대 100개 목록 표시</small>
            </a>
            <?php } ?>
        </div>
    </section>

    <section class="hb-card hb-mode-note">
        <strong>접속 안내</strong>
        <p>다른 담당자는 <code><?php echo HB_URL; ?>/</code> 주소로 들어오면 이 모드 선택 화면을 볼 수 있습니다. 공용 운영판은 관리자 권한 계정에게만 표시됩니다.</p>
        <div class="hb-actions">
            <?php if ($is_admin) { ?><a class="hb-btn hb-btn-primary" href="<?php echo HB_URL; ?>/admin/operation.php">공용 운영판 바로 열기</a><?php } ?>
            <a class="hb-btn" href="<?php echo HB_URL; ?>/player.php">내 BGM 화면 열기</a>
        </div>
    </section>
</div>
<script>
(function(){
    var el = document.getElementById('hbModeClock');
    function tick(){
        if (!el) return;
        var d = new Date();
        var h = String(d.getHours()).padStart(2,'0');
        var m = String(d.getMinutes()).padStart(2,'0');
        var s = String(d.getSeconds()).padStart(2,'0');
        el.textContent = h + ':' + m + ':' + s;
    }
    tick();
    setInterval(tick, 1000);
})();
</script>
<?php
include_once(G5_PATH.'/tail.php');
?>
