<?php
include_once('./_common.php');
$g5['title'] = '하루브금 설치 점검';
$checks = hb_health_checks();
include_once(G5_PATH.'/head.php');
?>
<link rel="stylesheet" href="<?php echo HB_URL; ?>/assets/haru_bgm.css?ver=20260616e">
<div class="hb-wrap">
    <section class="hb-page-head"><div><p class="hb-kicker">ADMIN</p><h1>설치 점검</h1><p>DB 테이블, 업로드 폴더, PHP 업로드 제한을 한 번에 확인합니다.</p></div><a class="hb-btn" href="<?php echo HB_URL; ?>/admin/index.php">관리자 홈</a></section>
    <?php echo hb_nav_admin(); ?>
    <section class="hb-card">
        <div class="hb-table-wrap"><table class="hb-table"><thead><tr><th>항목</th><th>상태</th><th>내용</th></tr></thead><tbody>
        <?php foreach ($checks as $c) { ?>
            <tr><td><?php echo hb_e($c['label']); ?></td><td><?php echo $c['ok'] ? '<span class="hb-ok">정상</span>' : '<span class="hb-off">확인 필요</span>'; ?></td><td><?php echo hb_e($c['message']); ?></td></tr>
        <?php } ?>
        </tbody></table></div>
    </section>
</div>
<?php include_once(G5_PATH.'/tail.php'); ?>
