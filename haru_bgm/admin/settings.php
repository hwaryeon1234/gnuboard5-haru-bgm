<?php
include_once('./_common.php');
$g5['title'] = '하루브금 환경설정';
$priority_allow = array('user_first','global_first','single_first','block_first');
$end_allow = array('fade_stop','finish_current');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $priority = isset($_POST['priority_mode']) && in_array($_POST['priority_mode'], $priority_allow, true) ? $_POST['priority_mode'] : 'user_first';
    $window = isset($_POST['single_window_seconds']) ? max(30, min(600, (int)$_POST['single_window_seconds'])) : 90;
    $fade = isset($_POST['fadeout_seconds']) ? max(0, min(20, (int)$_POST['fadeout_seconds'])) : 4;
    $end = isset($_POST['block_end_action']) && in_array($_POST['block_end_action'], $end_allow, true) ? $_POST['block_end_action'] : 'fade_stop';
    $refresh = isset($_POST['auto_refresh_seconds']) ? max(15, min(300, (int)$_POST['auto_refresh_seconds'])) : 60;
    $debug = isset($_POST['show_debug_badge']) ? 1 : 0;
    hb_update_setting('priority_mode', $priority);
    hb_update_setting('single_window_seconds', $window);
    hb_update_setting('fadeout_seconds', $fade);
    hb_update_setting('block_end_action', $end);
    hb_update_setting('auto_refresh_seconds', $refresh);
    hb_update_setting('show_debug_badge', $debug);
    alert('저장되었습니다.', HB_URL.'/admin/settings.php');
}
$set = hb_get_settings();
include_once(G5_PATH.'/head.php');
?>
<link rel="stylesheet" href="<?php echo HB_URL; ?>/assets/haru_bgm.css?ver=20260616e">
<div class="hb-wrap">
    <section class="hb-page-head"><div><p class="hb-kicker">ADMIN</p><h1>환경설정</h1><p>겹치는 시간표가 있을 때 무엇을 먼저 틀지, 시간대 끝 처리를 어떻게 할지 정합니다.</p></div><a class="hb-btn" href="<?php echo HB_URL; ?>/admin/index.php">관리자 홈</a></section>
    <?php echo hb_nav_admin(); ?>
    <form class="hb-card hb-form" method="post">
        <label>재생 우선순위
            <select name="priority_mode">
                <option value="user_first" <?php echo $set['priority_mode']==='user_first'?'selected':''; ?>>개인 우선: 내 정각 → 내 시간대 → 공통 정각 → 공통 시간대</option>
                <option value="global_first" <?php echo $set['priority_mode']==='global_first'?'selected':''; ?>>공통 방송 우선: 운영자 공통 음악 먼저</option>
                <option value="single_first" <?php echo $set['priority_mode']==='single_first'?'selected':''; ?>>정각 시간표 우선: 시간대 묶음보다 정각 음악 먼저</option>
                <option value="block_first" <?php echo $set['priority_mode']==='block_first'?'selected':''; ?>>시간대 묶음 우선: 긴 BGM 구간 먼저</option>
            </select>
        </label>
        <div class="hb-two">
            <label>정각 재생 허용 범위/초<input type="number" name="single_window_seconds" min="30" max="600" value="<?php echo (int)$set['single_window_seconds']; ?>"><span class="hb-muted-mini">탭 절전/모바일 복귀 보정용입니다.</span></label>
            <label>페이드아웃/초<input type="number" name="fadeout_seconds" min="0" max="20" value="<?php echo (int)$set['fadeout_seconds']; ?>"><span class="hb-muted-mini">시간대 종료 또는 우선순위 전환 시 사용됩니다.</span></label>
        </div>
        <label>시간대 종료 처리
            <select name="block_end_action">
                <option value="fade_stop" <?php echo $set['block_end_action']==='fade_stop'?'selected':''; ?>>끝 시간이 되면 페이드아웃 후 정지</option>
                <option value="finish_current" <?php echo $set['block_end_action']==='finish_current'?'selected':''; ?>>현재 곡까지만 재생 후 정지</option>
            </select>
        </label>
        <label>시간표 새로고침 주기/초<input type="number" name="auto_refresh_seconds" min="15" max="300" value="<?php echo (int)$set['auto_refresh_seconds']; ?>"></label>
        <label class="hb-inline"><input type="checkbox" name="show_debug_badge" value="1" <?php echo !empty($set['show_debug_badge']) ? 'checked' : ''; ?>> 디버그 배지 표시</label>
        <div class="hb-actions"><button class="hb-btn hb-btn-primary" type="submit">저장하기</button></div>
    </form>
</div>
<?php include_once(G5_PATH.'/tail.php'); ?>
