<?php
include_once('./_common.php');
$g5['title'] = '하루브금 순서표 모드';
$can_write = hb_user_has_admin_auth('990160', 'w');
$can_delete = hb_user_has_admin_auth('990160', 'd');
include_once(HB_PATH.'/admin/_head.php');
$sequence = hb_table('sequence');
$res = sql_query("SELECT * FROM `{$sequence}` ORDER BY seq_sort ASC, seq_id DESC", false);
?>
<div id="haruBgmAdminApp" class="hb-app"><?php echo hb_nav_admin(); ?><main class="hb-app-main"><div class="hb-wrap">
<section class="hb-page-head"><div><p class="hb-kicker">SEQUENCE</p><h1>순서표 모드</h1><p>교회 예배, 방송, 행사처럼 담당자가 다음 곡을 눌러 진행하는 공용 진행표입니다.</p></div><div class="hb-actions"><?php if ($can_write) { ?><a class="hb-btn hb-btn-primary" href="<?php echo HB_URL; ?>/admin/sequence_form.php">+ 순서표 추가</a><?php } ?></div></section>
<section class="hb-card"><div class="hb-table-wrap"><table class="hb-table"><thead><tr><th>구분</th><th>순서표</th><th>항목</th><th>상태</th><th>관리</th></tr></thead><tbody>
<?php for($i=0; $res && ($row=sql_fetch_array($res)); $i++){ $cnt=hb_sequence_item_count($row['seq_id']); ?>
<tr><td data-hb-label="구분"><?php echo hb_sequence_type_label($row['seq_type']); ?></td><td data-hb-label="순서표"><strong><?php echo hb_e($row['seq_title']); ?></strong><span class="hb-muted-mini"><?php echo hb_e($row['seq_memo']); ?></span></td><td data-hb-label="항목"><?php echo $cnt; ?>개<span class="hb-muted-mini"><?php echo hb_e(hb_sequence_item_titles($row['seq_id'])); ?></span></td><td data-hb-label="상태"><?php echo $row['seq_use'] ? '<span class="hb-ok">사용</span>' : '<span class="hb-off">꺼짐</span>'; ?></td><td data-hb-label="관리" class="hb-row-actions"><?php if ($row['seq_use'] && $cnt > 0) { ?><a class="hb-btn hb-btn-small hb-btn-primary" href="<?php echo HB_URL; ?>/admin/sequence_runner.php?seq_id=<?php echo (int)$row['seq_id']; ?>">진행판</a><?php } ?><?php if ($can_write) { ?><a class="hb-btn hb-btn-small" href="<?php echo HB_URL; ?>/admin/sequence_form.php?seq_id=<?php echo (int)$row['seq_id']; ?>">수정</a><?php } ?><?php if ($can_delete) { ?><form method="post" action="<?php echo HB_URL; ?>/admin/sequence_delete.php" onsubmit="return confirm('삭제할까요?');"><?php echo hb_csrf_field(); ?><input type="hidden" name="seq_id" value="<?php echo (int)$row['seq_id']; ?>"><button type="submit" class="hb-btn hb-btn-small hb-danger">삭제</button></form><?php } ?></td></tr>
<?php } if($i===0){ ?><tr><td colspan="5"><div class="hb-empty"><div class="hb-empty-icon">■</div><strong>순서표가 없습니다</strong><p>예배/방송/행사용 진행표를 만들어보세요.</p></div></td></tr><?php } ?>
</tbody></table></div></section>
</div></main></div>
<?php include_once(HB_PATH.'/admin/_tail.php'); 
