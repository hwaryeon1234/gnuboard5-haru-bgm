<?php
include_once('./_common.php');
$g5['title'] = '하루브금 공통 시간대';
$hb_haru_head_row_was_set = array_key_exists('row', get_defined_vars());
$hb_haru_head_row_backup = $hb_haru_head_row_was_set ? $row : null;
include_once(G5_PATH.'/head.php');
if ($hb_haru_head_row_was_set) {
    $row = $hb_haru_head_row_backup;
} else {
    unset($row);
}
unset($hb_haru_head_row_was_set, $hb_haru_head_row_backup);
$block = hb_table('block');
$res = sql_query("SELECT * FROM `{$block}` WHERE bl_scope='global' ORDER BY bl_start_time ASC, bl_id DESC");
?>
<link rel="stylesheet" href="<?php echo HB_URL; ?>/assets/haru_bgm.css?ver=20260625-radiov3">
<div class="hb-app"><?php echo hb_nav_admin(); ?><main class="hb-app-main"><div class="hb-wrap">
    <section class="hb-page-head"><div><p class="hb-kicker">ADMIN</p><h1>공통 시간대 묶음</h1><p>특정 시간대 안에서 여러 음악을 순서대로 또는 랜덤으로 재생합니다.</p></div><div class="hb-actions"><a class="hb-btn" href="<?php echo HB_URL; ?>/admin/index.php">관리자 홈</a><a class="hb-btn hb-btn-primary" href="<?php echo HB_URL; ?>/admin/block_form.php">+ 공통 시간대 추가</a></div></section>
    <section class="hb-card"><div class="hb-table-wrap"><table class="hb-table"><thead><tr><th>시간대</th><th>제목</th><th>곡 수</th><th>방식</th><th>요일</th><th>기간</th><th>상태</th><th>관리</th></tr></thead><tbody>
    <?php for ($i=0; $row=sql_fetch_array($res); $i++) { $cnt = hb_block_item_count($row['bl_id']); ?>
        <tr>
            <td><strong><?php echo hb_time_hm($row['bl_start_time']); ?> ~ <?php echo hb_time_hm($row['bl_end_time']); ?></strong></td>
            <td><?php echo hb_e($row['bl_title']); ?><br><span class="hb-muted-mini"><?php echo hb_e(hb_block_item_titles($row['bl_id'])); ?></span></td>
            <td><?php echo $cnt; ?>곡</td>
            <td><?php echo hb_play_mode_label($row['bl_play_mode']); ?><?php echo $row['bl_repeat'] ? ' · 반복' : ' · 1회'; ?></td>
            <td><?php echo hb_days_label($row['bl_days']); ?></td>
            <td><?php echo hb_e($row['bl_start_date'] ?: '상시'); ?> ~ <?php echo hb_e($row['bl_end_date'] ?: '상시'); ?></td>
            <td><?php echo $row['bl_use'] ? '<span class="hb-ok">사용</span>' : '<span class="hb-off">꺼짐</span>'; ?></td>
            <td class="hb-row-actions"><a class="hb-btn hb-btn-small" href="<?php echo HB_URL; ?>/admin/block_form.php?bl_id=<?php echo (int)$row['bl_id']; ?>">수정</a><a class="hb-btn hb-btn-small hb-danger" href="<?php echo HB_URL; ?>/admin/block_delete.php?bl_id=<?php echo (int)$row['bl_id']; ?>" onclick="return confirm('삭제할까요?');">삭제</a></td>
        </tr>
    <?php } if ($i === 0) { ?><tr><td colspan="8"><div class="hb-empty"><div class="hb-empty-icon">🎼</div><strong>공통 시간대 묶음이 없습니다</strong><p>예: 10:00~11:00 안에 여러 곡을 넣어 매장/교회/방송 BGM처럼 사용할 수 있어요.</p></div></td></tr><?php } ?>
    </tbody></table></div></section>
</div></main></div>
<?php include_once(G5_PATH.'/tail.php'); ?>
