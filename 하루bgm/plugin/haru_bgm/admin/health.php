<?php
// DB 점검/복구 POST는 플러그인 관리자 공통 부트스트랩보다 먼저 처리합니다.
// G5 관리자 공통 훅/전역 토큰/스키마 게이트와 충돌하지 않도록,
// 로그인 세션 + 최고관리자 + 하루BGM 전용 CSRF만 검증한 뒤 PRG 방식으로 GET 화면으로 복귀합니다.
$hb_health_is_post = isset($_SERVER['REQUEST_METHOD']) && strtoupper((string)$_SERVER['REQUEST_METHOD']) === 'POST';
if ($hb_health_is_post) {
    include_once(dirname(__FILE__).'/../_common.php');
    if (!hb_is_super_admin()) {
        alert('DB 점검 및 복구는 최고관리자만 실행할 수 있습니다.', HB_URL.'/admin/health.php');
    }
    hb_check_csrf();
    hb_check_admin_token_if_present();
    $action = isset($_POST['action']) ? hb_scalar_string($_POST['action'], '') : '';
    if ($action !== 'schema_check_repair') {
        alert('요청 정보를 확인할 수 없습니다. 시스템 점검 화면에서 다시 실행해주세요.', HB_URL.'/admin/health.php');
    }
    $_SESSION['hb_health_schema_result'] = hb_schema_check_repair();
    goto_url(HB_URL.'/admin/health.php?db_checked=1');
    exit;
}

include_once('./_common.php');
$g5['title'] = '하루BGM 시스템 점검';

$hb_schema_result = null;
if (isset($_SESSION['hb_health_schema_result']) && is_array($_SESSION['hb_health_schema_result'])) {
    $hb_schema_result = $_SESSION['hb_health_schema_result'];
    unset($_SESSION['hb_health_schema_result']);
}

$hb_schema_attempted = !empty($GLOBALS['hb_schema_bootstrap_attempted']);
$hb_schema_ok = !empty($GLOBALS['hb_schema_bootstrap_ok']);
$checks = hb_health_checks();

$hb_total = count($checks);
$hb_ok_count = 0;
$hb_need_count = 0;
$hb_groups = array();
foreach ($checks as $c) {
    if (!empty($c['ok'])) $hb_ok_count++; else $hb_need_count++;
    $label = isset($c['label']) ? (string)$c['label'] : '기타';
    $group = '기타 점검';
    if (strpos($label, 'DB 테이블:') === 0) $group = 'DB 테이블';
    elseif (strpos($label, 'DB 엔진:') === 0) $group = 'DB 엔진';
    elseif (strpos($label, 'DB 컬럼 정의:') === 0) $group = 'DB 컬럼 정의';
    elseif (strpos($label, 'DB 컬럼:') === 0) $group = 'DB 컬럼';
    elseif (strpos($label, 'DB 문자셋:') === 0) $group = 'DB 문자셋';
    elseif (strpos($label, 'DB 기본값:') === 0) $group = 'DB 기본값';
    elseif (strpos($label, 'DB 인덱스:') === 0) $group = 'DB 인덱스';
    elseif (strpos($label, 'DB 외래키:') === 0) $group = 'DB 외래키';
    elseif (strpos($label, 'DB 무결성:') === 0) $group = 'DB 무결성';
    elseif (strpos($label, '음악 ') === 0) $group = '음악 저장소';
    elseif (strpos($label, 'PHP ') === 0 || strpos($label, 'Range ') === 0 || strpos($label, 'YouTube ') === 0) $group = '서버 환경';
    elseif (strpos($label, '사이트 전체') === 0 || strpos($label, '활성 파일') === 0 || strpos($label, '미사용 음악') === 0 || strpos($label, '제거된 구형') === 0) $group = '플러그인 상태';
    if (!isset($hb_groups[$group])) $hb_groups[$group] = array();
    $hb_groups[$group][] = $c;
}

$hb_group_order = array('DB 테이블','DB 엔진','DB 컬럼','DB 컬럼 정의','DB 문자셋','DB 기본값','DB 인덱스','DB 외래키','DB 무결성','음악 저장소','서버 환경','플러그인 상태','기타 점검');
$hb_health_percent = $hb_total > 0 ? (int)round(($hb_ok_count / $hb_total) * 100) : 100;
include_once(HB_PATH.'/admin/_head.php');
?>
<div id="haruBgmAdminApp" class="hb-app"><?php echo hb_nav_admin(); ?><main class="hb-app-main"><div class="hb-wrap hb-health-page">
    <section class="hb-page-head hb-health-page-head">
        <div>
            <h1>시스템 점검</h1>
            <p>데이터베이스, 저장소, 서버 환경과 플러그인 연결 상태를 한곳에서 확인합니다.</p>
        </div>
        <span class="hb-health-overall<?php echo $hb_need_count > 0 ? ' is-warning' : ' is-ok'; ?>">
            <span class="hb-health-overall-dot" aria-hidden="true"></span>
            <?php echo $hb_need_count > 0 ? '확인 필요 '.$hb_need_count.'개' : '전체 정상'; ?>
        </span>
    </section>

    <?php if (is_array($hb_schema_result)) { ?>
        <div class="hb-health-result<?php echo empty($hb_schema_result['ok']) ? ' is-error' : ''; ?>"><?php echo hb_e($hb_schema_result['message']); ?></div>
    <?php } ?>

    <section class="hb-health-overview" aria-label="점검 요약">
        <div class="hb-health-overview-main">
            <div class="hb-health-overview-copy">
                <span class="hb-health-overview-label">점검 결과</span>
                <strong><?php echo number_format($hb_ok_count); ?> / <?php echo number_format($hb_total); ?> 정상</strong>
                <span><?php echo $hb_need_count > 0 ? '확인이 필요한 항목부터 아래에서 펼쳐볼 수 있습니다.' : '현재 확인 가능한 항목은 모두 정상입니다.'; ?></span>
            </div>
            <div class="hb-health-progress" aria-label="정상 비율 <?php echo $hb_health_percent; ?>%">
                <span style="width:<?php echo $hb_health_percent; ?>%"></span>
            </div>
        </div>
        <dl class="hb-health-counts">
            <div><dt>전체</dt><dd><?php echo number_format($hb_total); ?></dd></div>
            <div><dt>정상</dt><dd class="is-ok"><?php echo number_format($hb_ok_count); ?></dd></div>
            <div><dt>확인 필요</dt><dd class="<?php echo $hb_need_count > 0 ? 'is-warning' : ''; ?>"><?php echo number_format($hb_need_count); ?></dd></div>
        </dl>
    </section>

    <section class="hb-health-db">
        <div class="hb-health-db-copy">
            <div class="hb-health-section-title">
                <h2>데이터베이스 점검</h2>
                <?php echo $hb_schema_ok ? '<span class="hb-health-chip is-ok">정상</span>' : '<span class="hb-health-chip is-warning">확인 필요</span>'; ?>
            </div>
            <p>테이블과 컬럼 등 필수 구성만 확인하며, 문제가 있을 때만 복구 작업을 실행합니다.</p>
        </div>
        <div class="hb-health-db-action">
            <?php if (hb_is_super_admin()) { ?>
                <?php if ($hb_schema_ok) { ?>
                    <a href="<?php echo HB_URL; ?>/admin/health.php?db_checked=1" class="hb-btn">DB 상태 다시 확인</a>
                <?php } else { ?>
                    <form method="post" action="<?php echo HB_URL; ?>/admin/health.php" class="hb-inline-form">
                        <?php echo hb_csrf_field(); ?>
                        <input type="hidden" name="action" value="schema_check_repair">
                        <button type="submit" class="hb-btn hb-btn-primary">필요한 DB 항목 복구</button>
                    </form>
                <?php } ?>
            <?php } else { ?>
                <span class="hb-muted-mini">복구 작업은 최고관리자만 실행할 수 있습니다.</span>
            <?php } ?>
        </div>
    </section>

    <section class="hb-health-list" aria-label="세부 점검 결과">
        <div class="hb-health-list-head">
            <div>
                <h2>세부 점검 결과</h2>
                <p>분류를 펼치면 각 항목의 상태와 확인 내용을 볼 수 있습니다.</p>
            </div>
        </div>
        <div class="hb-health-groups">
            <?php foreach ($hb_group_order as $group_name) { if (empty($hb_groups[$group_name])) continue;
                $rows = $hb_groups[$group_name];
                $need = 0;
                foreach ($rows as $row) if (empty($row['ok'])) $need++;
                $normal = count($rows) - $need;
            ?>
            <details class="hb-health-group<?php echo $need > 0 ? ' has-warning' : ''; ?>" <?php echo $need > 0 ? 'open' : ''; ?>>
                <summary>
                    <span class="hb-health-group-status<?php echo $need > 0 ? ' is-warning' : ' is-ok'; ?>" aria-hidden="true"></span>
                    <span class="hb-health-group-title"><?php echo hb_e($group_name); ?></span>
                    <span class="hb-health-group-meta"><?php echo number_format($normal); ?> 정상<?php echo $need ? ' · '.$need.' 확인 필요' : ''; ?></span>
                    <span class="hb-health-chevron" aria-hidden="true"></span>
                </summary>
                <div class="hb-health-group-body">
                    <div class="hb-health-row hb-health-row-head" aria-hidden="true">
                        <div>점검 항목</div><div>상태</div><div>확인 내용</div>
                    </div>
                    <?php foreach ($rows as $c) { ?>
                    <div class="hb-health-row">
                        <div class="hb-health-row-name"><?php echo hb_e($c['label']); ?></div>
                        <div><span class="hb-health-chip<?php echo !empty($c['ok']) ? ' is-ok' : ' is-warning'; ?>"><?php echo !empty($c['ok']) ? '정상' : '확인 필요'; ?></span></div>
                        <div class="hb-health-row-message"><?php echo hb_e($c['message']); ?></div>
                    </div>
                    <?php } ?>
                </div>
            </details>
            <?php } ?>
        </div>
    </section>
</div></main></div>
<?php include_once(HB_PATH.'/admin/_tail.php'); 
