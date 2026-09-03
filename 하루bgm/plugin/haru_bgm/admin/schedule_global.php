<?php
include_once('./_common.php');
$g5['title'] = '하루브금 공통 시간표';
$can_write = hb_user_has_admin_auth('990140', 'w');
$can_delete = hb_user_has_admin_auth('990140', 'd');
include_once(HB_PATH.'/admin/_head.php');
$schedule = hb_table('schedule');
$music = hb_table('music');
$res = sql_query("SELECT s.*, m.mf_title, m.mf_source, m.mf_youtube_id FROM `{$schedule}` s LEFT JOIN `{$music}` m ON s.mf_id=m.mf_id WHERE s.sc_scope='global' ORDER BY s.sc_time ASC, s.sc_id DESC", false);
?>
<div id="haruBgmAdminApp" class="hb-app"><?php echo hb_nav_admin(); ?><main class="hb-app-main"><div class="hb-wrap">
    <section class="hb-page-head"><div><p class="hb-kicker">ADMIN</p><h1>공통 시간표</h1><p>사이트 전체 방송에 적용되는 공통 시간표입니다.</p></div><div class="hb-actions"><?php if ($can_write) { ?><a class="hb-btn hb-btn-primary" href="<?php echo HB_URL; ?>/admin/schedule_form.php">+ 공통 시간 추가</a><?php } ?></div></section>
    <section class="hb-card"><div class="hb-table-wrap"><table class="hb-table"><thead><tr><th>시간</th><th>제목</th><th>음악/YouTube</th><th>요일</th><th>기간</th><th>상태</th><th>관리</th></tr></thead><tbody>
    <?php for ($i=0; $res && ($row=sql_fetch_array($res)); $i++) { ?>
        <tr><td data-hb-label="시간"><strong><?php echo hb_schedule_time_label($row); ?></strong><span class="hb-muted-mini"><?php echo hb_schedule_mode_label($row); ?><?php echo hb_schedule_is_range($row) && $row['sc_repeat'] ? ' · 반복' : ''; ?></span></td><td data-hb-label="제목"><?php echo hb_e($row['sc_title']); ?></td><td data-hb-label="음악/YouTube"><?php $sc_cnt = hb_schedule_item_count($row['sc_id']); $sc_titles = hb_schedule_item_titles($row['sc_id']); if (!$sc_titles) $sc_titles = $row['mf_title']; echo hb_e($sc_titles); ?> <span class="hb-muted-mini"><?php echo $sc_cnt > 1 ? '혼합 세트 · '.$sc_cnt.'개' : (isset($row['mf_source']) && $row['mf_source']==='youtube' ? 'YouTube · '.hb_e($row['mf_youtube_id']) : '파일 음악'); ?></span></td><td data-hb-label="요일"><?php echo hb_days_label($row['sc_days']); ?></td><td data-hb-label="기간"><?php echo hb_e($row['sc_start_date'] ?: '상시'); ?> ~ <?php echo hb_e($row['sc_end_date'] ?: '상시'); ?></td><td data-hb-label="상태"><?php echo $row['sc_use'] ? '<span class="hb-ok">사용</span>' : '<span class="hb-off">꺼짐</span>'; ?></td><td data-hb-label="관리" class="hb-row-actions"><?php if ($can_write) { ?><a class="hb-btn hb-btn-small" href="<?php echo HB_URL; ?>/admin/schedule_form.php?sc_id=<?php echo (int)$row['sc_id']; ?>">수정</a><?php } ?><?php if ($can_delete) { ?><form method="post" action="<?php echo HB_URL; ?>/admin/schedule_delete.php" onsubmit="return confirm('삭제할까요?');"><?php echo hb_csrf_field(); ?><input type="hidden" name="sc_id" value="<?php echo (int)$row['sc_id']; ?>"><button type="submit" class="hb-btn hb-btn-small hb-danger">삭제</button></form><?php } ?></td></tr>
    <?php } if ($i === 0) { ?><tr><td colspan="7"><div class="hb-empty"><div class="hb-empty-icon">■</div><strong>공통 시간표가 없습니다</strong></div></td></tr><?php } ?>
    </tbody></table></div></section>
</div></main></div>
<?php include_once(HB_PATH.'/admin/_tail.php'); 
