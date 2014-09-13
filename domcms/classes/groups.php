<?php

if(!defined('IN_SITE')) exit;

class groups extends base {
	
	protected $model = array (
		'groups' => array(
			'id' => array('type'=>'INT(255)','flags'=>'UNSIGNED NOT NULL AUTO_INCREMENT','inner_keys'=>'PRIMARY'),
			'title' => array('type'=>'VARCHAR(255)','default'=>''),
		),
		'groups_access' => array(
			'id_groups' => array('type'=>'INT(255)','flags'=>'UNSIGNED','outer_keys'=>'groups(id) ON UPDATE CASCADE ON DELETE CASCADE'),
			'id_modules_fields' => array('type'=>'INT(255)','flags'=>'UNSIGNED','outer_keys'=>'modules_fields(id) ON UPDATE CASCADE ON DELETE CASCADE'),
			'access_read' => array('type'=>'INT(10)','default'=>1,'flags'=>'UNSIGNED'),
			'access_write' => array('type'=>'INT(10)','default'=>0,'flags'=>'UNSIGNED'),
			'access_delete' => array('type'=>'INT(10)','default'=>0,'flags'=>'UNSIGNED'),
		),
	);
	
	static function getGroupsList() {
		global $db;
		return $db->get_list('SELECT id FROM groups ORDER BY id');
	}
	
}

?>