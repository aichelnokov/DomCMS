<?php

if(!defined('IN_SITE')) exit;

class modules extends base {
	
	protected $model = array (
		'modules' => array(
			'id' => array('type'=>'INT(255)','flags'=>'UNSIGNED NOT NULL AUTO_INCREMENT','inner_keys'=>'PRIMARY'),
			'class' => array('type'=>'VARCHAR(255)','default'=>''),
			'title' => array('type'=>'VARCHAR(255)','default'=>''),
			'tbl' => array('type'=>'VARCHAR(255)','default'=>''),
			'controls_view' => array('type'=>'VARCHAR(255)','default'=>''),
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
			'sort' => array('type'=>'INT(255)','default'=>0,'flags'=>'UNSIGNED NOT NULL'),
		),
	);
	
	function init() {
		return parent::init();
	}
	
	function checkModule($class,$table,$fields) {
		if ($id = $this->existsModule($class,$table)) {
		} else {
			$id = $this->registry->db->insert('modules',array('class'=>$class,'tbl'=>$table,'title'=>$table));
			$this->fillModuleFields($id,$fields);
		}
	}
	
	function fillModuleFields($id,$fields) {
		$access_array = array();
		$groups_list = groups::getGroupsList();
		foreach ($fields as $k => $v) {
			$id_modules_fields = $this->registry->db->insert('modules_fields',array('id_modules'=>$id,'name'=>$k,'title'=>$k));
			foreach ($groups_list as $group)
				$access_array[] = array(
					'id_groups' => $group,
					'id_modules_fields' => $id_modules_fields,
					'access_read' => 1,
					'access_write' => 0,
					'access_delete' => 0,
				);
			unset($id_modules_fields);
		}
		return $this->registry->db->insert('groups_access',$access_array);
	}
	
	function existsModule($class,$table) {
		return (0<$id=$this->registry->db->get_single("SELECT id FROM modules WHERE class='$class' AND tbl='$table'"))?$id:false;
	}
	
	function allow(&$object) {
		$object->addCrumb($this->registry->db->get_single('SELECT DISTINCT title FROM modules WHERE class="'.$object->name.'" LIMIT 1'),'/domcms/?module='.$object->name);
		if (method_exists($object,$object->mode.'_'.$object->action)) $return = $object->mode.'_'.$object->action;
		else if (method_exists($object,$object->action)) $return = $object->action;
		else if (method_exists($object,$object->mode)) $return = $object->mode;
		else return false;
		return $object->{$return}();
	}
	
	function modules_fields_view() {
		$this->addCrumb('Поля модуля',$this->url['module_mode']);
		return parent::view();
	}
	
	function modules_edit($add=false) {
		return parent::edit($add);
	}
	
	function modules_view() {
		$this->pagination = false;
		$this->sortable = false;
		return parent::view();
	}
	
}

?>