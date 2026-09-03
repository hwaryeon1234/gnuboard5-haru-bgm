<?php
include_once('./_common.php');
$g5['title'] = '하루BGM 환경설정';
$can_write = hb_user_has_admin_auth('990190', 'w');
$priority_allow = array('single_first','block_first');
$end_allow = array('fade_stop','finish_current');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hb_check_csrf();
    hb_check_admin_token_if_present();
    $was_sitewide = hb_sitewide_enabled();
    $priority_raw = isset($_POST['priority_mode']) ? hb_scalar_string($_POST['priority_mode'], '') : '';
    $priority = in_array($priority_raw, $priority_allow, true) ? $priority_raw : 'single_first';
    $window = isset($_POST['single_window_seconds']) ? max(30, min(600, hb_int_value($_POST['single_window_seconds'], 90))) : 90;
    $fade = isset($_POST['fadeout_seconds']) ? max(0, min(20, hb_int_value($_POST['fadeout_seconds'], 4))) : 4;
    $end_raw = isset($_POST['block_end_action']) ? hb_scalar_string($_POST['block_end_action'], '') : '';
    $end = in_array($end_raw, $end_allow, true) ? $end_raw : 'fade_stop';
    $refresh = isset($_POST['auto_refresh_seconds']) ? max(15, min(300, hb_int_value($_POST['auto_refresh_seconds'], 60))) : 60;
    $debug = isset($_POST['show_debug_badge']) ? 1 : 0;
    $sitewide = isset($_POST['sitewide_broadcast_enabled']) ? 1 : 0;
    $position_raw = isset($_POST['sitewide_position']) ? hb_scalar_string($_POST['sitewide_position'], '') : '';
    $position = in_array($position_raw, hb_sitewide_position_allowed(), true) ? $position_raw : 'bottom_center';
    $bottom_gap = isset($_POST['sitewide_bottom_gap']) ? max(0, min(400, hb_int_value($_POST['sitewide_bottom_gap'], 18))) : 18;

    if ($sitewide) {
        $hook = hb_sync_sitewide_hook();
        if (empty($hook['ok'])) alert('사이트 전체 방송을 켤 수 없습니다. '.$hook['message'], HB_URL.'/admin/settings.php');
    }

    if (!hb_db_begin()) alert('DB 작업을 시작하지 못했습니다. 잠시 후 다시 시도해주세요.', HB_URL.'/admin/settings.php');
    $save_ok = true;
    $save_ok = hb_update_setting('priority_mode', $priority) && $save_ok;
    $save_ok = hb_update_setting('single_window_seconds', $window) && $save_ok;
    $save_ok = hb_update_setting('fadeout_seconds', $fade) && $save_ok;
    $save_ok = hb_update_setting('block_end_action', $end) && $save_ok;
    $save_ok = hb_update_setting('auto_refresh_seconds', $refresh) && $save_ok;
    $save_ok = hb_update_setting('show_debug_badge', $debug) && $save_ok;
    $save_ok = hb_update_setting('sitewide_broadcast_enabled', $sitewide) && $save_ok;
    $save_ok = hb_update_setting('sitewide_position', $position) && $save_ok;
    $save_ok = hb_update_setting('sitewide_bottom_gap', $bottom_gap) && $save_ok;
    if ($save_ok && (bool)$sitewide !== (bool)$was_sitewide) {
        $state = hb_broadcast_set_state($sitewide ? 'auto' : 'stop');
        $save_ok = !empty($state['ok']);
    }
    if (!$save_ok) { hb_db_rollback(); alert('환경설정 저장 중 DB 오류가 발생했습니다. 기존 설정은 유지되었습니다.', HB_URL.'/admin/settings.php'); }
    if (!hb_db_commit()) { hb_db_rollback(); alert('환경설정 저장을 완료하지 못했습니다.', HB_URL.'/admin/settings.php'); }

    alert('설정을 저장했습니다.', HB_URL.'/admin/settings.php');
}

$set = hb_get_settings();
if (!in_array($set['priority_mode'], $priority_allow, true)) $set['priority_mode'] = 'single_first';
$sitewide_enabled = hb_sitewide_enabled();
$hook_installed = hb_sitewide_hook_installed();
$priority_labels = array(
    'single_first' => '정각 시간표 우선',
    'block_first' => '시간대 묶음 우선',
);
$end_labels = array(
    'fade_stop' => '페이드아웃 후 정지',
    'finish_current' => '현재 곡까지 재생',
);
include_once(HB_PATH.'/admin/_head.php');
?>
<div id="haruBgmAdminApp" class="hb-app"><?php echo hb_nav_admin(); ?><main class="hb-app-main"><div class="hb-wrap hb-settings-page">
    <section class="hb-page-head">
        <div>
            <p class="hb-kicker">SETTINGS</p>
            <h1>환경설정</h1>
            <p>방송 방식과 사이트 전체 재생 여부를 설정합니다.</p>
        </div>
    </section>

    <form method="post" class="hb-settings-form"><?php echo hb_csrf_field(); ?><?php if (!$can_write) { ?><div class="hb-card hb-setting-note"><strong>읽기 전용 권한입니다.</strong> 설정 변경은 쓰기 권한이 있는 관리자만 할 수 있습니다.</div><?php } ?><fieldset class="hb-settings-fieldset" <?php echo $can_write ? '' : 'disabled'; ?>>
        <section class="hb-card hb-sitewide-setting <?php echo $sitewide_enabled ? 'is-on' : ''; ?>" id="hb-setting-sitewide">
            <div class="hb-sitewide-setting-copy">
                <div class="hb-setting-icon">■</div>
                <div>
                    <p class="hb-kicker">SITE-WIDE BROADCAST</p>
                    <h2>사이트 전체 방송</h2>
                    <p class="hb-sub">켜면 공통 시간표와 공통 시간대 방송이 하루BGM 페이지뿐 아니라 사이트의 일반 페이지에서도 재생됩니다.</p>
                    <div class="hb-setting-badges">
                        <span class="hb-pill <?php echo $sitewide_enabled ? 'hb-pill-on' : ''; ?>"><?php echo $sitewide_enabled ? '방송 활성화' : '방송 꺼짐'; ?></span>
                        <span class="hb-pill <?php echo $hook_installed ? 'hb-pill-ok' : 'hb-pill-warn'; ?>"><?php echo $hook_installed ? '사이트 연결 정상' : '사이트 연결 필요'; ?></span>
                    </div>
                </div>
            </div>
            <label class="hb-switch" aria-label="사이트 전체 방송 활성화">
                <input type="checkbox" name="sitewide_broadcast_enabled" value="1" <?php echo $sitewide_enabled ? 'checked' : ''; ?>>
                <span class="hb-switch-ui"></span>
            </label>
        </section>
        <p class="hb-setting-note">브라우저 정책상 방문자는 처음 한 번 <b>BGM 켜기</b>를 눌러야 소리가 시작될 수 있습니다. 이후 같은 브라우저에서는 설정을 기억합니다.</p>

        <section class="hb-card hb-sitewide-position-setting" id="hb-setting-sitewide-position">
            <div class="hb-card-head"><div><p class="hb-kicker">PLAYER POSITION</p><h2>재생바 위치</h2><p class="hb-sub">방문자 화면 하단 어디에 재생바를 띄울지 정합니다. 오른쪽에서 실제 사이트 위에 바로 미리 확인할 수 있습니다.</p></div></div>
            <div class="hb-sitewide-position-layout">
                <div class="hb-sitewide-position-controls">
                    <div class="hb-position-radio-group" role="radiogroup" aria-label="재생바 위치 선택">
                        <label class="hb-position-radio"><input type="radio" name="sitewide_position" value="bottom_left" <?php echo $set['sitewide_position']==='bottom_left'?'checked':''; ?>><span>왼쪽 하단</span></label>
                        <label class="hb-position-radio"><input type="radio" name="sitewide_position" value="bottom_center" <?php echo $set['sitewide_position']==='bottom_center'?'checked':''; ?>><span>가운데 하단 <em>(기본값)</em></span></label>
                        <label class="hb-position-radio"><input type="radio" name="sitewide_position" value="bottom_right" <?php echo $set['sitewide_position']==='bottom_right'?'checked':''; ?>><span>오른쪽 하단</span></label>
                    </div>
                    <label class="hb-field">화면 아래쪽에서 띄우는 높이
                        <div class="hb-input-unit"><input type="number" id="hbSitewideBottomGap" name="sitewide_bottom_gap" min="0" max="400" value="<?php echo (int)$set['sitewide_bottom_gap']; ?>"><span>px</span></div>
                    </label>
                    <p class="hb-setting-note">다른 채팅 상담 버튼이나 리빌더 설정바처럼 화면 아래쪽에 이미 떠 있는 위젯과 겹치면 이 값을 올려 재생바를 더 위로 옮겨주세요.</p>
                </div>
                <div class="hb-sitewide-preview">
                    <div class="hb-sitewide-preview-frame-wrap">
                        <iframe id="hbSitewidePreviewFrame" class="hb-sitewide-preview-frame" src="<?php echo hb_e(G5_URL); ?>" loading="lazy" title="실제 사이트 미리보기" onload="this.closest('.hb-sitewide-preview').classList.add('is-loaded')" onerror="this.closest('.hb-sitewide-preview').classList.add('is-blocked')"></iframe>
                        <div class="hb-sitewide-preview-overlay" id="hbSitewidePreviewOverlay" data-position="<?php echo hb_e($set['sitewide_position']); ?>">
                            <div class="hb-sitewide-preview-bar"><span class="hb-sitewide-preview-dot"></span>하루BGM 재생바</div>
                        </div>
                        <div class="hb-sitewide-preview-blocked-note">이 브라우저/사이트 보안 설정 때문에 실제 화면 미리보기를 이 안에 띄울 수 없습니다. <a href="<?php echo hb_e(G5_URL); ?>" target="_blank" rel="noopener">새 창에서 사이트 열어 확인하기</a></div>
                    </div>
                    <p class="hb-setting-note">위 미리보기는 실제 <?php echo hb_e(G5_URL); ?> 화면입니다. 왼쪽에서 위치를 바꾸면 재생바 표시 위치가 바로 움직입니다. 실제 재생바는 저장 후 사이트에 반영됩니다.</p>
                </div>
            </div>
        </section>
        <script>
        (function(){
            var overlay = document.getElementById('hbSitewidePreviewOverlay');
            var gapInput = document.getElementById('hbSitewideBottomGap');
            var radios = document.querySelectorAll('input[name="sitewide_position"]');
            if(!overlay) return;
            function syncGap(){
                var gap = parseInt((gapInput && gapInput.value) || '18', 10);
                if(!isFinite(gap)) gap = 18;
                // 실제 사이트 화면(iframe)은 미리보기 상자 안에서 축소 표시되므로,
                // 여백값을 그대로 쓰지 않고 상자 높이 대비 비율로 환산해 보여줍니다.
                var scaled = Math.max(6, Math.min(70, Math.round(gap * 0.35)));
                overlay.style.setProperty('--hb-preview-gap', scaled + 'px');
            }
            function syncPosition(){
                var checked = document.querySelector('input[name="sitewide_position"]:checked');
                overlay.setAttribute('data-position', checked ? checked.value : 'bottom_center');
            }
            radios.forEach(function(r){ r.addEventListener('change', syncPosition); });
            if(gapInput) gapInput.addEventListener('input', syncGap);
            syncPosition();
            syncGap();
        })();
        </script>

        <div class="hb-settings-grid">
            <section class="hb-card hb-settings-section">
                <div class="hb-card-head"><div><p class="hb-kicker">PLAY ORDER</p><h2>재생 우선순위</h2><p class="hb-sub">정각 시간표와 시간대 묶음이 겹칠 때의 순서입니다.</p></div></div>
                <label class="hb-field">우선순위
                    <select name="priority_mode">
                        <option value="single_first" <?php echo $set['priority_mode']==='single_first'?'selected':''; ?>>정각 시간표 우선</option>
                        <option value="block_first" <?php echo $set['priority_mode']==='block_first'?'selected':''; ?>>시간대 묶음 우선</option>
                    </select>
                </label>
                <div class="hb-setting-current"><span>현재</span><strong><?php echo hb_e($priority_labels[$set['priority_mode']] ?? '정각 시간표 우선'); ?></strong></div>
            </section>

            <section class="hb-card hb-settings-section">
                <div class="hb-card-head"><div><p class="hb-kicker">TIMING</p><h2>재생 타이밍</h2><p class="hb-sub">절전 복귀 오차와 곡 전환 시간을 조정합니다.</p></div></div>
                <div class="hb-two hb-setting-grid-two">
                    <label class="hb-field">정각 허용 범위
                        <div class="hb-input-unit"><input type="number" name="single_window_seconds" min="30" max="600" value="<?php echo (int)$set['single_window_seconds']; ?>"><span>초</span></div>
                    </label>
                    <label class="hb-field">페이드아웃
                        <div class="hb-input-unit"><input type="number" name="fadeout_seconds" min="0" max="20" value="<?php echo (int)$set['fadeout_seconds']; ?>"><span>초</span></div>
                    </label>
                </div>
            </section>

            <section class="hb-card hb-settings-section">
                <div class="hb-card-head"><div><p class="hb-kicker">END ACTION</p><h2>시간대 종료</h2><p class="hb-sub">방송 시간이 끝났을 때 처리 방식을 정합니다.</p></div></div>
                <label class="hb-field">종료 방식
                    <select name="block_end_action">
                        <option value="fade_stop" <?php echo $set['block_end_action']==='fade_stop'?'selected':''; ?>>페이드아웃 후 정지</option>
                        <option value="finish_current" <?php echo $set['block_end_action']==='finish_current'?'selected':''; ?>>현재 곡까지 재생</option>
                    </select>
                </label>
                <div class="hb-setting-current"><span>현재</span><strong><?php echo hb_e($end_labels[$set['block_end_action']] ?? '페이드아웃 후 정지'); ?></strong></div>
            </section>

            <section class="hb-card hb-settings-section">
                <div class="hb-card-head"><div><p class="hb-kicker">SYSTEM</p><h2>새로고침 / 점검</h2><p class="hb-sub">시간표 갱신 주기와 점검 표시를 설정합니다.</p></div></div>
                <label class="hb-field">시간표 새로고침
                    <div class="hb-input-unit"><input type="number" name="auto_refresh_seconds" min="15" max="300" value="<?php echo (int)$set['auto_refresh_seconds']; ?>"><span>초</span></div>
                </label>
                <label class="hb-check-line"><input type="checkbox" name="show_debug_badge" value="1" <?php echo !empty($set['show_debug_badge']) ? 'checked' : ''; ?>><span>디버그 배지 표시</span></label>
            </section>
        </div>

        <section class="hb-card hb-settings-section" id="hb-setting-admin">
            <div class="hb-card-head"><div><p class="hb-kicker">ADMIN ACCESS</p><h2>관리자 권한</h2><p class="hb-sub">별도 아이디 목록을 사용하지 않고 그누보드5 관리권한을 그대로 사용합니다.</p></div></div>
            <div class="hb-setting-current"><span>권한 체계</span><strong>그누보드5 관리권한 990xxx</strong></div>
            <?php if (hb_is_super_admin()) { ?>
            <div class="hb-actions"><a class="hb-btn hb-btn-small" href="<?php echo G5_ADMIN_URL; ?>/auth_list.php">관리권한 설정 열기</a></div>
            <?php } ?>
        </section>

        <div class="hb-settings-savebar">
            <div><strong>변경사항 저장</strong><span>저장 즉시 다음 시간표 갱신부터 반영됩니다.</span></div>
            <button class="hb-btn hb-btn-primary" type="submit">설정 저장</button>
        </div>
        </fieldset>
    </form>
</div></main></div>
<?php include_once(HB_PATH.'/admin/_tail.php'); 
