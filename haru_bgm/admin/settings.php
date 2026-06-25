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
$priority_labels = array(
    'user_first' => '개인 우선',
    'global_first' => '공통 방송 우선',
    'single_first' => '정각 시간표 우선',
    'block_first' => '시간대 묶음 우선',
);
$end_labels = array(
    'fade_stop' => '끝나면 페이드아웃 후 정지',
    'finish_current' => '현재 곡까지만 재생 후 정지',
);
$hb_haru_head_row_was_set = array_key_exists('row', get_defined_vars());
$hb_haru_head_row_backup = $hb_haru_head_row_was_set ? $row : null;
include_once(G5_PATH.'/head.php');
if ($hb_haru_head_row_was_set) {
    $row = $hb_haru_head_row_backup;
} else {
    unset($row);
}
unset($hb_haru_head_row_was_set, $hb_haru_head_row_backup);
?>
<link rel="stylesheet" href="<?php echo HB_URL; ?>/assets/haru_bgm.css?ver=20260625-radiov2">
<div class="hb-app"><?php echo hb_nav_admin(); ?><main class="hb-app-main"><div class="hb-wrap">
    <section class="hb-page-head">
        <div>
            <p class="hb-kicker">ADMIN</p>
            <h1>환경설정</h1>
            <p>정각/시간대/새로고침/디버그 옵션을 한눈에 보이게 다시 묶었습니다. 자주 건드는 값끼리 모아두고, 설명도 같이 붙였습니다.</p>
        </div>
        <div class="hb-actions">
            <a class="hb-btn" href="<?php echo HB_URL; ?>/admin/index.php">관리자 홈</a>
            <a class="hb-btn" href="<?php echo HB_URL; ?>/admin/operation.php">공용 운영판</a>
        </div>
    </section>
    <form method="post" class="hb-settings-form">
        <div class="hb-settings-layout">
            <aside class="hb-card hb-settings-side">
                <p class="hb-kicker">QUICK GUIDE</p>
                <h2>빠른 이동</h2>
                <p class="hb-sub">헷갈리지 않게 기능별로 나눴습니다. 왼쪽에서 찾고 오른쪽에서 바로 수정하면 됩니다.</p>
                <nav class="hb-settings-nav">
                    <a href="#hb-setting-priority">재생 우선순위</a>
                    <a href="#hb-setting-timing">정각 재생/전환 시간</a>
                    <a href="#hb-setting-ending">시간대 종료 처리</a>
                    <a href="#hb-setting-display">새로고침/디버그</a>
                </nav>
                <div class="hb-setting-tip">
                    <strong>현재 적용값</strong>
                    <ul class="hb-setting-list">
                        <li><span>우선순위</span><b><?php echo $priority_labels[$set['priority_mode']] ?? '개인 우선'; ?></b></li>
                        <li><span>정각 허용 범위</span><b><?php echo (int)$set['single_window_seconds']; ?>초</b></li>
                        <li><span>페이드아웃</span><b><?php echo (int)$set['fadeout_seconds']; ?>초</b></li>
                        <li><span>시간대 종료</span><b><?php echo $end_labels[$set['block_end_action']] ?? '끝나면 페이드아웃 후 정지'; ?></b></li>
                        <li><span>새로고침</span><b><?php echo (int)$set['auto_refresh_seconds']; ?>초</b></li>
                        <li><span>디버그 배지</span><b><?php echo !empty($set['show_debug_badge']) ? '표시함' : '숨김'; ?></b></li>
                    </ul>
                </div>
            </aside>

            <div class="hb-settings-main">
                <section class="hb-card hb-settings-section" id="hb-setting-priority">
                    <div class="hb-card-head">
                        <div>
                            <p class="hb-kicker">PLAY ORDER</p>
                            <h2>재생 우선순위</h2>
                            <p class="hb-sub">시간이 겹칠 때 어떤 규칙으로 먼저 틀지 정합니다.</p>
                        </div>
                    </div>
                    <label>재생 우선순위
                        <select name="priority_mode">
                            <option value="user_first" <?php echo $set['priority_mode']==='user_first'?'selected':''; ?>>개인 우선: 내 정각 → 내 시간대 → 공통 정각 → 공통 시간대</option>
                            <option value="global_first" <?php echo $set['priority_mode']==='global_first'?'selected':''; ?>>공통 방송 우선: 운영자 공통 음악 먼저</option>
                            <option value="single_first" <?php echo $set['priority_mode']==='single_first'?'selected':''; ?>>정각 시간표 우선: 시간대 묶음보다 정각 음악 먼저</option>
                            <option value="block_first" <?php echo $set['priority_mode']==='block_first'?'selected':''; ?>>시간대 묶음 우선: 긴 BGM 구간 먼저</option>
                        </select>
                    </label>
                    <p class="hb-setting-help">보통은 <b>개인 우선</b>이나 <b>공통 방송 우선</b>만 자주 씁니다. 운영 방송을 확실히 우선하고 싶으면 공통 방송 우선을 쓰면 됩니다.</p>
                </section>

                <section class="hb-card hb-settings-section" id="hb-setting-timing">
                    <div class="hb-card-head">
                        <div>
                            <p class="hb-kicker">TIMING</p>
                            <h2>정각 재생 / 전환 시간</h2>
                            <p class="hb-sub">모바일 복귀나 탭 절전 때문에 생기는 오차를 보정하고, 전환 시 부드럽게 넘기도록 정합니다.</p>
                        </div>
                    </div>
                    <div class="hb-two hb-setting-grid-two">
                        <label>정각 재생 허용 범위 / 초
                            <input type="number" name="single_window_seconds" min="30" max="600" value="<?php echo (int)$set['single_window_seconds']; ?>">
                            <span class="hb-muted-mini">권장 60~120초 · 탭 절전/모바일 복귀 보정용</span>
                        </label>
                        <label>페이드아웃 / 초
                            <input type="number" name="fadeout_seconds" min="0" max="20" value="<?php echo (int)$set['fadeout_seconds']; ?>">
                            <span class="hb-muted-mini">권장 3~5초 · 시간대 종료 또는 우선순위 전환 시 사용</span>
                        </label>
                    </div>
                </section>

                <section class="hb-card hb-settings-section" id="hb-setting-ending">
                    <div class="hb-card-head">
                        <div>
                            <p class="hb-kicker">END ACTION</p>
                            <h2>시간대 종료 처리</h2>
                            <p class="hb-sub">설정된 시간대가 끝났을 때 즉시 끊을지, 현재 재생 중인 곡만 마저 틀지 정합니다.</p>
                        </div>
                    </div>
                    <label>시간대 종료 처리
                        <select name="block_end_action">
                            <option value="fade_stop" <?php echo $set['block_end_action']==='fade_stop'?'selected':''; ?>>끝 시간이 되면 페이드아웃 후 정지</option>
                            <option value="finish_current" <?php echo $set['block_end_action']==='finish_current'?'selected':''; ?>>현재 곡까지만 재생 후 정지</option>
                        </select>
                    </label>
                    <p class="hb-setting-help">방송 시간을 칼같이 맞춰야 하면 <b>페이드아웃 후 정지</b>, 자연스럽게 곡을 마무리하고 싶으면 <b>현재 곡까지만 재생</b>이 좋습니다.</p>
                </section>

                <section class="hb-card hb-settings-section" id="hb-setting-display">
                    <div class="hb-card-head">
                        <div>
                            <p class="hb-kicker">DISPLAY / DEBUG</p>
                            <h2>새로고침 / 디버그</h2>
                            <p class="hb-sub">플레이어가 시간표를 다시 읽어오는 주기와, 점검용 표시를 다룹니다.</p>
                        </div>
                    </div>
                    <div class="hb-two hb-setting-grid-two">
                        <label>시간표 새로고침 주기 / 초
                            <input type="number" name="auto_refresh_seconds" min="15" max="300" value="<?php echo (int)$set['auto_refresh_seconds']; ?>">
                            <span class="hb-muted-mini">권장 30~60초 · 너무 짧으면 불필요한 요청이 늘 수 있습니다.</span>
                        </label>
                        <div class="hb-setting-plain">
                            <label class="hb-inline"><input type="checkbox" name="show_debug_badge" value="1" <?php echo !empty($set['show_debug_badge']) ? 'checked' : ''; ?>> 디버그 배지 표시</label>
                            <p class="hb-setting-help hb-setting-help-plain">문제 확인할 때만 켜두는 것을 권장합니다. 평소 운영 화면은 끄는 편이 깔끔합니다.</p>
                        </div>
                    </div>
                </section>

                <div class="hb-card hb-settings-footer">
                    <div>
                        <p class="hb-kicker">SAVE</p>
                        <h2>설정 저장</h2>
                        <p class="hb-sub">값을 바꾼 뒤 저장하면 바로 반영됩니다. 이상하면 다시 기본에 가까운 값으로 되돌리면 됩니다.</p>
                    </div>
                    <div class="hb-actions">
                        <button class="hb-btn hb-btn-primary" type="submit">저장하기</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div></main></div>
<?php include_once(G5_PATH.'/tail.php'); ?>
