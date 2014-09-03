<?php

if(!defined('IN_SITE')) exit;

date_default_timezone_set("Europe/Moscow");
session_start();
define('MAGIC_QUOTES', ( get_magic_quotes_gpc() ) ? true : false );
srand(time());

$config = array();

require_once(__DIR__.'\classes\registry.php');
require_once(__DIR__.'\classes\base.php');
// clases with create objects
// Example:
// global $objects
require_once(__DIR__.'\classes\debug.php');
require_once(__DIR__.'\classes\templater.php');
require_once(__DIR__.'\classes\db_mysqli.php');

?>