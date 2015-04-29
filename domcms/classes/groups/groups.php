<?php

if(!defined('IN_SITE')) exit;

class groups extends base {
	
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
		$this->addListButton($this->modulesChain['link']['users'],'glyphicon-user');
		$this->addListButton($this->modulesChain['children']['groups_access'],'glyphicon-lock');
		return parent::view();
	}
	
}

?>