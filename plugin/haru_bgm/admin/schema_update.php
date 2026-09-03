<?php
// DB 점검/복구는 시스템 점검 화면(health.php)에서 직접 처리합니다.
// 구버전 화면/즐겨찾기에서 이 파일을 호출해도 오류 페이지가 되지 않도록 안전하게 돌려보냅니다.
include_once('./_common.php');
goto_url(HB_URL.'/admin/health.php');
exit;
