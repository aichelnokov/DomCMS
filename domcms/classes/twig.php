<?php

if(!defined('IN_SITE')) exit;

class J_Twig {

	public $paths = array();
	
	function __construct() {
		require_once(ROOT_DIR.'\Twig\Autoloader.php');
		Twig_Autoloader::register();
	}

	function getHTMLFromFile($template,$vars) {
		$loader = new Twig_Loader_Filesystem($this->paths);
		$twig = new Twig_Environment($loader, array(
			'cache' => ROOT_DIR.'/cache',
			'auto_reload' => true
		));
		$t = $twig->loadTemplate($template);
		return $t->render($vars);
	}
	
	function getHTMLFromText($text,$vars) {
		$loader = new Twig_Loader_String();
		$twig = new Twig_Environment($loader, array(
			'cache' => ROOT_DIR.'/cache',
			'auto_reload' => true
		));
		$t = $twig->loadTemplate($text);
		return $t->render($vars);
	}
}
?>
