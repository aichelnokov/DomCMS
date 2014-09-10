<?php

if(!defined('IN_SITE')) exit;

class modules extends base {
	
	protected $model = array (
		'modules' => array(
			'id' => array('type'=>'INT(255)','flags'=>'UNSIGNED NOT NULL AUTO_INCREMENT','inner_keys'=>'PRIMARY'),
			'class' => array('type'=>'VARCHAR(255)','default'=>''),
			'title' => array('type'=>'VARCHAR(255)','default'=>''),
			'tbl' => array('type'=>'VARCHAR(255)','default'=>''),
		),
		'modules_fields' => array(
			'id' => array('type'=>'INT(255)','flags'=>'UNSIGNED NOT NULL AUTO_INCREMENT','inner_keys'=>'PRIMARY'),
			'id_modules' => array('type'=>'INT(255)','default'=>0,'flags'=>'UNSIGNED','outer_keys'=>'modules(id) ON UPDATE CASCADE ON DELETE CASCADE'),
			'name' => array('type'=>'VARCHAR(255)','default'=>''),
			'title' => array('type'=>'VARCHAR(255)','default'=>''),
		),
		'modules_menus' => array(
			'id' => array('type'=>'INT(255)','flags'=>'UNSIGNED NOT NULL AUTO_INCREMENT','inner_keys'=>'PRIMARY'),
			'id_parent' => array('type'=>'INT(255)','flags'=>'UNSIGNED NOT NULL','outer_keys'=>'modules_menus(id) ON UPDATE CASCADE ON DELETE CASCADE'),
			'id_modules' => array('type'=>'INT(255)','default'=>0,'flags'=>'UNSIGNED','outer_keys'=>'modules(id) ON UPDATE CASCADE ON DELETE CASCADE'),
			'title' => array('type'=>'VARCHAR(255)','default'=>''),
			'mode' => array('type'=>'VARCHAR(255)','default'=>''),
			'action' => array('type'=>'VARCHAR(255)','default'=>''),
			'order' => array('type'=>'INT(255)','default'=>0,'flags'=>'UNSIGNED NOT NULL'),
		),
	);
	
	function init() {
		return parent::init();
	}
	
	function checkModule($class,$table,$fields) {
		if ($id = $this->existsModule($class,$table)) {
		} else {
			$this->registry->db->insert('modules',array('class'=>$class,'tbl'=>$table,'title'=>$table));
			$id = $this->registry->db->insert_id();
			$this->fillModuleFields($id,$fields);
		}
	}
	
	function fillModuleFields($id,$fields) {
		$fill_array = array();
		foreach ($fields as $k => $v) {
			$fill_array[] = array('id_modules'=>$id,'name'=>$k,'title'=>$k);
		}
		return $this->registry->db->insert('modules_fields',$fill_array);
	}
	
	function existsModule($class,$table) {
		return (0<$id=$this->registry->db->get_single("SELECT id FROM modules WHERE class='$class' AND tbl='$table'"))?$id:false;
	}
	
}

$modules = base::j('modules','modules');
?>