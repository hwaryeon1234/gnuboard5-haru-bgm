<?php
// 하루BGM 사이트 전체 방송 bootstrap.
if (!defined('_GNUBOARD_')) return;
$hb_sitewide_file = (defined('G5_PLUGIN_PATH') ? G5_PLUGIN_PATH : dirname(__DIR__).'/plugin').'/haru_bgm/sitewide.php';
if (is_file($hb_sitewide_file)) include_once($hb_sitewide_file);
unset($hb_sitewide_file);
