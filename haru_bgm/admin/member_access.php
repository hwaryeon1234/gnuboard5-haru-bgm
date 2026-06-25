<?php
include_once('./_common.php');
$g5['title'] = '하루브금 회원 사용설정';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$rows = 100;
$from = ($page - 1) * $rows;
$member_table = hb_member_table_name();
$access = hb_table('member_access');
$where = "1";
if ($q !== '') { $qs = hb_escape($q); $where .= " AND (m.mb_id LIKE '%{$qs}%' OR m.mb_nick LIKE '%{$qs}%' OR m.mb_name LIKE '%{$qs}%')"; }
$default_enabled = hb_member_default_enabled() ? 1 : 0;
if ($status === 'off') $where .= $default_enabled ? " AND a.ma_enabled=0" : " AND (a.ma_enabled=0 OR a.ma_enabled IS NULL)";
if ($status === 'on') $where .= $default_enabled ? " AND (a.ma_enabled=1 OR a.ma_enabled IS NULL)" : " AND a.ma_enabled=1";
$total = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$member_table}` m LEFT JOIN `{$access}` a ON m.mb_id=a.mb_id WHERE {$where}", false);
$res = sql_query("SELECT m.mb_id, m.mb_nick, m.mb_name, m.mb_level, m.mb_datetime, m.mb_today_login, a.ma_enabled, a.ma_memo, a.ma_updated_by, a.ma_updated_at FROM `{$member_table}` m LEFT JOIN `{$access}` a ON m.mb_id=a.mb_id WHERE {$where} ORDER BY m.mb_datetime DESC, m.mb_id ASC LIMIT {$from}, {$rows}", false);
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
<link rel="stylesheet" href="<?php echo HB_URL; ?>/assets/haru_bgm.css?ver=20260625-radiov5">
<div class="hb-app"><?php echo hb_nav_admin(); ?><main class="hb-app-main"><div class="hb-wrap">
    <section class="hb-page-head"><div><p class="hb-kicker">ADMIN</p><h1>회원 사용설정</h1><p>운영자가 회원별로 하루브금 회원용 기능 사용 여부를 지정합니다. 검색/필터/일괄 ON·OFF를 지원합니다.</p></div><div class="hb-actions"><a class="hb-btn" href="<?php echo HB_URL; ?>/admin/index.php">관리자 홈</a><a class="hb-btn hb-btn-primary" href="<?php echo HB_URL; ?>/admin/operation.php">공용 운영판</a></div></section>
    <section class="hb-card"><div class="hb-access-default"><div><strong>새 회원/미지정 회원 기본값</strong><p>개별 설정이 없는 회원에게 적용됩니다. 기존 흐름을 유지하려면 ON을 권장합니다.</p></div><form method="post" action="<?php echo HB_URL; ?>/admin/member_access_update.php" class="hb-checks"><input type="hidden" name="mode" value="default"><input type="hidden" name="return_url" value="<?php echo hb_e($_SERVER['REQUEST_URI']); ?>"><label><input type="radio" name="member_default_enabled" value="1" <?php echo $default_enabled ? 'checked' : ''; ?>> 기본 ON</label><label><input type="radio" name="member_default_enabled" value="0" <?php echo !$default_enabled ? 'checked' : ''; ?>> 기본 OFF</label><button type="submit" class="hb-btn hb-btn-primary">기본값 저장</button></form></div>
    <form class="hb-access-search" method="get" action="<?php echo HB_URL; ?>/admin/member_access.php"><input type="text" name="q" value="<?php echo hb_e($q); ?>" placeholder="아이디 / 닉네임 / 이름 검색"><select name="status"><option value="">전체 상태</option><option value="on" <?php echo $status==='on'?'selected':''; ?>>ON만</option><option value="off" <?php echo $status==='off'?'selected':''; ?>>OFF만</option></select><button type="submit" class="hb-btn hb-btn-primary">검색</button><a class="hb-btn" href="<?php echo HB_URL; ?>/admin/member_access.php">초기화</a></form></section>
    <section class="hb-card"><form method="post" action="<?php echo HB_URL; ?>/admin/member_access_update.php"><input type="hidden" name="mode" value="bulk"><input type="hidden" name="return_url" value="<?php echo hb_e($_SERVER['REQUEST_URI']); ?>"><div class="hb-card-head"><div><p class="hb-kicker">MEMBERS</p><h2>회원별 ON / OFF</h2></div><div class="hb-actions"><span class="hb-pill"><?php echo (int)($total['cnt'] ?? 0); ?>명</span><button class="hb-btn hb-btn-small hb-btn-primary" type="submit" name="ma_enabled" value="1">선택 ON</button><button class="hb-btn hb-btn-small hb-danger" type="submit" name="ma_enabled" value="0">선택 OFF</button></div></div>
        <?php for ($i=0; $row=sql_fetch_array($res); $i++) { $enabled = isset($row['ma_enabled']) && $row['ma_enabled'] !== null ? (int)$row['ma_enabled'] : $default_enabled; ?>
            <div class="hb-access-row"><label class="hb-access-check"><input type="checkbox" name="mb_ids[]" value="<?php echo hb_e($row['mb_id']); ?>"></label><div class="hb-access-member"><strong><?php echo hb_e($row['mb_nick'] ? $row['mb_nick'] : $row['mb_id']); ?></strong><span><?php echo hb_e($row['mb_id']); ?> · Lv.<?php echo (int)$row['mb_level']; ?><?php echo $row['mb_name'] ? ' · '.hb_e($row['mb_name']) : ''; ?><?php echo $row['mb_today_login'] ? ' · 최근접속 '.hb_e($row['mb_today_login']) : ''; ?></span></div><span class="hb-access-state <?php echo $enabled ? 'is-on' : 'is-off'; ?>"><?php echo $enabled ? 'ON 사용' : 'OFF 차단'; ?></span><div class="hb-access-updated"><?php echo $row['ma_updated_at'] ? hb_e($row['ma_updated_at']).'<br>by '.hb_e($row['ma_updated_by']) : '개별 설정 없음'; ?></div><div class="hb-row-actions"><button class="hb-btn hb-btn-small hb-btn-primary" type="submit" name="one_on" value="<?php echo hb_e($row['mb_id']); ?>">ON</button><button class="hb-btn hb-btn-small hb-danger" type="submit" name="one_off" value="<?php echo hb_e($row['mb_id']); ?>">OFF</button></div></div>
        <?php } if ($i === 0) { ?><div class="hb-empty"><div class="hb-empty-icon">👤</div><strong>회원이 없습니다</strong><p>검색어를 다시 확인해주세요.</p></div><?php } ?>
        <?php if ((int)($total['cnt'] ?? 0) > $rows) { ?><div class="hb-actions hb-pager"><?php if ($page > 1) { ?><a class="hb-btn" href="<?php echo HB_URL; ?>/admin/member_access.php?q=<?php echo urlencode($q); ?>&status=<?php echo urlencode($status); ?>&page=<?php echo $page-1; ?>">이전</a><?php } ?><span class="hb-pill"><?php echo $page; ?> 페이지</span><?php if ($from + $rows < (int)$total['cnt']) { ?><a class="hb-btn" href="<?php echo HB_URL; ?>/admin/member_access.php?q=<?php echo urlencode($q); ?>&status=<?php echo urlencode($status); ?>&page=<?php echo $page+1; ?>">다음</a><?php } ?></div><?php } ?>
    </form></section>
</div></main></div>
<?php include_once(G5_PATH.'/tail.php'); ?>
