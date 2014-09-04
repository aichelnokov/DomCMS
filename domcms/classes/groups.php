<?php

if(!defined('IN_SITE')) exit;

class groups extends base {
	
	private $model = array (
		'groups' => array(
			'id' => array('type'=>'INT(255)','default'=>0,'flags'=>'NOT NULL UNSIGNED AUTO_INCREMENT','inner_keys'=>'UNIQUE PRIMARY'),
			'name' => array('type'=>'VARCHAR(255)','default'=>''),
		),
		'groups_access' => array(
			'id_groups' => array('type'=>'INT(255)','default'=>0,'flags'=>'UNSIGNED','outer_keys'=>'groups(id) ON UPDATE CASCADE ON DELETE CASCADE'),
			'name_table' => array('type'=>'VARCHAR(255)','default'=>''),
			'name_field' => array('type'=>'VARCHAR(255)','default'=>''),
			'access_read' => array('type'=>'INT(10)','default'=>1,'flags'=>'UNSIGNED'),
			'access_write' => array('type'=>'INT(10)','default'=>0,'flags'=>'UNSIGNED'),
			'access_delete' => array('type'=>'INT(10)','default'=>0,'flags'=>'UNSIGNED'),
		),
	);
	
	
}

$groups = base::j('groups','groups');
?>