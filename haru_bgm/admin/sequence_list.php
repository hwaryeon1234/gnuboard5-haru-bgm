<?php
include_once('./_common.php');
$g5['title'] = '하루브금 순서표 모드';
$hb_haru_head_row_was_set = array_key_exists('row', get_defined_vars());
$hb_haru_head_row_backup = $hb_haru_head_row_was_set ? $row : null;
include_once(G5_PATH.'/head.php');
if ($hb_haru_head_row_was_set) {
    $row = $hb_haru_head_row_backup;
} else {
    unset($row);
}
unset($hb_haru_head_row_was_set, $hb_haru_head_row_backup);
$sequence = hb_table('sequence');
$res = sql_query("SELECT * FROM `{$sequence}` ORDER BY seq_sort ASC, seq_id DESC", false);
?>
<link rel="stylesheet" href="<?php echo HB_URL; ?>/assets/haru_bgm.css?ver=20260625-radiov5">
<div class="hb-app"><?php echo hb_nav_admin(); ?><main class="hb-app-main"><div class="hb-wrap">
<section class="hb-page-head"><div><p class="hb-kicker">SEQUENCE</p><h1>순서표 모드</h1><p>교회 예배, 방송, 행사처럼 담당자가 다음 곡을 눌러 진행하는 공용 진행표입니다.</p></div><div class="hb-actions"><a class="hb-btn hb-btn-primary" href="<?php echo HB_URL; ?>/admin/sequence_form.php">+ 순서표 추가</a><a class="hb-btn" href="<?php echo HB_URL; ?>/admin/index.php">관리자 홈</a></div></section>
<section class="hb-card"><div class="hb-table-wrap"><table class="hb-table"><thead><tr><th>구분</th><th>순서표</th><th>항목</th><th>상태</th><th>관리</th></tr></thead><tbody>
<?php for($i=0; $row=sql_fetch_array($res); $i++){ $cnt=hb_sequence_item_count($row['seq_id']); ?>
<tr><td><?php echo hb_sequence_type_label($row['seq_type']); ?></td><td><strong><?php echo hb_e($row['seq_title']); ?></strong><span class="hb-muted-mini"><?php echo hb_e($row['seq_memo']); ?></span></td><td><?php echo $cnt; ?>개<span class="hb-muted-mini"><?php echo hb_e(hb_sequence_item_titles($row['seq_id'])); ?></span></td><td><?php echo $row['seq_use'] ? '<span class="hb-ok">사용</span>' : '<span class="hb-off">꺼짐</span>'; ?></td><td class="hb-row-actions"><a class="hb-btn hb-btn-small hb-btn-primary" href="<?php echo HB_URL; ?>/admin/sequence_runner.php?seq_id=<?php echo (int)$row['seq_id']; ?>">진행판</a><a class="hb-btn hb-btn-small" href="<?php echo HB_URL; ?>/admin/sequence_form.php?seq_id=<?php echo (int)$row['seq_id']; ?>">수정</a><a class="hb-btn hb-btn-small hb-danger" href="<?php echo HB_URL; ?>/admin/sequence_delete.php?seq_id=<?php echo (int)$row['seq_id']; ?>" onclick="return confirm('삭제할까요?');">삭제</a></td></tr>
<?php } if($i===0){ ?><tr><td colspan="5"><div class="hb-empty"><div class="hb-empty-icon">🎚️</div><strong>순서표가 없습니다</strong><p>예배/방송/행사용 진행표를 만들어보세요.</p></div></td></tr><?php } ?>
</tbody></table></div></section>
</div></main></div>
<?php include_once(G5_PATH.'/tail.php'); ?>
