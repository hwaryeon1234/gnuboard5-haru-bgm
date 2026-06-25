<?php
include_once('./_common.php');
hb_require_member_bgm_enabled();
$g5['title'] = '내 시간대 묶음';
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
$mb_id = hb_escape($member['mb_id']);
$res = sql_query("SELECT * FROM `{$block}` WHERE bl_scope='user' AND mb_id='{$mb_id}' ORDER BY bl_start_time ASC, bl_id DESC");
?>
<link rel="stylesheet" href="<?php echo HB_URL; ?>/assets/haru_bgm.css?ver=20260625-radiov2">
<div class="hb-wrap hb-radio">
    <section class="hb-page-head"><div><p class="hb-kicker">MY PLAYLIST</p><h1>내 시간대 묶음</h1><p>특정 시간대 안에서 여러 음악을 내 브라우저에서만 이어서 재생합니다.</p></div><div class="hb-actions"><a class="hb-btn" href="<?php echo HB_URL; ?>/index.php">하루브금 홈</a><a class="hb-btn" href="<?php echo HB_URL; ?>/my_schedule.php">내 시간표</a><a class="hb-btn hb-btn-primary" href="<?php echo HB_URL; ?>/my_block_form.php">+ 시간대 추가</a></div></section>
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
            <td class="hb-row-actions"><a class="hb-btn hb-btn-small" href="<?php echo HB_URL; ?>/my_block_form.php?bl_id=<?php echo (int)$row['bl_id']; ?>">수정</a><a class="hb-btn hb-btn-small hb-danger" href="<?php echo HB_URL; ?>/my_block_delete.php?bl_id=<?php echo (int)$row['bl_id']; ?>" onclick="return confirm('삭제할까요?');">삭제</a></td>
        </tr>
    <?php } if ($i === 0) { ?><tr><td colspan="8"><div class="hb-empty"><div class="hb-empty-icon">🎚️</div><strong>내 시간대 묶음이 없습니다</strong><p>예: 21:00~22:00 작업 BGM 여러 곡, 06:00~06:30 기상 음악 등을 만들 수 있어요.</p></div></td></tr><?php } ?>
    </tbody></table></div></section>
</div>
<?php include_once(G5_PATH.'/tail.php'); ?>
