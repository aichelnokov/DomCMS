<?php
header('Content-Type: text/html; charset=utf-8');
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

define('IN_SITE',true);
define('ROOT_DIR',realpath(__DIR__.'\\..').'\\');

require_once(ROOT_DIR.'domcms\loader.php');

if (!$users->isValid()) $template->file = 'login.html';
else $template->file = 'domcms.html';


$template->users = $users;

$template->path = '/domcms/templates/';
$template->render(false,'404.html');

?>