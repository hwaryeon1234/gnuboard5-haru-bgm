<?php
if (!defined('_GNUBOARD_')) exit;

if (!defined('HB_SCHEMA_VERSION')) define('HB_SCHEMA_VERSION', '163');
if (!defined('HB_PLUGIN_VERSION')) define('HB_PLUGIN_VERSION', '1.5.47');
if (!defined('HB_ASSET_VERSION')) define('HB_ASSET_VERSION', '20260904-v1547-r1');
if (!defined('HB_ADMIN_MENU_ROOT')) define('HB_ADMIN_MENU_ROOT', '990000');
if (!defined('HB_MAX_SCHEDULE_ITEMS')) define('HB_MAX_SCHEDULE_ITEMS', 20);
if (!defined('HB_MAX_BLOCK_ITEMS')) define('HB_MAX_BLOCK_ITEMS', 100);
if (!defined('HB_MAX_SEQUENCE_ITEMS')) define('HB_MAX_SEQUENCE_ITEMS', 30);
if (!defined('HB_MAX_YOUTUBE_BULK_ITEMS')) define('HB_MAX_YOUTUBE_BULK_ITEMS', 100);
if (!defined('HB_MAX_YOUTUBE_TEXT_BYTES')) define('HB_MAX_YOUTUBE_TEXT_BYTES', 262144);
if (!defined('HB_MAX_UPLOAD_FILES')) define('HB_MAX_UPLOAD_FILES', 50);
if (!defined('HB_MAX_UPLOAD_FILE_BYTES')) define('HB_MAX_UPLOAD_FILE_BYTES', 268435456);
if (!defined('HB_MAX_STORAGE_BYTES')) define('HB_MAX_STORAGE_BYTES', 10737418240);
if (!defined('HB_MIN_FREE_BYTES')) define('HB_MIN_FREE_BYTES', 536870912);
if (!defined('HB_MAX_ADMIN_POST_BYTES')) define('HB_MAX_ADMIN_POST_BYTES', 1048576);
if (!defined('HB_MAX_API_SCHEDULE_PARENTS')) define('HB_MAX_API_SCHEDULE_PARENTS', 200);
if (!defined('HB_MAX_API_BLOCK_PARENTS')) define('HB_MAX_API_BLOCK_PARENTS', 100);
// 200개 시간표 × 20곡 + 100개 시간대 × 100곡을 모두 JSON으로 보낼 수 있는 상한입니다.
if (!defined('HB_MAX_API_PAYLOAD_BYTES')) define('HB_MAX_API_PAYLOAD_BYTES', 33554432);
if (!defined('HB_LOG_RETENTION_DAYS')) define('HB_LOG_RETENTION_DAYS', 90);
if (!defined('HB_MAX_MEMO_CHARS')) define('HB_MAX_MEMO_CHARS', 12000);
if (!defined('HB_MAX_MEMO_BYTES')) define('HB_MAX_MEMO_BYTES', 60000);

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

function hb_scalar_string($value, $default='') {
    if ($value === null) return (string)$default;
    if (!is_scalar($value)) return (string)$default;
    return (string)$value;
}

function hb_int_value($value, $default=0) {
    if (!is_scalar($value) || is_bool($value)) return (int)$default;
    $value = trim((string)$value);
    if (!preg_match('/^-?\d+$/', $value)) return (int)$default;
    return (int)$value;
}

function hb_escape($str) {
    $str = hb_scalar_string($str, '');
    if (function_exists('sql_real_escape_string')) {
        return sql_real_escape_string($str);
    }
    return addslashes($str);
}

function hb_e($str) {
    return htmlspecialchars(hb_scalar_string($str, ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function hb_text_limit($str, $max_chars) {
    if (is_array($str) || is_object($str) || is_resource($str)) return '';
    $str = trim((string)$str);
    $max_chars = max(0, (int)$max_chars);
    if ($max_chars === 0 || $str === '') return '';
    if (function_exists('mb_substr')) return mb_substr($str, 0, $max_chars, 'UTF-8');
    if (preg_match_all('/./us', $str, $m) !== false) return implode('', array_slice($m[0], 0, $max_chars));
    return substr($str, 0, $max_chars);
}

function hb_text_fits($str, $max_chars=HB_MAX_MEMO_CHARS, $max_bytes=HB_MAX_MEMO_BYTES) {
    if (is_array($str) || is_object($str) || is_resource($str)) return false;
    $str = (string)$str;
    if (strlen($str) > max(1, (int)$max_bytes)) return false;
    if (function_exists('mb_strlen')) return mb_strlen($str, 'UTF-8') <= max(1, (int)$max_chars);
    if (preg_match_all('/./us', $str, $m) !== false) return count($m[0]) <= max(1, (int)$max_chars);
    return strlen($str) <= max(1, (int)$max_chars);
}

function hb_csrf_token() {
    if (!isset($_SESSION) || !is_array($_SESSION)) $_SESSION = array();
    if (empty($_SESSION['hb_csrf_token']) || !is_string($_SESSION['hb_csrf_token']) || strlen($_SESSION['hb_csrf_token']) < 32) {
        try {
            $_SESSION['hb_csrf_token'] = bin2hex(random_bytes(32));
        } catch (Throwable $e) {
            $_SESSION['hb_csrf_token'] = hash('sha256', uniqid((string)mt_rand(), true));
        }
    }
    return (string)$_SESSION['hb_csrf_token'];
}

function hb_csrf_field() {
    $html = '<input type="hidden" name="hb_token" value="'.hb_e(hb_csrf_token()).'">';
    // 이 서버의 다른 관리자 화면(sosododam_pwa.php, sosododam_telegram.php,
    // contact_channel_config.php 등)은 모두 admin.lib.php의 get_admin_token()이 만드는
    // 표준 token 필드를 폼에 함께 심습니다. admin.js가 관리자 페이지의 모든 폼 제출
    // 버튼 클릭을 가로채 이 값을 매번 서버에서 다시 검증하므로, 하루BGM 폼도 다른
    // 커스텀 관리자 화면들과 동일하게 이 필드를 심어 같은 규약을 따릅니다.
    // 함수가 없는 순정 그누보드 환경에서는 조건이 거짓이라 아무 영향이 없습니다.
    if (function_exists('get_admin_token')) {
        $html .= '<input type="hidden" name="token" value="'.hb_e(get_admin_token()).'">';
    }
    return $html;
}

function hb_check_csrf($json=false) {
    $posted = isset($_POST['hb_token']) && is_scalar($_POST['hb_token']) ? (string)$_POST['hb_token'] : '';
    $saved = isset($_SESSION['hb_csrf_token']) ? (string)$_SESSION['hb_csrf_token'] : '';
    $ok = $posted !== '' && $saved !== '';
    if ($ok) $ok = function_exists('hash_equals') ? hash_equals($saved, $posted) : ($saved === $posted);
    if ($ok) return true;
    if ($json || (defined('HB_JSON_MODE') && HB_JSON_MODE)) {
        http_response_code(403);
        hb_json_exit(array('ok'=>false, 'message'=>'invalid_csrf_token'));
    }
    alert('요청 확인값이 만료되었거나 올바르지 않습니다. 페이지를 새로고침한 뒤 다시 시도해주세요.');
    exit;
}

// 이 서버의 다른 커스텀 관리자 화면(sosododam_pwa.php 등)과 동일하게, check_admin_token()이
// 있는 환경에서는 그 표준 검증도 함께 통과시킵니다. hb_check_csrf()는 이미 하루BGM 자체
// CSRF(hb_token)를 검증했으므로, 이 함수는 그 위에 이 서버의 관리자 공통 규약을 얹는
// 역할만 합니다. check_admin_token() 자체가 실패 시 alert()로 즉시 종료하므로 반환값을
// 별도로 처리하지 않습니다. 함수가 없는 순정 그누보드 환경에서는 아무 일도 하지 않습니다.
function hb_check_admin_token_if_present() {
    if (function_exists('check_admin_token')) check_admin_token();
}

function hb_is_super_admin() {
    global $member, $is_admin, $config;
    if (isset($is_admin) && $is_admin === 'super') return true;
    $mb_id = isset($member['mb_id']) ? trim((string)$member['mb_id']) : '';
    $cf_admin = isset($config['cf_admin']) ? trim((string)$config['cf_admin']) : '';
    return $mb_id !== '' && $cf_admin !== '' && hash_equals($cf_admin, $mb_id);
}

function hb_admin_menu_map() {
    return array(
        'index.php' => '990100',
        'operation.php' => '990110',
        'today.php' => '990120',
        'music_list.php' => '990130',
        'music_form.php' => '990130',
        'music_update.php' => '990130',
        'music_delete.php' => '990130',
        'schedule_global.php' => '990140',
        'schedule_form.php' => '990140',
        'schedule_update.php' => '990140',
        'schedule_delete.php' => '990140',
        'block_global.php' => '990150',
        'block_form.php' => '990150',
        'block_update.php' => '990150',
        'block_delete.php' => '990150',
        'sequence_list.php' => '990160',
        'sequence_form.php' => '990160',
        'sequence_update.php' => '990160',
        'sequence_delete.php' => '990160',
        'sequence_runner.php' => '990160',
        'logs.php' => '990180',
        'settings.php' => '990190',
        'health.php' => '990190',
        'schema_update.php' => '990190',
        'sitewide_toggle.php' => '990110',
        'sitewide_control.php' => '990110',
        'api_operation_schedule.php' => '990110'
    );
}

function hb_admin_menu_for_script($script='') {
    if ($script === '') {
        $script = isset($_SERVER['SCRIPT_NAME']) ? basename(str_replace('\\', '/', (string)$_SERVER['SCRIPT_NAME'])) : 'index.php';
    }
    $map = hb_admin_menu_map();
    return isset($map[$script]) ? $map[$script] : '990100';
}

function hb_admin_required_auth_for_script($script='') {
    if ($script === '') {
        $script = isset($_SERVER['SCRIPT_NAME']) ? basename(str_replace('\\', '/', (string)$_SERVER['SCRIPT_NAME'])) : '';
    }
    if (preg_match('/_delete\.php$/', $script)) return 'd';
    if (preg_match('/_form\.php$/', $script)) return 'w';
    if (preg_match('/(_update|sitewide_toggle|sitewide_control)\.php$/', $script)) return 'w';
    if ($script === 'settings.php' && isset($_SERVER['REQUEST_METHOD']) && strtoupper((string)$_SERVER['REQUEST_METHOD']) === 'POST') return 'w';
    return 'r';
}

function hb_user_has_admin_auth($menu_id='', $attr='r') {
    global $member, $g5;
    if (hb_is_super_admin()) return true;
    static $cache = array();
    $mb_id = isset($member['mb_id']) ? trim((string)$member['mb_id']) : '';
    if ($mb_id === '' || empty($g5['auth_table'])) return false;
    $menu_id = $menu_id !== '' ? preg_replace('/[^0-9]/', '', (string)$menu_id) : '990100';
    $attr = strtolower((string)$attr);
    $cache_key = $mb_id.'|'.$menu_id.'|'.$attr;
    if (array_key_exists($cache_key, $cache)) return $cache[$cache_key];
    $mb_sql = hb_escape($mb_id);
    $menu_sql = hb_escape($menu_id);
    $row = sql_fetch("SELECT au_auth FROM `{$g5['auth_table']}` WHERE mb_id='{$mb_sql}' AND au_menu='{$menu_sql}' LIMIT 1", false);
    $auth = $row && isset($row['au_auth']) ? strtolower((string)$row['au_auth']) : '';

    return $cache[$cache_key] = ($auth !== '' && strpos($auth, $attr) !== false);
}

function hb_user_has_any_admin_auth($menu_ids, $attr='r') {
    if (hb_is_super_admin()) return true;
    if (!is_array($menu_ids)) $menu_ids = array($menu_ids);
    foreach ($menu_ids as $menu_id) {
        if (hb_user_has_admin_auth((string)$menu_id, $attr)) return true;
    }
    return false;
}

function hb_is_plugin_admin() {
    global $member, $g5;
    static $cached = null;
    if ($cached !== null) return $cached;
    if (hb_is_super_admin()) return $cached = true;
    $mb_id = isset($member['mb_id']) ? trim((string)$member['mb_id']) : '';
    if ($mb_id === '' || empty($g5['auth_table'])) return $cached = false;
    $menu_ids = array_values(array_unique(array_filter(array_map(function($id){
        return preg_replace('/[^0-9]/', '', (string)$id);
    }, hb_admin_menu_map()))));
    if (!$menu_ids) return $cached = false;
    $quoted = array();
    foreach ($menu_ids as $menu_id) $quoted[] = "'".hb_escape($menu_id)."'";
    $mb_sql = hb_escape($mb_id);
    $row = sql_fetch("SELECT au_auth FROM `{$g5['auth_table']}` WHERE mb_id='{$mb_sql}' AND au_menu IN (".implode(',', $quoted).") AND au_auth<>'' LIMIT 1", false);
    return $cached = ($row && !empty($row['au_auth']));
}

function hb_is_admin_area_request() {
    $script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', (string)$_SERVER['SCRIPT_NAME']) : '';
    return strpos($script, '/haru_bgm/admin/') !== false || strpos($script, '/adm/') !== false;
}

function hb_ensure_data_dir() {
    if (!defined('HB_DATA_PATH') || HB_DATA_PATH === '') return false;
    $ok = true;
    if (!is_dir(HB_DATA_PATH)) {
        @mkdir(HB_DATA_PATH, G5_DIR_PERMISSION, true);
        @chmod(HB_DATA_PATH, G5_DIR_PERMISSION);
    }
    if (!is_dir(HB_DATA_PATH) || !is_writable(HB_DATA_PATH)) $ok = false;
    $index = HB_DATA_PATH.'/index.php';
    if (!file_exists($index)) {
        if (@file_put_contents($index, "<?php\n// silence\n", LOCK_EX) === false) $ok = false;
        @chmod($index, G5_FILE_PERMISSION);
    }
    if (!is_file($index)) $ok = false;
    // Apache 계열에서는 실제 음악 파일의 직접 HTTP 접근을 차단하고
    // 항상 stream.php의 권한/방송 상태 검사를 거치게 합니다.
    $htaccess = HB_DATA_PATH.'/.htaccess';
    $deny = "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nOrder allow,deny\nDeny from all\n</IfModule>\n";
    if (!is_file($htaccess) || trim((string)@file_get_contents($htaccess)) !== trim($deny)) {
        if (@file_put_contents($htaccess, $deny, LOCK_EX) === false) $ok = false;
        if (defined('G5_FILE_PERMISSION')) @chmod($htaccess, G5_FILE_PERMISSION);
    }
    if (!is_file($htaccess)) $ok = false;
    return $ok;
}

function hb_legacy_member_relative_files() {
    return array(
        'player.php','api_schedule.php',
        'my_schedule.php','my_schedule_form.php','my_schedule_update.php','my_schedule_delete.php',
        'my_blocks.php','my_block_form.php','my_block_update.php','my_block_delete.php',
        'my_music_list.php','my_music_form.php','my_music_update.php','my_music_delete.php',
        'admin/member_access.php','admin/member_access_update.php','admin/user_schedule.php'
    );
}

function hb_cleanup_legacy_member_files() {
    if (!defined('HB_PATH') || HB_PATH === '' || !hb_is_super_admin()) return array('removed'=>0, 'remaining'=>array());
    $removed = 0;
    $remaining = array();
    foreach (hb_legacy_member_relative_files() as $relative) {
        $relative = str_replace('\\', '/', (string)$relative);
        $path = rtrim(HB_PATH, '/\\').'/'.$relative;
        if (!is_file($path)) continue;
        if (@unlink($path)) $removed++;
        else $remaining[] = $relative;
    }
    return array('removed'=>$removed, 'remaining'=>$remaining);
}

function hb_schema_required_tables() {
    return array('music','schedule','schedule_day','play_log','block','block_day','block_item','schedule_item','sequence','sequence_item','settings','broadcast_state');
}

function hb_schema_required_tables_exist() {
    $names = array();
    foreach (hb_schema_required_tables() as $name) $names[] = hb_table($name);
    if (!$names) return true;
    $quoted = array();
    foreach ($names as $name) $quoted[] = "'".hb_escape($name)."'";
    $res = sql_query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN (".implode(',', $quoted).")", false);
    if (!$res) return false;
    $seen = array();
    while ($row = sql_fetch_array($res)) {
        if (isset($row['TABLE_NAME'])) $seen[(string)$row['TABLE_NAME']] = true;
    }
    foreach ($names as $name) if (empty($seen[$name])) return false;
    return true;
}

function hb_table_engine($table) {
    $table_sql = hb_escape((string)$table);
    $row = sql_fetch("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table_sql}' LIMIT 1", false);
    return $row && isset($row['ENGINE']) ? strtoupper((string)$row['ENGINE']) : '';
}

function hb_schema_required_engines_current() {
    foreach (hb_schema_required_tables() as $name) {
        if (hb_table_engine(hb_table($name)) !== 'INNODB') return false;
    }
    return true;
}

function hb_ensure_required_engines() {
    $ok = true;
    foreach (hb_schema_required_tables() as $name) {
        $table = hb_table($name);
        $engine = hb_table_engine($table);
        if ($engine === '') { $ok = false; continue; }
        if ($engine === 'INNODB') continue;
        if (!sql_query("ALTER TABLE `{$table}` ENGINE=InnoDB", false)) $ok = false;
    }
    return $ok;
}

function hb_ensure_required_charsets() {
    $ok = true;
    foreach (hb_schema_required_tables() as $name) {
        $table = hb_table($name);
        $collation = hb_table_collation($table);
        if ($collation !== '' && strpos($collation, 'utf8mb4_') === 0) continue;
        if (!sql_query("ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", false)) $ok = false;
    }
    return $ok;
}

function hb_schema_required_columns() {
    // 마커만 맞고 일부 기본 컬럼이 유실된 DB를 정상으로 오판하지 않도록
    // 현재 관리자/전체방송 코드가 사용하는 활성 스키마 전체를 확인합니다.
    return array(
        'music' => array(
            'mf_id','mf_title','mf_source','mf_file','mf_youtube_url','mf_youtube_id',
            'mf_org_name','mf_mime','mf_size','mf_volume','mf_type','mf_memo','mf_use',
            'mf_created_at','mf_updated_at'
        ),
        'schedule' => array(
            'sc_id','sc_scope','mf_id','sc_title','sc_time','sc_play_mode','sc_end_time',
            'sc_repeat','sc_days','sc_start_date','sc_end_date','sc_once','sc_sort','sc_use',
            'sc_created_at','sc_updated_at'
        ),
        'schedule_day' => array('sc_id','sd_weekday'),
        'play_log' => array(
            'pl_id','sc_id','mf_id','sc_scope','mb_id','pl_played_at','pl_ip','pl_user_agent',
            'pl_action','pl_status','pl_message'
        ),
        'block' => array(
            'bl_id','bl_scope','bl_title','bl_start_time','bl_end_time','bl_days',
            'bl_start_date','bl_end_date','bl_play_mode','bl_repeat','bl_sort','bl_use',
            'bl_created_at','bl_updated_at'
        ),
        'block_day' => array('bl_id','bd_weekday'),
        'block_item' => array('bi_id','bl_id','mf_id','bi_sort','bi_created_at'),
        'schedule_item' => array('si_id','sc_id','mf_id','si_sort','si_created_at'),
        'sequence' => array(
            'seq_id','seq_title','seq_type','seq_memo','seq_use','seq_sort','seq_created_at','seq_updated_at'
        ),
        'sequence_item' => array(
            'siq_id','seq_id','mf_id','siq_title','siq_memo','siq_sort','siq_created_at'
        ),
        'settings' => array('st_key','st_value','st_updated_at'),
        'broadcast_state' => array(
            'bs_id','bs_mode','mf_id','bs_seek_seconds','bs_started_epoch_ms','bs_revision','bs_updated_by','bs_updated_at'
        )
    );
}

function hb_column_exists($table, $column) {
    $table_sql = hb_escape((string)$table);
    $column_sql = hb_escape((string)$column);
    // SHOW ... LIKE는 '_'와 '%'를 와일드카드로 해석하므로 정확한 스키마 검사가 아닙니다.
    // information_schema의 등가 비교로 실제 테이블/컬럼 이름이 정확히 존재하는지 확인합니다.
    $row = sql_fetch("SELECT 1 AS hb_exists FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table_sql}' AND COLUMN_NAME='{$column_sql}' LIMIT 1", false);
    return $row && !empty($row['hb_exists']);
}

function hb_column_schema_info($table, $column) {
    $table_sql = hb_escape((string)$table);
    $column_sql = hb_escape((string)$column);
    $row = sql_fetch("SELECT COLUMN_TYPE, IS_NULLABLE, EXTRA, CHARACTER_SET_NAME, COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table_sql}' AND COLUMN_NAME='{$column_sql}' LIMIT 1", false);
    return is_array($row) ? $row : array();
}

function hb_normalize_column_type($type) {
    $type = strtolower(preg_replace('/\s+/', '', hb_scalar_string($type, '')));
    // MySQL 8은 정수 display width를 생략할 수 있고 MariaDB는 유지할 수 있으므로 의미 없는 폭만 제거합니다.
    return preg_replace('/\b(tinyint|smallint|mediumint|int|integer|bigint)\(\d+\)/', '$1', $type);
}

function hb_expected_column_type($definition) {
    $definition = trim(hb_scalar_string($definition, ''));
    if ($definition === '') return '';
    $parts = preg_split('/\s+/', $definition, 2);
    return hb_normalize_column_type(isset($parts[0]) ? $parts[0] : '');
}

function hb_definition_default_spec($definition, &$has_default=null) {
    $definition = trim(hb_scalar_string($definition, ''));
    $has_default = false;
    if (!preg_match('/\bDEFAULT\s+(NULL|CURRENT_TIMESTAMP|\'[^\']*\'|"[^"]*"|[-+]?[0-9]+(?:\.[0-9]+)?)/i', $definition, $m)) return null;
    $has_default = true;
    $raw = trim($m[1]);
    if (strcasecmp($raw, 'NULL') === 0) return null;
    if (strcasecmp($raw, 'CURRENT_TIMESTAMP') === 0) return 'CURRENT_TIMESTAMP';
    if (($raw[0] === "'" && substr($raw,-1) === "'") || ($raw[0] === '"' && substr($raw,-1) === '"')) return substr($raw,1,-1);
    return (string)$raw;
}

function hb_column_definition_matches($table, $column, $definition, &$detail=null) {
    $info = hb_column_schema_info($table, $column);
    if (!$info) { $detail = '컬럼 없음'; return false; }
    $expected_type = hb_expected_column_type($definition);
    $actual_type = hb_normalize_column_type(isset($info['COLUMN_TYPE']) ? $info['COLUMN_TYPE'] : '');
    if ($expected_type === '' || $actual_type !== $expected_type) {
        $detail = '타입 불일치: '.($actual_type !== '' ? $actual_type : '확인 실패').' / 기대 '.$expected_type;
        return false;
    }
    $definition_upper = strtoupper(' '.trim(hb_scalar_string($definition, '')).' ');
    $expected_nullable = strpos($definition_upper, ' NOT NULL ') === false;
    $actual_nullable = isset($info['IS_NULLABLE']) && strtoupper((string)$info['IS_NULLABLE']) === 'YES';
    if ($actual_nullable !== $expected_nullable) {
        $detail = 'NULL 허용 불일치';
        return false;
    }
    $expected_auto = strpos($definition_upper, ' AUTO_INCREMENT ') !== false;
    $actual_auto = isset($info['EXTRA']) && stripos((string)$info['EXTRA'], 'auto_increment') !== false;
    if ($actual_auto !== $expected_auto) {
        $detail = 'AUTO_INCREMENT 불일치';
        return false;
    }
    if (preg_match('/^(?:var)?char|^text|^tinytext|^mediumtext|^longtext|^enum\(|^set\(/', $expected_type)) {
        $charset = isset($info['CHARACTER_SET_NAME']) ? strtolower((string)$info['CHARACTER_SET_NAME']) : '';
        if ($charset !== 'utf8mb4') {
            $detail = '문자셋 불일치: '.($charset !== '' ? $charset : '확인 실패').' / 기대 utf8mb4';
            return false;
        }
    }
    $has_default = false;
    $expected_default = hb_definition_default_spec($definition, $has_default);
    if ($has_default) {
        $actual_default = array_key_exists('COLUMN_DEFAULT', $info) ? $info['COLUMN_DEFAULT'] : null;
        if ($expected_default === null) {
            if ($actual_default !== null) { $detail = 'DEFAULT 불일치: '.(string)$actual_default.' / 기대 NULL'; return false; }
        } elseif (strcasecmp((string)$expected_default, 'CURRENT_TIMESTAMP') === 0) {
            if (stripos((string)$actual_default, 'current_timestamp') !== 0) { $detail = 'DEFAULT 불일치: '.(string)$actual_default.' / 기대 CURRENT_TIMESTAMP'; return false; }
        } elseif (!hb_default_values_match($actual_default, $expected_default)) {
            $detail = 'DEFAULT 불일치: '.($actual_default === null ? 'NULL' : (string)$actual_default).' / 기대 '.(string)$expected_default;
            return false;
        }
    }
    $detail = '정상';
    return true;
}

function hb_schema_required_column_definitions_current() {
    foreach (hb_schema_column_definitions() as $table_key => $columns) {
        $table = hb_table($table_key);
        foreach ($columns as $column => $definition) {
            if (!hb_column_definition_matches($table, $column, $definition)) return false;
        }
    }
    return true;
}

function hb_table_collation($table) {
    $table_sql = hb_escape((string)$table);
    $row = sql_fetch("SELECT TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table_sql}' LIMIT 1", false);
    return $row && isset($row['TABLE_COLLATION']) ? strtolower((string)$row['TABLE_COLLATION']) : '';
}

function hb_schema_required_charsets_current() {
    foreach (hb_schema_required_tables() as $table_key) {
        $collation = hb_table_collation(hb_table($table_key));
        if ($collation === '' || strpos($collation, 'utf8mb4_') !== 0) return false;
    }
    return true;
}

function hb_schema_required_columns_exist() {
    $required = hb_schema_required_columns();
    if (!$required) return true;
    $table_names = array();
    foreach ($required as $table_name => $columns) $table_names[$table_name] = hb_table($table_name);
    $quoted = array();
    foreach ($table_names as $table) $quoted[] = "'".hb_escape($table)."'";
    $res = sql_query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN (".implode(',', $quoted).")", false);
    if (!$res) return false;
    $seen = array();
    while ($row = sql_fetch_array($res)) {
        if (!isset($row['TABLE_NAME'], $row['COLUMN_NAME'])) continue;
        $seen[(string)$row['TABLE_NAME']][(string)$row['COLUMN_NAME']] = true;
    }
    foreach ($required as $table_name => $columns) {
        $table = $table_names[$table_name];
        foreach ($columns as $column) if (empty($seen[$table][(string)$column])) return false;
    }
    return true;
}

function hb_schema_required_defaults() {
    $out = array();
    foreach (hb_schema_column_definitions() as $table_key => $columns) {
        foreach ($columns as $column => $definition) {
            $has_default = false;
            $default = hb_definition_default_spec($definition, $has_default);
            if (!$has_default) continue;
            $out[$table_key][$column] = array('default'=>$default, 'definition'=>$definition);
        }
    }
    return $out;
}

function hb_column_default_value($table, $column, &$found=null) {
    $table_sql = hb_escape((string)$table);
    $column_sql = hb_escape((string)$column);
    $row = sql_fetch("SELECT COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table_sql}' AND COLUMN_NAME='{$column_sql}' LIMIT 1", false);
    $found = is_array($row) && array_key_exists('COLUMN_DEFAULT', $row);
    if (!$found) return null;
    return $row['COLUMN_DEFAULT'];
}

function hb_default_values_match($actual, $expected) {
    if ($expected === null) return $actual === null;
    $a = strtolower(trim((string)$actual));
    $e = strtolower(trim((string)$expected));
    if ($e === 'current_timestamp') return $a === 'current_timestamp' || $a === 'current_timestamp()';
    if (is_numeric($a) && is_numeric($e)) return (string)(0 + $a) === (string)(0 + $e);
    return $a === $e;
}

function hb_schema_required_defaults_current() {
    foreach (hb_schema_required_defaults() as $table_key => $columns) {
        $table = hb_table($table_key);
        foreach ($columns as $column => $spec) {
            $found = false;
            $actual = hb_column_default_value($table, $column, $found);
            if (!$found) return false;
            if (!hb_default_values_match($actual, $spec['default'])) return false;
        }
    }
    return true;
}

function hb_ensure_required_defaults() {
    $ok = true;
    foreach (hb_schema_required_defaults() as $table_key => $columns) {
        $table = hb_table($table_key);
        if (!hb_table_exists($table)) { $ok = false; continue; }
        foreach ($columns as $column => $spec) {
            $found = false;
            $actual = hb_column_default_value($table, $column, $found);
            if (!$found) { $ok = false; continue; }
            if (hb_default_values_match($actual, $spec['default'])) continue;
            if (!hb_column_safe_to_modify($table, $column, $spec['definition'])) { $ok = false; continue; }
            $sql = hb_column_migration_sql($table, $column, $spec['definition']);
            if ($sql === '' || !sql_query($sql, false)) $ok = false;
        }
    }
    return $ok;
}

function hb_schema_marker_path() {
    if (!defined('HB_DATA_PATH') || HB_DATA_PATH === '') return '';
    return rtrim(HB_DATA_PATH, '/\\').'/.schema_'.preg_replace('/[^0-9A-Za-z_.-]/', '', (string)HB_SCHEMA_VERSION).'.ready';
}

function hb_schema_marker_is_fresh($max_age=900) {
    $path = hb_schema_marker_path();
    if ($path === '' || !is_file($path)) return false;
    $mtime = @filemtime($path);
    $age = $mtime ? (time() - (int)$mtime) : PHP_INT_MAX;
    if (!$mtime || $age < -300 || $age > max(300, (int)$max_age)) return false;
    $body = trim((string)@file_get_contents($path));
    return hash_equals((string)HB_SCHEMA_VERSION, $body);
}

function hb_schema_marker_remove() {
    $path = hb_schema_marker_path();
    if ($path !== '' && is_file($path)) @unlink($path);
}

function hb_schema_marker_write() {
    $path = hb_schema_marker_path();
    if ($path === '' || !is_dir(dirname($path)) || !is_writable(dirname($path))) return false;
    $ok = @file_put_contents($path, (string)HB_SCHEMA_VERSION, LOCK_EX);
    if ($ok === false) return false;
    if (defined('G5_FILE_PERMISSION')) @chmod($path, G5_FILE_PERMISSION);
    return true;
}

function hb_schema_is_current() {
    $settings = hb_table('settings');
    if (!hb_table_exists($settings)) return false;
    $key = hb_escape('schema_version');
    $row = sql_fetch("SELECT st_value FROM `{$settings}` WHERE st_key='{$key}' LIMIT 1", false);
    if (!$row || !isset($row['st_value']) || (string)$row['st_value'] !== (string)HB_SCHEMA_VERSION) return false;
    return hb_schema_required_tables_exist() && hb_schema_required_engines_current() && hb_schema_required_columns_exist() && hb_schema_required_column_definitions_current() && hb_schema_required_charsets_current() && hb_schema_required_defaults_current() && hb_schema_required_indexes_exist() && hb_schema_required_foreign_keys_current() && hb_schema_data_integrity_current();
}

// 공개 polling/스트리밍 같은 고빈도 요청에서 information_schema 전체 검사를 매번 반복하지 않습니다.
// 설치·업그레이드 시 hb_ensure_schema_current()가 깊은 검증 후 버전별 마커를 기록하고,
// 런타임은 마커가 일정 시간 이상 오래됐을 때만 다시 전체 스키마를 확인합니다.
function hb_schema_runtime_ready($max_age=300) {
    static $request_cache = null;
    if ($request_cache !== null) return $request_cache;
    $max_age = max(60, min(900, (int)$max_age));
    if (hb_schema_marker_is_fresh($max_age)) return $request_cache = true;
    $ok = hb_schema_is_current();
    if ($ok) hb_schema_marker_write();
    return $request_cache = (bool)$ok;
}

function hb_ensure_schema_current($force=false) {
    static $ready = false;
    if ($ready && !$force) return true;
    $settings = hb_table('settings');
    if (!$force && hb_schema_marker_is_fresh() && hb_table_exists($settings)) {
        $key = hb_escape('schema_version');
        $marker_row = sql_fetch("SELECT st_value FROM `{$settings}` WHERE st_key='{$key}' LIMIT 1", false);
        if ($marker_row && isset($marker_row['st_value']) && (string)$marker_row['st_value'] === (string)HB_SCHEMA_VERSION
            && hb_schema_required_tables_exist() && hb_schema_required_engines_current() && hb_schema_required_columns_exist() && hb_schema_required_column_definitions_current() && hb_schema_required_charsets_current() && hb_schema_required_defaults_current() && hb_schema_required_indexes_exist() && hb_schema_required_foreign_keys_current() && hb_schema_data_integrity_current()) {
            $ready = true;
            return true;
        }
    }

    if (!$force && hb_schema_is_current()) {
        $ready = true;
        hb_schema_marker_write();
        return true;
    }

    $ok = hb_ensure_tables();
    if ($ok) {
        $ok = hb_schema_required_tables_exist() && hb_schema_required_engines_current() && hb_schema_required_columns_exist() && hb_schema_required_column_definitions_current() && hb_schema_required_charsets_current() && hb_schema_required_defaults_current() && hb_schema_required_indexes_exist() && hb_schema_required_foreign_keys_current() && hb_schema_data_integrity_current();
        if ($ok) $ok = hb_update_setting('schema_version', HB_SCHEMA_VERSION);
    }
    if ($ok) hb_schema_marker_write();
    else hb_schema_marker_remove();
    $ready = (bool)$ok;
    return $ready;
}


function hb_schema_repair_lock_path() {
    $candidates = array();
    if (defined('HB_DATA_PATH') && HB_DATA_PATH !== '') $candidates[] = HB_DATA_PATH;
    if (defined('G5_DATA_PATH') && G5_DATA_PATH !== '') $candidates[] = G5_DATA_PATH;
    if (function_exists('sys_get_temp_dir')) $candidates[] = sys_get_temp_dir();
    foreach ($candidates as $dir) {
        if (is_dir($dir) && is_writable($dir)) return rtrim($dir, '/\\').'/.haru_bgm_schema_repair.lock';
    }
    return '';
}

function hb_schema_repair_lock($acquire=true, $timeout=3) {
    // DB GET_LOCK에 의존하지 않습니다. 일부 호스팅/DB 프록시에서 GET_LOCK이 제한되면
    // 정상 설치 후 재점검 버튼조차 오류가 날 수 있으므로 data 폴더의 flock으로 직렬화합니다.
    static $handle = null;
    if (!$acquire) {
        if (is_resource($handle)) {
            @flock($handle, LOCK_UN);
            @fclose($handle);
        }
        $handle = null;
        return true;
    }
    if (is_resource($handle)) return true;
    // DB 설치/복구 잠금은 음악 저장 폴더의 .htaccess 준비와 독립적이어야 합니다.
    // 저장 폴더 권한 문제 때문에 DB 설치 자체가 busy로 오인되는 것을 막습니다.
    $path = hb_schema_repair_lock_path();
    if ($path === '') return false;
    $fh = @fopen($path, 'c');
    if (!$fh) return false;
    $timeout = max(0, min(10, (int)$timeout));
    $deadline = microtime(true) + $timeout;
    do {
        if (@flock($fh, LOCK_EX | LOCK_NB)) {
            $handle = $fh;
            return true;
        }
        if ($timeout === 0) break;
        usleep(100000);
    } while (microtime(true) < $deadline);
    @fclose($fh);
    return false;
}

function hb_schema_check_repair() {
    // 반복 실행 가능한 DB 점검/복구 진입점입니다.
    // 정상 스키마에는 CREATE/ALTER를 다시 실행하지 않고 검증만 수행합니다.
    // DB 복구는 음원 저장 폴더와 독립적으로 실행합니다. 폴더 권한이 없어도
    // 스키마 복구 잠금은 G5 data/temp fallback으로 확보하고, 저장소 문제는
    // health의 별도 점검 항목으로 표시합니다.
    $data_dir_ready = hb_ensure_data_dir();
    hb_cleanup_legacy_member_files();

    if (hb_schema_is_current()) {
        hb_schema_marker_write();
        return array('ok'=>true, 'status'=>'already', 'message'=>'현재 데이터베이스 구성이 정상입니다. 변경된 항목은 없습니다.');
    }

    // 같은 순간 여러 탭/클릭에서 ALTER가 겹치지 않도록 data 폴더의 파일 잠금으로 직렬화합니다.
    if (!hb_schema_repair_lock(true, 3)) {
        return array('ok'=>false, 'status'=>'busy', 'message'=>'다른 데이터베이스 점검 작업이 진행 중입니다. 현재 화면을 새로고침한 뒤 다시 확인해주세요.');
    }

    try {
        // 잠금을 얻기 전 다른 요청이 이미 복구했을 수 있으므로 한 번 더 검사합니다.
        if (hb_schema_is_current()) {
            hb_schema_marker_write();
            return array('ok'=>true, 'status'=>'already', 'message'=>'현재 데이터베이스 구성이 정상입니다. 변경된 항목은 없습니다.');
        }

        hb_schema_marker_remove();
        $ok = hb_ensure_schema_current(true);
        if ($ok && hb_schema_is_current()) {
            hb_schema_marker_write();
            return array('ok'=>true, 'status'=>'repaired', 'message'=>$data_dir_ready ? '필요한 데이터베이스 항목을 복구했습니다. 현재 구성은 정상입니다.' : '데이터베이스 항목은 복구했습니다. 음원 저장 폴더 권한은 별도로 확인해주세요.');
        }

        hb_schema_marker_remove();
        return array('ok'=>false, 'status'=>'fail', 'message'=>'일부 데이터베이스 항목을 복구하지 못했습니다. 아래에서 확인이 필요한 항목을 확인해주세요.');
    } catch (Throwable $e) {
        hb_schema_marker_remove();
        return array('ok'=>false, 'status'=>'fail', 'message'=>'데이터베이스 점검 중 오류가 발생했습니다. 아래 점검 결과에서 확인이 필요한 항목을 확인해주세요.');
    } finally {
        hb_schema_repair_lock(false);
    }
}

function hb_schema_auto_install_if_missing() {
    // 신규 설치·부분 설치·구버전 설치 모두 첫 플러그인 요청에서 안전검사 후
    // 필요한 스키마를 자동 생성/보정합니다. 기존 코드는 schema_version 값이
    // 조금이라도 있으면 구버전/미완성 상태까지 자동 복구를 건너뛰어 설치가 막혔습니다.
    static $attempted = false;
    if ($attempted) return !empty($GLOBALS['hb_schema_auto_install_ok']);
    $attempted = true;
    if (hb_schema_marker_is_fresh()) {
        $GLOBALS['hb_schema_auto_install_ok'] = true;
        return true;
    }
    if (hb_schema_is_current()) {
        hb_schema_marker_write();
        $GLOBALS['hb_schema_auto_install_ok'] = true;
        return true;
    }
    if (!hb_schema_repair_lock(true, 5)) {
        $GLOBALS['hb_schema_auto_install_ok'] = false;
        return false;
    }
    try {
        $ok = hb_ensure_schema_current(true);
        $GLOBALS['hb_schema_auto_install_ok'] = $ok && hb_schema_is_current();
        return !empty($GLOBALS['hb_schema_auto_install_ok']);
    } catch (Throwable $e) {
        $GLOBALS['hb_schema_auto_install_ok'] = false;
        return false;
    } finally {
        hb_schema_repair_lock(false);
    }
}

function hb_ensure_tables() {
    $music = hb_table('music');
    $schedule = hb_table('schedule');
    $log = hb_table('play_log');

    sql_query("CREATE TABLE IF NOT EXISTS `{$music}` (
        `mf_id` int(11) NOT NULL AUTO_INCREMENT,
        `mf_title` varchar(255) NOT NULL DEFAULT '',
        `mf_source` enum('file','youtube') NOT NULL DEFAULT 'file',
        `mf_file` varchar(255) NOT NULL DEFAULT '',
        `mf_youtube_url` varchar(500) NOT NULL DEFAULT '',
        `mf_youtube_id` varchar(30) NULL DEFAULT NULL,
        `mf_org_name` varchar(255) NOT NULL DEFAULT '',
        `mf_mime` varchar(100) NOT NULL DEFAULT '',
        `mf_size` int(11) NOT NULL DEFAULT 0,
        `mf_volume` tinyint(3) NOT NULL DEFAULT 80,
        `mf_type` varchar(30) NOT NULL DEFAULT 'music',
        `mf_memo` text NULL,
        `mf_use` tinyint(1) NOT NULL DEFAULT 1,
        `mf_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `mf_updated_at` datetime NULL DEFAULT NULL,
        PRIMARY KEY (`mf_id`),
        KEY `mf_use` (`mf_use`),
        UNIQUE KEY `youtube_id_unique` (`mf_youtube_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false);

    $schedule_day = hb_table('schedule_day');
    sql_query("CREATE TABLE IF NOT EXISTS `{$schedule_day}` (
        `sc_id` int(11) NOT NULL,
        `sd_weekday` tinyint(3) NOT NULL,
        PRIMARY KEY (`sc_id`,`sd_weekday`),
        KEY `weekday` (`sd_weekday`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false);

    sql_query("CREATE TABLE IF NOT EXISTS `{$schedule}` (
        `sc_id` int(11) NOT NULL AUTO_INCREMENT,
        `sc_scope` enum('global','user') NOT NULL DEFAULT 'global',
        `mf_id` int(11) NOT NULL DEFAULT 0,
        `sc_title` varchar(255) NOT NULL DEFAULT '',
        `sc_time` time NOT NULL DEFAULT '00:00:00',
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
        KEY `scope_use` (`sc_scope`, `sc_use`),
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
        `pl_action` varchar(30) NOT NULL DEFAULT 'auto',
        `pl_status` varchar(20) NOT NULL DEFAULT 'success',
        `pl_message` varchar(255) NOT NULL DEFAULT '',
        PRIMARY KEY (`pl_id`),
        KEY `actor_date` (`mb_id`, `pl_played_at`),
        KEY `schedule` (`sc_id`),
        KEY `played_at` (`pl_played_at`),
        KEY `status_date` (`pl_status`, `pl_played_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false);

    $block_day = hb_table('block_day');
    sql_query("CREATE TABLE IF NOT EXISTS `{$block_day}` (
        `bl_id` int(11) NOT NULL,
        `bd_weekday` tinyint(3) NOT NULL,
        PRIMARY KEY (`bl_id`,`bd_weekday`),
        KEY `weekday` (`bd_weekday`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false);

    $block = hb_table('block');
    $block_item = hb_table('block_item');

    sql_query("CREATE TABLE IF NOT EXISTS `{$block}` (
        `bl_id` int(11) NOT NULL AUTO_INCREMENT,
        `bl_scope` enum('global','user') NOT NULL DEFAULT 'global',
        `bl_title` varchar(255) NOT NULL DEFAULT '',
        `bl_start_time` time NOT NULL DEFAULT '00:00:00',
        `bl_end_time` time NOT NULL DEFAULT '00:00:00',
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
        KEY `scope_use` (`bl_scope`, `bl_use`),
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
        `seq_title` varchar(255) NOT NULL DEFAULT '',
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


    $broadcast_state = hb_table('broadcast_state');
    sql_query("CREATE TABLE IF NOT EXISTS `{$broadcast_state}` (
        `bs_id` tinyint(1) NOT NULL DEFAULT 1,
        `bs_mode` enum('auto','manual','stop') NOT NULL DEFAULT 'auto',
        `mf_id` int(11) NULL DEFAULT NULL,
        `bs_seek_seconds` decimal(10,3) NOT NULL DEFAULT 0.000,
        `bs_started_epoch_ms` bigint(20) NOT NULL DEFAULT 0,
        `bs_revision` bigint(20) NOT NULL DEFAULT 1,
        `bs_updated_by` varchar(50) NOT NULL DEFAULT '',
        `bs_updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`bs_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false);
    sql_query("INSERT IGNORE INTO `{$broadcast_state}` SET bs_id=1, bs_mode='auto', bs_revision=1, bs_updated_at=NOW()", false);

    $tables_ok = hb_schema_required_tables_exist();
    // 한 단계의 보정 실패가 뒤 단계의 설치 시도까지 막지 않게 각 단계를 독립 실행합니다.
    // 예: 오래된 컬럼 정의가 남아 있어도 누락 인덱스/기본값/설정은 가능한 범위까지 계속 복구합니다.
    $engines_ok = $tables_ok ? hb_ensure_required_engines() : false;
    $charsets_ok = $tables_ok ? hb_ensure_required_charsets() : false;
    $columns_ok = $tables_ok ? hb_ensure_required_columns() : false;
    // 기존 1.5.24의 mf_youtube_id/mf_id는 빈 문자열·0 센티널과 NOT NULL 정의를
    // 사용했으므로, 컬럼 정의를 먼저 보정한 뒤 NULL 정규화를 실행해야 첫 복구에서
    // YouTube UNIQUE 인덱스까지 한 번에 설치할 수 있습니다.
    $sentinels_ok = $tables_ok && $columns_ok ? hb_normalize_schema_sentinels() : false;
    $days_ok = $tables_ok ? hb_sync_legacy_day_rows() : false;
    $defaults_ok = $tables_ok ? hb_ensure_required_defaults() : false;
    $indexes_ok = $tables_ok ? hb_ensure_required_indexes() : false;
    $foreign_keys_ok = $tables_ok ? hb_ensure_required_foreign_keys() : false;
    $settings_ok = $tables_ok ? hb_seed_settings() : false;
    return $tables_ok && $engines_ok && $charsets_ok && $columns_ok && $sentinels_ok && $days_ok && $defaults_ok && $indexes_ok && $foreign_keys_ok && $settings_ok
        && hb_schema_required_tables_exist() && hb_schema_required_engines_current()
        && hb_schema_required_columns_exist() && hb_schema_required_column_definitions_current() && hb_schema_required_charsets_current() && hb_schema_required_defaults_current() && hb_schema_required_indexes_exist() && hb_schema_required_foreign_keys_current() && hb_schema_data_integrity_current();
}

function hb_schema_column_definitions() {
    return array(
        'music' => array(
            'mf_id' => "int(11) NOT NULL AUTO_INCREMENT",
            'mf_title' => "varchar(255) NOT NULL DEFAULT ''",
            'mf_source' => "enum('file','youtube') NOT NULL DEFAULT 'file'",
            'mf_file' => "varchar(255) NOT NULL DEFAULT ''",
            'mf_youtube_url' => "varchar(500) NOT NULL DEFAULT ''",
            'mf_youtube_id' => "varchar(30) NULL DEFAULT NULL",
            'mf_org_name' => "varchar(255) NOT NULL DEFAULT ''",
            'mf_mime' => "varchar(100) NOT NULL DEFAULT ''",
            'mf_size' => "int(11) NOT NULL DEFAULT 0",
            'mf_volume' => "tinyint(3) NOT NULL DEFAULT 80",
            'mf_type' => "varchar(30) NOT NULL DEFAULT 'music'",
            'mf_memo' => "text NULL",
            'mf_use' => "tinyint(1) NOT NULL DEFAULT 1",
            'mf_created_at' => "datetime NOT NULL DEFAULT CURRENT_TIMESTAMP",
            'mf_updated_at' => "datetime NULL DEFAULT NULL"
        ),
        'schedule' => array(
            'sc_id' => "int(11) NOT NULL AUTO_INCREMENT",
            'sc_scope' => "enum('global','user') NOT NULL DEFAULT 'global'",
            'mf_id' => "int(11) NOT NULL DEFAULT 0",
            'sc_title' => "varchar(255) NOT NULL DEFAULT ''",
            'sc_time' => "time NOT NULL DEFAULT '00:00:00'",
            'sc_play_mode' => "enum('once','range') NOT NULL DEFAULT 'once'",
            'sc_end_time' => "time NULL DEFAULT NULL",
            'sc_repeat' => "tinyint(1) NOT NULL DEFAULT 0",
            'sc_days' => "varchar(50) NOT NULL DEFAULT '0,1,2,3,4,5,6'",
            'sc_start_date' => "date NULL DEFAULT NULL",
            'sc_end_date' => "date NULL DEFAULT NULL",
            'sc_once' => "tinyint(1) NOT NULL DEFAULT 0",
            'sc_sort' => "int(11) NOT NULL DEFAULT 0",
            'sc_use' => "tinyint(1) NOT NULL DEFAULT 1",
            'sc_created_at' => "datetime NOT NULL DEFAULT CURRENT_TIMESTAMP",
            'sc_updated_at' => "datetime NULL DEFAULT NULL"
        ),
        'schedule_day' => array(
            'sc_id' => "int(11) NOT NULL",
            'sd_weekday' => "tinyint(3) NOT NULL"
        ),
        'play_log' => array(
            'pl_id' => "int(11) NOT NULL AUTO_INCREMENT",
            'sc_id' => "int(11) NOT NULL DEFAULT 0",
            'mf_id' => "int(11) NOT NULL DEFAULT 0",
            'sc_scope' => "varchar(30) NOT NULL DEFAULT ''",
            'mb_id' => "varchar(50) NOT NULL DEFAULT ''",
            'pl_played_at' => "datetime NOT NULL DEFAULT CURRENT_TIMESTAMP",
            'pl_ip' => "varchar(45) NOT NULL DEFAULT ''",
            'pl_user_agent' => "varchar(255) NOT NULL DEFAULT ''",
            'pl_action' => "varchar(30) NOT NULL DEFAULT 'auto'",
            'pl_status' => "varchar(20) NOT NULL DEFAULT 'success'",
            'pl_message' => "varchar(255) NOT NULL DEFAULT ''"
        ),
        'block' => array(
            'bl_id' => "int(11) NOT NULL AUTO_INCREMENT",
            'bl_scope' => "enum('global','user') NOT NULL DEFAULT 'global'",
            'bl_title' => "varchar(255) NOT NULL DEFAULT ''",
            'bl_start_time' => "time NOT NULL DEFAULT '00:00:00'",
            'bl_end_time' => "time NOT NULL DEFAULT '00:00:00'",
            'bl_days' => "varchar(50) NOT NULL DEFAULT '0,1,2,3,4,5,6'",
            'bl_start_date' => "date NULL DEFAULT NULL",
            'bl_end_date' => "date NULL DEFAULT NULL",
            'bl_play_mode' => "enum('sequence','random') NOT NULL DEFAULT 'sequence'",
            'bl_repeat' => "tinyint(1) NOT NULL DEFAULT 1",
            'bl_sort' => "int(11) NOT NULL DEFAULT 0",
            'bl_use' => "tinyint(1) NOT NULL DEFAULT 1",
            'bl_created_at' => "datetime NOT NULL DEFAULT CURRENT_TIMESTAMP",
            'bl_updated_at' => "datetime NULL DEFAULT NULL"
        ),
        'block_day' => array(
            'bl_id' => "int(11) NOT NULL",
            'bd_weekday' => "tinyint(3) NOT NULL"
        ),
        'block_item' => array(
            'bi_id' => "int(11) NOT NULL AUTO_INCREMENT",
            'bl_id' => "int(11) NOT NULL DEFAULT 0",
            'mf_id' => "int(11) NOT NULL DEFAULT 0",
            'bi_sort' => "int(11) NOT NULL DEFAULT 0",
            'bi_created_at' => "datetime NOT NULL DEFAULT CURRENT_TIMESTAMP"
        ),
        'schedule_item' => array(
            'si_id' => "int(11) NOT NULL AUTO_INCREMENT",
            'sc_id' => "int(11) NOT NULL DEFAULT 0",
            'mf_id' => "int(11) NOT NULL DEFAULT 0",
            'si_sort' => "int(11) NOT NULL DEFAULT 0",
            'si_created_at' => "datetime NOT NULL DEFAULT CURRENT_TIMESTAMP"
        ),
        'sequence' => array(
            'seq_id' => "int(11) NOT NULL AUTO_INCREMENT",
            'seq_title' => "varchar(255) NOT NULL DEFAULT ''",
            'seq_type' => "varchar(30) NOT NULL DEFAULT 'general'",
            'seq_memo' => "text NULL",
            'seq_use' => "tinyint(1) NOT NULL DEFAULT 1",
            'seq_sort' => "int(11) NOT NULL DEFAULT 0",
            'seq_created_at' => "datetime NOT NULL DEFAULT CURRENT_TIMESTAMP",
            'seq_updated_at' => "datetime NULL DEFAULT NULL"
        ),
        'sequence_item' => array(
            'siq_id' => "int(11) NOT NULL AUTO_INCREMENT",
            'seq_id' => "int(11) NOT NULL DEFAULT 0",
            'mf_id' => "int(11) NOT NULL DEFAULT 0",
            'siq_title' => "varchar(255) NOT NULL DEFAULT ''",
            'siq_memo' => "varchar(255) NOT NULL DEFAULT ''",
            'siq_sort' => "int(11) NOT NULL DEFAULT 0",
            'siq_created_at' => "datetime NOT NULL DEFAULT CURRENT_TIMESTAMP"
        ),
        'settings' => array(
            'st_key' => "varchar(80) NOT NULL",
            'st_value' => "text NULL",
            'st_updated_at' => "datetime NOT NULL DEFAULT CURRENT_TIMESTAMP"
        ),
        'broadcast_state' => array(
            'bs_id' => "tinyint(1) NOT NULL DEFAULT 1",
            'bs_mode' => "enum('auto','manual','stop') NOT NULL DEFAULT 'auto'",
            'mf_id' => "int(11) NULL DEFAULT NULL",
            'bs_seek_seconds' => "decimal(10,3) NOT NULL DEFAULT 0.000",
            'bs_started_epoch_ms' => "bigint(20) NOT NULL DEFAULT 0",
            'bs_revision' => "bigint(20) NOT NULL DEFAULT 1",
            'bs_updated_by' => "varchar(50) NOT NULL DEFAULT ''",
            'bs_updated_at' => "datetime NOT NULL DEFAULT CURRENT_TIMESTAMP"
        )
    );
}

function hb_schema_index_definitions() {
    return array(
        'music' => array('PRIMARY'=>'(`mf_id`)', 'mf_use'=>'(`mf_use`)', 'youtube_id_unique'=>'(`mf_youtube_id`)'),
        'schedule' => array('PRIMARY'=>'(`sc_id`)', 'scope_use'=>'(`sc_scope`,`sc_use`)', 'sc_time'=>'(`sc_time`)', 'sc_use'=>'(`sc_use`)'),
        'schedule_day' => array('PRIMARY'=>'(`sc_id`,`sd_weekday`)', 'weekday'=>'(`sd_weekday`)'),
        'play_log' => array('PRIMARY'=>'(`pl_id`)', 'actor_date'=>'(`mb_id`,`pl_played_at`)', 'schedule'=>'(`sc_id`)', 'played_at'=>'(`pl_played_at`)', 'status_date'=>'(`pl_status`,`pl_played_at`)'),
        'block' => array('PRIMARY'=>'(`bl_id`)', 'scope_use'=>'(`bl_scope`,`bl_use`)', 'time_range'=>'(`bl_start_time`,`bl_end_time`)', 'bl_use'=>'(`bl_use`)'),
        'block_day' => array('PRIMARY'=>'(`bl_id`,`bd_weekday`)', 'weekday'=>'(`bd_weekday`)'),
        'block_item' => array('PRIMARY'=>'(`bi_id`)', 'block_sort'=>'(`bl_id`,`bi_sort`)', 'music'=>'(`mf_id`)'),
        'schedule_item' => array('PRIMARY'=>'(`si_id`)', 'schedule_sort'=>'(`sc_id`,`si_sort`)', 'music'=>'(`mf_id`)'),
        'sequence' => array('PRIMARY'=>'(`seq_id`)', 'seq_use'=>'(`seq_use`)', 'seq_sort'=>'(`seq_sort`)'),
        'sequence_item' => array('PRIMARY'=>'(`siq_id`)', 'sequence_sort'=>'(`seq_id`,`siq_sort`)', 'music'=>'(`mf_id`)'),
        'settings' => array('PRIMARY'=>'(`st_key`)'),
        'broadcast_state' => array('PRIMARY'=>'(`bs_id`)')
    );
}

function hb_schema_foreign_key_definitions() {
    return array(
        'schedule_day' => array(
            'fk_hb_schedule_day_schedule' => array('column'=>'sc_id', 'ref_table'=>'schedule', 'ref_column'=>'sc_id', 'on_delete'=>'CASCADE')
        ),
        'block_day' => array(
            'fk_hb_block_day_block' => array('column'=>'bl_id', 'ref_table'=>'block', 'ref_column'=>'bl_id', 'on_delete'=>'CASCADE')
        ),
        'block_item' => array(
            'fk_hb_block_item_block' => array('column'=>'bl_id', 'ref_table'=>'block', 'ref_column'=>'bl_id', 'on_delete'=>'CASCADE'),
            'fk_hb_block_item_music' => array('column'=>'mf_id', 'ref_table'=>'music', 'ref_column'=>'mf_id', 'on_delete'=>'CASCADE')
        ),
        'schedule_item' => array(
            'fk_hb_schedule_item_schedule' => array('column'=>'sc_id', 'ref_table'=>'schedule', 'ref_column'=>'sc_id', 'on_delete'=>'CASCADE'),
            'fk_hb_schedule_item_music' => array('column'=>'mf_id', 'ref_table'=>'music', 'ref_column'=>'mf_id', 'on_delete'=>'CASCADE')
        ),
        'sequence_item' => array(
            'fk_hb_sequence_item_sequence' => array('column'=>'seq_id', 'ref_table'=>'sequence', 'ref_column'=>'seq_id', 'on_delete'=>'CASCADE'),
            'fk_hb_sequence_item_music' => array('column'=>'mf_id', 'ref_table'=>'music', 'ref_column'=>'mf_id', 'on_delete'=>'CASCADE')
        ),
        'broadcast_state' => array(
            'fk_hb_broadcast_state_music' => array('column'=>'mf_id', 'ref_table'=>'music', 'ref_column'=>'mf_id', 'on_delete'=>'SET NULL')
        )
    );
}

function hb_foreign_key_info($table, $constraint_name='') {
    $table_sql = hb_escape((string)$table);
    $where = "kcu.CONSTRAINT_SCHEMA=DATABASE() AND kcu.TABLE_NAME='{$table_sql}' AND kcu.REFERENCED_TABLE_NAME IS NOT NULL";
    if ($constraint_name !== '') $where .= " AND kcu.CONSTRAINT_NAME='".hb_escape((string)$constraint_name)."'";
    $res = sql_query("SELECT kcu.CONSTRAINT_NAME, kcu.COLUMN_NAME, kcu.REFERENCED_TABLE_NAME, kcu.REFERENCED_COLUMN_NAME, rc.DELETE_RULE, rc.UPDATE_RULE
        FROM information_schema.KEY_COLUMN_USAGE kcu
        LEFT JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
          ON rc.CONSTRAINT_SCHEMA=kcu.CONSTRAINT_SCHEMA AND rc.CONSTRAINT_NAME=kcu.CONSTRAINT_NAME AND rc.TABLE_NAME=kcu.TABLE_NAME
        WHERE {$where} ORDER BY kcu.CONSTRAINT_NAME ASC, kcu.ORDINAL_POSITION ASC", false);
    if (!$res) return array();
    $out = array();
    while ($row = sql_fetch_array($res)) {
        $name = isset($row['CONSTRAINT_NAME']) ? (string)$row['CONSTRAINT_NAME'] : '';
        if ($name === '') continue;
        if (!isset($out[$name])) $out[$name] = array('columns'=>array(), 'ref_table'=>'', 'ref_columns'=>array(), 'on_delete'=>'', 'on_update'=>'');
        $out[$name]['columns'][] = isset($row['COLUMN_NAME']) ? (string)$row['COLUMN_NAME'] : '';
        $out[$name]['ref_table'] = isset($row['REFERENCED_TABLE_NAME']) ? (string)$row['REFERENCED_TABLE_NAME'] : '';
        $out[$name]['ref_columns'][] = isset($row['REFERENCED_COLUMN_NAME']) ? (string)$row['REFERENCED_COLUMN_NAME'] : '';
        if (isset($row['DELETE_RULE'])) $out[$name]['on_delete'] = strtoupper((string)$row['DELETE_RULE']);
        if (isset($row['UPDATE_RULE'])) $out[$name]['on_update'] = strtoupper((string)$row['UPDATE_RULE']);
    }
    return $out;
}

function hb_foreign_key_matches($actual, $table_key, $spec) {
    if (!is_array($actual) || !is_array($spec)) return false;
    $ref_table = hb_table(isset($spec['ref_table']) ? $spec['ref_table'] : '');
    $columns = array(isset($spec['column']) ? (string)$spec['column'] : '');
    $ref_columns = array(isset($spec['ref_column']) ? (string)$spec['ref_column'] : '');
    return isset($actual['columns'], $actual['ref_table'], $actual['ref_columns'], $actual['on_delete'])
        && $actual['columns'] === $columns
        && $actual['ref_table'] === $ref_table
        && $actual['ref_columns'] === $ref_columns
        && strtoupper((string)$actual['on_delete']) === strtoupper((string)$spec['on_delete']);
}

function hb_foreign_key_equivalent($table, $table_key, $spec) {
    foreach (hb_foreign_key_info($table) as $actual) {
        if (hb_foreign_key_matches($actual, $table_key, $spec)) return true;
    }
    return false;
}

function hb_ensure_foreign_key($table_key, $constraint_name, $spec) {
    $table = hb_table($table_key);
    $parent = hb_table(isset($spec['ref_table']) ? $spec['ref_table'] : '');
    if (!hb_table_exists($table) || !hb_table_exists($parent)) return false;
    $named = hb_foreign_key_info($table, $constraint_name);
    if ($named && isset($named[$constraint_name]) && hb_foreign_key_matches($named[$constraint_name], $table_key, $spec)) return true;
    if (hb_foreign_key_equivalent($table, $table_key, $spec)) return true;
    if ($named && isset($named[$constraint_name])) {
        $safe_name = preg_replace('/[^A-Za-z0-9_]/', '', (string)$constraint_name);
        if ($safe_name === '' || !sql_query("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$safe_name}`", false)) return false;
    }
    $safe_name = preg_replace('/[^A-Za-z0-9_]/', '', (string)$constraint_name);
    $column = preg_replace('/[^A-Za-z0-9_]/', '', (string)$spec['column']);
    $ref_column = preg_replace('/[^A-Za-z0-9_]/', '', (string)$spec['ref_column']);
    $on_delete = strtoupper((string)$spec['on_delete']);
    if ($safe_name === '' || $column === '' || $ref_column === '' || !in_array($on_delete, array('CASCADE','SET NULL','RESTRICT','NO ACTION'), true)) return false;
    $sql = "ALTER TABLE `{$table}` ADD CONSTRAINT `{$safe_name}` FOREIGN KEY (`{$column}`) REFERENCES `{$parent}` (`{$ref_column}`) ON DELETE {$on_delete}";
    return sql_query($sql, false) ? true : false;
}

function hb_ensure_required_foreign_keys() {
    $ok = true;
    foreach (hb_schema_foreign_key_definitions() as $table_key => $keys) {
        foreach ($keys as $name => $spec) if (!hb_ensure_foreign_key($table_key, $name, $spec)) $ok = false;
    }
    return $ok;
}

function hb_schema_required_foreign_keys_current() {
    foreach (hb_schema_foreign_key_definitions() as $table_key => $keys) {
        $table = hb_table($table_key);
        foreach ($keys as $name => $spec) {
            $actual = hb_foreign_key_info($table, $name);
            if (($actual && isset($actual[$name]) && hb_foreign_key_matches($actual[$name], $table_key, $spec)) || hb_foreign_key_equivalent($table, $table_key, $spec)) continue;
            return false;
        }
    }
    return true;
}

function hb_schema_integrity_count($sql) {
    $row = sql_fetch($sql, false);
    return $row && isset($row['cnt']) ? max(0, (int)$row['cnt']) : null;
}

function hb_schema_data_integrity() {
    $music = hb_table('music');
    $block = hb_table('block');
    $block_day = hb_table('block_day');
    $block_item = hb_table('block_item');
    $schedule = hb_table('schedule');
    $schedule_day = hb_table('schedule_day');
    $schedule_item = hb_table('schedule_item');
    $sequence = hb_table('sequence');
    $sequence_item = hb_table('sequence_item');
    $integrity_keys = array(
        'youtube_duplicates', 'block_day_orphans', 'block_item_orphans', 'block_music_orphans',
        'schedule_day_orphans', 'schedule_item_orphans', 'schedule_music_orphans',
        'sequence_item_orphans', 'sequence_music_orphans'
    );
    foreach (array($music, $block, $block_day, $block_item, $schedule, $schedule_day, $schedule_item, $sequence, $sequence_item) as $table) {
        if (!hb_table_exists($table)) return array_fill_keys($integrity_keys, null);
    }
    $checks = array(
        'youtube_duplicates' => hb_schema_integrity_count("SELECT COUNT(*) AS cnt FROM (SELECT mf_youtube_id FROM `{$music}` WHERE mf_youtube_id IS NOT NULL AND mf_youtube_id<>'' GROUP BY mf_youtube_id HAVING COUNT(*)>1) hb_duplicate_youtube"),
        'block_day_orphans' => hb_schema_integrity_count("SELECT COUNT(*) AS cnt FROM `{$block_day}` bd LEFT JOIN `{$block}` b ON b.bl_id=bd.bl_id WHERE b.bl_id IS NULL"),
        'block_item_orphans' => hb_schema_integrity_count("SELECT COUNT(*) AS cnt FROM `{$block_item}` bi LEFT JOIN `{$block}` b ON b.bl_id=bi.bl_id WHERE b.bl_id IS NULL"),
        'block_music_orphans' => hb_schema_integrity_count("SELECT COUNT(*) AS cnt FROM `{$block_item}` bi LEFT JOIN `{$music}` m ON m.mf_id=bi.mf_id WHERE m.mf_id IS NULL"),
        'schedule_day_orphans' => hb_schema_integrity_count("SELECT COUNT(*) AS cnt FROM `{$schedule_day}` sd LEFT JOIN `{$schedule}` s ON s.sc_id=sd.sc_id WHERE s.sc_id IS NULL"),
        'schedule_item_orphans' => hb_schema_integrity_count("SELECT COUNT(*) AS cnt FROM `{$schedule_item}` si LEFT JOIN `{$schedule}` s ON s.sc_id=si.sc_id WHERE s.sc_id IS NULL"),
        'schedule_music_orphans' => hb_schema_integrity_count("SELECT COUNT(*) AS cnt FROM `{$schedule_item}` si LEFT JOIN `{$music}` m ON m.mf_id=si.mf_id WHERE m.mf_id IS NULL"),
        'sequence_item_orphans' => hb_schema_integrity_count("SELECT COUNT(*) AS cnt FROM `{$sequence_item}` si LEFT JOIN `{$sequence}` s ON s.seq_id=si.seq_id WHERE s.seq_id IS NULL"),
        'sequence_music_orphans' => hb_schema_integrity_count("SELECT COUNT(*) AS cnt FROM `{$sequence_item}` si LEFT JOIN `{$music}` m ON m.mf_id=si.mf_id WHERE m.mf_id IS NULL")
    );
    return $checks;
}

function hb_schema_data_integrity_current() {
    foreach (hb_schema_data_integrity() as $count) if ($count === null || $count > 0) return false;
    return true;
}

function hb_normalize_schema_sentinels() {
    $ok = true;
    $music = hb_table('music');
    $broadcast_state = hb_table('broadcast_state');
    if (hb_table_exists($music) && !sql_query("UPDATE `{$music}` SET mf_youtube_id=NULL WHERE mf_source<>'youtube' OR mf_youtube_id=''", false)) $ok = false;
    if (hb_table_exists($broadcast_state) && !sql_query("UPDATE `{$broadcast_state}` SET mf_id=NULL WHERE mf_id=0", false)) $ok = false;
    return $ok;
}

function hb_index_exists($table, $index_name) {
    $table_sql = hb_escape((string)$table);
    $index_sql = hb_escape((string)$index_name);
    $row = sql_fetch("SELECT 1 AS hb_exists FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table_sql}' AND INDEX_NAME='{$index_sql}' LIMIT 1", false);
    return $row && !empty($row['hb_exists']);
}

function hb_index_info($table, $index_name) {
    $table_sql = hb_escape((string)$table);
    $index_sql = hb_escape((string)$index_name);
    $columns = array();
    $non_unique = null;
    $res = sql_query("SELECT COLUMN_NAME, NON_UNIQUE FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table_sql}' AND INDEX_NAME='{$index_sql}' ORDER BY SEQ_IN_INDEX ASC", false);
    if (!$res) return array('columns'=>array(), 'non_unique'=>null);
    while ($row = sql_fetch_array($res)) {
        if (isset($row['COLUMN_NAME'])) $columns[] = (string)$row['COLUMN_NAME'];
        if ($non_unique === null && isset($row['NON_UNIQUE'])) $non_unique = (int)$row['NON_UNIQUE'];
    }
    return array('columns'=>$columns, 'non_unique'=>$non_unique);
}

function hb_index_columns($table, $index_name) {
    $info = hb_index_info($table, $index_name);
    return isset($info['columns']) && is_array($info['columns']) ? $info['columns'] : array();
}

function hb_index_definition_columns($definition) {
    $out = array();
    if (preg_match_all('/`([A-Za-z0-9_]+)`/', (string)$definition, $m)) {
        foreach ($m[1] as $column) $out[] = (string)$column;
    }
    return $out;
}

function hb_index_matches($table, $index_name, $definition) {
    $expected = hb_index_definition_columns($definition);
    if (!$expected) return false;
    $info = hb_index_info($table, $index_name);
    if (!isset($info['columns']) || $info['columns'] !== $expected) return false;
    $expected_non_unique = ($index_name === 'PRIMARY' || $index_name === 'youtube_id_unique') ? 0 : 1;
    return isset($info['non_unique']) && $info['non_unique'] !== null && (int)$info['non_unique'] === $expected_non_unique;
}

function hb_column_has_nulls($table, $column) {
    $safe_column = preg_replace('/[^A-Za-z0-9_]/', '', (string)$column);
    if ($safe_column === '') return true;
    $row = sql_fetch("SELECT 1 AS hb_null FROM `{$table}` WHERE `{$safe_column}` IS NULL LIMIT 1", false);
    return $row && !empty($row['hb_null']);
}

function hb_column_safe_to_modify($table, $column, $definition) {
    $info = hb_column_schema_info($table, $column);
    if (!$info) return false;
    $actual = hb_normalize_column_type(isset($info['COLUMN_TYPE']) ? $info['COLUMN_TYPE'] : '');
    $expected = hb_expected_column_type($definition);
    $type_safe = ($actual === $expected);
    if (!$type_safe && preg_match('/^varchar\((\d+)\)$/', $actual, $a) && preg_match('/^varchar\((\d+)\)$/', $expected, $e)) {
        $type_safe = ((int)$e[1] >= (int)$a[1]);
    }
    if (!$type_safe) return false;
    $definition_upper = strtoupper(' '.trim(hb_scalar_string($definition, '')).' ');
    $expected_nullable = strpos($definition_upper, ' NOT NULL ') === false;
    $actual_nullable = isset($info['IS_NULLABLE']) && strtoupper((string)$info['IS_NULLABLE']) === 'YES';
    if (!$expected_nullable && $actual_nullable && hb_column_has_nulls($table, $column)) return false;
    return true;
}

function hb_column_migration_sql($table, $column, $definition) {
    $safe_column = preg_replace('/[^A-Za-z0-9_]/', '', (string)$column);
    if ($safe_column === '') return '';
    return "ALTER TABLE `{$table}` MODIFY `{$safe_column}` {$definition}";
}

function hb_ensure_column($table, $column, $definition) {
    if (!hb_column_exists($table, $column)) return sql_query("ALTER TABLE `{$table}` ADD `{$column}` {$definition}", false) ? true : false;
    if (hb_column_definition_matches($table, $column, $definition)) return true;
    if (!hb_column_safe_to_modify($table, $column, $definition)) return false;
    $sql = hb_column_migration_sql($table, $column, $definition);
    return $sql !== '' && sql_query($sql, false) ? true : false;
}

function hb_ensure_index($table, $index_name, $definition) {
    if ($index_name === 'youtube_id_unique' && $table === hb_table('music')) {
        $duplicate_count = hb_schema_integrity_count("SELECT COUNT(*) AS cnt FROM (SELECT mf_youtube_id FROM `{$table}` WHERE mf_youtube_id IS NOT NULL AND mf_youtube_id<>'' GROUP BY mf_youtube_id HAVING COUNT(*)>1) hb_duplicate_youtube");
        if ($duplicate_count === null || $duplicate_count > 0) return false;
    }
    $exists = hb_index_exists($table, $index_name);
    if ($exists && hb_index_matches($table, $index_name, $definition)) return true;
    if ($index_name === 'PRIMARY') {
        $alter = $exists
            ? "ALTER TABLE `{$table}` DROP PRIMARY KEY, ADD PRIMARY KEY {$definition}"
            : "ALTER TABLE `{$table}` ADD PRIMARY KEY {$definition}";
        return sql_query($alter, false) ? true : false;
    }
    $safe_name = preg_replace('/[^A-Za-z0-9_]/', '', (string)$index_name);
    if ($safe_name === '') return false;
    $key_sql = $index_name === 'youtube_id_unique' ? 'UNIQUE KEY' : 'KEY';
    $alter = $exists
        ? "ALTER TABLE `{$table}` DROP INDEX `{$safe_name}`, ADD {$key_sql} `{$safe_name}` {$definition}"
        : "ALTER TABLE `{$table}` ADD {$key_sql} `{$safe_name}` {$definition}";
    return sql_query($alter, false) ? true : false;
}

function hb_ensure_required_columns() {
    $ok = true;
    foreach (hb_schema_column_definitions() as $table_key => $columns) {
        $table = hb_table($table_key);
        if (!hb_table_exists($table)) { $ok = false; continue; }
        foreach ($columns as $column => $definition) {
            if (!hb_ensure_column($table, $column, $definition)) $ok = false;
        }
    }
    return $ok;
}

function hb_ensure_required_indexes() {
    $ok = true;
    foreach (hb_schema_index_definitions() as $table_key => $indexes) {
        $table = hb_table($table_key);
        if (!hb_table_exists($table)) { $ok = false; continue; }
        foreach ($indexes as $index_name => $definition) {
            if (!hb_ensure_index($table, $index_name, $definition)) $ok = false;
        }
    }
    return $ok;
}

function hb_schema_required_indexes_exist() {
    foreach (hb_schema_index_definitions() as $table_key => $indexes) {
        $table = hb_table($table_key);
        foreach ($indexes as $index_name => $definition) {
            if (!hb_index_matches($table, $index_name, $definition)) return false;
        }
    }
    return true;
}

function hb_schedule_is_range($row) {
    return isset($row['sc_play_mode']) && $row['sc_play_mode'] === 'range' && isset($row['sc_end_time']) && $row['sc_end_time'] !== null && $row['sc_end_time'] !== '';
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

function hb_parse_youtube_bulk($text, $max_items=null) {
    $text = trim(hb_scalar_string($text, ''));
    if ($text === '') return array();
    if (strlen($text) > HB_MAX_YOUTUBE_TEXT_BYTES) return array();
    if ($max_items === null) $max_items = HB_MAX_YOUTUBE_BULK_ITEMS + 1;
    $max_items = max(1, min(HB_MAX_YOUTUBE_BULK_ITEMS + 1, (int)$max_items));
    // 일반 문장이나 12자리 이상의 토큰 안에서 우연히 11글자 조각을 잘라
    // YouTube ID로 오인하지 않습니다. URL은 각 줄에서 추출하고,
    // 영상 ID 단독 입력은 해당 줄 전체가 정확히 11자일 때만 허용합니다.
    $text = str_replace(array("\r", "\t", ","), "\n", $text);
    $segments = preg_split('/\n+/u', $text);
    $out = array();
    $seen = array();
    foreach ((array)$segments as $segment) {
        $segment = trim(hb_scalar_string($segment, ''));
        if ($segment === '') continue;
        $candidates = array();
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $segment)) {
            $candidates[] = $segment;
        } else {
            preg_match_all('/https?:\/\/[^\s<>"\']+/u', $segment, $matches);
            $candidates = isset($matches[0]) && is_array($matches[0]) ? $matches[0] : array();
        }
        foreach ($candidates as $raw) {
            $raw = trim((string)$raw);
            if ($raw === '') continue;
            $id = hb_extract_youtube_id($raw);
            if (!$id || isset($seen[$id])) continue;
            $seen[$id] = true;
            $out[] = array('url' => $raw, 'id' => $id);
            if (count($out) >= $max_items) break 2;
        }
    }
    return $out;
}

function hb_create_youtube_musics_from_text($text, $title_prefix='', $volume=80, $type='music', $memo='', $links=null) {
    // 호출부가 개수 검증(HB_MAX_YOUTUBE_BULK_ITEMS 초과 여부)을 위해 이미
    // hb_parse_youtube_bulk()를 호출해 얻은 결과가 있다면 그 배열을 그대로 받아 재사용합니다.
    // 여기서 같은 텍스트를 다른 max_items로 다시 파싱하면, 검증 시점에 "허용 개수 초과"로
    // 판정한 목록과 실제 등록 시점에 실제로 만들어지는 목록이 서로 어긋날 수 있기 때문입니다.
    if ($links === null) $links = hb_parse_youtube_bulk($text, HB_MAX_YOUTUBE_BULK_ITEMS);
    if (!is_array($links) || !$links) return array();
    // YouTube 항목의 생성/소스변환 전체를 직렬화해, 기존 A 영상이 B/파일로 바뀌는 순간
    // 다른 요청이 아직 A라고 읽어 같은 mf_id를 잘못 재사용하는 경쟁 상태를 막습니다.
    if (!hb_acquire_youtube_registry_lock(5)) return array();
    // 여러 영상을 한 번에 처리하는 두 요청이 A→B / B→A 순서로 들어와도
    // advisory lock 획득 순서를 항상 동일하게 만들어 교차 대기/timeout을 피합니다.
    $lock_ids = array();
    foreach ($links as $link) if (!empty($link['id'])) $lock_ids[] = (string)$link['id'];
    if (!hb_acquire_youtube_locks($lock_ids, 5)) return array();
    $ids = array();
    $total = count($links);
    $idx = 0;
    foreach ($links as $link) {
        $idx++;
        $title = trim(hb_scalar_string($title_prefix, ''));
        if ($title === '') $title = 'YouTube BGM '.$link['id'];
        if ($total > 1 && $title_prefix !== '') $title = $title_prefix.' '.sprintf('%02d', $idx);
        $mf_id = hb_find_or_create_youtube_music($link['url'], $title, $volume, $type, $memo);
        if ($mf_id > 0) $ids[] = $mf_id;
    }
    return array_values(array_unique($ids));
}


function hb_days_all() {
    return array('0'=>'일', '1'=>'월', '2'=>'화', '3'=>'수', '4'=>'목', '5'=>'금', '6'=>'토');
}

function hb_clean_days($days) {
    if (!is_array($days)) $days = array();
    $allow = array_map('strval', array_keys(hb_days_all()));
    $out = array();
    foreach ($days as $d) {
        if (is_array($d) || is_object($d)) continue;
        $d = trim((string)$d);
        if (!preg_match('/^[0-6]$/', $d)) continue;
        if (in_array($d, $allow, true) && !in_array($d, $out, true)) $out[] = $d;
    }
    sort($out, SORT_NUMERIC);
    return implode(',', $out);
}

function hb_days_to_array($days_csv) {
    $out = array();
    foreach (explode(',', (string)$days_csv) as $day) {
        $day = trim($day);
        if (preg_match('/^[0-6]$/', $day) && !in_array((int)$day, $out, true)) $out[] = (int)$day;
    }
    sort($out, SORT_NUMERIC);
    return $out;
}

function hb_sync_schedule_days($sc_id, $days_csv) {
    $table = hb_table('schedule_day');
    $sc_id = (int)$sc_id;
    if ($sc_id < 1 || !hb_table_exists($table)) return false;
    if (!sql_query("DELETE FROM `{$table}` WHERE sc_id='{$sc_id}'", false)) return false;
    foreach (hb_days_to_array($days_csv) as $day) {
        if (!sql_query("INSERT INTO `{$table}` SET sc_id='{$sc_id}', sd_weekday='{$day}'", false)) return false;
    }
    return true;
}

function hb_sync_block_days($bl_id, $days_csv) {
    $table = hb_table('block_day');
    $bl_id = (int)$bl_id;
    if ($bl_id < 1 || !hb_table_exists($table)) return false;
    if (!sql_query("DELETE FROM `{$table}` WHERE bl_id='{$bl_id}'", false)) return false;
    foreach (hb_days_to_array($days_csv) as $day) {
        if (!sql_query("INSERT INTO `{$table}` SET bl_id='{$bl_id}', bd_weekday='{$day}'", false)) return false;
    }
    return true;
}

function hb_sync_legacy_day_rows() {
    $schedule = hb_table('schedule');
    $schedule_day = hb_table('schedule_day');
    $block = hb_table('block');
    $block_day = hb_table('block_day');
    if (!hb_table_exists($schedule) || !hb_table_exists($schedule_day) || !hb_table_exists($block) || !hb_table_exists($block_day)) return false;
    if (!sql_query("DELETE FROM `{$schedule_day}`", false) || !sql_query("DELETE FROM `{$block_day}`", false)) return false;
    $res = sql_query("SELECT sc_id, sc_days FROM `{$schedule}`", false);
    if (!$res) return false;
    while ($row = sql_fetch_array($res)) {
        $sc_id = (int)$row['sc_id'];
        foreach (hb_days_to_array(isset($row['sc_days']) ? $row['sc_days'] : '') as $day) {
            if (!sql_query("INSERT INTO `{$schedule_day}` SET sc_id='{$sc_id}', sd_weekday='{$day}'", false)) return false;
        }
    }
    $res = sql_query("SELECT bl_id, bl_days FROM `{$block}`", false);
    if (!$res) return false;
    while ($row = sql_fetch_array($res)) {
        $bl_id = (int)$row['bl_id'];
        foreach (hb_days_to_array(isset($row['bl_days']) ? $row['bl_days'] : '') as $day) {
            if (!sql_query("INSERT INTO `{$block_day}` SET bl_id='{$bl_id}', bd_weekday='{$day}'", false)) return false;
        }
    }
    return true;
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
    $file = basename(hb_scalar_string($file, ''));
    return preg_replace('/[^a-zA-Z0-9._-]/', '', $file);
}

function hb_unlink_music_file_row($row) {
    if (!is_array($row) || (isset($row['mf_source']) && $row['mf_source'] !== 'file')) return true;
    $file = hb_safe_file(isset($row['mf_file']) ? $row['mf_file'] : '');
    if ($file === '') return true;
    $path = HB_DATA_PATH.'/'.$file;
    if (!is_file($path)) return true;
    $size = @filesize($path);
    if (!@unlink($path)) return false;
    if ($size !== false && function_exists('hb_storage_usage_add')) hb_storage_usage_add(-((int)$size));
    return true;
}

function hb_orphan_music_files($limit=10) {
    $limit = max(1, min(100, (int)$limit));
    if (!defined('HB_DATA_PATH') || !is_dir(HB_DATA_PATH)) return array();
    $referenced = array();
    $music = hb_table('music');
    if (hb_table_exists($music)) {
        $res = sql_query("SELECT mf_file FROM `{$music}` WHERE mf_source='file' AND mf_file<>''", false);
        if ($res) while ($row = sql_fetch_array($res)) {
            $safe = hb_safe_file(isset($row['mf_file']) ? $row['mf_file'] : '');
            if ($safe !== '') $referenced[$safe] = true;
        }
    }
    $out = array();
    $files = @scandir(HB_DATA_PATH);
    if (!is_array($files)) return $out;
    foreach ($files as $file) {
        if (!preg_match('/^hb_[a-f0-9]{16,32}\.(?:mp3|wav|ogg|m4a)$/i', (string)$file)) continue;
        if (isset($referenced[$file])) continue;
        $out[] = $file;
        if (count($out) >= $limit) break;
    }
    return $out;
}


function hb_music_url($file, $mf_id=0) {
    $mf_id = max(0, (int)$mf_id);
    if ($mf_id > 0) return HB_URL.'/stream.php?id='.$mf_id;
    // 구형 관리자 코드 호환용 fallback입니다. 새 공개 payload는 파일명을 노출하지 않고 mf_id만 사용합니다.
    $file = hb_safe_file($file);
    if (!$file) return '';
    return HB_URL.'/stream.php?file='.rawurlencode($file);
}

function hb_extract_youtube_id($url) {
    $url = trim(hb_scalar_string($url, ''));
    if ($url === '') return '';
    if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url)) return $url;
    $parts = @parse_url($url);
    if (!$parts || empty($parts['host'])) return '';
    $host = strtolower(rtrim($parts['host'], '.'));
    $path = isset($parts['path']) ? trim($parts['path'], '/') : '';
    $is_youtu_be = ($host === 'youtu.be' || substr($host, -9) === '.youtu.be');
    $is_youtube = ($host === 'youtube.com' || substr($host, -12) === '.youtube.com');
    $is_nocookie = ($host === 'youtube-nocookie.com' || substr($host, -21) === '.youtube-nocookie.com');
    if ($is_youtu_be && preg_match('/^([a-zA-Z0-9_-]{11})(?:$|\/)/', $path, $m)) return $m[1];
    if ($is_youtube || $is_nocookie) {
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $q);
            if (isset($q['v']) && is_string($q['v']) && preg_match('/^[a-zA-Z0-9_-]{11}$/', $q['v'])) return $q['v'];
        }
        if (preg_match('#(?:^|/)(?:embed|shorts|live)/([a-zA-Z0-9_-]{11})(?:$|/)#', $path, $m)) return $m[1];
    }
    return '';
}

function hb_youtube_watch_url($youtube_id) {
    $youtube_id = trim(hb_scalar_string($youtube_id, ''));
    if (!preg_match('/^[a-zA-Z0-9_-]{11}$/', $youtube_id)) return '';
    return 'https://www.youtube.com/watch?v='.$youtube_id;
}


function hb_release_named_locks() {
    if (empty($GLOBALS['hb_bgm_named_locks']) || !is_array($GLOBALS['hb_bgm_named_locks'])) return;
    foreach (array_keys($GLOBALS['hb_bgm_named_locks']) as $lock_name) {
        $lock_sql = hb_escape((string)$lock_name);
        sql_fetch("SELECT RELEASE_LOCK('{$lock_sql}') AS hb_unlock", false);
    }
    $GLOBALS['hb_bgm_named_locks'] = array();
}

function hb_acquire_youtube_registry_lock($timeout=5) {
    $lock_name = 'haru_bgm_youtube_registry';
    if (!empty($GLOBALS['hb_bgm_named_locks'][$lock_name])) return true;
    $timeout = max(0, min(10, (int)$timeout));
    $lock_sql = hb_escape($lock_name);
    $row = sql_fetch("SELECT GET_LOCK('{$lock_sql}', {$timeout}) AS hb_lock", false);
    if (!$row || !isset($row['hb_lock']) || (int)$row['hb_lock'] !== 1) return false;
    if (empty($GLOBALS['hb_bgm_named_locks']) || !is_array($GLOBALS['hb_bgm_named_locks'])) {
        $GLOBALS['hb_bgm_named_locks'] = array();
        register_shutdown_function('hb_release_named_locks');
    }
    $GLOBALS['hb_bgm_named_locks'][$lock_name] = true;
    return true;
}

function hb_acquire_youtube_lock($youtube_id, $timeout=5) {
    $youtube_id = trim(hb_scalar_string($youtube_id, ''));
    if (!preg_match('/^[a-zA-Z0-9_-]{11}$/', $youtube_id)) return false;
    $lock_name = 'haru_bgm_yt_'.substr(hash('sha256', $youtube_id), 0, 32);
    if (!empty($GLOBALS['hb_bgm_named_locks'][$lock_name])) return true;
    $timeout = max(0, min(10, (int)$timeout));
    $lock_sql = hb_escape($lock_name);
    $row = sql_fetch("SELECT GET_LOCK('{$lock_sql}', {$timeout}) AS hb_lock", false);
    if (!$row || !isset($row['hb_lock']) || (int)$row['hb_lock'] !== 1) return false;
    if (empty($GLOBALS['hb_bgm_named_locks']) || !is_array($GLOBALS['hb_bgm_named_locks'])) {
        $GLOBALS['hb_bgm_named_locks'] = array();
        register_shutdown_function('hb_release_named_locks');
    }
    $GLOBALS['hb_bgm_named_locks'][$lock_name] = true;
    return true;
}


function hb_acquire_youtube_locks($youtube_ids, $timeout=5) {
    if (!is_array($youtube_ids)) return false;
    $ids = array();
    foreach ($youtube_ids as $youtube_id) {
        $youtube_id = trim(hb_scalar_string($youtube_id, ''));
        if (!preg_match('/^[a-zA-Z0-9_-]{11}$/', $youtube_id)) return false;
        $ids[$youtube_id] = true;
    }
    $ids = array_keys($ids);
    sort($ids, SORT_STRING);
    foreach ($ids as $youtube_id) {
        if (!hb_acquire_youtube_lock($youtube_id, $timeout)) return false;
    }
    return true;
}

function hb_find_or_create_youtube_music($url, $title='', $volume=80, $type='music', $memo='') {
    $yt_id = hb_extract_youtube_id($url);
    if (!$yt_id) return 0;
    if (!hb_acquire_youtube_registry_lock(5)) return 0;
    // 동일 영상의 동시 등록 요청도 SELECT→INSERT 사이를 통과하지 못하도록
    // 요청 종료(외부 트랜잭션 COMMIT 이후)까지 ID별 DB lock을 유지합니다.
    if (!hb_acquire_youtube_lock($yt_id, 5)) return 0;
    $music = hb_table('music');
    $yt_sql = hb_escape($yt_id);
    // 동일 YouTube 영상은 관리자 계정과 무관하게
    // 하나의 음악 항목을 재사용하여 중복 등록을 막습니다.
    $found = sql_fetch("SELECT mf_id, mf_use FROM `{$music}` WHERE mf_source='youtube' AND mf_youtube_id='{$yt_sql}' LIMIT 1", false);
    if ($found && isset($found['mf_id']) && (int)$found['mf_id'] > 0) {
        $found_id = (int)$found['mf_id'];
        // '바로 추가'를 다시 실행한 음악이 과거에 사용중지 상태였다면 실제 편성에서 빠지지 않도록 다시 활성화합니다.
        if (isset($found['mf_use']) && (int)$found['mf_use'] !== 1) {
            if (!sql_query("UPDATE `{$music}` SET mf_use='1', mf_updated_at=NOW() WHERE mf_id='{$found_id}'", false)) return 0;
        }
        return $found_id;
    }
    $url = hb_youtube_watch_url($yt_id);
    $title = hb_text_limit($title, 255);
    if ($title === '') $title = 'YouTube BGM '.$yt_id;
    $type = $type === 'bell' ? 'bell' : 'music';
    $volume = max(0, min(100, (int)$volume));
    $title_sql = hb_escape($title);
    $url_sql = hb_escape($url);
    $type_sql = hb_escape($type);
    $memo_sql = hb_escape($memo !== '' ? $memo : '공통 시간표에서 바로 등록');
    if (!sql_query("INSERT INTO `{$music}` SET mf_title='{$title_sql}', mf_source='youtube', mf_file='', mf_org_name='', mf_mime='', mf_size='0', mf_youtube_url='{$url_sql}', mf_youtube_id='{$yt_sql}', mf_volume='{$volume}', mf_type='{$type_sql}', mf_memo='{$memo_sql}', mf_use='1', mf_created_at=NOW()", false)) return 0;
    if (function_exists('sql_insert_id')) return (int)sql_insert_id();
    $last = sql_fetch("SELECT LAST_INSERT_ID() AS id", false);
    return $last && isset($last['id']) ? (int)$last['id'] : 0;
}

function hb_music_source_label($row) {
    $source = isset($row['mf_source']) ? $row['mf_source'] : 'file';
    return $source === 'youtube' ? 'YouTube' : '파일';
}

function hb_music_item_is_available($row) {
    if (!is_array($row)) return false;
    $source = isset($row['mf_source']) && $row['mf_source'] === 'youtube' ? 'youtube' : 'file';
    if ($source === 'youtube') {
        return preg_match('/^[a-zA-Z0-9_-]{11}$/', trim((string)(isset($row['mf_youtube_id']) ? $row['mf_youtube_id'] : ''))) === 1;
    }
    $safe_file = hb_safe_file(isset($row['mf_file']) ? $row['mf_file'] : '');
    if ($safe_file === '' || !defined('HB_DATA_PATH')) return false;
    $path = rtrim(HB_DATA_PATH, '/\\').'/'.$safe_file;
    return is_file($path) && is_readable($path);
}

function hb_music_item_payload($row) {
    $source = isset($row['mf_source']) && $row['mf_source'] === 'youtube' ? 'youtube' : 'file';
    $youtube_id = $source === 'youtube' && isset($row['mf_youtube_id']) ? trim((string)$row['mf_youtube_id']) : '';
    // 레거시 행에 잘못된 값이 남아 있어도 공개 API가 이를 플레이어로 전달하지 않습니다.
    if ($source === 'youtube' && !preg_match('/^[a-zA-Z0-9_-]{11}$/', $youtube_id)) $youtube_id = '';
    return array(
        'source' => $source,
        'url' => $source === 'file' ? hb_music_url($row['mf_file'], isset($row['mf_id']) ? (int)$row['mf_id'] : 0) : '',
        'youtube_id' => $youtube_id
    );
}

function hb_music_admin_label($row) {
    if ((isset($row['mf_source']) ? $row['mf_source'] : 'file') === 'youtube') {
        return 'YouTube · '.hb_e(isset($row['mf_youtube_id']) ? $row['mf_youtube_id'] : '');
    }
    return hb_e(isset($row['mf_org_name']) ? $row['mf_org_name'] : '');
}

function hb_json_flags() {
    $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
    if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
    return $flags;
}

function hb_json_encode($value) {
    $json = json_encode($value, hb_json_flags());
    return is_string($json) ? $json : 'null';
}

function hb_json_exit($arr) {
    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) @ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo hb_json_encode($arr);
    exit;
}

function hb_ip() {
    if (isset($_SERVER['REMOTE_ADDR'])) return substr($_SERVER['REMOTE_ADDR'], 0, 45);
    return '';
}


function hb_server_date_add($ymd, $days) {
    $ymd = hb_scalar_string($ymd, '');
    $ts = strtotime($ymd.' '.($days >= 0 ? '+' : '').(int)$days.' day');
    return $ts ? date('Y-m-d', $ts) : $ymd;
}

function hb_server_now_sql() {
    $now = defined('G5_TIME_YMDHIS') ? (string)G5_TIME_YMDHIS : date('Y-m-d H:i:s');
    return preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $now) ? $now : date('Y-m-d H:i:s');
}

function hb_cleanup_old_play_logs() {
    static $ran = false;
    if ($ran) return true;
    $ran = true;
    if (!hb_table_exists(hb_table('play_log')) || !hb_ensure_data_dir()) return false;
    $lock_path = rtrim(HB_DATA_PATH, '/\\').'/.play_log_cleanup.lock';
    $marker_path = rtrim(HB_DATA_PATH, '/\\').'/.play_log_cleanup.date';
    $fp = @fopen($lock_path, 'c+');
    if (!$fp || !@flock($fp, LOCK_EX | LOCK_NB)) {
        if (is_resource($fp)) @fclose($fp);
        return false;
    }
    $today = hb_site_clock_parts();
    $today = $today['today'];
    $marked = trim((string)@file_get_contents($marker_path));
    if ($marked === $today) {
        @flock($fp, LOCK_UN);
        @fclose($fp);
        return true;
    }
    $cutoff = hb_escape(hb_server_date_add($today, -HB_LOG_RETENTION_DAYS).' 00:00:00');
    $ok = sql_query("DELETE FROM `".hb_table('play_log')."` WHERE pl_played_at<'{$cutoff}'", false);
    if ($ok) @file_put_contents($marker_path, $today, LOCK_EX);
    @flock($fp, LOCK_UN);
    @fclose($fp);
    return $ok ? true : false;
}

function hb_service_date_allowed($row, $date, $days_key, $start_date_key, $end_date_key) {
    $date = trim(hb_scalar_string($date, ''));
    if ($date === '' || !hb_valid_date($date)) return false;
    $days_raw = isset($row[$days_key]) ? hb_scalar_string($row[$days_key], '') : '';
    $days = array_values(array_filter(explode(',', $days_raw), 'strlen'));
    $weekday = (string)(int)date('w', strtotime($date));
    if (!in_array($weekday, $days, true)) return false;
    $start_date = isset($row[$start_date_key]) ? trim(hb_scalar_string($row[$start_date_key], '')) : '';
    $end_date = isset($row[$end_date_key]) ? trim(hb_scalar_string($row[$end_date_key], '')) : '';
    if ($start_date !== '' && $start_date !== '0000-00-00' && $date < $start_date) return false;
    if ($end_date !== '' && $end_date !== '0000-00-00' && $date > $end_date) return false;
    return true;
}

function hb_schedule_service_date($row, $single_window_seconds=90) {
    $today = defined('G5_TIME_YMD') ? G5_TIME_YMD : date('Y-m-d');
    $now_his = defined('G5_TIME_HIS') ? G5_TIME_HIS : date('H:i:s');
    $now_sec = ((int)substr($now_his,0,2))*3600 + ((int)substr($now_his,3,2))*60 + (int)substr($now_his,6,2);
    $start_hm = hb_time_hm(isset($row['sc_time']) ? $row['sc_time'] : '00:00');
    $start_sec = ((int)substr($start_hm,0,2))*3600 + ((int)substr($start_hm,3,2))*60;
    $previous = hb_server_date_add($today, -1);
    if (hb_schedule_is_range($row)) {
        $end_hm = hb_time_hm($row['sc_end_time']);
        $end_sec = ((int)substr($end_hm,0,2))*3600 + ((int)substr($end_hm,3,2))*60;
        if ($end_sec < $start_sec && $now_sec < $end_sec
            && hb_service_date_allowed($row, $previous, 'sc_days', 'sc_start_date', 'sc_end_date')) return $previous;
        return $today;
    }
    $win = max(30, min(600, (int)$single_window_seconds));
    $cross_elapsed = $now_sec + 86400 - $start_sec;
    if ($start_sec > $now_sec && $cross_elapsed >= 0 && $cross_elapsed <= $win
        && hb_service_date_allowed($row, $previous, 'sc_days', 'sc_start_date', 'sc_end_date')) return $previous;
    return $today;
}

function hb_block_service_date_server($row) {
    $today = defined('G5_TIME_YMD') ? G5_TIME_YMD : date('Y-m-d');
    $now_his = defined('G5_TIME_HIS') ? G5_TIME_HIS : date('H:i:s');
    $now_sec = ((int)substr($now_his,0,2))*3600 + ((int)substr($now_his,3,2))*60 + (int)substr($now_his,6,2);
    $start_hm = hb_time_hm(isset($row['bl_start_time']) ? $row['bl_start_time'] : '00:00');
    $end_hm = hb_time_hm(isset($row['bl_end_time']) ? $row['bl_end_time'] : '00:00');
    $start_sec = ((int)substr($start_hm,0,2))*3600 + ((int)substr($start_hm,3,2))*60;
    $end_sec = ((int)substr($end_hm,0,2))*3600 + ((int)substr($end_hm,3,2))*60;
    $previous = hb_server_date_add($today, -1);
    if ($end_sec < $start_sec && $now_sec < $end_sec
        && hb_service_date_allowed($row, $previous, 'bl_days', 'bl_start_date', 'bl_end_date')) return $previous;
    return $today;
}

function hb_schedule_active_media_condition($schedule_alias='s') {
    $music = hb_table('music');
    $schedule_item = hb_table('schedule_item');
    $a = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$schedule_alias);
    if ($a === '') $a = 's';
    return "(m.mf_id IS NOT NULL OR EXISTS (SELECT 1 FROM `{$schedule_item}` hb_si INNER JOIN `{$music}` hb_mi ON hb_si.mf_id=hb_mi.mf_id WHERE hb_si.sc_id={$a}.sc_id AND hb_mi.mf_use=1))";
}

function hb_site_clock_parts() {
    $today = defined('G5_TIME_YMD') ? (string)G5_TIME_YMD : date('Y-m-d');
    $now_his = defined('G5_TIME_HIS') ? (string)G5_TIME_HIS : date('H:i:s');
    $stamp = strtotime($today.' '.$now_his);
    if ($stamp === false) $stamp = time();
    return array(
        'today' => date('Y-m-d', $stamp),
        'now_his' => date('H:i:s', $stamp),
        'now_sec' => ((int)date('H', $stamp) * 3600) + ((int)date('i', $stamp) * 60) + (int)date('s', $stamp),
        'w' => (int)date('w', $stamp),
        'prev_date' => date('Y-m-d', strtotime('-1 day', $stamp)),
        'prev_w' => (int)date('w', strtotime('-1 day', $stamp))
    );
}

function hb_schedule_day_sql($alias, $weekday) {
    $schedule_day = hb_table('schedule_day');
    $alias = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$alias);
    if ($alias === '') $alias = 's';
    return "EXISTS (SELECT 1 FROM `{$schedule_day}` hb_sd WHERE hb_sd.sc_id={$alias}.sc_id AND hb_sd.sd_weekday='".(int)$weekday."')";
}

function hb_block_day_sql($alias, $weekday) {
    $block_day = hb_table('block_day');
    $alias = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$alias);
    if ($alias === '') $alias = 'b';
    return "EXISTS (SELECT 1 FROM `{$block_day}` hb_bd WHERE hb_bd.bl_id={$alias}.bl_id AND hb_bd.bd_weekday='".(int)$weekday."')";
}

function hb_repair_schedule_primary_music($mf_id=0) {
    $schedule = hb_table('schedule');
    $schedule_item = hb_table('schedule_item');
    $music = hb_table('music');
    $where = "WHERE s.sc_scope='global'";
    if ($mf_id > 0) $where .= " AND s.mf_id='".(int)$mf_id."'";
    $res = sql_query("SELECT s.sc_id, s.mf_id FROM `{$schedule}` s LEFT JOIN `{$music}` m ON s.mf_id=m.mf_id AND m.mf_use=1 {$where} AND m.mf_id IS NULL", false);
    if (!$res) return false;
    while ($row = sql_fetch_array($res)) {
        $sc_id = (int)$row['sc_id'];
        $next = sql_fetch("SELECT si.mf_id FROM `{$schedule_item}` si INNER JOIN `{$music}` m2 ON si.mf_id=m2.mf_id WHERE si.sc_id='{$sc_id}' AND m2.mf_use=1 ORDER BY si.si_sort ASC, si.si_id ASC LIMIT 1", false);
        if ($next && (int)$next['mf_id'] > 0) {
            $next_id = (int)$next['mf_id'];
            if (!sql_query("UPDATE `{$schedule}` SET mf_id='{$next_id}', sc_updated_at=NOW() WHERE sc_id='{$sc_id}' AND sc_scope='global'", false)) return false;
        }
    }
    return true;
}


function hb_schedule_common_query($only_today=false, $limit=0) {
    $schedule = hb_table('schedule');
    $music = hb_table('music');
    $where = "s.sc_use = 1 AND s.sc_scope = 'global'";
    $join = "LEFT JOIN `{$music}` m ON s.mf_id = m.mf_id AND m.mf_use=1";
    $where .= ' AND '.hb_schedule_active_media_condition('s');
    if ($only_today) {
        $clock = hb_site_clock_parts();
        $today = $clock['today'];
        $w = $clock['w'];
        $prev_date = $clock['prev_date'];
        $prev_w = $clock['prev_w'];
        $now_his = $clock['now_his'];
        $now_sec = $clock['now_sec'];
        $single_window = max(30, min(600, (int)hb_get_setting('single_window_seconds', '90')));
        $today_cond = "(".hb_schedule_day_sql('s', $w)." AND (s.sc_start_date IS NULL OR s.sc_start_date = '0000-00-00' OR s.sc_start_date <= '{$today}') AND (s.sc_end_date IS NULL OR s.sc_end_date = '0000-00-00' OR s.sc_end_date >= '{$today}'))";
        $carry_cond = "(s.sc_play_mode='range' AND s.sc_end_time IS NOT NULL AND s.sc_end_time < s.sc_time AND '{$now_his}' < s.sc_end_time AND ".hb_schedule_day_sql('s', $prev_w)." AND (s.sc_start_date IS NULL OR s.sc_start_date = '0000-00-00' OR s.sc_start_date <= '{$prev_date}') AND (s.sc_end_date IS NULL OR s.sc_end_date = '0000-00-00' OR s.sc_end_date >= '{$prev_date}'))";
        $prev_single_cond = "(s.sc_play_mode='once' AND ({$now_sec} + 86400 - TIME_TO_SEC(s.sc_time)) BETWEEN 0 AND {$single_window} AND ".hb_schedule_day_sql('s', $prev_w)." AND (s.sc_start_date IS NULL OR s.sc_start_date='0000-00-00' OR s.sc_start_date <= '{$prev_date}') AND (s.sc_end_date IS NULL OR s.sc_end_date='0000-00-00' OR s.sc_end_date >= '{$prev_date}'))";
        $where .= " AND ({$today_cond} OR {$carry_cond} OR {$prev_single_cond})";
    }
    $limit_sql = (int)$limit > 0 ? ' LIMIT '.max(1, (int)$limit) : '';
    return "SELECT s.*, m.mf_title, m.mf_source, m.mf_file, m.mf_youtube_url, m.mf_youtube_id, m.mf_org_name, m.mf_mime, m.mf_size, m.mf_volume, m.mf_type, m.mf_memo, m.mf_use FROM `{$schedule}` s {$join} WHERE {$where} ORDER BY s.sc_time ASC, s.sc_sort ASC, s.sc_id ASC{$limit_sql}";
}

function hb_get_music_options($selected=0) {
    static $rows = null;
    $music = hb_table('music');
    $selected = (int)$selected;
    if ($rows === null) {
        $rows = array();
        $res = sql_query("SELECT mf_id, mf_title, mf_source FROM `{$music}` WHERE mf_use = 1 ORDER BY mf_id DESC", false);
        if ($res) while ($row = sql_fetch_array($res)) $rows[] = $row;
    }
    $html = '';
    foreach ($rows as $row) {
        $sel = ((int)$row['mf_id'] === $selected) ? ' selected' : '';
        $label = hb_music_source_label($row);
        $html .= '<option value="'.(int)$row['mf_id'].'"'.$sel.'>['.hb_e($label).'] '.hb_e($row['mf_title']).'</option>';
    }
    return $html;
}

function hb_schedule_items_map($sc_ids) {
    $ids = array_values(array_unique(array_filter(array_map('intval', (array)$sc_ids))));
    $out = array();
    foreach ($ids as $id) $out[$id] = array();
    if (!$ids) return $out;
    $schedule_item = hb_table('schedule_item');
    $music = hb_table('music');
    $in = implode(',', $ids);
    $res = sql_query("SELECT si.*, m.* FROM `{$schedule_item}` si INNER JOIN `{$music}` m ON si.mf_id=m.mf_id WHERE si.sc_id IN ({$in}) AND m.mf_use=1 ORDER BY si.sc_id ASC, si.si_sort ASC, si.si_id ASC", false);
    if (!$res) return false;
    while ($row = sql_fetch_array($res)) {
        $id = (int)$row['sc_id'];
        if (!isset($out[$id])) $out[$id] = array();
        if (count($out[$id]) < HB_MAX_SCHEDULE_ITEMS) $out[$id][] = $row;
    }
    return $out;
}

function hb_schedule_items($sc_id) {
    $schedule_item = hb_table('schedule_item');
    $music = hb_table('music');
    $sc_id = (int)$sc_id;
    $out = array();
    $limit = (int)HB_MAX_SCHEDULE_ITEMS;
    $res = sql_query("SELECT si.*, m.* FROM `{$schedule_item}` si INNER JOIN `{$music}` m ON si.mf_id=m.mf_id WHERE si.sc_id='{$sc_id}' AND m.mf_use=1 ORDER BY si.si_sort ASC, si.si_id ASC LIMIT {$limit}", false);
    if (!$res) return $out;
    while ($row = sql_fetch_array($res)) $out[] = $row;
    return $out;
}

function hb_schedule_item_ids($sc_id) {
    $items = hb_schedule_items($sc_id);
    $ids = array();
    foreach ($items as $item) $ids[] = (int)$item['mf_id'];
    return $ids;
}

function hb_schedule_effective_first_item($sc_id, $fallback_row=null) {
    $items = hb_schedule_items((int)$sc_id);
    foreach ($items as $item) if (hb_music_item_is_available($item)) return $item;
    if (is_array($fallback_row)
        && isset($fallback_row['mf_id']) && (int)$fallback_row['mf_id'] > 0
        && (!isset($fallback_row['mf_use']) || (int)$fallback_row['mf_use'] === 1)
        && hb_music_item_is_available($fallback_row)) {
        return $fallback_row;
    }
    return null;
}

function hb_log_target_is_valid($scope, $sc_id, $mf_id) {
    $scope = (string)$scope;
    $sc_id = max(0, (int)$sc_id);
    $mf_id = max(0, (int)$mf_id);
    if ($mf_id < 1) return false;
    if ($scope === 'broadcast') {
        if ($sc_id !== 0) return false;
        $state = hb_broadcast_state_row();
        return isset($state['bs_mode'], $state['mf_id']) && $state['bs_mode'] === 'manual' && (int)$state['mf_id'] === $mf_id;
    }
    if ($sc_id < 1) return false;

    $schedule = hb_table('schedule');
    $schedule_item = hb_table('schedule_item');
    $block = hb_table('block');
    $block_item = hb_table('block_item');
    $sequence = hb_table('sequence');
    $sequence_item = hb_table('sequence_item');

    if ($scope === 'global' || $scope === 'preview') {
        $row = sql_fetch("SELECT s.sc_id FROM `{$schedule}` s WHERE s.sc_id='{$sc_id}' AND s.sc_scope='global' AND s.sc_use='1' AND ((s.mf_id='{$mf_id}' AND NOT EXISTS (SELECT 1 FROM `{$schedule_item}` si0 WHERE si0.sc_id=s.sc_id)) OR EXISTS (SELECT 1 FROM `{$schedule_item}` si WHERE si.sc_id=s.sc_id AND si.mf_id='{$mf_id}')) LIMIT 1", false);
        return $row ? true : false;
    }
    if ($scope === 'global_block' || $scope === 'preview_block') {
        $row = sql_fetch("SELECT b.bl_id FROM `{$block}` b INNER JOIN `{$block_item}` bi ON bi.bl_id=b.bl_id AND bi.mf_id='{$mf_id}' WHERE b.bl_id='{$sc_id}' AND b.bl_scope='global' AND b.bl_use='1' LIMIT 1", false);
        if ($row) return true;
        // 특정 시간 동안 재생(range)은 JS에서 블록처럼 이어재생하지만 DB 실체는 schedule입니다.
        if ($scope === 'global_block') {
            $row = sql_fetch("SELECT s.sc_id FROM `{$schedule}` s WHERE s.sc_id='{$sc_id}' AND s.sc_scope='global' AND s.sc_use='1' AND s.sc_play_mode='range' AND ((s.mf_id='{$mf_id}' AND NOT EXISTS (SELECT 1 FROM `{$schedule_item}` si0 WHERE si0.sc_id=s.sc_id)) OR EXISTS (SELECT 1 FROM `{$schedule_item}` si WHERE si.sc_id=s.sc_id AND si.mf_id='{$mf_id}')) LIMIT 1", false);
            if ($row) return true;
        }
        return false;
    }
    if ($scope === 'sequence') {
        $row = sql_fetch("SELECT q.seq_id FROM `{$sequence}` q INNER JOIN `{$sequence_item}` qi ON qi.seq_id=q.seq_id AND qi.mf_id='{$mf_id}' WHERE q.seq_id='{$sc_id}' AND q.seq_use='1' LIMIT 1", false);
        return $row ? true : false;
    }
    return false;
}

function hb_db_begin() { return sql_query('START TRANSACTION', false) ? true : false; }
function hb_db_commit() { return sql_query('COMMIT', false) ? true : false; }
function hb_db_rollback() {
    $ok = sql_query('ROLLBACK', false) ? true : false;
    // 설정 저장 트랜잭션이 실패했을 때 DB는 원복됐는데 요청 메모리 캐시만 새 값으로 남아
    // 오류 응답의 종료 훅에서 실제 DB와 다른 방송 상태를 렌더링하지 않도록 폐기합니다.
    unset($GLOBALS['hb_bgm_settings_cache']);
    return $ok;
}

function hb_save_schedule_items($sc_id, $music_ids) {
    $schedule_item = hb_table('schedule_item');
    $sc_id = (int)$sc_id;
    $music_ids = hb_clean_music_ids($music_ids);
    if (count($music_ids) > HB_MAX_SCHEDULE_ITEMS) return false;
    if (!sql_query("DELETE FROM `{$schedule_item}` WHERE sc_id='{$sc_id}'", false)) return false;
    $sort = 0;
    foreach ($music_ids as $mf_id) {
        $sort += 10;
        if (!sql_query("INSERT INTO `{$schedule_item}` SET sc_id='{$sc_id}', mf_id='{$mf_id}', si_sort='{$sort}', si_created_at=NOW()", false)) return false;
    }
    return true;
}

function hb_schedule_item_count($sc_id) {
    $schedule_item = hb_table('schedule_item');
    $music = hb_table('music');
    $sc_id = (int)$sc_id;
    $row = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$schedule_item}` si INNER JOIN `{$music}` m ON si.mf_id=m.mf_id WHERE si.sc_id='{$sc_id}' AND m.mf_use=1", false);
    return $row && isset($row['cnt']) ? (int)$row['cnt'] : 0;
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

function hb_get_track_music_select_rows($selected_ids=array(), $max_rows=20, $initial_rows=4) {
    $selected_ids = is_array($selected_ids) ? array_values($selected_ids) : array();
    $max_rows = max(1, min(HB_MAX_BLOCK_ITEMS, (int)$max_rows));
    $initial_rows = max(1, min($max_rows, (int)$initial_rows));
    $selected_count = count($selected_ids);
    $visible_rows = max($initial_rows, $selected_count + ($selected_count < $max_rows ? 1 : 0));
    $visible_rows = min($max_rows, $visible_rows);
    $html = '';
    for ($i=0; $i<$visible_rows; $i++) {
        $selected = isset($selected_ids[$i]) ? (int)$selected_ids[$i] : 0;
        $html .= '<div class="hb-track-row"><span class="hb-track-no">'.($i+1).'</span><select name="mf_ids[]"><option value="">선택 안 함</option>'.hb_get_music_options($selected).'</select><span class="hb-track-tools"><button type="button" class="hb-track-up" title="위로">↑</button><button type="button" class="hb-track-down" title="아래로">↓</button><button type="button" class="hb-track-clear" title="비우기">×</button></span></div>';
    }
    $disabled = $visible_rows >= $max_rows ? ' disabled aria-disabled="true"' : '';
    $html .= '<div class="hb-track-add-wrap"><button type="button" class="hb-btn hb-btn-small hb-track-add" data-hb-track-max="'.$max_rows.'"'.$disabled.'>+ 음악 추가</button><span class="hb-track-limit"><b class="hb-track-count">'.$visible_rows.'</b> / '.$max_rows.'개</span></div>';
    return $html;
}

function hb_get_schedule_music_select_rows($selected_ids=array(), $rows=19) {
    $rows = min(HB_MAX_SCHEDULE_ITEMS - 1, max(1, (int)$rows));
    return hb_get_track_music_select_rows($selected_ids, $rows, 4);
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
    return hb_e(hb_json_encode($payload));
}

function hb_schedule_preview_items_attr($sc_id, $fallback_row=null) {
    $sc_id = (int)$sc_id;
    $payload = hb_media_items_payload(hb_schedule_items($sc_id), $fallback_row);
    foreach ($payload as &$item) {
        $item['id'] = $sc_id;
        $item['scope'] = 'preview';
    }
    unset($item);
    return hb_e(hb_json_encode($payload));
}

function hb_block_preview_items_attr($bl_id) {
    $bl_id = (int)$bl_id;
    $payload = hb_media_items_payload(hb_block_items($bl_id));
    foreach ($payload as &$item) {
        $item['id'] = $bl_id;
        $item['scope'] = 'preview';
        $item['preview_block'] = 1;
    }
    unset($item);
    return hb_e(hb_json_encode($payload));
}

function hb_valid_hm($time) {
    return is_string($time) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time);
}

function hb_valid_date($date) {
    $date = trim(hb_scalar_string($date, ''));
    if ($date === '') return true;
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m)) return false;
    return checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
}

function hb_validate_date_range($start_date, $end_date) {
    $start_date = trim(hb_scalar_string($start_date, ''));
    $end_date = trim(hb_scalar_string($end_date, ''));
    if (!hb_valid_date($start_date) || !hb_valid_date($end_date)) return '날짜 형식이 올바르지 않습니다.';
    if ($start_date !== '' && $end_date !== '' && $start_date > $end_date) return '시작 날짜는 종료 날짜보다 늦을 수 없습니다.';
    return '';
}

function hb_hm_to_sql($time) {
    return hb_escape(substr($time, 0, 5).':00');
}

function hb_scope_label($scope) {
    return $scope === 'global' ? '공통' : '알 수 없음';
}

function hb_play_mode_label($mode) {
    return $mode === 'random' ? '랜덤' : '순서대로';
}


function hb_block_common_query($only_today=false, $limit=0) {
    $block = hb_table('block');
    $block_item = hb_table('block_item');
    $music = hb_table('music');
    // 실제 재생 가능한 음악이 하나도 없는 묶음은 관리자 오늘표/공개 API 모두에서 제외합니다.
    // 음악 삭제·사용중지 후 빈 묶음이 '활성 편성'처럼 보이는 회귀를 막습니다.
    $where = "b.bl_use = 1 AND b.bl_scope = 'global' AND EXISTS (SELECT 1 FROM `{$block_item}` hb_bi INNER JOIN `{$music}` hb_bm ON hb_bi.mf_id=hb_bm.mf_id WHERE hb_bi.bl_id=b.bl_id AND hb_bm.mf_use=1)";
    if ($only_today) {
        $clock = hb_site_clock_parts();
        $today = $clock['today'];
        $w = $clock['w'];
        $prev_date = $clock['prev_date'];
        $prev_w = $clock['prev_w'];
        $now_his = $clock['now_his'];
        $today_cond = "(".hb_block_day_sql('b', $w)." AND (b.bl_start_date IS NULL OR b.bl_start_date = '0000-00-00' OR b.bl_start_date <= '{$today}') AND (b.bl_end_date IS NULL OR b.bl_end_date = '0000-00-00' OR b.bl_end_date >= '{$today}'))";
        $carry_cond = "(b.bl_end_time < b.bl_start_time AND '{$now_his}' < b.bl_end_time AND ".hb_block_day_sql('b', $prev_w)." AND (b.bl_start_date IS NULL OR b.bl_start_date = '0000-00-00' OR b.bl_start_date <= '{$prev_date}') AND (b.bl_end_date IS NULL OR b.bl_end_date = '0000-00-00' OR b.bl_end_date >= '{$prev_date}'))";
        $where .= " AND ({$today_cond} OR {$carry_cond})";
    }
    $limit_sql = (int)$limit > 0 ? ' LIMIT '.max(1, (int)$limit) : '';
    return "SELECT b.* FROM `{$block}` b WHERE {$where} ORDER BY b.bl_start_time ASC, b.bl_sort ASC, b.bl_id ASC{$limit_sql}";
}


function hb_block_items_map($bl_ids) {
    $ids = array_values(array_unique(array_filter(array_map('intval', (array)$bl_ids))));
    $out = array();
    foreach ($ids as $id) $out[$id] = array();
    if (!$ids) return $out;
    $block_item = hb_table('block_item');
    $music = hb_table('music');
    $in = implode(',', $ids);
    $res = sql_query("SELECT bi.*, m.* FROM `{$block_item}` bi INNER JOIN `{$music}` m ON bi.mf_id=m.mf_id WHERE bi.bl_id IN ({$in}) AND m.mf_use=1 ORDER BY bi.bl_id ASC, bi.bi_sort ASC, bi.bi_id ASC", false);
    if (!$res) return false;
    while ($row = sql_fetch_array($res)) {
        $id = (int)$row['bl_id'];
        if (!isset($out[$id])) $out[$id] = array();
        if (count($out[$id]) < HB_MAX_BLOCK_ITEMS) $out[$id][] = $row;
    }
    return $out;
}

function hb_runtime_schedule_snapshot() {
    $settings = hb_get_settings();
    $priority_mode = isset($settings['priority_mode']) ? $settings['priority_mode'] : 'single_first';
    $single_window = max(30, min(600, (int)$settings['single_window_seconds']));

    $schedule_rows = array();
    $res = sql_query(hb_schedule_common_query(true, HB_MAX_API_SCHEDULE_PARENTS + 1), false);
    if (!$res) return array('ok'=>false, 'message'=>'schedule_query_failed');
    while ($row = sql_fetch_array($res)) $schedule_rows[] = $row;
    if (count($schedule_rows) > HB_MAX_API_SCHEDULE_PARENTS) return array('ok'=>false, 'message'=>'schedule_limit_exceeded');

    $block_rows = array();
    $bres = sql_query(hb_block_common_query(true, HB_MAX_API_BLOCK_PARENTS + 1), false);
    if (!$bres) return array('ok'=>false, 'message'=>'block_query_failed');
    while ($row = sql_fetch_array($bres)) $block_rows[] = $row;
    if (count($block_rows) > HB_MAX_API_BLOCK_PARENTS) return array('ok'=>false, 'message'=>'block_limit_exceeded');

    $schedule_ids = array();
    foreach ($schedule_rows as $row) $schedule_ids[] = (int)$row['sc_id'];
    $schedule_items = hb_schedule_items_map($schedule_ids);
    if ($schedule_items === false) return array('ok'=>false, 'message'=>'schedule_items_query_failed');

    $block_ids = array();
    foreach ($block_rows as $row) $block_ids[] = (int)$row['bl_id'];
    $block_items = hb_block_items_map($block_ids);
    if ($block_items === false) return array('ok'=>false, 'message'=>'block_items_query_failed');

    $list = array();
    $blocks = array();
    foreach ($schedule_rows as $row) {
        $sid = (int)$row['sc_id'];
        $children = isset($schedule_items[$sid]) ? $schedule_items[$sid] : array();
        $items_payload = array();
        foreach ($children as $it) {
            if (!hb_music_item_is_available($it)) continue;
            $payload = hb_music_item_payload($it);
            $items_payload[] = array(
                'block_item_id' => isset($it['si_id']) ? (int)$it['si_id'] : 0,
                'music_id' => (int)$it['mf_id'],
                'music_title' => $it['mf_title'],
                'volume' => (int)$it['mf_volume'],
                'source' => $payload['source'],
                'url' => $payload['url'],
                'youtube_id' => $payload['youtube_id']
            );
        }
        if (!$items_payload && !empty($row['mf_id']) && !empty($row['mf_use']) && hb_music_item_is_available($row)) {
            $payload = hb_music_item_payload($row);
            $items_payload[] = array(
                'block_item_id' => 0,
                'music_id' => (int)$row['mf_id'],
                'music_title' => $row['mf_title'],
                'volume' => (int)$row['mf_volume'],
                'source' => $payload['source'],
                'url' => $payload['url'],
                'youtube_id' => $payload['youtube_id']
            );
        }
        if (!$items_payload) continue;

        if (hb_schedule_is_range($row)) {
            $blocks[] = array(
                'kind' => 'range', 'id' => 'range_'.$sid, 'log_id' => $sid,
                'scope' => $row['sc_scope'],
                'priority' => hb_priority_score('block', $row['sc_scope'], $priority_mode),
                'sort' => (int)$row['sc_sort'], 'title' => $row['sc_title'],
                'start' => hb_time_hm($row['sc_time']), 'end' => hb_time_hm($row['sc_end_time']),
                'days' => $row['sc_days'], 'days_label' => hb_days_label($row['sc_days']),
                'start_date' => $row['sc_start_date'], 'end_date' => $row['sc_end_date'],
                'service_date' => hb_schedule_service_date($row, $single_window),
                'mode' => 'sequence', 'repeat' => (int)$row['sc_repeat'], 'items' => $items_payload
            );
            continue;
        }

        $first = $items_payload[0];
        $list[] = array(
            'kind' => count($items_payload) > 1 ? 'single_set' : 'single',
            'id' => $sid, 'music_id' => (int)$first['music_id'], 'scope' => $row['sc_scope'],
            'priority' => hb_priority_score('single', $row['sc_scope'], $priority_mode),
            'sort' => (int)$row['sc_sort'], 'title' => $row['sc_title'],
            'music_title' => $first['music_title'], 'time' => hb_time_hm($row['sc_time']),
            'days' => $row['sc_days'], 'days_label' => hb_days_label($row['sc_days']),
            'start_date' => $row['sc_start_date'], 'end_date' => $row['sc_end_date'],
            'service_date' => hb_schedule_service_date($row, $single_window),
            'volume' => (int)$first['volume'], 'source' => $first['source'],
            'url' => $first['url'], 'youtube_id' => $first['youtube_id'],
            'items' => $items_payload, 'set_count' => count($items_payload)
        );
    }

    foreach ($block_rows as $row) {
        $bid = (int)$row['bl_id'];
        $children = isset($block_items[$bid]) ? $block_items[$bid] : array();
        $items = array();
        foreach ($children as $it) {
            if (!hb_music_item_is_available($it)) continue;
            $payload = hb_music_item_payload($it);
            $items[] = array(
                'block_item_id' => (int)$it['bi_id'], 'music_id' => (int)$it['mf_id'],
                'music_title' => $it['mf_title'], 'volume' => (int)$it['mf_volume'],
                'source' => $payload['source'], 'url' => $payload['url'], 'youtube_id' => $payload['youtube_id']
            );
        }
        if (!$items) continue;
        $blocks[] = array(
            'kind' => 'block', 'id' => $bid, 'log_id' => $bid, 'scope' => $row['bl_scope'],
            'priority' => hb_priority_score('block', $row['bl_scope'], $priority_mode),
            'sort' => (int)$row['bl_sort'], 'title' => $row['bl_title'],
            'start' => hb_time_hm($row['bl_start_time']), 'end' => hb_time_hm($row['bl_end_time']),
            'days' => $row['bl_days'], 'days_label' => hb_days_label($row['bl_days']),
            'start_date' => $row['bl_start_date'], 'end_date' => $row['bl_end_date'],
            'service_date' => hb_block_service_date_server($row), 'mode' => $row['bl_play_mode'],
            'repeat' => (int)$row['bl_repeat'], 'items' => $items
        );
    }

    return array(
        'ok'=>true,
        'settings'=>array(
            'priority_mode'=>$priority_mode,
            'priority_label'=>hb_setting_label_priority($priority_mode),
            'single_window_seconds'=>$single_window,
            'fadeout_seconds'=>max(0, min(20, (int)$settings['fadeout_seconds'])),
            'block_end_action'=>in_array($settings['block_end_action'], array('fade_stop','finish_current'), true) ? $settings['block_end_action'] : 'fade_stop',
            'auto_refresh_seconds'=>max(15, min(300, (int)$settings['auto_refresh_seconds'])),
            'show_debug_badge'=>(int)$settings['show_debug_badge']
        ),
        'items'=>$list,
        'blocks'=>$blocks
    );
}

function hb_json_bounded_output($payload, $max_bytes=HB_MAX_API_PAYLOAD_BYTES) {
    $json = hb_json_encode($payload);
    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) @ob_end_clean();
    }
    if (!is_string($json) || strlen($json) > max(1024, (int)$max_bytes)) {
        http_response_code(503);
        header('Retry-After: 10');
        echo hb_json_encode(array('ok'=>false, 'message'=>'payload_limit_exceeded'));
        return false;
    }
    echo $json;
    return true;
}

function hb_block_items($bl_id) {
    $block_item = hb_table('block_item');
    $music = hb_table('music');
    $bl_id = (int)$bl_id;
    $out = array();
    $limit = (int)HB_MAX_BLOCK_ITEMS;
    $res = sql_query("SELECT bi.*, m.* FROM `{$block_item}` bi INNER JOIN `{$music}` m ON bi.mf_id=m.mf_id WHERE bi.bl_id='{$bl_id}' AND m.mf_use=1 ORDER BY bi.bi_sort ASC, bi.bi_id ASC LIMIT {$limit}", false);
    if (!$res) return $out;
    while ($row = sql_fetch_array($res)) $out[] = $row;
    return $out;
}

function hb_block_item_count($bl_id) {
    $block_item = hb_table('block_item');
    $music = hb_table('music');
    $bl_id = (int)$bl_id;
    $row = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$block_item}` bi INNER JOIN `{$music}` m ON bi.mf_id=m.mf_id WHERE bi.bl_id='{$bl_id}' AND m.mf_use=1", false);
    return $row && isset($row['cnt']) ? (int)$row['cnt'] : 0;
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
        $id = hb_int_value($id, 0);
        if ($id > 0) $out[] = $id;
    }
    return $out;
}

function hb_request_body_too_large($max_bytes=HB_MAX_ADMIN_POST_BYTES) {
    $length = isset($_SERVER['CONTENT_LENGTH']) ? hb_int_value($_SERVER['CONTENT_LENGTH'], 0) : 0;
    return $length > max(1024, (int)$max_bytes);
}

function hb_youtube_text_too_large($text) {
    return strlen(hb_scalar_string($text, '')) > HB_MAX_YOUTUBE_TEXT_BYTES;
}

function hb_filter_active_music_ids($ids, $for_update=false) {
    $ids = array_values(array_unique(hb_clean_music_ids($ids)));
    if (!$ids) return array();
    // 여러 음악을 잠글 때 요청마다 입력 순서가 달라도 잠금 순서는 동일하게 유지해
    // 교차 편집 시 불필요한 deadlock 가능성을 낮춥니다.
    $query_ids = $ids;
    sort($query_ids, SORT_NUMERIC);
    $music = hb_table('music');
    $in = implode(',', array_map('intval', $query_ids));
    $allowed = array();
    $lock_sql = $for_update ? ' FOR UPDATE' : '';
    $res = sql_query("SELECT mf_id FROM `{$music}` WHERE mf_id IN ({$in}) AND mf_use=1 ORDER BY mf_id ASC{$lock_sql}", false);
    if (!$res) return array();
    while ($row = sql_fetch_array($res)) $allowed[(int)$row['mf_id']] = true;
    // 실제 재생 순서는 관리자가 제출한 순서를 그대로 보존합니다.
    $out = array();
    foreach ($ids as $id) if (isset($allowed[$id])) $out[] = $id;
    return $out;
}


function hb_save_block_items($bl_id, $music_ids) {
    $block_item = hb_table('block_item');
    $bl_id = (int)$bl_id;
    $music_ids = hb_clean_music_ids($music_ids);
    if (count($music_ids) > HB_MAX_BLOCK_ITEMS) return false;
    if (!sql_query("DELETE FROM `{$block_item}` WHERE bl_id='{$bl_id}'", false)) return false;
    $sort = 0;
    foreach ($music_ids as $mf_id) {
        $sort += 10;
        if (!sql_query("INSERT INTO `{$block_item}` SET bl_id='{$bl_id}', mf_id='{$mf_id}', bi_sort='{$sort}', bi_created_at=NOW()", false)) return false;
    }
    return true;
}

function hb_get_block_music_select_rows($selected_ids=array(), $rows=HB_MAX_BLOCK_ITEMS) {
    $rows = min(HB_MAX_BLOCK_ITEMS, max(1, (int)$rows));
    return hb_get_track_music_select_rows($selected_ids, $rows, 4);
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
    $limit = (int)HB_MAX_SEQUENCE_ITEMS;
    $res = sql_query("SELECT siq.*, m.* FROM `{$sequence_item}` siq INNER JOIN `{$music}` m ON siq.mf_id=m.mf_id WHERE siq.seq_id='{$seq_id}' AND m.mf_use=1 ORDER BY siq.siq_sort ASC, siq.siq_id ASC LIMIT {$limit}", false);
    if (!$res) return $out;
    while ($row = sql_fetch_array($res)) $out[] = $row;
    return $out;
}

function hb_sequence_item_count($seq_id) {
    $sequence_item = hb_table('sequence_item');
    $music = hb_table('music');
    $seq_id = (int)$seq_id;
    $row = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$sequence_item}` siq INNER JOIN `{$music}` m ON siq.mf_id=m.mf_id WHERE siq.seq_id='{$seq_id}' AND m.mf_use=1", false);
    return $row && isset($row['cnt']) ? (int)$row['cnt'] : 0;
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
    $music_ids = hb_clean_music_ids($music_ids);
    if (count($music_ids) > HB_MAX_SEQUENCE_ITEMS) return false;
    if (!sql_query("DELETE FROM `{$sequence_item}` WHERE seq_id='{$seq_id}'", false)) return false;
    $sort = 0;
    foreach ($music_ids as $idx => $mf_id) {
        $sort += 10;
        $title = isset($titles[$idx]) ? hb_escape(hb_text_limit($titles[$idx], 255)) : '';
        $memo = isset($memos[$idx]) ? hb_escape(hb_text_limit($memos[$idx], 255)) : '';
        if (!sql_query("INSERT INTO `{$sequence_item}` SET seq_id='{$seq_id}', mf_id='{$mf_id}', siq_title='{$title}', siq_memo='{$memo}', siq_sort='{$sort}', siq_created_at=NOW()", false)) return false;
    }
    return true;
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
    return hb_e(hb_json_encode($out));
}

function hb_get_sequence_music_select_rows($selected_ids=array(), $step_titles=array(), $step_memos=array(), $max_rows=HB_MAX_SEQUENCE_ITEMS, $initial_rows=4) {
    $selected_ids = is_array($selected_ids) ? array_values($selected_ids) : array();
    $step_titles = is_array($step_titles) ? array_values($step_titles) : array();
    $step_memos = is_array($step_memos) ? array_values($step_memos) : array();
    $max_rows = max(1, min(HB_MAX_SEQUENCE_ITEMS, (int)$max_rows));
    $initial_rows = max(1, min($max_rows, (int)$initial_rows));
    $selected_count = count($selected_ids);
    $visible_rows = max($initial_rows, $selected_count + ($selected_count < $max_rows ? 1 : 0));
    $visible_rows = min($max_rows, $visible_rows);
    $html = '';
    for ($i=0; $i<$visible_rows; $i++) {
        $selected = isset($selected_ids[$i]) ? (int)$selected_ids[$i] : 0;
        $title = isset($step_titles[$i]) ? hb_e($step_titles[$i]) : '';
        $memo = isset($step_memos[$i]) ? hb_e($step_memos[$i]) : '';
        $html .= '<div class="hb-track-row hb-seq-row"><span class="hb-track-no">'.($i+1).'</span><select name="mf_ids[]"><option value="">선택 안 함</option>'.hb_get_music_options($selected).'</select><input type="text" name="step_titles[]" maxlength="255" placeholder="순서명 예: 입례송 / 찬양 1" value="'.$title.'"><input type="text" name="step_memos[]" maxlength="255" placeholder="메모" value="'.$memo.'"><span class="hb-track-tools"><button type="button" class="hb-track-up" title="위로" aria-label="위로 이동">↑</button><button type="button" class="hb-track-down" title="아래로" aria-label="아래로 이동">↓</button><button type="button" class="hb-track-clear" title="비우기" aria-label="이 행 비우기">×</button></span></div>';
    }
    $disabled = $visible_rows >= $max_rows ? ' disabled aria-disabled="true"' : '';
    $html .= '<div class="hb-track-add-wrap"><button type="button" class="hb-btn hb-btn-small hb-track-add" data-hb-track-max="'.$max_rows.'"'.$disabled.'>+ 순서 추가</button><span class="hb-track-limit"><b class="hb-track-count">'.$visible_rows.'</b> / '.$max_rows.'개</span></div>';
    return $html;
}

function hb_today_operation_entries() {
    $today = array();
    $single_window = max(30, min(600, (int)hb_get_setting('single_window_seconds', '90')));
    $res = sql_query(hb_schedule_common_query(true, HB_MAX_API_SCHEDULE_PARENTS), false);
    while ($res && ($row = sql_fetch_array($res))) {
        $today[] = array(
            'kind'=>hb_schedule_is_range($row) ? 'range' : 'single',
            'start'=>hb_time_hm($row['sc_time']),
            'end'=>hb_schedule_is_range($row) ? hb_time_hm($row['sc_end_time']) : '',
            'service_date'=>hb_schedule_service_date($row, $single_window),
            'sort'=>(int)$row['sc_sort'],
            'id'=>(int)$row['sc_id'],
            'row'=>$row
        );
    }
    $bres = sql_query(hb_block_common_query(true, HB_MAX_API_BLOCK_PARENTS), false);
    while ($bres && ($row = sql_fetch_array($bres))) {
        if (hb_block_item_count($row['bl_id']) < 1) continue;
        $today[] = array(
            'kind'=>'block',
            'start'=>hb_time_hm($row['bl_start_time']),
            'end'=>hb_time_hm($row['bl_end_time']),
            'service_date'=>hb_block_service_date_server($row),
            'sort'=>(int)$row['bl_sort'],
            'id'=>(int)$row['bl_id'],
            'row'=>$row
        );
    }
    usort($today, function($a, $b) {
        if ($a['service_date'] !== $b['service_date']) return strcmp($a['service_date'], $b['service_date']);
        if ($a['start'] !== $b['start']) return strcmp($a['start'], $b['start']);
        if ($a['sort'] !== $b['sort']) return $a['sort'] <=> $b['sort'];
        if ($a['kind'] !== $b['kind']) return strcmp($a['kind'], $b['kind']);
        return $a['id'] <=> $b['id'];
    });
    return $today;
}

function hb_music_usage_map($mf_ids) {
    $ids = array_values(array_unique(array_filter(array_map('intval', (array)$mf_ids))));
    $out = array();
    foreach ($ids as $id) $out[$id] = array('schedule'=>0, 'block'=>0, 'sequence'=>0, 'broadcast'=>0, 'global'=>0);
    if (!$ids) return $out;
    $in = implode(',', $ids);
    $schedule = hb_table('schedule');
    $schedule_item = hb_table('schedule_item');
    $block = hb_table('block');
    $block_item = hb_table('block_item');
    $sequence_item = hb_table('sequence_item');

    // v1.5.5부터 회원 편성 기능은 제거되었습니다. 기존 DB에 남아 있을 수 있는
    // 구형 user 범위 행은 관리자 사용처 통계에 섞지 않고 global 편성만 집계합니다.
    $sql = "SELECT x.mf_id, COUNT(DISTINCT x.sc_id) AS cnt FROM ("
         ."SELECT mf_id, sc_id FROM `{$schedule}` WHERE sc_scope='global' AND mf_id IN ({$in}) UNION "
         ."SELECT si.mf_id, s.sc_id FROM `{$schedule_item}` si INNER JOIN `{$schedule}` s ON s.sc_id=si.sc_id WHERE s.sc_scope='global' AND si.mf_id IN ({$in})"
         .") x GROUP BY x.mf_id";
    $res = sql_query($sql, false);
    if ($res) while ($row = sql_fetch_array($res)) {
        $id = (int)$row['mf_id']; if (!isset($out[$id])) continue;
        $out[$id]['schedule'] = (int)$row['cnt'];
        $out[$id]['global'] += (int)$row['cnt'];
    }

    $res = sql_query("SELECT bi.mf_id, COUNT(DISTINCT b.bl_id) AS cnt FROM `{$block_item}` bi INNER JOIN `{$block}` b ON b.bl_id=bi.bl_id WHERE b.bl_scope='global' AND bi.mf_id IN ({$in}) GROUP BY bi.mf_id", false);
    if ($res) while ($row = sql_fetch_array($res)) {
        $id = (int)$row['mf_id']; if (!isset($out[$id])) continue;
        $out[$id]['block'] = (int)$row['cnt'];
        $out[$id]['global'] += (int)$row['cnt'];
    }

    $res = sql_query("SELECT mf_id, COUNT(DISTINCT seq_id) AS cnt FROM `{$sequence_item}` WHERE mf_id IN ({$in}) GROUP BY mf_id", false);
    if ($res) while ($row = sql_fetch_array($res)) {
        $id = (int)$row['mf_id']; if (!isset($out[$id])) continue;
        $out[$id]['sequence'] = (int)$row['cnt'];
        $out[$id]['global'] += (int)$row['cnt'];
    }

    $state = hb_sitewide_enabled() ? hb_broadcast_state_row() : array();
    if (isset($state['bs_mode']) && $state['bs_mode'] === 'manual') {
        $id = isset($state['mf_id']) ? (int)$state['mf_id'] : 0;
        if ($id > 0 && isset($out[$id])) {
            $out[$id]['broadcast'] = 1;
            $out[$id]['global'] += 1;
        }
    }
    return $out;
}

function hb_music_usage_counts($mf_id) {
    $mf_id = (int)$mf_id;
    $map = hb_music_usage_map(array($mf_id));
    return isset($map[$mf_id]) ? $map[$mf_id] : array('schedule'=>0, 'block'=>0, 'sequence'=>0, 'broadcast'=>0, 'global'=>0);
}

function hb_default_settings() {
    return array(
        'priority_mode' => 'single_first',
        'single_window_seconds' => '90',
        'fadeout_seconds' => '4',
        'block_end_action' => 'fade_stop',
        'auto_refresh_seconds' => '60',
        'show_debug_badge' => '0',
        'sitewide_broadcast_enabled' => '0',
        // 사이트 전체 재생바 위치. 기본값은 하단 중앙입니다.
        'sitewide_position' => 'bottom_center',
        // 화면 하단에서 재생바까지의 여백(px). RB빌더 등 관리자 화면 하단에 떠 있는
        // 설정바와 겹치지 않도록 기본값을 여유 있게 잡았습니다. 필요하면 환경설정에서
        // 더 조정할 수 있습니다.
        'sitewide_bottom_gap' => '90'
    );
}

function hb_sitewide_position_allowed() {
    return array('bottom_left', 'bottom_center', 'bottom_right');
}

function hb_seed_settings() {
    $settings = hb_table('settings');
    $ok = true;
    foreach (hb_default_settings() as $key => $value) {
        $key = hb_escape($key);
        $value = hb_escape($value);
        if (!sql_query("INSERT IGNORE INTO `{$settings}` SET st_key='{$key}', st_value='{$value}', st_updated_at=NOW()", false)) $ok = false;
    }
    unset($GLOBALS['hb_bgm_settings_cache']);
    return $ok;
}

function hb_get_setting($key, $default='') {
    $all = hb_get_settings();
    $value = array_key_exists($key, $all) ? $all[$key] : $default;
    if ($key === 'priority_mode' && !in_array($value, array('single_first','block_first'), true)) $value = 'single_first';
    if ($key === 'sitewide_position' && !in_array($value, hb_sitewide_position_allowed(), true)) $value = 'bottom_center';
    if ($key === 'sitewide_bottom_gap') $value = (string)max(0, min(400, hb_int_value($value, 18)));
    return $value;
}

function hb_update_setting($key, $value) {
    $settings = hb_table('settings');
    $key_raw = hb_scalar_string($key, '');
    $value_raw = hb_scalar_string($value, '');
    $key_sql = hb_escape($key_raw);
    $value_sql = hb_escape($value_raw);
    $ok = sql_query("INSERT INTO `{$settings}` SET st_key='{$key_sql}', st_value='{$value_sql}', st_updated_at=NOW() ON DUPLICATE KEY UPDATE st_value='{$value_sql}', st_updated_at=NOW()", false) ? true : false;
    if ($ok && isset($GLOBALS['hb_bgm_settings_cache']) && is_array($GLOBALS['hb_bgm_settings_cache'])) {
        $GLOBALS['hb_bgm_settings_cache'][$key_raw] = $value_raw;
    }
    return $ok;
}

function hb_get_settings() {
    if (isset($GLOBALS['hb_bgm_settings_cache']) && is_array($GLOBALS['hb_bgm_settings_cache'])) return $GLOBALS['hb_bgm_settings_cache'];
    $out = hb_default_settings();
    $settings = hb_table('settings');
    // 공개 페이지/초기 설치에서 information_schema를 매 요청 조회하지 않습니다.
    // 존재하지 않는 테이블 SELECT는 error=false로 조용히 실패하고 기본값을 사용합니다.
    $res = sql_query("SELECT st_key, st_value FROM `{$settings}`", false);
    if ($res) {
        while ($row = sql_fetch_array($res)) {
            if (!isset($row['st_key'])) continue;
            $out[(string)$row['st_key']] = isset($row['st_value']) ? (string)$row['st_value'] : '';
        }
    }
    if (!in_array($out['priority_mode'], array('single_first','block_first'), true)) $out['priority_mode'] = 'single_first';
    if (!in_array($out['sitewide_position'], hb_sitewide_position_allowed(), true)) $out['sitewide_position'] = 'bottom_center';
    $out['sitewide_bottom_gap'] = (string)max(0, min(400, hb_int_value($out['sitewide_bottom_gap'], 18)));
    $GLOBALS['hb_bgm_settings_cache'] = $out;
    return $out;
}



function hb_broadcast_state_row($ensure=false) {
    $table = hb_table('broadcast_state');
    // 2초 polling 경로에서 information_schema 조회를 반복하지 않습니다.
    // error=false 직접 SELECT로 테이블 미생성 상태도 조용히 기본 상태로 처리합니다.
    $row = sql_fetch("SELECT * FROM `{$table}` WHERE bs_id=1 LIMIT 1", false);
    if (!$row && $ensure) {
        sql_query("INSERT IGNORE INTO `{$table}` SET bs_id=1, bs_mode='auto', bs_revision=1, bs_updated_at=NOW()", false);
        $row = sql_fetch("SELECT * FROM `{$table}` WHERE bs_id=1 LIMIT 1", false);
    }
    return $row ?: array(
        'bs_id'=>1, 'bs_mode'=>'auto', 'mf_id'=>null, 'bs_seek_seconds'=>0,
        'bs_started_epoch_ms'=>0, 'bs_revision'=>1, 'bs_updated_by'=>'', 'bs_updated_at'=>''
    );
}

function hb_broadcast_set_state($mode, $mf_id=0, $seek_seconds=0.0) {
    global $member;
    if (!in_array($mode, array('auto','manual','stop'), true)) {
        return array('ok'=>false, 'message'=>'잘못된 전체 방송 모드입니다.');
    }
    $mf_id = max(0, (int)$mf_id);
    $seek_seconds = (float)$seek_seconds;
    if (!is_finite($seek_seconds) || $seek_seconds < 0) $seek_seconds = 0.0;
    $seek_seconds = min(9999999.999, $seek_seconds);
    if ($mode === 'manual') {
        $music = hb_table('music');
        $row = sql_fetch("SELECT mf_id, mf_use, mf_source, mf_file, mf_youtube_id FROM `{$music}` WHERE mf_id='{$mf_id}' LIMIT 1", false);
        if (!$row || (int)$row['mf_use'] !== 1) return array('ok'=>false, 'message'=>'재생 가능한 음악을 찾을 수 없습니다.');
        if (isset($row['mf_source']) && $row['mf_source'] === 'file') {
            $safe_file = hb_safe_file(isset($row['mf_file']) ? $row['mf_file'] : '');
            if ($safe_file === '' || !is_file(rtrim(HB_DATA_PATH, '/\\').'/'.$safe_file) || !is_readable(rtrim(HB_DATA_PATH, '/\\').'/'.$safe_file)) {
                return array('ok'=>false, 'message'=>'음악 원본 파일을 찾을 수 없습니다. 시스템 점검을 확인해주세요.');
            }
        } elseif (!preg_match('/^[a-zA-Z0-9_-]{11}$/', trim((string)(isset($row['mf_youtube_id']) ? $row['mf_youtube_id'] : '')))) {
            return array('ok'=>false, 'message'=>'YouTube 영상 ID가 없어 재생할 수 없습니다.');
        }
    } else {
        $mf_id = 0;
        $seek_seconds = 0.0;
    }
    $table = hb_table('broadcast_state');
    // 새 설치 직후에도 UPDATE가 빈 테이블에 적용되지 않도록 기본 행을 먼저 보장합니다.
    hb_broadcast_state_row(true);
    $state_exists = sql_fetch("SELECT bs_id FROM `{$table}` WHERE bs_id=1 LIMIT 1", false);
    if (!$state_exists || empty($state_exists['bs_id'])) return array('ok'=>false, 'message'=>'전체 방송 상태 저장소를 준비하지 못했습니다.');
    $by = isset($member['mb_id']) ? hb_escape((string)$member['mb_id']) : '';
    $mode_sql = hb_escape($mode);
    $started_ms = $mode === 'manual' ? (int)round(microtime(true) * 1000) : 0;
    $seek_sql = number_format($seek_seconds, 3, '.', '');
    $mf_sql = $mode === 'manual' ? "'".(int)$mf_id."'" : 'NULL';
    $updated = sql_query("UPDATE `{$table}` SET bs_mode='{$mode_sql}', mf_id={$mf_sql}, bs_seek_seconds='{$seek_sql}', bs_started_epoch_ms='{$started_ms}', bs_revision=bs_revision+1, bs_updated_by='{$by}', bs_updated_at=NOW() WHERE bs_id=1", false);
    if (!$updated) return array('ok'=>false, 'message'=>'전체 방송 상태를 저장하지 못했습니다.');
    $saved = sql_fetch("SELECT * FROM `{$table}` WHERE bs_id=1 LIMIT 1", false);
    if (!$saved) return array('ok'=>false, 'message'=>'전체 방송 상태 저장 결과를 확인하지 못했습니다.');
    return array('ok'=>true, 'state'=>$saved);
}

function hb_broadcast_payload() {
    $state = hb_broadcast_state_row();
    $payload = array(
        'mode' => isset($state['bs_mode']) ? (string)$state['bs_mode'] : 'auto',
        'revision' => isset($state['bs_revision']) ? (int)$state['bs_revision'] : 1,
        'started_epoch_ms' => isset($state['bs_started_epoch_ms']) ? (int)$state['bs_started_epoch_ms'] : 0,
        'seek_seconds' => isset($state['bs_seek_seconds']) ? (float)$state['bs_seek_seconds'] : 0.0,
        'updated_at' => isset($state['bs_updated_at']) ? (string)$state['bs_updated_at'] : '',
        'item' => null
    );
    if ($payload['mode'] === 'manual' && !empty($state['mf_id'])) {
        $music = hb_table('music');
        $mf_id = (int)$state['mf_id'];
        $row = sql_fetch("SELECT * FROM `{$music}` WHERE mf_id='{$mf_id}' AND mf_use=1 LIMIT 1", false);
        if ($row) {
            $media = hb_music_item_payload($row);
            $media_available = $media['source'] === 'youtube'
                ? $media['youtube_id'] !== ''
                : (hb_safe_file(isset($row['mf_file']) ? $row['mf_file'] : '') !== ''
                    && is_file(rtrim(HB_DATA_PATH, '/\\').'/'.hb_safe_file(isset($row['mf_file']) ? $row['mf_file'] : ''))
                    && is_readable(rtrim(HB_DATA_PATH, '/\\').'/'.hb_safe_file(isset($row['mf_file']) ? $row['mf_file'] : '')));
            if (!$media_available) {
                // 삭제된 파일/오염된 YouTube ID를 전체방송 API에 내보내면 모든 클라이언트가
                // 같은 항목을 반복 재시도합니다. 상태는 수정하지 않고 응답만 안전하게 강등합니다.
                $payload['mode'] = hb_sitewide_enabled() ? 'auto' : 'stop';
                $payload['started_epoch_ms'] = 0;
                $payload['seek_seconds'] = 0.0;
                return $payload;
            }
            $payload['item'] = array(
                'id' => 'broadcast_'.$mf_id,
                'music_id' => $mf_id,
                'music_title' => (string)$row['mf_title'],
                'title' => (string)$row['mf_title'],
                'volume' => (int)$row['mf_volume'],
                'source' => $media['source'],
                'url' => $media['url'],
                'youtube_id' => $media['youtube_id']
            );
        } else {
            // 공개 조회 API에서는 DB를 수정하지 않습니다. 대상 음악이 사라졌다면
            // 응답만 자동 편성/정지 상태로 안전하게 강등합니다.
            $payload['mode'] = hb_sitewide_enabled() ? 'auto' : 'stop';
            $payload['started_epoch_ms'] = 0;
            $payload['seek_seconds'] = 0.0;
            $payload['item'] = null;
        }
    }
    return $payload;
}

function hb_sitewide_enabled() {
    return hb_get_setting('sitewide_broadcast_enabled', '0') === '1';
}

function hb_sitewide_hook_path() {
    if (defined('G5_EXTEND_PATH') && G5_EXTEND_PATH) return rtrim(G5_EXTEND_PATH, '/\\').'/haru_bgm.extend.php';
    if (defined('G5_PATH') && G5_PATH) return rtrim(G5_PATH, '/\\').'/extend/haru_bgm.extend.php';
    return '';
}

function hb_sitewide_hook_contents() {
    return <<<'PHP'
<?php
// 하루BGM 사이트 전체 방송 bootstrap. 설정에서 켜고 끌 수 있습니다.
if (!defined('_GNUBOARD_')) return;
$hb_sitewide_file = (defined('G5_PLUGIN_PATH') ? G5_PLUGIN_PATH : dirname(__DIR__).'/plugin').'/haru_bgm/sitewide.php';
if (is_file($hb_sitewide_file)) include_once($hb_sitewide_file);
unset($hb_sitewide_file);
PHP;
}

function hb_sitewide_hook_installed() {
    $path = hb_sitewide_hook_path();
    if ($path === '' || !is_file($path)) return false;
    $body = @file_get_contents($path);
    return is_string($body) && strpos($body, 'haru_bgm/sitewide.php') !== false;
}

function hb_sync_sitewide_hook() {
    $path = hb_sitewide_hook_path();
    if ($path === '') return array('ok'=>false, 'message'=>'그누보드 extend 경로를 확인할 수 없습니다.');
    if (!is_file($path)) {
        return array('ok'=>false, 'message'=>'배포 파일 extend/haru_bgm.extend.php가 없습니다. 전체 ZIP을 사이트 루트에 덮어써주세요.');
    }
    $existing = @file_get_contents($path);
    if (!is_string($existing) || strpos($existing, 'haru_bgm/sitewide.php') === false) {
        return array('ok'=>false, 'message'=>'extend/haru_bgm.extend.php 내용이 하루BGM 연결 파일과 일치하지 않습니다.');
    }
    $direct = hb_direct_access_protection_status(true);
    if (empty($direct['ok'])) return array('ok'=>false, 'message'=>'음악 원본 직접 접근 차단을 확인할 수 없습니다. '.(isset($direct['message']) ? $direct['message'] : '웹서버 설정을 확인해주세요.'));
    return array('ok'=>true, 'message'=>'사이트 전체 방송 연결 및 음악 원본 직접 접근 차단이 정상입니다.');
}

function hb_sitewide_should_skip() {
    $script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\','/', (string)$_SERVER['SCRIPT_NAME']) : '';
    if (defined('G5_IS_ADMIN') && G5_IS_ADMIN) return true;
    if (strpos($script, '/adm/') !== false) return true;
    // v1.5.5부터 회원용 프론트 기능은 제거되었습니다. 플러그인 자체의 관리자/API/스트림/404 페이지에
    // 사이트 전체 플레이어를 다시 주입하면 JSON·바이너리 응답이나 제거된 엔드포인트 응답을 오염시킬 수 있습니다.
    if (strpos($script, '/haru_bgm/') !== false) return true;
    // 그누보드 표준 팝업창 페이지(쪽지, 스크랩, 신고, 블라인드 등)는 작은 별도 창으로 열리며,
    // 하단 고정 재생바가 뜨면 좁은 화면을 심하게 가립니다. 파일명이 이런 패턴이면 건너뜁니다.
    // 이 패턴은 서버 측 1차 방어이며, 테마가 다른 파일명을 쓰는 경우까지 대비해
    // sitewide.php의 클라이언트 측 팝업 자동 감지가 2차로 한 번 더 막습니다.
    $basename = strtolower(basename($script));
    $popup_patterns = array('_popin', 'memo_form', 'memo.php', 'scrap_popin', 'del_popin', 'shingo');
    foreach ($popup_patterns as $pattern) {
        if (strpos($basename, $pattern) !== false) return true;
    }
    if (defined('HB_SITEWIDE_RENDERED')) return true;
    return false;
}


function hb_music_is_current_manual_broadcast($mf_id) {
    $mf_id = (int)$mf_id;
    if ($mf_id < 1 || !hb_sitewide_enabled()) return false;
    $state = hb_broadcast_state_row();
    return isset($state['bs_mode'], $state['mf_id']) && $state['bs_mode'] === 'manual' && (int)$state['mf_id'] === $mf_id;
}

function hb_broadcast_reset_if_music($mf_id) {
    $mf_id = (int)$mf_id;
    if ($mf_id < 1) return true;
    $state = hb_broadcast_state_row();
    if (!isset($state['bs_mode'], $state['mf_id'], $state['bs_revision']) || $state['bs_mode'] !== 'manual' || (int)$state['mf_id'] !== $mf_id) return true;

    // 읽은 상태의 revision/음악/모드가 그대로일 때만 reset합니다.
    // 그 사이 다른 관리자가 새 전체방송을 시작했다면 0행 UPDATE로 끝나 새 명령을 덮어쓰지 않습니다.
    $table = hb_table('broadcast_state');
    $revision = max(0, (int)$state['bs_revision']);
    $mode = hb_sitewide_enabled() ? 'auto' : 'stop';
    $mode_sql = hb_escape($mode);
    $by = isset($GLOBALS['member']['mb_id']) ? hb_escape((string)$GLOBALS['member']['mb_id']) : '';
    $updated = sql_query("UPDATE `{$table}` SET bs_mode='{$mode_sql}', mf_id=NULL, bs_seek_seconds='0.000', bs_started_epoch_ms='0', bs_revision=bs_revision+1, bs_updated_by='{$by}', bs_updated_at=NOW() WHERE bs_id=1 AND bs_mode='manual' AND mf_id='{$mf_id}' AND bs_revision='{$revision}'", false);
    return $updated ? true : false;
}

function hb_music_is_global_broadcast_used($mf_id) {
    $mf_id = (int)$mf_id;
    if ($mf_id < 1) return false;
    if (hb_music_is_current_manual_broadcast($mf_id)) return true;

    // 공개 스트림은 지금 재생 시점에 실제로 활성인 공통 편성만 허용합니다.
    // 하루 전체 편성을 순회하며 자식 항목을 N+1 조회하던 방식도 단일 EXISTS 쿼리로 줄입니다.
    $clock = hb_site_clock_parts();
    $today = hb_escape($clock['today']);
    $prev_date = hb_escape($clock['prev_date']);
    $now_his = hb_escape($clock['now_his']);
    $now_sec = (int)$clock['now_sec'];
    $w = (int)$clock['w'];
    $prev_w = (int)$clock['prev_w'];
    $single_window = max(30, min(600, (int)hb_get_setting('single_window_seconds', '90')));
    $schedule = hb_table('schedule');
    $schedule_item = hb_table('schedule_item');
    $block = hb_table('block');
    $block_item = hb_table('block_item');
    $music = hb_table('music');
    $schedule_today_dates = "(s.sc_start_date IS NULL OR s.sc_start_date='0000-00-00' OR s.sc_start_date<='{$today}') AND (s.sc_end_date IS NULL OR s.sc_end_date='0000-00-00' OR s.sc_end_date>='{$today}')";
    $schedule_prev_dates = "(s.sc_start_date IS NULL OR s.sc_start_date='0000-00-00' OR s.sc_start_date<='{$prev_date}') AND (s.sc_end_date IS NULL OR s.sc_end_date='0000-00-00' OR s.sc_end_date>='{$prev_date}')";
    $schedule_today_active = "(".hb_schedule_day_sql('s', $w)." AND {$schedule_today_dates} AND ((s.sc_play_mode='once' AND ({$now_sec}-TIME_TO_SEC(s.sc_time)) BETWEEN 0 AND {$single_window}) OR (s.sc_play_mode='range' AND s.sc_end_time IS NOT NULL AND ((s.sc_end_time>s.sc_time AND '{$now_his}'>=s.sc_time AND '{$now_his}'<s.sc_end_time) OR (s.sc_end_time<s.sc_time AND '{$now_his}'>=s.sc_time))))";
    $schedule_carry_active = "((s.sc_play_mode='range' AND s.sc_end_time IS NOT NULL AND s.sc_end_time<s.sc_time AND '{$now_his}'<s.sc_end_time AND ".hb_schedule_day_sql('s', $prev_w)." AND {$schedule_prev_dates}) OR (s.sc_play_mode='once' AND ({$now_sec}+86400-TIME_TO_SEC(s.sc_time)) BETWEEN 0 AND {$single_window} AND ".hb_schedule_day_sql('s', $prev_w)." AND {$schedule_prev_dates}))";
    $block_today_dates = "(b.bl_start_date IS NULL OR b.bl_start_date='0000-00-00' OR b.bl_start_date<='{$today}') AND (b.bl_end_date IS NULL OR b.bl_end_date='0000-00-00' OR b.bl_end_date>='{$today}')";
    $block_prev_dates = "(b.bl_start_date IS NULL OR b.bl_start_date='0000-00-00' OR b.bl_start_date<='{$prev_date}') AND (b.bl_end_date IS NULL OR b.bl_end_date='0000-00-00' OR b.bl_end_date>='{$prev_date}')";
    $block_today_active = "(".hb_block_day_sql('b', $w)." AND {$block_today_dates} AND ((b.bl_end_time>b.bl_start_time AND '{$now_his}'>=b.bl_start_time AND '{$now_his}'<b.bl_end_time) OR (b.bl_end_time<b.bl_start_time AND '{$now_his}'>=b.bl_start_time))";
    $block_carry_active = "(b.bl_end_time<b.bl_start_time AND '{$now_his}'<b.bl_end_time AND ".hb_block_day_sql('b', $prev_w)." AND {$block_prev_dates})";
    $sql = "SELECT 1 AS hb_used FROM `{$music}` m WHERE m.mf_id='{$mf_id}' AND m.mf_use=1 AND (
        EXISTS (SELECT 1 FROM `{$schedule}` s WHERE s.sc_use=1 AND s.sc_scope='global' AND (((s.mf_id='{$mf_id}') AND NOT EXISTS (SELECT 1 FROM `{$schedule_item}` si0 WHERE si0.sc_id=s.sc_id)) OR EXISTS (SELECT 1 FROM `{$schedule_item}` si WHERE si.sc_id=s.sc_id AND si.mf_id='{$mf_id}')) AND ({$schedule_today_active} OR {$schedule_carry_active}))
        OR EXISTS (SELECT 1 FROM `{$block}` b INNER JOIN `{$block_item}` bi ON bi.bl_id=b.bl_id AND bi.mf_id='{$mf_id}' WHERE b.bl_use=1 AND b.bl_scope='global' AND ({$block_today_active} OR {$block_carry_active}))
    ) LIMIT 1";
    $row = sql_fetch($sql, false);
    return $row && !empty($row['hb_used']);
}

function hb_stream_file_allowed($file) {
    $file = hb_safe_file($file);
    if ($file === '') return false;
    $music = hb_table('music');
    $file_sql = hb_escape($file);
    $row = sql_fetch("SELECT mf_id, mf_use, mf_source FROM `{$music}` WHERE mf_file='{$file_sql}' LIMIT 1", false);
    if (!$row || (isset($row['mf_source']) && $row['mf_source'] !== 'file')) return false;
    if (hb_is_plugin_admin()) {
        // 사용 중지 파일은 음악 보관함 권한이 있는 관리자만 점검할 수 있습니다.
        if ((int)$row['mf_use'] !== 1) return hb_user_has_admin_auth('990130', 'r');
        // 활성 파일 미리듣기는 실제 재생 UI를 가진 관리자 메뉴 권한으로 한정합니다.
        return hb_user_has_any_admin_auth(array('990110','990120','990130','990160'), 'r');
    }
    if ((int)$row['mf_use'] !== 1) return false;
    return hb_sitewide_enabled() && hb_music_is_global_broadcast_used((int)$row['mf_id']);
}

function hb_priority_score($kind, $scope='global', $mode='single_first') {
    // 공통 정각 시간표와 공통 시간대 사이의 우선순위만 계산합니다.
    // 구버전에서 남은 알 수 없는 우선순위 값은 정각 우선으로 호환합니다.
    if ($mode === 'block_first') return $kind === 'block' ? 10 : 20;
    return $kind === 'single' ? 10 : 20;
}

function hb_setting_label_priority($mode) {
    return $mode === 'block_first' ? '시간대 묶음 우선' : '정각 시간표 우선';
}

function hb_guess_title_from_filename($name) {
    $base = pathinfo((string)$name, PATHINFO_FILENAME);
    $base = preg_replace('/[_\-]+/', ' ', $base);
    $base = trim($base);
    return $base !== '' ? hb_text_limit($base, 255) : '하루브금 음악';
}

function hb_upload_error_message($code) {
    $code = (int)$code;
    $map = array(
        UPLOAD_ERR_INI_SIZE => '서버의 업로드 허용 용량(upload_max_filesize)을 초과했습니다.',
        UPLOAD_ERR_FORM_SIZE => '폼에서 허용한 업로드 용량을 초과했습니다.',
        UPLOAD_ERR_PARTIAL => '파일이 일부만 업로드되었습니다. 다시 시도해주세요.',
        UPLOAD_ERR_NO_FILE => '업로드된 파일이 없습니다.',
        UPLOAD_ERR_NO_TMP_DIR => '서버 임시 업로드 폴더가 없습니다.',
        UPLOAD_ERR_CANT_WRITE => '서버가 업로드 파일을 저장하지 못했습니다.',
        UPLOAD_ERR_EXTENSION => '서버 확장 기능이 파일 업로드를 중단했습니다.'
    );
    return isset($map[$code]) ? $map[$code] : '파일 업로드 중 오류가 발생했습니다. (code '.$code.')';
}

function hb_ini_bytes($value) {
    $value = trim(hb_scalar_string($value, ''));
    if ($value === '' || $value === '-1') return $value === '-1' ? PHP_INT_MAX : 0;
    if (!preg_match('/^([0-9]+(?:\.[0-9]+)?)\s*([KMGTP]?)B?$/i', $value, $m)) return (int)$value;
    $number = (float)$m[1];
    $powers = array(''=>0,'K'=>1,'M'=>2,'G'=>3,'T'=>4,'P'=>5);
    $unit = strtoupper($m[2]);
    return (int)round($number * pow(1024, isset($powers[$unit]) ? $powers[$unit] : 0));
}

function hb_human_bytes($bytes) {
    $bytes = max(0, (float)$bytes);
    $units = array('B','KB','MB','GB','TB');
    $i = 0;
    while ($bytes >= 1024 && $i < count($units)-1) { $bytes /= 1024; $i++; }
    return ($i === 0 ? (string)(int)$bytes : number_format($bytes, $bytes >= 100 ? 0 : 1)).' '.$units[$i];
}

function hb_effective_upload_limit_bytes() {
    $limits = array((int)HB_MAX_UPLOAD_FILE_BYTES);
    $upload = hb_ini_bytes(ini_get('upload_max_filesize'));
    $post = hb_ini_bytes(ini_get('post_max_size'));
    if ($upload > 0 && $upload < PHP_INT_MAX) $limits[] = $upload;
    if ($post > 0 && $post < PHP_INT_MAX) $limits[] = $post;
    return max(1, min($limits));
}

function hb_storage_usage_bytes() {
    if (isset($GLOBALS['hb_bgm_storage_usage_cache'])) return (int)$GLOBALS['hb_bgm_storage_usage_cache'];
    $total = 0;
    if (is_dir(HB_DATA_PATH)) {
        try {
            $it = new DirectoryIterator(HB_DATA_PATH);
            foreach ($it as $file) {
                if ($file->isDot() || !$file->isFile()) continue;
                if (!preg_match('/^hb_[A-Za-z0-9]+\.(?:mp3|wav|ogg|m4a)$/i', $file->getFilename())) continue;
                $total += max(0, (int)$file->getSize());
            }
        } catch (Throwable $e) { return $total; }
    }
    $GLOBALS['hb_bgm_storage_usage_cache'] = $total;
    return $total;
}

function hb_storage_usage_add($bytes) {
    $current = hb_storage_usage_bytes();
    $GLOBALS['hb_bgm_storage_usage_cache'] = max(0, $current + (int)$bytes);
}

function hb_audio_signature_matches($path, $ext) {
    $ext = strtolower((string)$ext);
    $fh = @fopen($path, 'rb');
    if (!$fh) return false;
    $head = @fread($fh, 1048576);
    $file_size = @filesize($path);
    if (!is_string($head) || strlen($head) < 12) {
        @fclose($fh);
        return false;
    }
    if ($ext === 'wav') {
        $scan = $head;
        if ($file_size !== false && $file_size > strlen($head) && @fseek($fh, max(0, (int)$file_size - 1048576), SEEK_SET) === 0) {
            $tail = @fread($fh, 1048576);
            if (is_string($tail)) $scan .= $tail;
        }
        @fclose($fh);
        return substr($head, 0, 4) === 'RIFF' && substr($head, 8, 4) === 'WAVE' && strpos($scan, 'fmt ') !== false && strpos($scan, 'data') !== false;
    }
    if ($ext === 'ogg') {
        @fclose($fh);
        return substr($head, 0, 4) === 'OggS' && (strpos($head, 'vorbis') !== false || strpos($head, 'OpusHead') !== false || strpos($head, 'Speex   ') !== false);
    }
    if ($ext === 'm4a') {
        $lead = substr($head, 0, 128);
        $ftyp_pos = strpos($lead, 'ftyp');
        $brand_area = $ftyp_pos === false ? '' : substr($lead, $ftyp_pos + 4, 64);
        @fclose($fh);
        return $ftyp_pos !== false && $ftyp_pos < 64 && (strpos($brand_area, 'M4A ') !== false || strpos($brand_area, 'M4B ') !== false || strpos($brand_area, 'isom') !== false || strpos($brand_area, 'iso2') !== false || strpos($brand_area, 'mp4') !== false || strpos($brand_area, 'mp41') !== false || strpos($brand_area, 'mp42') !== false || strpos($brand_area, 'qt  ') !== false);
    }
    if ($ext === 'mp3') {
        $offset = 0;
        // ID3v2 헤더는 최소 10바이트(0~9)이며 flags 바이트는 인덱스 5입니다.
        // strlen($head) >= 10 조건이 인덱스 5,6,7,8,9 접근을 항상 보장하지만,
        // 문자열 오프셋 접근(ord($head[5]))이 이 보장 관계에 암묵적으로 의존하지 않도록
        // 실제 바이트 존재 여부를 명시적으로 다시 확인한 뒤에만 접근합니다.
        if (substr($head, 0, 3) === 'ID3' && strlen($head) >= 10) {
            $b = array_map('ord', str_split(substr($head, 6, 4)));
            $tagSize = (($b[0] & 0x7f) << 21) | (($b[1] & 0x7f) << 14) | (($b[2] & 0x7f) << 7) | ($b[3] & 0x7f);
            $flags_byte = strlen($head) > 5 ? ord($head[5]) : 0;
            $offset = 10 + $tagSize + (($flags_byte & 0x10) ? 10 : 0);
            if ($offset >= strlen($head) && $file_size !== false && $offset < (int)$file_size) {
                if (@fseek($fh, $offset, SEEK_SET) === 0) {
                    $after_tag = @fread($fh, 65536);
                    if (is_string($after_tag)) {
                        $head = $after_tag;
                        $offset = 0;
                    }
                }
            }
        }
        $limit = strlen($head) - 3;
        for ($i=max(0,$offset); $i<$limit; $i++) {
            $a = ord($head[$i]); $b = ord($head[$i+1]); $c = ord($head[$i+2]);
            $sync = $a === 0xFF && ($b & 0xE0) === 0xE0;
            $version_ok = ($b & 0x18) !== 0x08;
            $layer_ok = ($b & 0x06) !== 0x00;
            $bitrate = ($c >> 4) & 0x0F; $sample_rate = ($c >> 2) & 0x03;
            if ($sync && $version_ok && $layer_ok && $bitrate > 0 && $bitrate < 15 && $sample_rate < 3) {
                @fclose($fh);
                return true;
            }
        }
    }
    @fclose($fh);
    return false;
}

function hb_absolute_data_url($relative) {
    $base = rtrim(defined('HB_DATA_URL') ? (string)HB_DATA_URL : '', '/');
    if ($base === '') return '';
    if (!preg_match('#^https?://#i', $base)) {
        $site_base = defined('G5_URL') ? rtrim((string)G5_URL, '/') : '';
        if (preg_match('#^https?://#i', $site_base)) {
            $base = $site_base.'/'.ltrim($base, '/');
        } else {
            $host = '';
            if (defined('G5_DOMAIN') && G5_DOMAIN !== '') $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', (string)G5_DOMAIN);
            if ($host === '') return '';
            $https = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
            $base = ($https ? 'https://' : 'http://').$host.'/'.ltrim($base, '/');
        }
    }
    return $base.'/'.ltrim((string)$relative, '/');
}

function hb_http_probe_status($url) {
    if (!preg_match('#^https?://#i', (string)$url)) return 0;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(CURLOPT_NOBODY=>true, CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>false, CURLOPT_CONNECTTIMEOUT=>2, CURLOPT_TIMEOUT=>4, CURLOPT_USERAGENT=>'HaruBGM/'.HB_PLUGIN_VERSION.' access-probe'));
        @curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        // 일부 Nginx/Apache 설정은 HEAD만 거부하고 GET은 허용합니다.
        // HEAD 결과만 믿으면 원본 정적 노출을 놓칠 수 있으므로 405/무응답일 때 1바이트 GET으로 재확인합니다.
        if ($code === 405 || $code === 0) {
            curl_setopt_array($ch, array(CURLOPT_NOBODY=>false, CURLOPT_RANGE=>'0-0'));
            @curl_exec($ch);
            $get_code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            if ($get_code > 0) $code = $get_code;
        }
        curl_close($ch);
        return $code;
    }
    $ctx = stream_context_create(array('http'=>array('method'=>'HEAD','timeout'=>4,'ignore_errors'=>true,'follow_location'=>0,'header'=>"User-Agent: HaruBGM/".HB_PLUGIN_VERSION." access-probe\r\n")));
    @file_get_contents($url, false, $ctx);
    $code = 0;
    if (!empty($http_response_header) && preg_match('#HTTP/\S+\s+(\d{3})#', (string)$http_response_header[0], $m)) $code = (int)$m[1];
    if ($code === 405 || $code === 0) {
        $get_ctx = stream_context_create(array('http'=>array('method'=>'GET','timeout'=>4,'ignore_errors'=>true,'follow_location'=>0,'header'=>"User-Agent: HaruBGM/".HB_PLUGIN_VERSION." access-probe\r\nRange: bytes=0-0\r\n")));
        @file_get_contents($url, false, $get_ctx);
        if (!empty($http_response_header) && preg_match('#HTTP/\S+\s+(\d{3})#', (string)$http_response_header[0], $m)) $code = (int)$m[1];
    }
    if ($code > 0) return $code;
    return 0;
}

function hb_direct_access_protection_status($force_probe=false) {
    static $cached = null;
    if (!$force_probe && is_array($cached)) return $cached;
    if (!hb_ensure_data_dir() || !is_dir(HB_DATA_PATH) || !is_writable(HB_DATA_PATH)) return array('ok'=>false, 'message'=>'음악 저장 폴더에 접근할 수 없어 직접 접근 차단을 검사하지 못했습니다.');
    try { $token = function_exists('random_bytes') ? bin2hex(random_bytes(8)) : md5(uniqid('', true)); }
    catch (Throwable $e) { $token = md5(uniqid((string)mt_rand(), true)); }
    $name = 'hb_access_probe_'.$token.'.txt';
    $path = rtrim(HB_DATA_PATH, '/\\').'/'.$name;
    if (@file_put_contents($path, 'haru-bgm-access-probe', LOCK_EX) === false) return array('ok'=>false, 'message'=>'직접 접근 차단 검사 파일을 만들지 못했습니다.');
    @chmod($path, G5_FILE_PERMISSION);
    $url = hb_absolute_data_url($name);
    $status = $url !== '' ? hb_http_probe_status($url) : 0;
    @unlink($path);
    if (in_array($status, array(401,403,404,410), true)) $cached = array('ok'=>true, 'message'=>'실제 HTTP 직접 접근 검사 차단 확인 (HTTP '.$status.')');
    elseif (in_array($status, array(200,206), true)) $cached = array('ok'=>false, 'message'=>'위험: data/haru_bgm 원본 파일이 정적 URL로 직접 열립니다 (HTTP '.$status.'). 웹서버 deny 설정이 필요합니다.');
    elseif ($status > 0) $cached = array('ok'=>false, 'message'=>'직접 접근 검사 결과가 확정적이지 않습니다 (HTTP '.$status.'). Nginx location/Apache AllowOverride 설정을 확인해주세요.');
    else $cached = array('ok'=>false, 'message'=>'자기 서버 HTTP 검사에 실패해 직접 접근 차단을 확인하지 못했습니다. Nginx location/Apache AllowOverride 설정을 확인해주세요.');
    return $cached;
}

function hb_storage_quota_lock(&$error='') {
    $error = '';
    $path = rtrim(HB_DATA_PATH, '/\\').'/.quota.lock';
    $fp = @fopen($path, 'c+');
    if (!$fp) { $error = '음악 저장소 잠금 파일을 열 수 없습니다.'; return false; }
    if (!@flock($fp, LOCK_EX)) { @fclose($fp); $error = '음악 저장소 용량 잠금을 얻지 못했습니다.'; return false; }
    return $fp;
}

function hb_storage_quota_unlock($fp) {
    if (is_resource($fp)) { @flock($fp, LOCK_UN); @fclose($fp); }
}

function hb_upload_music_file($file, &$error) {
    $error = '';
    $upload_error = isset($file['error']) ? (int)$file['error'] : UPLOAD_ERR_NO_FILE;
    if ($upload_error !== UPLOAD_ERR_OK) {
        $error = hb_upload_error_message($upload_error);
        return false;
    }
    $tmp_name = isset($file['tmp_name']) ? hb_scalar_string($file['tmp_name'], '') : '';
    if ($tmp_name === '' || !is_uploaded_file($tmp_name)) {
        $error = '정상적인 HTTP 업로드 파일이 아닙니다.';
        return false;
    }
    if (!hb_ensure_data_dir() || !is_dir(HB_DATA_PATH) || !is_writable(HB_DATA_PATH)) {
        $error = '음악 저장 폴더에 쓰기 권한이 없습니다.';
        return false;
    }
    $direct = hb_direct_access_protection_status();
    if (empty($direct['ok'])) {
        $error = '보안을 위해 음악 업로드를 중단했습니다. '.(isset($direct['message']) ? $direct['message'] : '음악 원본 직접 접근 차단을 확인해주세요.');
        return false;
    }
    $org = isset($file['name']) ? hb_text_limit(basename(hb_scalar_string($file['name'], '')), 255) : '';
    $actual_size = @filesize($tmp_name);
    $size = $actual_size !== false ? (int)$actual_size : 0;
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
    $effective_limit = hb_effective_upload_limit_bytes();
    if ($size > $effective_limit) {
        $error = '파일 1개 최대 용량('.hb_human_bytes($effective_limit).')을 초과했습니다.';
        return false;
    }
    if (!hb_audio_signature_matches($tmp_name, $ext)) {
        $error = '파일 내용이 실제 '.$ext.' 오디오 형식으로 확인되지 않습니다.';
        return false;
    }
    $mime = '';
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detected = @$finfo->file($tmp_name);
        if (is_string($detected)) $mime = strtolower(trim($detected));
    } elseif (function_exists('mime_content_type')) {
        $detected = @mime_content_type($tmp_name);
        if (is_string($detected)) $mime = strtolower(trim($detected));
    }
    if ($mime === '') $mime = 'application/octet-stream';
    $mime_allow = array(
        'mp3' => array('audio/mpeg','audio/mp3','audio/x-mpeg','application/octet-stream'),
        'wav' => array('audio/wav','audio/x-wav','audio/wave','audio/vnd.wave','application/octet-stream'),
        'ogg' => array('audio/ogg','application/ogg','application/octet-stream'),
        'm4a' => array('audio/mp4','audio/x-m4a','video/mp4','application/mp4','application/octet-stream')
    );
    if ($mime !== '' && !in_array($mime, $mime_allow[$ext], true)) {
        $error = '파일 내용이 선택한 음악 형식('.$ext.')과 일치하지 않습니다. 감지 형식: '.$mime;
        return false;
    }
    try {
        $rand = function_exists('random_bytes') ? bin2hex(random_bytes(8)) : md5(uniqid('', true));
    } catch (Throwable $e) {
        $rand = md5(uniqid((string)mt_rand(), true));
    }
    $lock_error = '';
    $quota_lock = hb_storage_quota_lock($lock_error);
    if (!$quota_lock) { $error = $lock_error ?: '음악 저장소 잠금을 얻지 못했습니다.'; return false; }
    unset($GLOBALS['hb_bgm_storage_usage_cache']);
    $used = hb_storage_usage_bytes();
    if ($used + $size > HB_MAX_STORAGE_BYTES) {
        hb_storage_quota_unlock($quota_lock);
        $error = '하루BGM 음악 저장 한도('.hb_human_bytes(HB_MAX_STORAGE_BYTES).')를 초과합니다.';
        return false;
    }
    $free = @disk_free_space(HB_DATA_PATH);
    if ($free !== false && ((float)$free - $size) < HB_MIN_FREE_BYTES) {
        hb_storage_quota_unlock($quota_lock);
        $error = '서버 디스크 여유 공간이 부족합니다. 최소 '.hb_human_bytes(HB_MIN_FREE_BYTES).'의 여유 공간을 남겨야 합니다.';
        return false;
    }
    $save = 'hb_'.$rand.'.'.$ext;
    $dest = HB_DATA_PATH.'/'.$save;
    if (!move_uploaded_file($tmp_name, $dest)) {
        hb_storage_quota_unlock($quota_lock);
        $error = '파일 업로드에 실패했습니다.';
        return false;
    }
    @chmod($dest, G5_FILE_PERMISSION);
    unset($GLOBALS['hb_bgm_storage_usage_cache']);
    $after = hb_storage_usage_bytes();
    if ($after > HB_MAX_STORAGE_BYTES) {
        @unlink($dest); unset($GLOBALS['hb_bgm_storage_usage_cache']); hb_storage_quota_unlock($quota_lock);
        $error = '동시 업로드로 저장 한도를 초과해 방금 파일을 정리했습니다. 다시 시도해주세요.';
        return false;
    }
    hb_storage_quota_unlock($quota_lock);
    return array('save'=>$save, 'org'=>$org, 'size'=>$size, 'mime'=>$mime);
}

function hb_files_rearray($files) {
    $out = array();
    if (!is_array($files) || !array_key_exists('name', $files)) return $out;
    if (!is_array($files['name'])) {
        return array(array(
            'name' => hb_scalar_string($files['name'], ''),
            'type' => hb_scalar_string(isset($files['type']) ? $files['type'] : '', ''),
            'tmp_name' => hb_scalar_string(isset($files['tmp_name']) ? $files['tmp_name'] : '', ''),
            'error' => hb_int_value(isset($files['error']) ? $files['error'] : UPLOAD_ERR_NO_FILE, UPLOAD_ERR_NO_FILE),
            'size' => max(0, hb_int_value(isset($files['size']) ? $files['size'] : 0, 0))
        ));
    }
    foreach ($files['name'] as $i => $name) {
        $name = hb_scalar_string($name, '');
        $error = hb_int_value(isset($files['error'][$i]) ? $files['error'][$i] : UPLOAD_ERR_NO_FILE, UPLOAD_ERR_NO_FILE);
        if ($name === '' && $error === UPLOAD_ERR_NO_FILE) continue;
        $out[] = array(
            'name' => $name,
            'type' => hb_scalar_string(isset($files['type'][$i]) ? $files['type'][$i] : '', ''),
            'tmp_name' => hb_scalar_string(isset($files['tmp_name'][$i]) ? $files['tmp_name'][$i] : '', ''),
            'error' => $error,
            'size' => max(0, hb_int_value(isset($files['size'][$i]) ? $files['size'][$i] : 0, 0))
        );
    }
    return $out;
}

function hb_table_exists($table) {
    $table_sql = hb_escape((string)$table);
    // '_'와 '%'가 포함된 그누보드 테이블 접두어도 와일드카드가 아닌 정확한 이름으로 검사합니다.
    $row = sql_fetch("SELECT 1 AS hb_exists FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table_sql}' LIMIT 1", false);
    return $row && !empty($row['hb_exists']);
}

function hb_health_checks() {
    $checks = array();
    $required = hb_schema_required_tables();
    foreach ($required as $name) {
        $table_name = hb_table($name);
        $ok = hb_table_exists($table_name);
        $checks[] = array(
            'label' => 'DB 테이블: '.$table_name,
            'ok' => $ok,
            'message' => $ok ? '정상' : '없음 또는 생성 실패'
        );
    }
    foreach ($required as $name) {
        $table_name = hb_table($name);
        $engine = hb_table_engine($table_name);
        $ok = ($engine === 'INNODB');
        $checks[] = array(
            'label' => 'DB 엔진: '.$table_name,
            'ok' => $ok,
            'message' => $ok ? 'InnoDB · 트랜잭션 사용 가능' : ($engine !== '' ? $engine.' · InnoDB 전환 필요' : '테이블 엔진 확인 실패')
        );
    }
    foreach (hb_schema_required_columns() as $table_key => $columns) {
        $table_name = hb_table($table_key);
        foreach ($columns as $column) {
            $ok = hb_table_exists($table_name) && hb_column_exists($table_name, $column);
            $checks[] = array(
                'label' => 'DB 컬럼: '.$table_name.'.'.$column,
                'ok' => $ok,
                'message' => $ok ? '정상' : '없음 또는 업그레이드 실패'
            );
        }
    }
    foreach (hb_schema_column_definitions() as $table_key => $columns) {
        $table_name = hb_table($table_key);
        foreach ($columns as $column => $definition) {
            $detail = '';
            $ok = hb_column_definition_matches($table_name, $column, $definition, $detail);
            if (!$ok) {
                $sql = hb_column_migration_sql($table_name, $column, $definition);
                $detail .= $sql !== '' ? ' · 백업 후 점검 SQL: '.$sql : '';
                if (!hb_column_safe_to_modify($table_name, $column, $definition)) $detail .= ' · 자동 보정하지 않은 위험 변경입니다.';
            }
            $checks[] = array(
                'label' => 'DB 컬럼 정의: '.$table_name.'.'.$column,
                'ok' => $ok,
                'message' => $detail
            );
        }
    }
    foreach ($required as $name) {
        $table_name = hb_table($name);
        $collation = hb_table_collation($table_name);
        $ok = $collation !== '' && strpos($collation, 'utf8mb4_') === 0;
        $checks[] = array(
            'label' => 'DB 문자셋: '.$table_name,
            'ok' => $ok,
            'message' => $ok ? $collation : ($collation !== '' ? $collation.' · utf8mb4 전환 필요' : '문자셋 확인 실패')
        );
    }
    foreach (hb_schema_required_defaults() as $table_key => $columns) {
        $table_name = hb_table($table_key);
        foreach ($columns as $column => $spec) {
            $found = false;
            $actual = hb_column_default_value($table_name, $column, $found);
            $ok = $found && hb_default_values_match($actual, $spec['default']);
            $checks[] = array(
                'label' => 'DB 기본값: '.$table_name.'.'.$column,
                'ok' => $ok,
                'message' => $ok ? '정상' : '기본값 불일치 또는 업그레이드 필요'
            );
        }
    }
    foreach (hb_schema_index_definitions() as $table_key => $indexes) {
        $table_name = hb_table($table_key);
        foreach ($indexes as $index_name => $definition) {
            $ok = hb_table_exists($table_name) && hb_index_matches($table_name, $index_name, $definition);
            $checks[] = array(
                'label' => 'DB 인덱스: '.$table_name.'.'.$index_name,
                'ok' => $ok,
                'message' => $ok ? '정상' : '없음·구성 불일치 또는 업그레이드 실패'
            );
        }
    }
    foreach (hb_schema_foreign_key_definitions() as $table_key => $keys) {
        $table_name = hb_table($table_key);
        foreach ($keys as $constraint_name => $spec) {
            $named_keys = hb_foreign_key_info($table_name, $constraint_name);
            $named = isset($named_keys[$constraint_name]) ? $named_keys[$constraint_name] : array();
            $ok = hb_table_exists($table_name) && hb_foreign_key_matches($named, $table_key, $spec);
            if (!$ok) $ok = hb_table_exists($table_name) && hb_foreign_key_equivalent($table_name, $table_key, $spec);
            $checks[] = array(
                'label' => 'DB 외래키: '.$table_name.'.'.$constraint_name,
                'ok' => $ok,
                'message' => $ok ? '정상' : '없음·구성 불일치 또는 업그레이드 실패'
            );
        }
    }
    $integrity = hb_schema_data_integrity();
    $integrity_labels = array(
        'youtube_duplicates' => 'YouTube ID 중복',
        'block_day_orphans' => '시간대 요일의 시간대 고아 행',
        'block_item_orphans' => '시간대 항목의 시간대 고아 행',
        'block_music_orphans' => '시간대 항목의 음악 고아 행',
        'schedule_day_orphans' => '시간표 요일의 시간표 고아 행',
        'schedule_item_orphans' => '시간표 항목의 시간표 고아 행',
        'schedule_music_orphans' => '시간표 항목의 음악 고아 행',
        'sequence_item_orphans' => '순서표 항목의 순서표 고아 행',
        'sequence_music_orphans' => '순서표 항목의 음악 고아 행'
    );
    foreach ($integrity_labels as $key => $label) {
        $count = isset($integrity[$key]) ? $integrity[$key] : null;
        $ok = $count !== null && $count === 0;
        $checks[] = array('label'=>'DB 무결성: '.$label, 'ok'=>$ok, 'message'=>$count === null ? '검사 실패' : ($ok ? '정상' : '확인 필요 '.$count.'건'));
    }
    $checks[] = array('label'=>'음악 저장 폴더', 'ok'=>is_dir(HB_DATA_PATH), 'message'=>HB_DATA_PATH);
    $checks[] = array('label'=>'음악 저장 폴더 쓰기 권한', 'ok'=>is_dir(HB_DATA_PATH) && is_writable(HB_DATA_PATH), 'message'=>(is_dir(HB_DATA_PATH) && is_writable(HB_DATA_PATH)) ? '업로드 가능' : '권한 확인 필요');
    $direct = hb_direct_access_protection_status();
    $checks[] = array('label'=>'음악 원본 직접 접근 차단', 'ok'=>!empty($direct['ok']), 'message'=>isset($direct['message']) ? $direct['message'] : '확인 실패');
    $upload_raw = (string)ini_get('upload_max_filesize');
    $post_raw = (string)ini_get('post_max_size');
    $upload_bytes = hb_ini_bytes($upload_raw);
    $post_bytes = hb_ini_bytes($post_raw);
    $checks[] = array('label'=>'PHP upload_max_filesize', 'ok'=>$upload_bytes > 0, 'message'=>$upload_raw.' · 플러그인 실효 파일 상한 '.hb_human_bytes(hb_effective_upload_limit_bytes()));
    $checks[] = array('label'=>'PHP post_max_size', 'ok'=>$post_raw === '0' || $post_bytes > 0, 'message'=>$post_raw === '0' ? '무제한(서버 설정)' : $post_raw);
    $storage_used = hb_storage_usage_bytes();
    $checks[] = array('label'=>'음악 저장 용량', 'ok'=>$storage_used <= HB_MAX_STORAGE_BYTES, 'message'=>hb_human_bytes($storage_used).' / '.hb_human_bytes(HB_MAX_STORAGE_BYTES));
    $disk_free = @disk_free_space(HB_DATA_PATH);
    $checks[] = array('label'=>'음악 저장 디스크 여유', 'ok'=>$disk_free === false ? false : $disk_free >= HB_MIN_FREE_BYTES, 'message'=>$disk_free === false ? '디스크 여유 공간 확인 실패' : hb_human_bytes($disk_free).' 남음 · 최소 '.hb_human_bytes(HB_MIN_FREE_BYTES).' 필요');
    $checks[] = array('label'=>'PHP 버전', 'ok'=>version_compare(PHP_VERSION, '7.4.0', '>='), 'message'=>PHP_VERSION);
    $checks[] = array('label'=>'Range 스트리밍', 'ok'=>true, 'message'=>'stream.php 사용');
    $checks[] = array('label'=>'YouTube 링크 재생', 'ok'=>true, 'message'=>'IFrame Player API 사용');
    $checks[] = array('label'=>'사이트 전체 방송 연결', 'ok'=>!hb_sitewide_enabled() || hb_sitewide_hook_installed(), 'message'=>hb_sitewide_enabled() ? (hb_sitewide_hook_installed() ? '활성화 · extend 연결 정상' : '활성화 상태이나 extend 연결 파일 확인 필요') : '비활성화');
    $missing_active_files = array();
    $music_table = hb_table('music');
    if (hb_table_exists($music_table)) {
        $res = sql_query("SELECT mf_id, mf_title, mf_file FROM `{$music_table}` WHERE mf_use='1' AND mf_source='file' AND mf_file<>'' ORDER BY mf_id ASC", false);
        if ($res) {
            while ($row = sql_fetch_array($res)) {
                $safe = hb_safe_file(isset($row['mf_file']) ? $row['mf_file'] : '');
                if ($safe === '' || !is_file(rtrim(HB_DATA_PATH, '/\\').'/'.$safe)) {
                    $missing_active_files[] = '#'.(int)$row['mf_id'].' '.hb_text_limit(isset($row['mf_title']) ? $row['mf_title'] : '', 40);
                    if (count($missing_active_files) >= 10) break;
                }
            }
        }
    }
    $checks[] = array('label'=>'활성 파일 음악 원본', 'ok'=>!$missing_active_files, 'message'=>$missing_active_files ? '원본 확인 필요: '.implode(', ', $missing_active_files) : '누락된 활성 파일 없음');
    $orphan_files = hb_orphan_music_files(10);
    $checks[] = array('label'=>'미사용 음악 파일', 'ok'=>!$orphan_files, 'message'=>$orphan_files ? 'DB에서 사용하지 않는 파일 확인 필요: '.implode(', ', $orphan_files) : '고아 파일 없음');
    $legacy_remaining = array();
    foreach (hb_legacy_member_relative_files() as $relative) {
        if (is_file(rtrim(HB_PATH, '/\\').'/'.$relative)) $legacy_remaining[] = $relative;
    }
    $checks[] = array('label'=>'제거된 구형 회원 기능 파일', 'ok'=>!$legacy_remaining, 'message'=>$legacy_remaining ? implode(', ', $legacy_remaining) : '남은 파일 없음');
    return $checks;
}

function hb_nav_admin_items() {
    if (!defined('HB_URL')) return array();
    return array(
        array('menu'=>'990130','group' => 'BGM 관리', 'label' => '음악 관리', 'icon' => 'music', 'url' => HB_URL.'/admin/music_list.php', 'match' => array('/admin/music_list.php','/admin/music_form.php')),
        array('menu'=>'990140','group' => 'BGM 관리', 'label' => '공통 시간표', 'icon' => 'calendar', 'url' => HB_URL.'/admin/schedule_global.php', 'match' => array('/admin/schedule_global.php','/admin/schedule_form.php')),
        array('menu'=>'990150','group' => 'BGM 관리', 'label' => '공통 시간대', 'icon' => 'layers', 'url' => HB_URL.'/admin/block_global.php', 'match' => array('/admin/block_global.php','/admin/block_form.php')),
        array('menu'=>'990160','group' => 'BGM 관리', 'label' => '순서표 모드', 'icon' => 'list', 'url' => HB_URL.'/admin/sequence_list.php', 'match' => array('/admin/sequence_list.php','/admin/sequence_form.php','/admin/sequence_runner.php')),

        array('menu'=>'990120','group' => '방송 관리', 'label' => '오늘 운영표', 'icon' => 'clock', 'url' => HB_URL.'/admin/today.php', 'match' => array('/admin/today.php')),
        array('menu'=>'990180','group' => '방송 관리', 'label' => '재생 로그', 'icon' => 'log', 'url' => HB_URL.'/admin/logs.php', 'match' => array('/admin/logs.php')),

        array('menu'=>'990190','group' => '시스템', 'label' => '환경설정', 'icon' => 'settings', 'url' => HB_URL.'/admin/settings.php', 'match' => array('/admin/settings.php')),
        array('menu'=>'990190','group' => '시스템', 'label' => '시스템 점검', 'icon' => 'check', 'url' => HB_URL.'/admin/health.php', 'match' => array('/admin/health.php')),
    );
}

function hb_nav_icon_svg($name) {
    $icons = array(
        'music'     => '<path d="M9 17V4l10-1v13"/><circle cx="6" cy="17" r="3"/><circle cx="16" cy="16" r="3"/>',
        'calendar'  => '<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 2v4M16 2v4"/>',
        'layers'    => '<path d="M12 2 2 7l10 5 10-5z"/><path d="M2 12l10 5 10-5"/><path d="M2 17l10 5 10-5"/>',
        'list'      => '<path d="M8 6h13M8 12h13M8 18h13"/><circle cx="3.5" cy="6" r="1"/><circle cx="3.5" cy="12" r="1"/><circle cx="3.5" cy="18" r="1"/>',
        'broadcast' => '<path d="M12 2v6"/><circle cx="12" cy="13" r="3"/><path d="M5.5 9.5a9 9 0 0 1 13 0M2.5 6.5a13 13 0 0 1 19 0"/>',
        'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
        'log'       => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/>',
        'settings'  => '<circle cx="12" cy="12" r="3"/><path d="M19.4 13a7.7 7.7 0 0 0 0-2l2-1.6-2-3.4-2.4 1a7.5 7.5 0 0 0-1.7-1L15 3h-6l-.3 2.6a7.5 7.5 0 0 0-1.7 1l-2.4-1-2 3.4L4.6 11a7.7 7.7 0 0 0 0 2l-2 1.6 2 3.4 2.4-1a7.5 7.5 0 0 0 1.7 1L9 21h6l.3-2.6a7.5 7.5 0 0 0 1.7-1l2.4 1 2-3.4z"/>',
        'check'     => '<circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.5 2.5L16 9"/>',
        'switch'    => '<path d="M7 16V4M4 7l3-3 3 3"/><path d="M17 8v12M14 17l3 3 3-3"/>',
        'home'      => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/>',
        'plus'      => '<path d="M12 5v14M5 12h14"/>',
    );
    return isset($icons[$name]) ? $icons[$name] : '';
}

// hb_nav_icon_svg()가 반환하는 <path>/<circle> 조각을 완전한 <svg> 태그로 감쌉니다.
// 통계 배지, 퀵액션 아이콘처럼 CSS로 stroke 색만 입히면 되는 단색 라인 아이콘에 씁니다.
function hb_nav_icon_svg_tag($name) {
    $body = hb_nav_icon_svg($name);
    if ($body === '') return '';
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$body.'</svg>';
}

function hb_nav_admin($active_group = null) {
    if (!defined('HB_URL')) return '';
    $current_script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : '';
    $items = array();
    foreach (hb_nav_admin_items() as $item) {
        $menu_id = isset($item['menu']) ? (string)$item['menu'] : '990100';
        if (hb_user_has_admin_auth($menu_id, 'r')) $items[] = $item;
    }
    $groups = array();
    foreach ($items as $item) $groups[$item['group']][] = $item;
    $dash_allowed = hb_user_has_admin_auth('990100', 'r');
    $operation_allowed = hb_user_has_admin_auth('990110', 'r');
    $dash_active = $dash_allowed && (strpos($current_script, '/admin/index.php') !== false);
    $home_url = $dash_allowed ? HB_URL.'/admin/index.php' : ($items ? $items[0]['url'] : G5_URL);

    $html = '<aside class="hb-side" aria-label="하루BGM 관리자 메뉴">';
    $html .= '<div class="hb-side-top">';
    $html .= '<a class="hb-side-brand" href="'.hb_e($home_url).'" aria-label="하루BGM 관리자"><span class="hb-side-brand-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'.hb_nav_icon_svg('broadcast').'</svg></span><span>하루BGM <b>관리자</b></span></a>';
    if ($operation_allowed) {
        $html .= '<a class="hb-admin-onair" id="hbAdminOnAir" href="'.HB_URL.'/admin/operation.php" data-api="'.HB_URL.'/admin/api_operation_schedule.php" data-base="'.HB_URL.'"><span class="hb-admin-onair-dot" aria-hidden="true"></span><span class="hb-admin-onair-copy"><small>ON AIR</small><strong id="hbAdminOnAirText">방송 상태 확인</strong></span><span class="hb-admin-onair-arrow" aria-hidden="true">›</span></a>';
    } else {
        $html .= '<span class="hb-admin-onair hb-admin-onair-static"><span class="hb-admin-onair-dot" aria-hidden="true"></span><span class="hb-admin-onair-copy"><small>HARU BGM</small><strong>관리 메뉴</strong></span></span>';
    }
    $html .= '<button type="button" class="hb-side-toggle" aria-controls="haruBgmAdminNav" aria-expanded="false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg><span>메뉴</span></button>';
    $html .= '</div>';
    $html .= '<button type="button" class="hb-side-backdrop" aria-label="메뉴 닫기"></button>';
    $html .= '<nav id="haruBgmAdminNav" class="hb-side-nav">';
    if ($dash_allowed) {
        $html .= '<a class="hb-side-link'.($dash_active ? ' is-active' : '').'" href="'.HB_URL.'/admin/index.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'.hb_nav_icon_svg('home').'</svg><span>대시보드</span></a>';
    }
    $html .= '<a class="hb-side-link hb-side-mobile-home" href="'.G5_URL.'"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'.hb_nav_icon_svg('switch').'</svg><span>사이트로</span></a>';
    foreach ($groups as $group_name => $group_items) {
        $html .= '<p class="hb-side-group">'.hb_e($group_name).'</p>';
        foreach ($group_items as $item) {
            $is_active = false;
            foreach ($item['match'] as $match_path) {
                if ($match_path !== '' && substr($current_script, -strlen($match_path)) === $match_path) { $is_active = true; break; }
            }
            $html .= '<a class="hb-side-link'.($is_active ? ' is-active' : '').'" href="'.hb_e($item['url']).'"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'.hb_nav_icon_svg($item['icon']).'</svg><span>'.hb_e($item['label']).'</span></a>';
        }
    }
    $html .= '</nav>';
    $html .= '<div class="hb-side-foot"><a href="'.G5_URL.'">사이트로 돌아가기</a></div>';
    $html .= '</aside>';
    return $html;
}

function hb_goto($url) {
    goto_url($url);
    exit;
}
