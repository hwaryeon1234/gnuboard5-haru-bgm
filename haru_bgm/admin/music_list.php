<?php
include_once('./_common.php');
$g5['title'] = '하루브금 음악 관리';
$hb_haru_head_row_was_set = array_key_exists('row', get_defined_vars());
$hb_haru_head_row_backup = $hb_haru_head_row_was_set ? $row : null;
include_once(G5_PATH.'/head.php');
if ($hb_haru_head_row_was_set) {
    $row = $hb_haru_head_row_backup;
} else {
    unset($row);
}
unset($hb_haru_head_row_was_set, $hb_haru_head_row_backup);
$music = hb_table('music');
$schedule = hb_table('schedule');
$block_item = hb_table('block_item');
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$rows = 100;
$from = ($page - 1) * $rows;
$total = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$music}`");
$total_count = (int)$total['cnt'];
$total_page = max(1, (int)ceil($total_count / $rows));
$res = sql_query("SELECT * FROM `{$music}` ORDER BY mf_id DESC LIMIT {$from}, {$rows}");
?>
<link rel="stylesheet" href="<?php echo HB_URL; ?>/assets/haru_bgm.css?ver=20260625-radiov3">
<div class="hb-app"><?php echo hb_nav_admin(); ?><main class="hb-app-main"><div class="hb-wrap">
    <section class="hb-page-head">
        <div><p class="hb-kicker">ADMIN</p><h1>음악 보관함</h1><p>한 페이지에 최대 100개까지 넉넉하게 표시합니다. 파일 음악과 YouTube 링크를 같이 관리할 수 있어요.</p></div>
        <div class="hb-actions"><a class="hb-btn" href="<?php echo HB_URL; ?>/admin/index.php">관리자 홈</a><a class="hb-btn hb-btn-primary" href="<?php echo HB_URL; ?>/admin/music_form.php">+ 음악 등록</a></div>
    </section>
    <section class="hb-card">
        <div class="hb-card-head"><div><p class="hb-kicker">LIBRARY</p><h2>등록 음악 <?php echo number_format($total_count); ?>개</h2></div><span class="hb-pill">page <?php echo $page; ?> / <?php echo $total_page; ?></span></div>
        <div class="hb-table-wrap">
            <table class="hb-table"><thead><tr><th>ID</th><th>제목</th><th>종류</th><th>파일/링크</th><th>사용처</th><th>볼륨</th><th>상태</th><th>미리듣기</th><th>관리</th></tr></thead><tbody>
            <?php for ($i=0; $row=sql_fetch_array($res); $i++) {
                $mf_id = (int)$row['mf_id'];
                $sc = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$schedule}` WHERE mf_id='{$mf_id}'");
                $bi = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$block_item}` WHERE mf_id='{$mf_id}'");
                $source = isset($row['mf_source']) ? $row['mf_source'] : 'file';
            ?>
                <tr>
                    <td><?php echo $mf_id; ?></td>
                    <td><strong><?php echo hb_e($row['mf_title']); ?></strong><?php if ($row['mf_memo']) { ?><span class="hb-muted-mini"><?php echo hb_e(cut_str(strip_tags($row['mf_memo']), 40)); ?></span><?php } ?></td>
                    <td><span class="hb-pill hb-pill-mini"><?php echo hb_music_source_label($row); ?></span></td>
                    <td><?php echo $source === 'youtube' ? '<a href="'.hb_e($row['mf_youtube_url']).'" target="_blank" rel="noopener">'.hb_e($row['mf_youtube_id']).'</a>' : hb_e($row['mf_org_name']); ?></td>
                    <td><?php echo (int)$sc['cnt']; ?>개 시간표 · <?php echo (int)$bi['cnt']; ?>개 시간대</td>
                    <td><?php echo (int)$row['mf_volume']; ?>%</td>
                    <td><?php echo $row['mf_use'] ? '<span class="hb-ok">사용</span>' : '<span class="hb-off">꺼짐</span>'; ?></td>
                    <td><?php if ($source === 'youtube') { ?><a class="hb-btn hb-btn-small" href="<?php echo hb_e($row['mf_youtube_url']); ?>" target="_blank" rel="noopener">YouTube</a><?php } else { ?><audio controls preload="none" src="<?php echo hb_music_url($row['mf_file']); ?>"></audio><?php } ?></td>
                    <td class="hb-row-actions"><a class="hb-btn hb-btn-small" href="<?php echo HB_URL; ?>/admin/music_form.php?mf_id=<?php echo $mf_id; ?>">수정</a><a class="hb-btn hb-btn-small hb-danger" href="<?php echo HB_URL; ?>/admin/music_delete.php?mf_id=<?php echo $mf_id; ?>" onclick="return confirm('삭제할까요? 연결된 시간표와 시간대 목록에서도 함께 정리됩니다.');">삭제</a></td>
                </tr>
            <?php } if ($i === 0) { ?><tr><td colspan="9"><div class="hb-empty"><div class="hb-empty-icon">🎵</div><strong>등록된 음악이 없습니다</strong></div></td></tr><?php } ?>
            </tbody></table>
        </div>
        <?php if ($total_page > 1) { ?><div class="hb-actions hb-pager"><?php if ($page > 1) { ?><a class="hb-btn" href="?page=<?php echo $page-1; ?>">이전</a><?php } ?><span class="hb-muted">100개씩 표시 중</span><?php if ($page < $total_page) { ?><a class="hb-btn" href="?page=<?php echo $page+1; ?>">다음</a><?php } ?></div><?php } ?>
    </section>
</div></main></div>
<?php include_once(G5_PATH.'/tail.php'); ?>
