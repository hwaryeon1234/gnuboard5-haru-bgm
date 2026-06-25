<?php
include_once('./_common.php');
$g5['title'] = '하루브금 재생 로그';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$where = '1';
if ($status === 'success' || $status === 'fail') $where .= " AND l.pl_status='".hb_escape($status)."'";
if ($action !== '') $where .= " AND l.pl_action='".hb_escape(substr($action,0,30))."'";
$hb_haru_head_row_was_set = array_key_exists('row', get_defined_vars());
$hb_haru_head_row_backup = $hb_haru_head_row_was_set ? $row : null;
include_once(G5_PATH.'/head.php');
if ($hb_haru_head_row_was_set) {
    $row = $hb_haru_head_row_backup;
} else {
    unset($row);
}
unset($hb_haru_head_row_was_set, $hb_haru_head_row_backup);
$log = hb_table('play_log');
$music = hb_table('music');
$res = sql_query("SELECT l.*, m.mf_title, m.mf_source, m.mf_youtube_id FROM `{$log}` l LEFT JOIN `{$music}` m ON l.mf_id=m.mf_id WHERE {$where} ORDER BY l.pl_id DESC LIMIT 500", false);
?>
<link rel="stylesheet" href="<?php echo HB_URL; ?>/assets/haru_bgm.css?ver=20260625-radiov3">
<div class="hb-app"><?php echo hb_nav_admin(); ?><main class="hb-app-main"><div class="hb-wrap">
    <section class="hb-page-head"><div><p class="hb-kicker">ADMIN</p><h1>재생 로그</h1><p>자동재생, 미리듣기, 수동 진행의 성공/실패를 추적합니다.</p></div><a class="hb-btn" href="<?php echo HB_URL; ?>/admin/index.php">관리자 홈</a></section>
    <section class="hb-card hb-log-filter">
        <form method="get" class="hb-actions">
            <select name="status"><option value="">성공/실패 전체</option><option value="success" <?php echo $status==='success'?'selected':''; ?>>성공만</option><option value="fail" <?php echo $status==='fail'?'selected':''; ?>>실패만</option></select>
            <select name="action"><option value="">동작 전체</option><option value="auto" <?php echo $action==='auto'?'selected':''; ?>>자동재생</option><option value="preview" <?php echo $action==='preview'?'selected':''; ?>>미리듣기</option><option value="manual" <?php echo $action==='manual'?'selected':''; ?>>수동진행</option></select>
            <button class="hb-btn hb-btn-primary" type="submit">필터</button>
            <a class="hb-btn" href="<?php echo HB_URL; ?>/admin/logs.php">초기화</a>
        </form>
    </section>
    <section class="hb-card"><div class="hb-table-wrap"><table class="hb-table"><thead><tr><th>시간</th><th>상태</th><th>동작</th><th>회원</th><th>구분</th><th>음악</th><th>메시지</th><th>IP</th></tr></thead><tbody>
    <?php for ($i=0; $row=sql_fetch_array($res); $i++) { ?>
        <tr><td><?php echo hb_e($row['pl_played_at']); ?></td><td><?php echo $row['pl_status']==='fail' ? '<span class="hb-off">실패</span>' : '<span class="hb-ok">성공</span>'; ?></td><td><?php echo hb_e($row['pl_action'] ?: 'auto'); ?></td><td><?php echo hb_e($row['mb_id']); ?></td><td><?php echo hb_e($row['sc_scope']); ?></td><td><?php echo hb_e($row['mf_title']); ?><span class="hb-muted-mini"><?php echo $row['mf_source']==='youtube' ? 'YouTube · '.hb_e($row['mf_youtube_id']) : '파일'; ?></span></td><td><?php echo hb_e($row['pl_message']); ?></td><td><?php echo hb_e($row['pl_ip']); ?></td></tr>
    <?php } if ($i === 0) { ?><tr><td colspan="8"><div class="hb-empty"><div class="hb-empty-icon">📡</div><strong>재생 로그가 없습니다</strong></div></td></tr><?php } ?>
    </tbody></table></div></section>
</div></main></div>
<?php include_once(G5_PATH.'/tail.php'); ?>
