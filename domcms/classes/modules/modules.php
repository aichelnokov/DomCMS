<?php

if(!defined('IN_SITE')) exit;

class modules extends base {
	
	protected $modulesRegistry = array();
	
	function getModulesRegistry() {
		$this->modulesRegistry = $this->registry->db->get_data('SELECT m.id, m.title, m.class, m.fields_view, m.buttons_view, m.controls_view, m.buttons_edit, m.controls_edit, m.tbl, m.tbl as k FROM modules AS m',true,'k');
		$this->modulesRegistryById = array();
		foreach ($this->modulesRegistry as $k => $v) {
			$this->modulesRegistry[$k]['fields_view'] = explode(' ',$v['fields_view']);
			$this->modulesRegistry[$k]['controls_view'] = explode(' ',$v['controls_view']);
			$this->modulesRegistry[$k]['buttons_view'] = explode(' ',$v['buttons_view']);
			$this->modulesRegistry[$k]['controls_edit'] = explode(' ',$v['controls_edit']);
			$this->modulesRegistry[$k]['buttons_edit'] = explode(' ',$v['buttons_edit']);
			$this->modulesRegistryById[$v['id']] = &$this->modulesRegistry[$k];
		}
		return true;
	}
	
	function getModulesById($id=false) {
		if (!$id) return false;
		return !empty($this->modulesRegistryById[$id]) ? $this->modulesRegistryById[$id] : false;
	}
	
	function init() {
		$this->getModulesRegistry();
		return parent::init();
	}
	
	function checkModule($class,$table,$fields,$relations) {
		if ($module = $this->existsModule($class,$table,true)) {
			//echo 'Module $class='.$class.' &mode='.$table.' is exists!<br>';
		} else {
			$id = $this->registry->db->insert('modules',array('class'=>$class,'tbl'=>$table,'title'=>$table));
			$this->fillModuleFields($id,$fields);
		}
		if (!empty($relations)) {
			foreach ($relations as $k => $v) {
				$this->modulesRegistry[$k]['link'][$table] = $this->modulesRegistry[$table];
			}				
		}
	}
	
	function existsModule($class,$table,$full=false) {
		if (!empty($this->modulesRegistry[$table]))
			return ($full===false) ? $this->modulesRegistry[$table]['id'] : $this->modulesRegistry[$table];
		else {
			if ($full==false) {
					return (0<$id=$this->registry->db->get_single("SELECT id FROM modules WHERE class='$class' AND tbl='$table' LIMIT 1"))?$id:false;
			} else {
				return (0<$objects=$this->registry->db->get_data("SELECT * FROM modules WHERE class='$class' AND tbl='$table' LIMIT 1",true,'tbl'))?$objects:false;
			}			
		}
	
	}
	
	function fillChainRelations(&$chain,$model) {
		if (empty($chain['relations'])) $chain['relations'] = array();
		if (empty($chain['link'])) $chain['link'] = array();
		foreach ($model[$chain['tbl']] as $k => $v) {
			if (empty($v['outer_keys'])) continue;
			$relation_table = substr($v['outer_keys'],0,strpos($v['outer_keys'],'('));
			if ($relation_table==$chain['tbl']) {
				$chain['tree'] = $this->modulesRegistry[$relation_table];
				$chain['tree']['tree'] = $this->modulesRegistry[$relation_table];
			} else {
				if (preg_match('/ON DELETE SET NULL/',$v['outer_keys'])>0) {
					$chain['link'][$k] = $this->modulesRegistry[$relation_table]; // просто отдельная связь
				} elseif (preg_match('/ON DELETE CASCADE/',$v['outer_keys'])>0 AND preg_match('/'.$relation_table.'/',$chain['tbl'])==0) { // связь многая ко многим
					$chain['relations'][$k] = $this->getModulesChainParents($chain,$model);
				}
			}
		}
	}
	
	function getModulesChainParents(&$chain,$model) {
		$parents = array();
		foreach ($model[$chain['tbl']] as $k => $v) {
			if (empty($v['outer_keys'])) continue;
			$relation_table = substr($v['outer_keys'],0,strpos($v['outer_keys'],'('));
			if ($relation_table==$chain['tbl']) {
				$chain['tree'] = $this->modulesRegistry[$relation_table];
				continue; 
			}
			if (preg_match('/ON DELETE CASCADE/',$v['outer_keys'])>0 AND preg_match('/'.$relation_table.'/',$chain['tbl'])>0)
				if (!empty($model[$relation_table])) {
					$parents = $this->modulesRegistry[$relation_table];
					$parents['parents'] = $this->getModulesChainParents($parents,$model);
				}
		}
		return $parents;
	}
	
	function getModulesChainChildren(&$chain,$model) {
		$children = array();
		foreach ($model as $k => $v) {
			if ($k==$chain['tbl']) continue;
			foreach ($v as $k1 => $v1) {
				if (isset($v1['outer_keys'])) {
					if (preg_match('/'.$chain['tbl'].'\(/',$v1['outer_keys'])>0) {
						$children[$k] = $this->modulesRegistry[$k];
						$children[$k]['children'] = $this->getModulesChainChildren($children[$k],$model);
					}
				}
			}
		}
		return $children;
	}
	
	function getModulesChain() {
		$domcms = $this->registry->{$_SESSION['domcms']};
		if (empty($domcms)) return false;
		$chain = array();
		foreach ($domcms->model as $k => $v) {
			if ($k === $domcms->mode) {
				$chain = $this->modulesRegistry[$k];
				$chain['parents'] = $this->getModulesChainParents($chain,$domcms->model); // Возвращение цепочки с родительскими модулями
				$chain['children'] = $this->getModulesChainChildren($chain,$domcms->model); // Возвращение цепочки с дочерними модулями
				$this->fillChainRelations($chain,$domcms->model);
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
		if (method_exists($object,$object->mode.'_'.$object->action)) $return = $object->mode.'_'.$object->action;
		else if (method_exists($object,$object->action)) $return = $object->action;
		else if (method_exists($object,$object->mode)) $return = $object->mode;
		else return false;
		return $object->{$return}();
	}
	
	function modules_fields_view() {
		$this->addFilter($this->modulesChain['parents'],false);
		return parent::view();
	}
	
	function modules_menus_add() {
		return $this->modules_menus_edit(true);
	}
	
	function modules_menus_edit($add=false) {
		$this->filters['icon'] = array(
			'values' => array(
				'' => '-',
				'align-justify' => 'align-justify',
				'th-list' => 'th-list',
				'plus' => 'plus',
				'minus' => 'minus',
				'th-large' => 'th-large',
				'envelope' => 'envelope',
				'cog' => 'cog',
				'star' => 'star',
				'user' => 'user',
				'lock' => 'lock',
				'stats' => 'stats',
				'shopping-cart' => 'shopping-cart',
				'folder-open' => 'folder-open',
			),
		);
		return parent::edit($add);
	}
	
	function modules_menus_view() {
		$this->sortable = 'sort';
		return parent::view();
	}
	
	function modules_edit($add=false) {
		return parent::edit($add);
	}
	
	function modules_view() {
		$this->pagination = false;
		$this->addLink($this->modulesChain['children']['modules_menus']);
		$this->addListButton($this->modulesChain['children']['modules_fields']);
		return parent::view();
	}
	
}

?>