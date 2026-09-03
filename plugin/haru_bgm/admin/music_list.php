<?php
include_once('./_common.php');
$g5['title'] = '하루브금 음악 관리';
$can_write = hb_user_has_admin_auth('990130', 'w');
$can_delete = hb_user_has_admin_auth('990130', 'd');
include_once(HB_PATH.'/admin/_head.php');
$music = hb_table('music');
$schedule = hb_table('schedule');
$block_item = hb_table('block_item');
$sequence_item = hb_table('sequence_item');
$page = isset($_GET['page']) ? max(1, hb_int_value($_GET['page'], 1)) : 1;
$rows = 100;
$total = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$music}`", false);
$total_count = $total && isset($total['cnt']) ? (int)$total['cnt'] : 0;
$total_page = max(1, (int)ceil($total_count / $rows));
$page = min($page, $total_page);
$from = ($page - 1) * $rows;
$res = sql_query("SELECT * FROM `{$music}` ORDER BY mf_id DESC LIMIT {$from}, {$rows}", false);
$music_rows = array();
$music_ids = array();
while ($res && ($tmp = sql_fetch_array($res))) { $music_rows[] = $tmp; $music_ids[] = (int)$tmp['mf_id']; }
$usage_map = hb_music_usage_map($music_ids);
?>
<div id="haruBgmAdminApp" class="hb-app"><?php echo hb_nav_admin(); ?><main class="hb-app-main"><div class="hb-wrap">
    <section class="hb-page-head">
        <div><p class="hb-kicker">ADMIN</p><h1>음악 보관함</h1><p>한 페이지에 최대 100개까지 넉넉하게 표시합니다. 파일 음악과 YouTube 링크를 같이 관리할 수 있어요.</p></div>
        <div class="hb-actions"><?php if ($can_write) { ?><a class="hb-btn hb-btn-primary" href="<?php echo HB_URL; ?>/admin/music_form.php">+ 음악 등록</a><?php } ?></div>
    </section>
    <section class="hb-card">
        <div class="hb-card-head"><div><p class="hb-kicker">LIBRARY</p><h2>등록 음악 <?php echo number_format($total_count); ?>개</h2></div><span class="hb-pill">page <?php echo $page; ?> / <?php echo $total_page; ?></span></div>
        <div class="hb-table-wrap">
            <table class="hb-table"><thead><tr><th>ID</th><th>제목</th><th>종류</th><th>파일/링크</th><th>사용처</th><th>볼륨</th><th>상태</th><th>미리듣기</th><th>관리</th></tr></thead><tbody>
            <?php for ($i=0; $i<count($music_rows); $i++) { $row = $music_rows[$i];
                $mf_id = (int)$row['mf_id'];
                $usage = isset($usage_map[$mf_id]) ? $usage_map[$mf_id] : array('schedule'=>0,'block'=>0,'sequence'=>0,'broadcast'=>0,'global'=>0);
                $source = isset($row['mf_source']) ? $row['mf_source'] : 'file';
            ?>
                <tr>
                    <td data-hb-label="ID"><?php echo $mf_id; ?></td>
                    <td data-hb-label="제목"><strong><?php echo hb_e($row['mf_title']); ?></strong><?php if ($row['mf_memo']) { ?><span class="hb-muted-mini"><?php echo hb_e(cut_str(strip_tags($row['mf_memo']), 40)); ?></span><?php } ?></td>
                    <td data-hb-label="종류"><span class="hb-pill hb-pill-mini"><?php echo hb_music_source_label($row); ?></span></td>
                    <td data-hb-label="파일/링크"><?php echo $source === 'youtube' ? '<a href="'.hb_e(hb_youtube_watch_url($row['mf_youtube_id'])).'" target="_blank" rel="noopener">'.hb_e($row['mf_youtube_id']).'</a>' : hb_e($row['mf_org_name']); ?></td>
                    <td data-hb-label="사용처"><?php echo (int)$usage['schedule']; ?>개 시간표 · <?php echo (int)$usage['block']; ?>개 시간대 · <?php echo (int)$usage['sequence']; ?>개 순서표<?php echo !empty($usage['broadcast']) ? ' · 전체송출 중' : ''; ?></td>
                    <td data-hb-label="볼륨"><?php echo (int)$row['mf_volume']; ?>%</td>
                    <td data-hb-label="상태"><?php echo $row['mf_use'] ? '<span class="hb-ok">사용</span>' : '<span class="hb-off">꺼짐</span>'; ?></td>
                    <td data-hb-label="미리듣기"><?php if ($source === 'youtube') { ?><a class="hb-btn hb-btn-small" href="<?php echo hb_e(hb_youtube_watch_url($row['mf_youtube_id'])); ?>" target="_blank" rel="noopener">YouTube</a><?php } else { ?><audio controls preload="none" src="<?php echo hb_music_url($row['mf_file'], $mf_id); ?>"></audio><?php } ?></td>
                    <td data-hb-label="관리" class="hb-row-actions"><?php if ($can_write) { ?><a class="hb-btn hb-btn-small" href="<?php echo HB_URL; ?>/admin/music_form.php?mf_id=<?php echo $mf_id; ?>">수정</a><?php } ?><?php if ($can_delete) { ?><form method="post" action="<?php echo HB_URL; ?>/admin/music_delete.php" onsubmit="return confirm('삭제할까요? 연결된 시간표와 시간대 목록에서도 함께 정리됩니다.');"><?php echo hb_csrf_field(); ?><input type="hidden" name="mf_id" value="<?php echo $mf_id; ?>"><button type="submit" class="hb-btn hb-btn-small hb-danger">삭제</button></form><?php } ?></td>
                </tr>
            <?php } if ($i === 0) { ?><tr><td colspan="9"><div class="hb-empty"><div class="hb-empty-icon">■</div><strong>등록된 음악이 없습니다</strong></div></td></tr><?php } ?>
            </tbody></table>
        </div>
        <?php if ($total_page > 1) { ?><div class="hb-actions hb-pager"><?php if ($page > 1) { ?><a class="hb-btn" href="?page=<?php echo $page-1; ?>">이전</a><?php } ?><span class="hb-muted">100개씩 표시 중</span><?php if ($page < $total_page) { ?><a class="hb-btn" href="?page=<?php echo $page+1; ?>">다음</a><?php } ?></div><?php } ?>
    </section>
</div></main></div>
<?php include_once(HB_PATH.'/admin/_tail.php'); 
