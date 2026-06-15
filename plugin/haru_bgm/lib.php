<?php
if (!defined('_GNUBOARD_')) exit;

function hb_table($name) {
    if (defined('G5_TABLE_PREFIX')) {
        $prefix = G5_TABLE_PREFIX;
    } elseif (isset($GLOBALS['g5']['table_prefix']) && $GLOBALS['g5']['table_prefix']) {
        $prefix = $GLOBALS['g5']['table_prefix'];
    } else {
        $prefix = 'g5_';
    }
    return $prefix.'haru_bgm_'.$name;
}

function hb_escape($str) {
    if (function_exists('sql_real_escape_string')) {
        return sql_real_escape_string($str);
    }
    return addslashes((string)$str);
}

function hb_e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function hb_ensure_data_dir() {
    if (!is_dir(HB_DATA_PATH)) {
        @mkdir(HB_DATA_PATH, G5_DIR_PERMISSION, true);
        @chmod(HB_DATA_PATH, G5_DIR_PERMISSION);
    }
    $index = HB_DATA_PATH.'/index.php';
    if (!file_exists($index)) {
        @file_put_contents($index, "<?php\n// silence\n");
        @chmod($index, G5_FILE_PERMISSION);
    }
}

function hb_ensure_tables() {
    $music = hb_table('music');
    $schedule = hb_table('schedule');
    $log = hb_table('play_log');

    sql_query("CREATE TABLE IF NOT EXISTS `{$music}` (
        `mf_id` int(11) NOT NULL AUTO_INCREMENT,
        `mf_title` varchar(255) NOT NULL,
        `mf_source` enum('file','youtube') NOT NULL DEFAULT 'file',
        `mf_file` varchar(255) NOT NULL DEFAULT '',
        `mf_youtube_url` varchar(500) NOT NULL DEFAULT '',
        `mf_youtube_id` varchar(30) NOT NULL DEFAULT '',
        `mf_org_name` varchar(255) NOT NULL DEFAULT '',
        `mf_mime` varchar(100) NOT NULL DEFAULT '',
        `mf_size` int(11) NOT NULL DEFAULT 0,
        `mf_volume` tinyint(3) NOT NULL DEFAULT 80,
        `mf_type` varchar(30) NOT NULL DEFAULT 'music',
        `mf_memo` text NULL,
        `mf_use` tinyint(1) NOT NULL DEFAULT 1,
        `mb_id` varchar(50) NOT NULL DEFAULT '',
        `mf_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `mf_updated_at` datetime NULL DEFAULT NULL,
        PRIMARY KEY (`mf_id`),
        KEY `mf_use` (`mf_use`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false);

    sql_query("CREATE TABLE IF NOT EXISTS `{$schedule}` (
        `sc_id` int(11) NOT NULL AUTO_INCREMENT,
        `sc_scope` enum('global','user') NOT NULL DEFAULT 'global',
        `mb_id` varchar(50) NOT NULL DEFAULT '',
        `mf_id` int(11) NOT NULL DEFAULT 0,
        `sc_title` varchar(255) NOT NULL,
        `sc_time` time NOT NULL,
        `sc_play_mode` enum('once','range') NOT NULL DEFAULT 'once',
        `sc_end_time` time NULL DEFAULT NULL,
        `sc_repeat` tinyint(1) NOT NULL DEFAULT 0,
        `sc_days` varchar(50) NOT NULL DEFAULT '0,1,2,3,4,5,6',
        `sc_start_date` date NULL DEFAULT NULL,
        `sc_end_date` date NULL DEFAULT NULL,
        `sc_once` tinyint(1) NOT NULL DEFAULT 0,
        `sc_sort` int(11) NOT NULL DEFAULT 0,
        `sc_use` tinyint(1) NOT NULL DEFAULT 1,
        `sc_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `sc_updated_at` datetime NULL DEFAULT NULL,
        PRIMARY KEY (`sc_id`),
        KEY `scope_member` (`sc_scope`, `mb_id`),
        KEY `sc_time` (`sc_time`),
        KEY `sc_use` (`sc_use`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false);

    sql_query("CREATE TABLE IF NOT EXISTS `{$log}` (
        `pl_id` int(11) NOT NULL AUTO_INCREMENT,
        `sc_id` int(11) NOT NULL DEFAULT 0,
        `mf_id` int(11) NOT NULL DEFAULT 0,
        `sc_scope` varchar(30) NOT NULL DEFAULT '',
        `mb_id` varchar(50) NOT NULL DEFAULT '',
        `pl_played_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `pl_ip` varchar(45) NOT NULL DEFAULT '',
        `pl_user_agent` varchar(255) NOT NULL DEFAULT '',
        PRIMARY KEY (`pl_id`),
        KEY `member_date` (`mb_id`, `pl_played_at`),
        KEY `schedule` (`sc_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false);

    $block = hb_table('block');
    $block_item = hb_table('block_item');

    sql_query("CREATE TABLE IF NOT EXISTS `{$block}` (
        `bl_id` int(11) NOT NULL AUTO_INCREMENT,
        `bl_scope` enum('global','user') NOT NULL DEFAULT 'global',
        `mb_id` varchar(50) NOT NULL DEFAULT '',
        `bl_title` varchar(255) NOT NULL,
        `bl_start_time` time NOT NULL,
        `bl_end_time` time NOT NULL,
        `bl_days` varchar(50) NOT NULL DEFAULT '0,1,2,3,4,5,6',
        `bl_start_date` date NULL DEFAULT NULL,
        `bl_end_date` date NULL DEFAULT NULL,
        `bl_play_mode` enum('sequence','random') NOT NULL DEFAULT 'sequence',
        `bl_repeat` tinyint(1) NOT NULL DEFAULT 1,
        `bl_sort` int(11) NOT NULL DEFAULT 0,
        `bl_use` tinyint(1) NOT NULL DEFAULT 1,
        `bl_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `bl_updated_at` datetime NULL DEFAULT NULL,
        PRIMARY KEY (`bl_id`),
        KEY `scope_member` (`bl_scope`, `mb_id`),
        KEY `time_range` (`bl_start_time`, `bl_end_time`),
        KEY `bl_use` (`bl_use`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false);

    sql_query("CREATE TABLE IF NOT EXISTS `{$block_item}` (
        `bi_id` int(11) NOT NULL AUTO_INCREMENT,
        `bl_id` int(11) NOT NULL DEFAULT 0,
        `mf_id` int(11) NOT NULL DEFAULT 0,
        `bi_sort` int(11) NOT NULL DEFAULT 0,
        `bi_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`bi_id`),
        KEY `block_sort` (`bl_id`, `bi_sort`),
        KEY `music` (`mf_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false);

    $schedule_item = hb_table('schedule_item');
    sql_query("CREATE TABLE IF NOT EXISTS `{$schedule_item}` (
        `si_id` int(11) NOT NULL AUTO_INCREMENT,
        `sc_id` int(11) NOT NULL DEFAULT 0,
        `mf_id` int(11) NOT NULL DEFAULT 0,
        `si_sort` int(11) NOT NULL DEFAULT 0,
        `si_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`si_id`),
        KEY `schedule_sort` (`sc_id`, `si_sort`),
        KEY `music` (`mf_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false);

    $sequence = hb_table('sequence');
    $sequence_item = hb_table('sequence_item');
    sql_query("CREATE TABLE IF NOT EXISTS `{$sequence}` (
        `seq_id` int(11) NOT NULL AUTO_INCREMENT,
        `seq_title` varchar(255) NOT NULL,
        `seq_type` varchar(30) NOT NULL DEFAULT 'general',
        `seq_memo` text NULL,
        `seq_use` tinyint(1) NOT NULL DEFAULT 1,
        `seq_sort` int(11) NOT NULL DEFAULT 0,
        `seq_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `seq_updated_at` datetime NULL DEFAULT NULL,
        PRIMARY KEY (`seq_id`),
        KEY `seq_use` (`seq_use`),
        KEY `seq_sort` (`seq_sort`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false);
    sql_query("CREATE TABLE IF NOT EXISTS `{$sequence_item}` (
        `siq_id` int(11) NOT NULL AUTO_INCREMENT,
        `seq_id` int(11) NOT NULL DEFAULT 0,
        `mf_id` int(11) NOT NULL DEFAULT 0,
        `siq_title` varchar(255) NOT NULL DEFAULT '',
        `siq_memo` varchar(255) NOT NULL DEFAULT '',
        `siq_sort` int(11) NOT NULL DEFAULT 0,
        `siq_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`siq_id`),
        KEY `sequence_sort` (`seq_id`, `siq_sort`),
        KEY `music` (`mf_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false);

    $settings = hb_table('settings');
    sql_query("CREATE TABLE IF NOT EXISTS `{$settings}` (
        `st_key` varchar(80) NOT NULL,
        `st_value` text NULL,
        `st_updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`st_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false);

    $member_access = hb_table('member_access');
    sql_query("CREATE TABLE IF NOT EXISTS `{$member_access}` (
        `ma_id` int(11) NOT NULL AUTO_INCREMENT,
        `mb_id` varchar(50) NOT NULL,
        `ma_enabled` tinyint(1) NOT NULL DEFAULT 1,
        `ma_memo` varchar(255) NOT NULL DEFAULT '',
        `ma_updated_by` varchar(50) NOT NULL DEFAULT '',
        `ma_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `ma_updated_at` datetime NULL DEFAULT NULL,
        PRIMARY KEY (`ma_id`),
        UNIQUE KEY `mb_id` (`mb_id`),
        KEY `ma_enabled` (`ma_enabled`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false);

    hb_ensure_music_columns();
    hb_ensure_schedule_columns();
    hb_ensure_log_columns();
    hb_seed_settings();
}

function hb_ensure_column($table, $column, $definition) {
    $table_sql = hb_escape($table);
    $column_sql = hb_escape($column);
    $row = sql_fetch("SHOW COLUMNS FROM `{$table}` LIKE '{$column_sql}'", false);
    if (!$row) {
        sql_query("ALTER TABLE `{$table}` ADD `{$column}` {$definition}", false);
    }
}

function hb_ensure_music_columns() {
    $music = hb_table('music');
    hb_ensure_column($music, 'mf_source', "enum('file','youtube') NOT NULL DEFAULT 'file' AFTER `mf_title`");
    hb_ensure_column($music, 'mf_youtube_url', "varchar(500) NOT NULL DEFAULT '' AFTER `mf_file`");
    hb_ensure_column($music, 'mf_youtube_id', "varchar(30) NOT NULL DEFAULT '' AFTER `mf_youtube_url`");
}


function hb_ensure_schedule_columns() {
    $schedule = hb_table('schedule');
    hb_ensure_column($schedule, 'sc_play_mode', "enum('once','range') NOT NULL DEFAULT 'once' AFTER `sc_time`");
    hb_ensure_column($schedule, 'sc_end_time', "time NULL DEFAULT NULL AFTER `sc_play_mode`");
    hb_ensure_column($schedule, 'sc_repeat', "tinyint(1) NOT NULL DEFAULT 0 AFTER `sc_end_time`");
}


function hb_ensure_log_columns() {
    $log = hb_table('play_log');
    hb_ensure_column($log, 'pl_action', "varchar(30) NOT NULL DEFAULT 'auto' AFTER `sc_scope`");
    hb_ensure_column($log, 'pl_status', "varchar(20) NOT NULL DEFAULT 'success' AFTER `pl_action`");
    hb_ensure_column($log, 'pl_message', "varchar(255) NOT NULL DEFAULT '' AFTER `pl_status`");
}

function hb_schedule_is_range($row) {
    return isset($row['sc_play_mode']) && $row['sc_play_mode'] === 'range' && !empty($row['sc_end_time']) && $row['sc_end_time'] !== '00:00:00';
}

function hb_schedule_mode_label($row) {
    return hb_schedule_is_range($row) ? '특정 시간 동안 재생' : '정각 1회 재생';
}

function hb_schedule_time_label($row) {
    if (hb_schedule_is_range($row)) {
        return hb_time_hm($row['sc_time']).' ~ '.hb_time_hm($row['sc_end_time']);
    }
    return hb_time_hm($row['sc_time']);
}

function hb_parse_youtube_bulk($text) {
    $text = trim((string)$text);
    if ($text === '') return array();
    $text = str_replace(array("\r", "\t", ","), "\n", $text);
    preg_match_all('/(?:https?:\/\/[^\s<>"\']+|[a-zA-Z0-9_-]{11})/u', $text, $matches);
    $out = array();
    $seen = array();
    foreach ($matches[0] as $raw) {
        $raw = trim($raw);
        if ($raw === '') continue;
        $id = hb_extract_youtube_id($raw);
        if (!$id || isset($seen[$id])) continue;
        $seen[$id] = true;
        $out[] = array('url' => $raw, 'id' => $id);
    }
    return $out;
}

function hb_create_youtube_musics_from_text($text, $title_prefix='', $volume=80, $type='music', $memo='', $mb_id='') {
    $links = hb_parse_youtube_bulk($text);
    $ids = array();
    $total = count($links);
    $idx = 0;
    foreach ($links as $link) {
        $idx++;
        $title = trim((string)$title_prefix);
        if ($title === '') $title = 'YouTube BGM '.$link['id'];
        if ($total > 1 && $title_prefix !== '') $title = $title_prefix.' '.sprintf('%02d', $idx);
        $mf_id = hb_find_or_create_youtube_music($link['url'], $title, $volume, $type, $memo, $mb_id);
        if ($mf_id > 0) $ids[] = $mf_id;
    }
    return array_values(array_unique($ids));
}


function hb_days_all() {
    return array('0'=>'일', '1'=>'월', '2'=>'화', '3'=>'수', '4'=>'목', '5'=>'금', '6'=>'토');
}

function hb_clean_days($days) {
    if (!is_array($days)) $days = array();
    $allow = array_keys(hb_days_all());
    $out = array();
    foreach ($days as $d) {
        $d = (string)(int)$d;
        if (in_array($d, $allow, true) && !in_array($d, $out, true)) $out[] = $d;
    }
    if (!$out) $out = $allow;
    sort($out, SORT_NUMERIC);
    return implode(',', $out);
}

function hb_days_label($days) {
    $map = hb_days_all();
    $parts = array_filter(explode(',', (string)$days), 'strlen');
    sort($parts, SORT_NUMERIC);
    if ($parts === array('0','1','2','3','4','5','6')) return '매일';
    if ($parts === array('1','2','3','4','5')) return '평일';
    if ($parts === array('0','6')) return '주말';
    $labels = array();
    foreach ($parts as $d) {
        if (isset($map[$d])) $labels[] = $map[$d];
    }
    return implode(' · ', $labels);
}

function hb_time_hm($time) {
    return substr((string)$time, 0, 5);
}

function hb_safe_file($file) {
    $file = basename((string)$file);
    return preg_replace('/[^a-zA-Z0-9._-]/', '', $file);
}

function hb_music_url($file) {
    $file = hb_safe_file($file);
    if (!$file) return '';
    return HB_URL.'/stream.php?file='.rawurlencode($file);
}

function hb_extract_youtube_id($url) {
    $url = trim((string)$url);
    if ($url === '') return '';
    if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url)) return $url;
    $parts = @parse_url($url);
    if (!$parts || empty($parts['host'])) return '';
    $host = strtolower($parts['host']);
    $path = isset($parts['path']) ? trim($parts['path'], '/') : '';
    if (strpos($host, 'youtu.be') !== false && preg_match('/^[a-zA-Z0-9_-]{11}/', $path, $m)) return substr($m[0], 0, 11);
    if (strpos($host, 'youtube.com') !== false || strpos($host, 'youtube-nocookie.com') !== false) {
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $q);
            if (!empty($q['v']) && preg_match('/^[a-zA-Z0-9_-]{11}$/', $q['v'])) return $q['v'];
        }
        if (preg_match('#(?:embed|shorts|live)/([a-zA-Z0-9_-]{11})#', $path, $m)) return $m[1];
    }
    return '';
}


function hb_find_or_create_youtube_music($url, $title='', $volume=80, $type='music', $memo='', $mb_id='') {
    $yt_id = hb_extract_youtube_id($url);
    if (!$yt_id) return 0;
    $music = hb_table('music');
    $yt_sql = hb_escape($yt_id);
    $found = sql_fetch("SELECT mf_id FROM `{$music}` WHERE mf_source='youtube' AND mf_youtube_id='{$yt_sql}' LIMIT 1", false);
    if ($found && isset($found['mf_id']) && (int)$found['mf_id'] > 0) return (int)$found['mf_id'];
    $url = trim((string)$url);
    $title = trim((string)$title);
    if ($title === '') $title = 'YouTube BGM '.$yt_id;
    $type = $type === 'bell' ? 'bell' : 'music';
    $volume = max(0, min(100, (int)$volume));
    $title_sql = hb_escape($title);
    $url_sql = hb_escape($url);
    $type_sql = hb_escape($type);
    $memo_sql = hb_escape($memo !== '' ? $memo : '공통 시간표에서 바로 등록');
    $mb_id_sql = hb_escape($mb_id);
    sql_query("INSERT INTO `{$music}` SET mf_title='{$title_sql}', mf_source='youtube', mf_file='', mf_org_name='', mf_mime='', mf_size='0', mf_youtube_url='{$url_sql}', mf_youtube_id='{$yt_sql}', mf_volume='{$volume}', mf_type='{$type_sql}', mf_memo='{$memo_sql}', mf_use='1', mb_id='{$mb_id_sql}', mf_created_at=NOW()");
    if (function_exists('sql_insert_id')) return (int)sql_insert_id();
    $last = sql_fetch("SELECT LAST_INSERT_ID() AS id", false);
    return $last && isset($last['id']) ? (int)$last['id'] : 0;
}

function hb_music_source_label($row) {
    $source = isset($row['mf_source']) ? $row['mf_source'] : 'file';
    return $source === 'youtube' ? 'YouTube' : '파일';
}

function hb_music_item_payload($row) {
    $source = isset($row['mf_source']) && $row['mf_source'] === 'youtube' ? 'youtube' : 'file';
    return array(
        'source' => $source,
        'url' => $source === 'file' ? hb_music_url($row['mf_file']) : '',
        'youtube_id' => $source === 'youtube' ? (isset($row['mf_youtube_id']) ? $row['mf_youtube_id'] : '') : ''
    );
}

function hb_music_admin_label($row) {
    if ((isset($row['mf_source']) ? $row['mf_source'] : 'file') === 'youtube') {
        return 'YouTube · '.hb_e(isset($row['mf_youtube_id']) ? $row['mf_youtube_id'] : '');
    }
    return hb_e(isset($row['mf_org_name']) ? $row['mf_org_name'] : '');
}

function hb_json_exit($arr) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function hb_ip() {
    if (isset($_SERVER['REMOTE_ADDR'])) return substr($_SERVER['REMOTE_ADDR'], 0, 45);
    return '';
}

function hb_schedule_query($mb_id, $only_today=false) {
    $schedule = hb_table('schedule');
    $music = hb_table('music');
    $mb_id = hb_escape($mb_id);
    $where = "s.sc_use = 1 AND m.mf_use = 1 AND (s.sc_scope = 'global' OR (s.sc_scope = 'user' AND s.mb_id = '{$mb_id}'))";
    if ($only_today) {
        $w = date('w');
        $today = G5_TIME_YMD;
        $where .= " AND FIND_IN_SET('{$w}', s.sc_days)";
        $where .= " AND (s.sc_start_date IS NULL OR s.sc_start_date = '0000-00-00' OR s.sc_start_date <= '{$today}')";
        $where .= " AND (s.sc_end_date IS NULL OR s.sc_end_date = '0000-00-00' OR s.sc_end_date >= '{$today}')";
    }
    return "SELECT s.*, m.* FROM `{$schedule}` s INNER JOIN `{$music}` m ON s.mf_id = m.mf_id WHERE {$where} ORDER BY s.sc_time ASC, s.sc_sort ASC, s.sc_id ASC";
}


function hb_schedule_common_query($only_today=false) {
    $schedule = hb_table('schedule');
    $music = hb_table('music');
    $where = "s.sc_use = 1 AND m.mf_use = 1 AND s.sc_scope = 'global'";
    if ($only_today) {
        $w = date('w');
        $today = G5_TIME_YMD;
        $where .= " AND FIND_IN_SET('{$w}', s.sc_days)";
        $where .= " AND (s.sc_start_date IS NULL OR s.sc_start_date = '0000-00-00' OR s.sc_start_date <= '{$today}')";
        $where .= " AND (s.sc_end_date IS NULL OR s.sc_end_date = '0000-00-00' OR s.sc_end_date >= '{$today}')";
    }
    return "SELECT s.*, m.* FROM `{$schedule}` s INNER JOIN `{$music}` m ON s.mf_id = m.mf_id WHERE {$where} ORDER BY s.sc_time ASC, s.sc_sort ASC, s.sc_id ASC";
}

function hb_get_music_options($selected=0) {
    $music = hb_table('music');
    $selected = (int)$selected;
    $html = '';
    $res = sql_query("SELECT * FROM `{$music}` WHERE mf_use = 1 ORDER BY mf_id DESC");
    while ($row = sql_fetch_array($res)) {
        $sel = ((int)$row['mf_id'] === $selected) ? ' selected' : '';
        $label = hb_music_source_label($row);
        $html .= '<option value="'.(int)$row['mf_id'].'"'.$sel.'>['.hb_e($label).'] '.hb_e($row['mf_title']).'</option>';
    }
    return $html;
}



function hb_schedule_items($sc_id) {
    $schedule_item = hb_table('schedule_item');
    $music = hb_table('music');
    $sc_id = (int)$sc_id;
    $out = array();
    $res = sql_query("SELECT si.*, m.* FROM `{$schedule_item}` si INNER JOIN `{$music}` m ON si.mf_id=m.mf_id WHERE si.sc_id='{$sc_id}' AND m.mf_use=1 ORDER BY si.si_sort ASC, si.si_id ASC");
    while ($row = sql_fetch_array($res)) $out[] = $row;
    return $out;
}

function hb_schedule_item_ids($sc_id) {
    $items = hb_schedule_items($sc_id);
    $ids = array();
    foreach ($items as $item) $ids[] = (int)$item['mf_id'];
    return $ids;
}

function hb_save_schedule_items($sc_id, $music_ids) {
    $schedule_item = hb_table('schedule_item');
    $sc_id = (int)$sc_id;
    sql_query("DELETE FROM `{$schedule_item}` WHERE sc_id='{$sc_id}'");
    $sort = 0;
    foreach (hb_clean_music_ids($music_ids) as $mf_id) {
        $sort += 10;
        sql_query("INSERT INTO `{$schedule_item}` SET sc_id='{$sc_id}', mf_id='{$mf_id}', si_sort='{$sort}', si_created_at=NOW()");
    }
}

function hb_schedule_item_count($sc_id) {
    $schedule_item = hb_table('schedule_item');
    $music = hb_table('music');
    $sc_id = (int)$sc_id;
    $row = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$schedule_item}` si INNER JOIN `{$music}` m ON si.mf_id=m.mf_id WHERE si.sc_id='{$sc_id}' AND m.mf_use=1");
    return (int)$row['cnt'];
}

function hb_schedule_item_titles($sc_id, $limit=4) {
    $items = hb_schedule_items($sc_id);
    $names = array();
    foreach ($items as $idx => $item) {
        if ($idx >= $limit) break;
        $names[] = $item['mf_title'];
    }
    $more = count($items) > $limit ? ' 외 '.(count($items)-$limit).'개' : '';
    return implode(' · ', $names).$more;
}

function hb_get_schedule_music_select_rows($selected_ids=array(), $rows=20) {
    $selected_ids = is_array($selected_ids) ? array_values($selected_ids) : array();
    $rows = min(100, max($rows, count($selected_ids) + 3));
    $html = '';
    for ($i=0; $i<$rows; $i++) {
        $selected = isset($selected_ids[$i]) ? (int)$selected_ids[$i] : 0;
        $html .= '<div class="hb-track-row"><span class="hb-track-no">'.($i+1).'</span><select name="mf_ids[]"><option value="">선택 안 함</option>'.hb_get_music_options($selected).'</select><span class="hb-track-tools"><button type="button" class="hb-track-up" title="위로">↑</button><button type="button" class="hb-track-down" title="아래로">↓</button><button type="button" class="hb-track-clear" title="비우기">×</button></span></div>';
    }
    return $html;
}


function hb_media_items_payload($items, $fallback_row=null) {
    $out = array();
    if (is_array($items)) {
        foreach ($items as $it) {
            if (!$it) continue;
            $payload = hb_music_item_payload($it);
            $out[] = array(
                'music_id' => isset($it['mf_id']) ? (int)$it['mf_id'] : 0,
                'music_title' => isset($it['mf_title']) ? $it['mf_title'] : (isset($it['sc_title']) ? $it['sc_title'] : '음악'),
                'volume' => isset($it['mf_volume']) ? (int)$it['mf_volume'] : 80,
                'source' => $payload['source'],
                'url' => $payload['url'],
                'youtube_id' => $payload['youtube_id']
            );
        }
    }
    if (!$out && is_array($fallback_row)) {
        $payload = hb_music_item_payload($fallback_row);
        $out[] = array(
            'music_id' => isset($fallback_row['mf_id']) ? (int)$fallback_row['mf_id'] : 0,
            'music_title' => isset($fallback_row['mf_title']) ? $fallback_row['mf_title'] : (isset($fallback_row['sc_title']) ? $fallback_row['sc_title'] : '음악'),
            'volume' => isset($fallback_row['mf_volume']) ? (int)$fallback_row['mf_volume'] : 80,
            'source' => $payload['source'],
            'url' => $payload['url'],
            'youtube_id' => $payload['youtube_id']
        );
    }
    return $out;
}

function hb_media_items_attr($items, $fallback_row=null) {
    $payload = hb_media_items_payload($items, $fallback_row);
    return hb_e(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function hb_schedule_preview_items_attr($sc_id, $fallback_row=null) {
    return hb_media_items_attr(hb_schedule_items($sc_id), $fallback_row);
}

function hb_block_preview_items_attr($bl_id) {
    return hb_media_items_attr(hb_block_items($bl_id));
}

function hb_valid_hm($time) {
    return is_string($time) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time);
}

function hb_hm_to_sql($time) {
    return hb_escape(substr($time, 0, 5).':00');
}

function hb_scope_label($scope) {
    return $scope === 'global' ? '공통' : '개인';
}

function hb_play_mode_label($mode) {
    return $mode === 'random' ? '랜덤' : '순서대로';
}

function hb_block_query($mb_id, $only_today=false) {
    $block = hb_table('block');
    $mb_id = hb_escape($mb_id);
    $where = "b.bl_use = 1 AND (b.bl_scope = 'global' OR (b.bl_scope = 'user' AND b.mb_id = '{$mb_id}'))";
    if ($only_today) {
        $w = date('w');
        $today = G5_TIME_YMD;
        $where .= " AND FIND_IN_SET('{$w}', b.bl_days)";
        $where .= " AND (b.bl_start_date IS NULL OR b.bl_start_date = '0000-00-00' OR b.bl_start_date <= '{$today}')";
        $where .= " AND (b.bl_end_date IS NULL OR b.bl_end_date = '0000-00-00' OR b.bl_end_date >= '{$today}')";
    }
    return "SELECT b.* FROM `{$block}` b WHERE {$where} ORDER BY b.bl_start_time ASC, b.bl_sort ASC, b.bl_id ASC";
}


function hb_block_common_query($only_today=false) {
    $block = hb_table('block');
    $where = "b.bl_use = 1 AND b.bl_scope = 'global'";
    if ($only_today) {
        $w = date('w');
        $today = G5_TIME_YMD;
        $where .= " AND FIND_IN_SET('{$w}', b.bl_days)";
        $where .= " AND (b.bl_start_date IS NULL OR b.bl_start_date = '0000-00-00' OR b.bl_start_date <= '{$today}')";
        $where .= " AND (b.bl_end_date IS NULL OR b.bl_end_date = '0000-00-00' OR b.bl_end_date >= '{$today}')";
    }
    return "SELECT b.* FROM `{$block}` b WHERE {$where} ORDER BY b.bl_start_time ASC, b.bl_sort ASC, b.bl_id ASC";
}

function hb_block_items($bl_id) {
    $block_item = hb_table('block_item');
    $music = hb_table('music');
    $bl_id = (int)$bl_id;
    $out = array();
    $res = sql_query("SELECT bi.*, m.* FROM `{$block_item}` bi INNER JOIN `{$music}` m ON bi.mf_id=m.mf_id WHERE bi.bl_id='{$bl_id}' AND m.mf_use=1 ORDER BY bi.bi_sort ASC, bi.bi_id ASC");
    while ($row = sql_fetch_array($res)) $out[] = $row;
    return $out;
}

function hb_block_item_count($bl_id) {
    $block_item = hb_table('block_item');
    $music = hb_table('music');
    $bl_id = (int)$bl_id;
    $row = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$block_item}` bi INNER JOIN `{$music}` m ON bi.mf_id=m.mf_id WHERE bi.bl_id='{$bl_id}' AND m.mf_use=1");
    return (int)$row['cnt'];
}

function hb_block_item_titles($bl_id, $limit=4) {
    $items = hb_block_items($bl_id);
    $names = array();
    foreach ($items as $idx => $item) {
        if ($idx >= $limit) break;
        $names[] = $item['mf_title'];
    }
    $more = count($items) > $limit ? ' 외 '.(count($items)-$limit).'곡' : '';
    return implode(' · ', $names).$more;
}

function hb_clean_music_ids($ids) {
    if (!is_array($ids)) $ids = array();
    $out = array();
    foreach ($ids as $id) {
        $id = (int)$id;
        if ($id > 0) $out[] = $id;
    }
    return $out;
}

function hb_save_block_items($bl_id, $music_ids) {
    $block_item = hb_table('block_item');
    $bl_id = (int)$bl_id;
    sql_query("DELETE FROM `{$block_item}` WHERE bl_id='{$bl_id}'");
    $sort = 0;
    foreach (hb_clean_music_ids($music_ids) as $mf_id) {
        $sort += 10;
        sql_query("INSERT INTO `{$block_item}` SET bl_id='{$bl_id}', mf_id='{$mf_id}', bi_sort='{$sort}', bi_created_at=NOW()");
    }
}

function hb_get_block_music_select_rows($selected_ids=array(), $rows=100) {
    $selected_ids = is_array($selected_ids) ? array_values($selected_ids) : array();
    $rows = min(100, max($rows, count($selected_ids) + 3));
    $html = '';
    for ($i=0; $i<$rows; $i++) {
        $selected = isset($selected_ids[$i]) ? (int)$selected_ids[$i] : 0;
        $html .= '<div class="hb-track-row"><span class="hb-track-no">'.($i+1).'</span><select name="mf_ids[]"><option value="">선택 안 함</option>'.hb_get_music_options($selected).'</select><span class="hb-track-tools"><button type="button" class="hb-track-up" title="위로">↑</button><button type="button" class="hb-track-down" title="아래로">↓</button><button type="button" class="hb-track-clear" title="비우기">×</button></span></div>';
    }
    return $html;
}



function hb_sequence_type_label($type) {
    $map = array(
        'church' => '교회',
        'broadcast' => '방송',
        'event' => '행사',
        'store' => '매장/학교',
        'general' => '기타'
    );
    return isset($map[$type]) ? $map[$type] : '기타';
}

function hb_sequence_items($seq_id) {
    $sequence_item = hb_table('sequence_item');
    $music = hb_table('music');
    $seq_id = (int)$seq_id;
    $out = array();
    $res = sql_query("SELECT siq.*, m.* FROM `{$sequence_item}` siq INNER JOIN `{$music}` m ON siq.mf_id=m.mf_id WHERE siq.seq_id='{$seq_id}' AND m.mf_use=1 ORDER BY siq.siq_sort ASC, siq.siq_id ASC");
    while ($row = sql_fetch_array($res)) $out[] = $row;
    return $out;
}

function hb_sequence_item_count($seq_id) {
    $sequence_item = hb_table('sequence_item');
    $music = hb_table('music');
    $seq_id = (int)$seq_id;
    $row = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$sequence_item}` siq INNER JOIN `{$music}` m ON siq.mf_id=m.mf_id WHERE siq.seq_id='{$seq_id}' AND m.mf_use=1");
    return (int)$row['cnt'];
}

function hb_sequence_item_titles($seq_id, $limit=5) {
    $items = hb_sequence_items($seq_id);
    $names = array();
    foreach ($items as $idx => $item) {
        if ($idx >= $limit) break;
        $names[] = $item['siq_title'] ? $item['siq_title'] : $item['mf_title'];
    }
    $more = count($items) > $limit ? ' 외 '.(count($items)-$limit).'개' : '';
    return implode(' · ', $names).$more;
}

function hb_save_sequence_items($seq_id, $music_ids, $titles=array(), $memos=array()) {
    $sequence_item = hb_table('sequence_item');
    $seq_id = (int)$seq_id;
    sql_query("DELETE FROM `{$sequence_item}` WHERE seq_id='{$seq_id}'");
    $sort = 0;
    foreach (hb_clean_music_ids($music_ids) as $idx => $mf_id) {
        $sort += 10;
        $title = isset($titles[$idx]) ? hb_escape(substr(trim((string)$titles[$idx]), 0, 250)) : '';
        $memo = isset($memos[$idx]) ? hb_escape(substr(trim((string)$memos[$idx]), 0, 250)) : '';
        sql_query("INSERT INTO `{$sequence_item}` SET seq_id='{$seq_id}', mf_id='{$mf_id}', siq_title='{$title}', siq_memo='{$memo}', siq_sort='{$sort}', siq_created_at=NOW()");
    }
}

function hb_sequence_preview_items_attr($seq_id) {
    $items = hb_sequence_items($seq_id);
    $out = array();
    foreach ($items as $item) {
        $payload = hb_music_item_payload($item);
        $out[] = array(
            'music_id' => (int)$item['mf_id'],
            'music_title' => $item['siq_title'] ? $item['siq_title'] : $item['mf_title'],
            'title' => $item['siq_title'] ? $item['siq_title'] : $item['mf_title'],
            'volume' => (int)$item['mf_volume'],
            'source' => $payload['source'],
            'url' => $payload['url'],
            'youtube_id' => $payload['youtube_id']
        );
    }
    return hb_e(json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function hb_get_sequence_music_select_rows($selected_ids=array(), $step_titles=array(), $step_memos=array(), $rows=30) {
    $selected_ids = is_array($selected_ids) ? array_values($selected_ids) : array();
    $rows = min(100, max($rows, count($selected_ids) + 5));
    $html = '';
    for ($i=0; $i<$rows; $i++) {
        $selected = isset($selected_ids[$i]) ? (int)$selected_ids[$i] : 0;
        $title = isset($step_titles[$i]) ? hb_e($step_titles[$i]) : '';
        $memo = isset($step_memos[$i]) ? hb_e($step_memos[$i]) : '';
        $html .= '<div class="hb-track-row hb-seq-row"><span class="hb-track-no">'.($i+1).'</span><select name="mf_ids[]"><option value="">선택 안 함</option>'.hb_get_music_options($selected).'</select><input type="text" name="step_titles[]" placeholder="순서명 예: 입례송 / 찬양 1" value="'.$title.'"><input type="text" name="step_memos[]" placeholder="메모" value="'.$memo.'"></div>';
    }
    return $html;
}

function hb_today_operation_entries() {
    $today = array();
    $res = sql_query(hb_schedule_common_query(true));
    while ($row = sql_fetch_array($res)) {
        $today[] = array('kind'=>hb_schedule_is_range($row) ? 'range' : 'single', 'start'=>hb_time_hm($row['sc_time']), 'end'=>hb_schedule_is_range($row) ? hb_time_hm($row['sc_end_time']) : '', 'row'=>$row);
    }
    $bres = sql_query(hb_block_common_query(true));
    while ($row = sql_fetch_array($bres)) {
        if (hb_block_item_count($row['bl_id']) < 1) continue;
        $today[] = array('kind'=>'block', 'start'=>hb_time_hm($row['bl_start_time']), 'end'=>hb_time_hm($row['bl_end_time']), 'row'=>$row);
    }
    usort($today, function($a, $b) {
        if ($a['start'] === $b['start']) return 0;
        return $a['start'] < $b['start'] ? -1 : 1;
    });
    return $today;
}

function hb_default_settings() {
    return array(
        'priority_mode' => 'user_first',
        'single_window_seconds' => '90',
        'fadeout_seconds' => '4',
        'block_end_action' => 'fade_stop',
        'auto_refresh_seconds' => '60',
        'show_debug_badge' => '0',
        'max_block_tracks' => '100',
        'member_default_enabled' => '1',
        'operation_confirm_play' => '0'
    );
}

function hb_seed_settings() {
    $settings = hb_table('settings');
    foreach (hb_default_settings() as $key => $value) {
        $key = hb_escape($key);
        $value = hb_escape($value);
        sql_query("INSERT IGNORE INTO `{$settings}` SET st_key='{$key}', st_value='{$value}', st_updated_at=NOW()", false);
    }
}

function hb_get_setting($key, $default='') {
    $settings = hb_table('settings');
    $key_sql = hb_escape($key);
    $row = sql_fetch("SELECT st_value FROM `{$settings}` WHERE st_key='{$key_sql}'");
    if ($row && array_key_exists('st_value', $row)) return (string)$row['st_value'];
    $defaults = hb_default_settings();
    return array_key_exists($key, $defaults) ? $defaults[$key] : $default;
}

function hb_update_setting($key, $value) {
    $settings = hb_table('settings');
    $key = hb_escape($key);
    $value = hb_escape($value);
    sql_query("INSERT INTO `{$settings}` SET st_key='{$key}', st_value='{$value}', st_updated_at=NOW() ON DUPLICATE KEY UPDATE st_value='{$value}', st_updated_at=NOW()", false);
}

function hb_get_settings() {
    $out = hb_default_settings();
    $settings = hb_table('settings');
    $res = sql_query("SELECT st_key, st_value FROM `{$settings}`", false);
    if ($res) {
        while ($row = sql_fetch_array($res)) {
            $out[$row['st_key']] = (string)$row['st_value'];
        }
    }
    return $out;
}


function hb_member_table_name() {
    if (isset($GLOBALS['g5']['member_table']) && $GLOBALS['g5']['member_table']) return $GLOBALS['g5']['member_table'];
    return (defined('G5_TABLE_PREFIX') ? G5_TABLE_PREFIX : 'g5_').'member';
}

function hb_member_default_enabled() {
    return hb_get_setting('member_default_enabled', '1') !== '0';
}

function hb_member_access_row($mb_id) {
    $member_access = hb_table('member_access');
    $mb_id = hb_escape($mb_id);
    return sql_fetch("SELECT * FROM `{$member_access}` WHERE mb_id='{$mb_id}' LIMIT 1", false);
}

function hb_is_member_bgm_enabled($mb_id) {
    global $is_admin;
    if ($is_admin) return true;
    $mb_id = trim((string)$mb_id);
    if ($mb_id === '') return false;
    $row = hb_member_access_row($mb_id);
    if ($row && isset($row['ma_enabled'])) return (int)$row['ma_enabled'] === 1;
    return hb_member_default_enabled();
}

function hb_member_bgm_status_label($mb_id) {
    return hb_is_member_bgm_enabled($mb_id) ? '사용 가능' : '사용 차단';
}

function hb_member_access_effective($mb_id) {
    $row = hb_member_access_row($mb_id);
    if ($row && isset($row['ma_enabled'])) return (int)$row['ma_enabled'];
    return hb_member_default_enabled() ? 1 : 0;
}

function hb_set_member_bgm_enabled($mb_id, $enabled, $memo='', $admin_id='') {
    $member_access = hb_table('member_access');
    $mb_id = hb_escape($mb_id);
    $enabled = $enabled ? 1 : 0;
    $memo = hb_escape(substr((string)$memo, 0, 255));
    $admin_id = hb_escape($admin_id);
    sql_query("INSERT INTO `{$member_access}` SET mb_id='{$mb_id}', ma_enabled='{$enabled}', ma_memo='{$memo}', ma_updated_by='{$admin_id}', ma_created_at=NOW(), ma_updated_at=NOW() ON DUPLICATE KEY UPDATE ma_enabled='{$enabled}', ma_memo='{$memo}', ma_updated_by='{$admin_id}', ma_updated_at=NOW()", false);
}

function hb_require_member_bgm_enabled() {
    global $member, $is_admin, $g5;
    if ($is_admin) return true;
    $mb_id = isset($member['mb_id']) ? $member['mb_id'] : '';
    if (hb_is_member_bgm_enabled($mb_id)) return true;
    if (defined('HB_JSON_MODE') && HB_JSON_MODE) {
        hb_json_exit(array('ok'=>false, 'message'=>'member_bgm_disabled', 'notice'=>'운영자가 이 계정의 하루브금 회원용 사용을 꺼두었습니다.'));
    }
    $g5['title'] = '하루브금 사용 제한';
    include_once(G5_PATH.'/head.php');
    echo '<link rel="stylesheet" href="'.HB_URL.'/assets/haru_bgm.css?ver=20260616f">';
    echo '<div class="hb-wrap"><section class="hb-card hb-empty"><div class="hb-empty-icon">🔒</div><strong>하루브금 사용이 꺼져 있습니다</strong><p>이 계정은 운영자 설정에 따라 회원용 하루브금 화면을 사용할 수 없습니다.</p><p class="hb-muted-mini">필요하면 사이트 운영자에게 사용 허용을 요청하세요.</p><div class="hb-actions hb-actions-center"><a class="hb-btn" href="'.G5_URL.'">사이트 홈으로</a></div></section></div>';
    include_once(G5_PATH.'/tail.php');
    exit;
}

function hb_priority_score($kind, $scope, $mode='user_first') {
    // 낮을수록 먼저 재생됩니다. 수동 재생은 JS에서 항상 최우선입니다.
    if ($mode === 'global_first') {
        if ($scope === 'global' && $kind === 'single') return 10;
        if ($scope === 'global' && $kind === 'block') return 20;
        if ($scope === 'user' && $kind === 'single') return 30;
        return 40;
    }
    if ($mode === 'single_first') {
        if ($kind === 'single' && $scope === 'user') return 10;
        if ($kind === 'single' && $scope === 'global') return 20;
        if ($kind === 'block' && $scope === 'user') return 30;
        return 40;
    }
    if ($mode === 'block_first') {
        if ($kind === 'block' && $scope === 'user') return 10;
        if ($kind === 'block' && $scope === 'global') return 20;
        if ($kind === 'single' && $scope === 'user') return 30;
        return 40;
    }
    // 기본: 개인 시간표 > 개인 시간대 > 공통 시간표 > 공통 시간대
    if ($scope === 'user' && $kind === 'single') return 10;
    if ($scope === 'user' && $kind === 'block') return 20;
    if ($scope === 'global' && $kind === 'single') return 30;
    return 40;
}

function hb_setting_label_priority($mode) {
    $labels = array(
        'user_first' => '개인 우선',
        'global_first' => '공통 방송 우선',
        'single_first' => '정각 시간표 우선',
        'block_first' => '시간대 묶음 우선'
    );
    return isset($labels[$mode]) ? $labels[$mode] : $labels['user_first'];
}

function hb_guess_title_from_filename($name) {
    $base = pathinfo((string)$name, PATHINFO_FILENAME);
    $base = preg_replace('/[_\-]+/', ' ', $base);
    $base = trim($base);
    return $base !== '' ? $base : '하루브금 음악';
}

function hb_upload_music_file($file, &$error) {
    $error = '';
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        $error = '업로드된 파일이 없습니다.';
        return false;
    }
    hb_ensure_data_dir();
    if (!is_dir(HB_DATA_PATH) || !is_writable(HB_DATA_PATH)) {
        $error = '음악 저장 폴더에 쓰기 권한이 없습니다.';
        return false;
    }
    $org = isset($file['name']) ? $file['name'] : '';
    $size = isset($file['size']) ? (int)$file['size'] : 0;
    $mime = isset($file['type']) ? $file['type'] : '';
    $ext = strtolower(pathinfo($org, PATHINFO_EXTENSION));
    $allow = array('mp3','wav','ogg','m4a');
    if (!in_array($ext, $allow, true)) {
        $error = 'mp3, wav, ogg, m4a 파일만 업로드할 수 있습니다.';
        return false;
    }
    if ($size <= 0) {
        $error = '파일 크기가 올바르지 않습니다.';
        return false;
    }
    $rand = function_exists('random_bytes') ? bin2hex(random_bytes(8)) : md5(uniqid('', true));
    $save = 'hb_'.$rand.'.'.$ext;
    $dest = HB_DATA_PATH.'/'.$save;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        $error = '파일 업로드에 실패했습니다.';
        return false;
    }
    @chmod($dest, G5_FILE_PERMISSION);
    return array('save'=>$save, 'org'=>$org, 'size'=>$size, 'mime'=>$mime);
}

function hb_files_rearray($files) {
    $out = array();
    if (!isset($files['name'])) return $out;
    if (!is_array($files['name'])) {
        return array($files);
    }
    foreach ($files['name'] as $i => $name) {
        if ($name === '') continue;
        $out[] = array(
            'name' => $name,
            'type' => isset($files['type'][$i]) ? $files['type'][$i] : '',
            'tmp_name' => isset($files['tmp_name'][$i]) ? $files['tmp_name'][$i] : '',
            'error' => isset($files['error'][$i]) ? $files['error'][$i] : 0,
            'size' => isset($files['size'][$i]) ? $files['size'][$i] : 0
        );
    }
    return $out;
}

function hb_table_exists($table) {
    $table = hb_escape($table);
    $row = sql_fetch("SHOW TABLES LIKE '{$table}'", false);
    return $row ? true : false;
}

function hb_health_checks() {
    $checks = array();
    $required = array('music','schedule','schedule_item','block','block_item','sequence','sequence_item','play_log','settings','member_access');
    foreach ($required as $name) {
        $checks[] = array(
            'label' => 'DB 테이블: '.hb_table($name),
            'ok' => hb_table_exists(hb_table($name)),
            'message' => hb_table_exists(hb_table($name)) ? '정상' : '없음 또는 생성 실패'
        );
    }
    $checks[] = array('label'=>'음악 저장 폴더', 'ok'=>is_dir(HB_DATA_PATH), 'message'=>HB_DATA_PATH);
    $checks[] = array('label'=>'음악 저장 폴더 쓰기 권한', 'ok'=>is_dir(HB_DATA_PATH) && is_writable(HB_DATA_PATH), 'message'=>(is_dir(HB_DATA_PATH) && is_writable(HB_DATA_PATH)) ? '업로드 가능' : '권한 확인 필요');
    $checks[] = array('label'=>'PHP upload_max_filesize', 'ok'=>true, 'message'=>ini_get('upload_max_filesize'));
    $checks[] = array('label'=>'PHP post_max_size', 'ok'=>true, 'message'=>ini_get('post_max_size'));
    $checks[] = array('label'=>'PHP 버전', 'ok'=>version_compare(PHP_VERSION, '7.4.0', '>='), 'message'=>PHP_VERSION);
    $checks[] = array('label'=>'Range 스트리밍', 'ok'=>true, 'message'=>'stream.php 사용');
    $checks[] = array('label'=>'YouTube 링크 재생', 'ok'=>true, 'message'=>'IFrame Player API 사용');
    return $checks;
}

function hb_nav_admin() {
    if (!defined('HB_URL')) return '';
    return '<div class="hb-subnav">'
        .'<a href="'.HB_URL.'/index.php">모드 선택</a>'
        .'<a href="'.HB_URL.'/admin/index.php">관리자 홈</a>'
        .'<a href="'.HB_URL.'/admin/operation.php">공용 운영판</a>'
        .'<a href="'.HB_URL.'/admin/today.php">오늘 운영표</a>'
        .'<a href="'.HB_URL.'/admin/sequence_list.php">순서표 모드</a>'
        .'<a href="'.HB_URL.'/admin/music_list.php">음악 관리</a>'
        .'<a href="'.HB_URL.'/admin/schedule_global.php">공통 시간표</a>'
        .'<a href="'.HB_URL.'/admin/block_global.php">공통 시간대</a>'
        .'<a href="'.HB_URL.'/admin/member_access.php">회원 사용설정</a>'
        .'<a href="'.HB_URL.'/admin/logs.php">재생 로그</a>'
        .'<a href="'.HB_URL.'/admin/settings.php">환경설정</a>'
        .'<a href="'.HB_URL.'/admin/health.php">설치 점검</a>'
        .'</div>';
}

function hb_goto($url) {
    goto_url($url);
    exit;
}
?>
