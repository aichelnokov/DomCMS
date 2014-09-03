<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

define('IN_SITE',true);
define('ROOT_DIR',realpath(__DIR__.'\\..').'\\');
echo ROOT_DIR;

require_once(ROOT_DIR.'domcms\loader.php');

$template->file = 'login.html';

$template->path = '/domcms/templates/';
$template->render(false,'404.html');

?>