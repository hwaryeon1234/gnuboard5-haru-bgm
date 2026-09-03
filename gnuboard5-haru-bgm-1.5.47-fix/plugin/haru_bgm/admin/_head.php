<?php
if (!defined('_GNUBOARD_')) exit;
if (!defined('HB_G5_ADMIN_SHELL')) define('HB_G5_ADMIN_SHELL', true);
$hb_admin_title = isset($g5['title']) && $g5['title'] ? $g5['title'] : '하루BGM 관리자';
$g5['title'] = $hb_admin_title;
if (function_exists('add_stylesheet')) {
    add_stylesheet('<link rel="stylesheet" href="'.HB_URL.'/assets/haru_bgm_admin.css?ver='.rawurlencode(HB_ASSET_VERSION).'">', 99);
}
if (function_exists('add_javascript')) {
    add_javascript('<script defer src="'.HB_URL.'/assets/haru_bgm_ui.js?ver='.rawurlencode(HB_ASSET_VERSION).'"></script>', 90);
}
include_once G5_ADMIN_PATH.'/admin.head.php';
?>
<div class="hb-g5-admin-shell">
