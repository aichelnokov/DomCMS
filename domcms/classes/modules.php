<?php

if(!defined('IN_SITE')) exit;

class modules extends base {
	
	protected $model = array (
		'modules' => array(
			'id' => array('type'=>'INT(255)','flags'=>'UNSIGNED NOT NULL AUTO_INCREMENT','inner_keys'=>'PRIMARY'),
			'name' => array('type'=>'VARCHAR(255)','default'=>''),
			'table' => array('type'=>'VARCHAR(255)','default'=>''),
		),
		'modules_fields' => array(
			'id' => array('type'=>'INT(255)','flags'=>'UNSIGNED NOT NULL AUTO_INCREMENT','inner_keys'=>'PRIMARY'),
			'id_modules' => array('type'=>'INT(255)','default'=>0,'flags'=>'UNSIGNED','outer_keys'=>'modules(id) ON UPDATE CASCADE ON DELETE CASCADE'),
			'name' => array('type'=>'VARCHAR(255)','default'=>''),
		),
	);
	
	function init() {
		return parent::init();
	}
	
}

$modules = base::j('modules','modules');
?>