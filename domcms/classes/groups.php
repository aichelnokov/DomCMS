<?php

if(!defined('IN_SITE')) exit;

class groups extends base {
	
	public $model = array (
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
	
	public $modelRelations = array(
		'groups' => array(
			'link' => array(
				'id_groups' => 'users',
			),
		),
	);
	
	static function getGroupsList() {
		global $db;
		return $db->get_list('SELECT id FROM groups ORDER BY id');
	}
	
	static function getOwnerId() {
		static $owner;
		if (empty($owner)) {
			global $db;
			$owner = $db->get_single('SELECT id FROM groups WHERE title="Владелец" LIMIT 1');
		}
		return $owner;
	}
	
	static function getUnregisteredId() {
		static $unregistered;
		if (empty($unregistered)) {
			global $db;
			$unregistered = $db->get_single('SELECT id FROM groups WHERE title="Не зарегистрированные" LIMIT 1');
		}
		return $unregistered;
	}
	
	function groups_view() {
		$this->addListButton($this->modulesChain['children']['groups_access'],'glyphicon-lock');
		return parent::view();
	}
	
}

?>