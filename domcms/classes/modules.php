<?php

if(!defined('IN_SITE')) exit;

class modules extends base {
	
	protected $modulesRegistry = array();
	
	protected $model = array (
		'modules' => array(
			'id' => array('type'=>'INT(255)','flags'=>'UNSIGNED NOT NULL AUTO_INCREMENT','inner_keys'=>'PRIMARY'),
			'class' => array('type'=>'VARCHAR(255)','default'=>''),
			'title' => array('type'=>'VARCHAR(255)','default'=>''),
			'tbl' => array('type'=>'VARCHAR(255)','default'=>''),
			'controls_view' => array('type'=>'VARCHAR(255)','default'=>'edit delete'),
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
		$this->modulesRegistry = $this->registry->db->get_data('SELECT m.id, m.title, m.class, m.controls_view, m.tbl, m.tbl as k FROM modules AS m',true,'k');
		return parent::init();
	}
	
	function checkModule($class,$table,$fields) {
		if ($module = $this->existsModule($class,$table,true)) {
			
		} else {
			$id = $this->registry->db->insert('modules',array('class'=>$class,'tbl'=>$table,'title'=>$table));
			$this->fillModuleFields($id,$fields);
		}
	}
	
	function existsModule($class,$table,$full=false) {
		if ($full==false)
			return (0<$id=$this->registry->db->get_single("SELECT id FROM modules WHERE class='$class' AND tbl='$table' LIMIT 1"))?$id:false;
		else
			return (0<$objects=$this->registry->db->get_data("SELECT * FROM modules WHERE class='$class' AND tbl='$table' LIMIT 1",true,'tbl'))?$objects:false;
	}
	
	function getModulesChainParents($chain,$model) {
		$parents = array();
		foreach ($model[$chain['tbl']] as $k => $v) {
			if (!empty($v['outer_keys'])) {
				if (preg_match('/'.$chain['tbl'].'\(/',$v['outer_keys'])>0) continue; // id_parent в этой же таблице
				$outer_mode = substr($v['outer_keys'],0,strpos($v['outer_keys'],'('));
				if (!empty($model[$outer_mode])) {
					$parents = $this->registry->modules->modulesRegistry[$outer_mode];
					$parents['parents'] = $this->getModulesChainParents($parents,$model);
				}
			}
		}
		return $parents;
	}
	
	function getModulesChainChildrens($chain,$model) {
		$children = array();
		foreach ($model as $k => $v) {
			if ($k==$chain['tbl']) continue;
			foreach ($v as $k1 => $v1) {
				if (isset($v1['outer_keys'])) {
					if (preg_match('/'.$chain['tbl'].'\(/',$v1['outer_keys'])>0) {
						$children[$k] = $this->registry->modules->modulesRegistry[$k];
						$children[$k]['children'] = $this->getModulesChainChildrens($children[$k],$model);
					}
				}
			}
		}
		return $children;
	}
	
	function getModulesChain($domcms) {
		if (empty($domcms)) return false;
		$chain = array();
		foreach ($domcms->model as $k => $v) {
			if ($k === $domcms->module && $domcms->module === $domcms->mode) {
				$chain = $this->registry->modules->modulesRegistry[$k];
				$chain['children'] = $this->getModulesChainChildrens($chain,$domcms->model); // Возвращение цепочки с дочерними модулями
				return $chain; // Сразу вернуть корневое звено
			} elseif ($k === $domcms->mode) {
				$chain = $this->registry->modules->modulesRegistry[$k];
				$chain['children'] = $this->getModulesChainChildrens($chain,$domcms->model); // Возвращение цепочки с дочерними модулями
				$chain['parents'] = $this->getModulesChainParents($chain,$domcms->model); // Возвращение цепочки с родительскими модулями
			}
		}
		return $chain;
	}
	
	function fillModuleFields($id,$fields) {
		if (empty($id) or empty($fields)) return false;
		$access_array = array();
		$groups_list = groups::getGroupsList();
		$groups_owner = groups::getOwnerId();
		foreach ($fields as $k => $v) {
			$id_modules_fields = $this->registry->db->insert('modules_fields',array('id_modules'=>$id,'name'=>$k,'title'=>$k));
			foreach ($groups_list as $group)
				$access_array[] = array(
					'id_groups' => $group,
					'id_modules_fields' => $id_modules_fields,
					'access_read' => 1,
					'access_write' => ( !empty($groups_owner) && $groups_owner==$group ) ?1 :0,
					'access_delete' => ( !empty($groups_owner) && $groups_owner==$group ) ?1 :0,
				);
			unset($id_modules_fields);
		}
		return $this->registry->db->insert('groups_access',$access_array);
	}
	
	function allow(&$object) {
		$object->addCrumb($object->title,'/domcms/?module='.$object->name);
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