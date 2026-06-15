<?php
include_once('./_common.php');
$g5['title'] = '하루브금 공통 시간표';
include_once(G5_PATH.'/head.php');
$schedule = hb_table('schedule');
$music = hb_table('music');
$res = sql_query("SELECT s.*, m.mf_title, m.mf_source, m.mf_youtube_id FROM `{$schedule}` s LEFT JOIN `{$music}` m ON s.mf_id=m.mf_id WHERE s.sc_scope='global' ORDER BY s.sc_time ASC, s.sc_id DESC");
?>
<link rel="stylesheet" href="<?php echo HB_URL; ?>/assets/haru_bgm.css?ver=20260616e">
<div class="hb-wrap">
    <section class="hb-page-head"><div><p class="hb-kicker">ADMIN</p><h1>공통 시간표</h1><p>모든 로그인 회원에게 적용되는 시간표입니다.</p></div><div class="hb-actions"><a class="hb-btn" href="<?php echo HB_URL; ?>/admin/index.php">관리자 홈</a><a class="hb-btn" href="<?php echo HB_URL; ?>/admin/block_global.php">공통 시간대</a><a class="hb-btn hb-btn-primary" href="<?php echo HB_URL; ?>/admin/schedule_form.php">+ 공통 시간 추가</a></div></section>
    <section class="hb-card"><div class="hb-table-wrap"><table class="hb-table"><thead><tr><th>시간</th><th>제목</th><th>음악/YouTube</th><th>요일</th><th>기간</th><th>상태</th><th>관리</th></tr></thead><tbody>
    <?php for ($i=0; $row=sql_fetch_array($res); $i++) { ?>
        <tr><td><strong><?php echo hb_schedule_time_label($row); ?></strong><span class="hb-muted-mini"><?php echo hb_schedule_mode_label($row); ?><?php echo hb_schedule_is_range($row) && $row['sc_repeat'] ? ' · 반복' : ''; ?></span></td><td><?php echo hb_e($row['sc_title']); ?></td><td><?php $sc_cnt = hb_schedule_item_count($row['sc_id']); $sc_titles = hb_schedule_item_titles($row['sc_id']); if (!$sc_titles) $sc_titles = $row['mf_title']; echo hb_e($sc_titles); ?> <span class="hb-muted-mini"><?php echo $sc_cnt > 1 ? '혼합 세트 · '.$sc_cnt.'개' : (isset($row['mf_source']) && $row['mf_source']==='youtube' ? 'YouTube · '.hb_e($row['mf_youtube_id']) : '파일 음악'); ?></span></td><td><?php echo hb_days_label($row['sc_days']); ?></td><td><?php echo hb_e($row['sc_start_date'] ?: '상시'); ?> ~ <?php echo hb_e($row['sc_end_date'] ?: '상시'); ?></td><td><?php echo $row['sc_use'] ? '<span class="hb-ok">사용</span>' : '<span class="hb-off">꺼짐</span>'; ?></td><td class="hb-row-actions"><a class="hb-btn hb-btn-small" href="<?php echo HB_URL; ?>/admin/schedule_form.php?sc_id=<?php echo (int)$row['sc_id']; ?>">수정</a><a class="hb-btn hb-btn-small hb-danger" href="<?php echo HB_URL; ?>/admin/schedule_delete.php?sc_id=<?php echo (int)$row['sc_id']; ?>" onclick="return confirm('삭제할까요?');">삭제</a></td></tr>
    <?php } if ($i === 0) { ?><tr><td colspan="7"><div class="hb-empty"><div class="hb-empty-icon">🗓️</div><strong>공통 시간표가 없습니다</strong></div></td></tr><?php } ?>
    </tbody></table></div></section>
</div>
<?php include_once(G5_PATH.'/tail.php'); ?>
