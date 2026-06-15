<?php
include_once('./_common.php');
$g5['title'] = '하루브금 개인 시간표 현황';
include_once(G5_PATH.'/head.php');
$schedule = hb_table('schedule');
$music = hb_table('music');
$res = sql_query("SELECT s.*, m.mf_title FROM `{$schedule}` s LEFT JOIN `{$music}` m ON s.mf_id=m.mf_id WHERE s.sc_scope='user' ORDER BY s.sc_created_at DESC LIMIT 300");
?>
<link rel="stylesheet" href="<?php echo HB_URL; ?>/assets/haru_bgm.css?ver=20260615b">
<div class="hb-wrap">
    <section class="hb-page-head"><div><p class="hb-kicker">ADMIN</p><h1>개인 시간표 현황</h1><p>회원들이 만든 개인 시간표를 확인합니다.</p></div><a class="hb-btn" href="<?php echo HB_URL; ?>/admin/index.php">관리자 홈</a></section>
    <section class="hb-card"><div class="hb-table-wrap"><table class="hb-table"><thead><tr><th>회원</th><th>시간</th><th>제목</th><th>음악</th><th>요일</th><th>상태</th></tr></thead><tbody>
    <?php for ($i=0; $row=sql_fetch_array($res); $i++) { ?>
        <tr><td><?php echo hb_e($row['mb_id']); ?></td><td><strong><?php echo hb_time_hm($row['sc_time']); ?></strong></td><td><?php echo hb_e($row['sc_title']); ?></td><td><?php echo hb_e($row['mf_title']); ?></td><td><?php echo hb_days_label($row['sc_days']); ?></td><td><?php echo $row['sc_use'] ? '<span class="hb-ok">사용</span>' : '<span class="hb-off">꺼짐</span>'; ?></td></tr>
    <?php } if ($i === 0) { ?><tr><td colspan="6"><div class="hb-empty"><div class="hb-empty-icon">👤</div><strong>개인 시간표가 없습니다</strong></div></td></tr><?php } ?>
    </tbody></table></div></section>
</div>
<?php include_once(G5_PATH.'/tail.php'); ?>
