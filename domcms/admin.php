<?php
header('Content-Type: text/html; charset=utf-8');
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

define('IN_SITE',true);
define('ROOT_DIR',realpath(__DIR__.'\\..').'\\');

require_once(ROOT_DIR.'domcms\loader.php');

if (!$users->isValid()) $template->file = 'login.html';
else {
	// get global vars
	$module = base::getvar('module','modules');
	$mode = base::getvar('mode',$module);
	$action = base::getvar('action','view');
	// include class
	if (file_exists(ROOT_DIR.'domcms/classes/'.$module.'.php')) {
		require_once(ROOT_DIR.'domcms/classes/'.$module.'.php');
		$admin = base::j($module,$module);
		$admin->domcms($module,$mode,$action);
	}
	if (empty($template->file)) $template->file = 'domcms.html';
	$template->domcms = $admin;
}
$template->users = $users;

$template->path = '/domcms/templates/';
$template->render(false,'404.html');

?>