<?php
define('HB_JSON_MODE', true);
include_once('./_common.php');
hb_require_member_bgm_enabled();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$settings = hb_get_settings();
$priority_mode = isset($settings['priority_mode']) ? $settings['priority_mode'] : 'user_first';

$list = array();
$blocks = array();
$res = sql_query(hb_schedule_query($member['mb_id'], true));
while ($row = sql_fetch_array($res)) {
    if (hb_schedule_is_range($row)) {
        $range_items = hb_schedule_items($row['sc_id']);
        $items_payload = array();
        foreach ($range_items as $it) {
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
        if (!$items_payload) {
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
        $blocks[] = array(
            'kind' => 'range',
            'id' => 'range_'.$row['sc_id'],
            'log_id' => (int)$row['sc_id'],
            'scope' => $row['sc_scope'],
            'priority' => hb_priority_score('block', $row['sc_scope'], $priority_mode),
            'title' => $row['sc_title'],
            'start' => hb_time_hm($row['sc_time']),
            'end' => hb_time_hm($row['sc_end_time']),
            'days' => $row['sc_days'],
            'days_label' => hb_days_label($row['sc_days']),
            'mode' => 'sequence',
            'repeat' => (int)$row['sc_repeat'],
            'items' => $items_payload
        );
        continue;
    }
    $single_items = hb_schedule_items($row['sc_id']);
    $single_payloads = array();
    foreach ($single_items as $it) {
        $it_payload = hb_music_item_payload($it);
        $single_payloads[] = array(
            'block_item_id' => isset($it['si_id']) ? (int)$it['si_id'] : 0,
            'music_id' => (int)$it['mf_id'],
            'music_title' => $it['mf_title'],
            'volume' => (int)$it['mf_volume'],
            'source' => $it_payload['source'],
            'url' => $it_payload['url'],
            'youtube_id' => $it_payload['youtube_id']
        );
    }
    if (!$single_payloads) {
        $payload = hb_music_item_payload($row);
        $single_payloads[] = array(
            'block_item_id' => 0,
            'music_id' => (int)$row['mf_id'],
            'music_title' => $row['mf_title'],
            'volume' => (int)$row['mf_volume'],
            'source' => $payload['source'],
            'url' => $payload['url'],
            'youtube_id' => $payload['youtube_id']
        );
    }
    $first_payload = $single_payloads[0];
    $list[] = array(
        'kind' => count($single_payloads) > 1 ? 'single_set' : 'single',
        'id' => (int)$row['sc_id'],
        'music_id' => (int)$first_payload['music_id'],
        'scope' => $row['sc_scope'],
        'priority' => hb_priority_score('single', $row['sc_scope'], $priority_mode),
        'title' => $row['sc_title'],
        'music_title' => $first_payload['music_title'],
        'time' => hb_time_hm($row['sc_time']),
        'days' => $row['sc_days'],
        'days_label' => hb_days_label($row['sc_days']),
        'volume' => (int)$first_payload['volume'],
        'source' => $first_payload['source'],
        'url' => $first_payload['url'],
        'youtube_id' => $first_payload['youtube_id'],
        'items' => $single_payloads,
        'set_count' => count($single_payloads)
    );
}

$bres = sql_query(hb_block_query($member['mb_id'], true));
while ($b = sql_fetch_array($bres)) {
    $items = array();
    foreach (hb_block_items($b['bl_id']) as $item) {
        $payload = hb_music_item_payload($item);
        $items[] = array(
            'block_item_id' => (int)$item['bi_id'],
            'music_id' => (int)$item['mf_id'],
            'music_title' => $item['mf_title'],
            'volume' => (int)$item['mf_volume'],
            'source' => $payload['source'],
            'url' => $payload['url'],
            'youtube_id' => $payload['youtube_id']
        );
    }
    if (!$items) continue;
    $blocks[] = array(
        'kind' => 'block',
        'id' => (int)$b['bl_id'],
        'log_id' => (int)$b['bl_id'],
        'scope' => $b['bl_scope'],
        'priority' => hb_priority_score('block', $b['bl_scope'], $priority_mode),
        'title' => $b['bl_title'],
        'start' => hb_time_hm($b['bl_start_time']),
        'end' => hb_time_hm($b['bl_end_time']),
        'days' => $b['bl_days'],
        'days_label' => hb_days_label($b['bl_days']),
        'mode' => $b['bl_play_mode'],
        'repeat' => (int)$b['bl_repeat'],
        'items' => $items
    );
}

echo json_encode(array(
    'ok' => true,
    'server_date' => G5_TIME_YMD,
    'server_time' => G5_TIME_YMDHIS,
    'settings' => array(
        'priority_mode' => $priority_mode,
        'priority_label' => hb_setting_label_priority($priority_mode),
        'single_window_seconds' => max(30, min(600, (int)$settings['single_window_seconds'])),
        'fadeout_seconds' => max(0, min(20, (int)$settings['fadeout_seconds'])),
        'block_end_action' => in_array($settings['block_end_action'], array('fade_stop','finish_current'), true) ? $settings['block_end_action'] : 'fade_stop',
        'auto_refresh_seconds' => max(15, min(300, (int)$settings['auto_refresh_seconds'])),
        'show_debug_badge' => (int)$settings['show_debug_badge']
    ),
    'items' => $list,
    'blocks' => $blocks
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
