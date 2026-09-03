<?php
if (!defined('_GNUBOARD_')) return;
if (!defined('HB_DIR')) {
    define('HB_DIR', 'haru_bgm');
    define('HB_PATH', G5_PLUGIN_PATH.'/'.HB_DIR);
    define('HB_URL', G5_PLUGIN_URL.'/'.HB_DIR);
    define('HB_DATA_PATH', G5_DATA_PATH.'/'.HB_DIR);
    define('HB_DATA_URL', G5_DATA_URL.'/'.HB_DIR);
}
if (!is_file(HB_PATH.'/lib.php')) return;
include_once(HB_PATH.'/lib.php');

// extend 파일은 그누보드 초기화 도중 로드될 수 있으므로 DB 설정 조회는
// 실제 출력 시점(tail_sub/요청 종료)까지 미룹니다.
if (!function_exists('hb_sitewide_render')) {
    function hb_sitewide_render($force = false) {
        if (defined('HB_SITEWIDE_RENDERED')) return;
        if (!$force && hb_sitewide_should_skip()) return;
        // 설정 테이블만 남고 다른 스키마가 유실된 상태에서 죽은 플레이어를 출력하지 않습니다.
        if (!hb_schema_runtime_ready()) return;
        if (!hb_sitewide_enabled()) return;
        define('HB_SITEWIDE_RENDERED', true);
        $hb_sw_position = hb_get_setting('sitewide_position', 'bottom_center');
        $hb_sw_bottom_gap = (int)hb_get_setting('sitewide_bottom_gap', '90');
        ?>
<link rel="stylesheet" href="<?php echo HB_URL; ?>/assets/haru_bgm.css?ver=<?php echo rawurlencode(HB_ASSET_VERSION); ?>">
<div id="haruBgmSitewideHotzone" aria-hidden="true"></div>
<div id="haruBgmSitewide" class="hb-sitewide" data-hb-sitewide="1" data-position="<?php echo hb_e($hb_sw_position); ?>" style="--hb-sitewide-bottom-gap: <?php echo $hb_sw_bottom_gap; ?>px;">
    <div class="hb-sitewide-main">
        <span class="hb-sitewide-live" aria-hidden="true"></span>
        <div class="hb-sitewide-copy">
            <strong id="hbNowTitle">하루BGM</strong>
            <span id="hbStatusText">사이트 방송 대기 중</span>
        </div>
        <button type="button" class="hb-sitewide-sound" id="hbEnableSound">▶ BGM 켜기</button>
        <button type="button" class="hb-sitewide-icon" id="hbVolumeToggle" title="볼륨 조절" aria-label="볼륨 조절" aria-expanded="false">♪</button>
        <button type="button" class="hb-sitewide-icon" id="hbTodayOff" title="오늘만 끄기" aria-label="오늘만 끄기">×</button>
        <button type="button" class="hb-sitewide-icon" id="hbStopSound" title="정지" aria-label="정지">■</button>
    </div>
    <div class="hb-sitewide-volume" id="hbVolumeRow" hidden>
        <label for="hbVolume">볼륨</label>
        <input type="range" min="0" max="100" value="80" id="hbVolume">
        <span id="hbVolumeText">80%</span>
    </div>
    <audio id="hbAudio" preload="auto"></audio>
    <div class="hb-sitewide-youtube" id="hbYoutubeWrap"><div id="hbYouTubePlayer"></div></div>
</div>
<script>
(function(){
    // 서버 측 파일명 패턴으로 못 거른 팝업창(다른 테마의 커스텀 쪽지/스크랩 팝업 등)을
    // 대비한 2차 방어입니다. 다른 창이 열어준(window.opener) 좁은 화면(폭 900px 미만)은
    // 그누보드 표준 팝업창의 전형적인 특징이므로, 하단 고정 재생바가 뜨기 전에 즉시 숨깁니다.
    try {
        var isPopup = false;
        try { isPopup = !!(window.opener && !window.opener.closed && window.opener !== window); } catch(e) { isPopup = true; }
        var isNarrow = (window.outerWidth || window.innerWidth || 1024) < 900;
        if (isPopup && isNarrow) {
            var bar = document.getElementById('haruBgmSitewide');
            if (bar) bar.style.display = 'none';
            window.HARU_BGM_SITEWIDE_POPUP_SKIPPED = true;
        }
    } catch(e) {}
})();
</script>
<script>
(function(){
    // 평소에는 완전히 숨겨진(opacity:0, pointer-events:none) 재생바를, 화면 하단의
    // 보이지 않는 감지 영역(핫존)에 마우스가 들어오거나 재생바 자체에 포커스가 갔을 때만
    // 나타나게 합니다. 재생바가 opacity:0인 동안은 자체 :hover가 걸리지 않기 때문에
    // 핫존이라는 별도 요소로 진입을 감지합니다.
    var bar = document.getElementById('haruBgmSitewide');
    var hotzone = document.getElementById('haruBgmSitewideHotzone');
    if (!bar || !hotzone) return;
    var hideTimer = null;
    function wake(){ if (hideTimer) { clearTimeout(hideTimer); hideTimer = null; } bar.classList.add('is-awake'); }
    function sleepSoon(){
        if (hideTimer) clearTimeout(hideTimer);
        // 핫존에서 재생바로, 또는 재생바에서 핫존으로 마우스가 이동하는 짧은 순간에
        // 깜빡이며 사라지지 않도록 약간의 유예를 둡니다.
        hideTimer = setTimeout(function(){ bar.classList.remove('is-awake'); }, 220);
    }
    hotzone.addEventListener('mouseenter', wake);
    hotzone.addEventListener('mouseleave', sleepSoon);
    bar.addEventListener('mouseenter', wake);
    bar.addEventListener('mouseleave', sleepSoon);
})();
</script>
<script>
window.HARU_BGM = {
    apiSchedule: <?php echo hb_json_encode(HB_URL.'/api_sitewide.php'); ?>,
    apiBroadcast: <?php echo hb_json_encode(HB_URL.'/api_broadcast.php'); ?>,
    broadcastPollMs: 2000,
    apiLog: '',
    mode: 'sitewide',
    storagePrefix: 'haru_bgm_sitewide_'
};
</script>
<script>
// 위에서 팝업창으로 판정해 재생바를 숨긴 경우, 재생/스케줄 로직이 담긴 haru_bgm.js 자체를
// 이 창에서는 아예 불러오지 않습니다. 팝업창에서 스케줄 폴링이나 오디오 엘리먼트를 새로
// 만들 필요가 없고, 뒤에서 설명할 "페이지 이동 시 재생이 끊기는" 현상과도 무관하게
// 만들어 불필요한 재생 시도가 팝업에서 겹쳐 발생하지 않도록 합니다.
if (!window.HARU_BGM_SITEWIDE_POPUP_SKIPPED) {
    var hbScriptEl = document.createElement('script');
    hbScriptEl.defer = true;
    hbScriptEl.src = <?php echo hb_json_encode(HB_URL.'/assets/haru_bgm.js?ver='.rawurlencode(HB_ASSET_VERSION)); ?>;
    document.head.appendChild(hbScriptEl);
}
</script>
        <?php
    }
}

if (function_exists('add_event')) {
    add_event('tail_sub', 'hb_sitewide_render', 999, 0);
} else {
    // 구형/특수 환경 fallback에서도 JSON·파일 다운로드·이미지 응답 끝에
    // 플레이어 HTML이 붙어 응답 본문이 깨지지 않도록 HTML GET 요청만 허용합니다.
    if (!function_exists('hb_sitewide_render_fallback')) {
        function hb_sitewide_render_fallback() {
            $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string)$_SERVER['REQUEST_METHOD']) : 'GET';
            if ($method !== 'GET') return;
            $content_type = '';
            $attachment = false;
            if (function_exists('headers_list')) {
                foreach ((array)headers_list() as $header_line) {
                    $line = trim((string)$header_line);
                    if (stripos($line, 'Content-Type:') === 0) $content_type = strtolower(trim(substr($line, 13)));
                    if (stripos($line, 'Content-Disposition:') === 0 && stripos($line, 'attachment') !== false) $attachment = true;
                }
            }
            if ($attachment) return;
            $status = function_exists('http_response_code') ? (int)http_response_code() : 200;
            if ($status > 0 && $status !== 200) return;
            if ($content_type !== '' && strpos($content_type, 'text/html') !== 0 && strpos($content_type, 'application/xhtml+xml') !== 0) return;
            if ($content_type === '') {
                $accept = isset($_SERVER['HTTP_ACCEPT']) ? strtolower(hb_scalar_string($_SERVER['HTTP_ACCEPT'], '')) : '';
                // Content-Type을 따로 지정하지 않은 오래된 페이지는 브라우저의 HTML 탐색 요청일 때만 fallback 출력합니다.
                // */* AJAX/파일 요청은 본문 오염 가능성이 있으므로 보수적으로 건너뜁니다.
                if ($accept === '' || (strpos($accept, 'text/html') === false && strpos($accept, 'application/xhtml+xml') === false)) return;
            }
            hb_sitewide_render();
        }
    }
    register_shutdown_function('hb_sitewide_render_fallback');
}

