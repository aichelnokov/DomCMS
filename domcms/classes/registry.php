<?php

if(!defined('IN_SITE')) exit;

class registry {
	function objectExists($name) {
		return isset($this->{$name});
	}
	
	function add(&$object) {
		$this->{$object->name}=null;
		$this->{$object->name}=$object;
	}
	
	function __get($name) {
		return null;
	}
	
	static function &get_registry(){
		static $registry;
		if(empty($registry)) $registry=new registry();
		return $registry;
	}
}

/*function &get_registry(){
	static $registry;
	if(empty($registry)) $registry=new registry();
	return $registry;
}*/

?>