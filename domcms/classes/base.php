<?php

if(!defined('IN_SITE')) exit;

class base {

	public $name='';//Имя модуля
	public $cache='';//Кэш
	protected $registry=null;	//Реестр объектов для ускорения доступа
	
	// Конструктор, создает новый объект, устанавливает его настройки и режим работы
	public function __construct($name,$config=array()) {
		$this->name=$name;
		base::extend($this,array_merge(array(),$config));
		$this->registry=registry::get_registry();
		$this->registry->{$this->name}=null;
		$this->registry->{$this->name}=&$this;
		return $this->checkModel();
	}
	
	function init() {
		return true;
	}
	
	function restore() {
		$this->registry=registry::get_registry();
		$this->registry->{$this->name}=null;
		$this->registry->{$this->name}=&$this;
		
		return $this->checkModel();
	}
	
	// Если существует метод set{name} вызываем его и передаем ему параметр value
	// Иначе просто создаем поле
	function __set($name,$value) {
		if(method_exists($this,'set'.$name)) $this->{'set'.$name}($value);
		else $this->{$name}=$value; 
	}
	
	function __destruct() {
		$this->registry->{$this->name}=null;
		$this->registry=null;
		if($this->cache=='session') {
			unset($this->crumbs);
			$_SESSION['OBJECTS'][$this->name]=serialize($this);
		}
	}
	
	function addObject($component) {
		if ($this->current_model=$this->registry->users->checkAccess($this->mode,$this->model[$component],'access_write')) {
			$this->current_model = array_intersect_key($this->current_model,$_POST);
			$this->edit_data = array();
			foreach ($this->current_model as $k => $v) {
				$this->edit_data[$k] = base::getvar($k,'');
				if (empty($this->edit_data[$k]))
					if (!isset($v['default']))
						unset($this->edit_data[$k]);
			}
			$this->registry->db->insert($this->mode,$this->edit_data);
			unset($this->edit_data);
			$this->id = $this->registry->db->insert_id();
			$this->addMessage('Запись добавлена','success');
			return true;
		}
	}
	
	function updateObject($component) {
		if ($this->current_model=$this->registry->users->checkAccess($this->mode,$this->model[$component],'access_write')) {
			$this->current_model = array_intersect_key($this->current_model,$_POST);
			$this->edit_data = array();
			foreach ($this->current_model as $k => $v) {
				$this->edit_data[$k] = base::getvar($k,isset($v['default'])?$v['default']:'');
			}
			$this->registry->db->update($this->mode,$this->edit_data,'id='.$this->id);
			unset($this->edit_data);
			$this->addMessage('Запись обновлена','success');
			return true;
		}
	}
	
	function getQuery($model,$params,$mode='') {
		if ($mode=='') $mode = $this->mode;
		$q = 'SELECT DISTINCT '.$mode.'.'.implode(', '.$mode.'.',array_keys($model)).' FROM '.$mode;
		if ($w=$this->registry->db->build_array('SELECT',$params)) $q .= ' WHERE '.$w;	
		return $q;
	}
	
	function getEmptyObject($model=array()) {
		if (empty($model)) return $model;
		$ret = array();
		foreach ($model as $k => $v) {
			$ret[$k] = isset($v['default']) ? $v['default'] : '';
		}
		return $ret;
	}
	
	function getObject($component) {
		if (empty($component)) return false;
		if ($this->current_model=$this->registry->users->checkAccess($this->mode,$this->model[$component],'access_read')) {
			if (!empty($this->modulesChain['tree']))
				$this->addFilter($this->modulesChain['tree'],true);
			if (!empty($this->modulesChain['parents']))
				$this->addFilter($this->modulesChain['parents'],true);
			//if (!empty($this->modulesChain['children']))
			//if (!empty($this->modulesChain['relations']))
			if (!empty($this->id)) {
				$q = $this->getQuery($this->current_model,array('id'=>$this->id));
				$q .= ' LIMIT 1';
				$ret = $this->registry->db->get_data($q,false);
			} else
				$ret = $this->getEmptyObject($this->current_model);
			foreach ($ret as $k => $v) {
				if (!empty($this->filters[$k]['value']))
					$ret[$k] = $this->filters[$k]['value'];
			}
			return $ret;
		} // else return false
	}
	
	function getObjects($component='',$params=array(),$order='') {
		if (empty($component)) return false;
		if ($current_model=$this->registry->users->checkAccess($this->mode,$this->model[$component],'access_read')) {
			$current_model = array_intersect_key($current_model,$this->listFields);
			$q = $this->getQuery($current_model,$params);
				if ($order!='') $q .= ' ORDER BY '.$order;
			return $this->registry->db->get_data($q);
		} else {
			// return error access to this mode
		}
	}
	
	function getObjectsRecursive($component='',$params=array(),$order='',$current_model=array()) {
		if (empty($component)) return false;
		if (empty($current_model))
			$current_model = $this->registry->users->checkAccess($this->mode,$this->model[$component],'access_read');
		if (!empty($current_model)) {
			$q = $this->getQuery($current_model,$params);
			if (empty($params['id_'.$component]))
				$q = strtr($q,array('=NULL'=>' IS NULL'));
			if ($order!='') $q .= ' ORDER BY '.$order;
			$ret = $this->registry->db->get_data($q);
			foreach ($ret as $k => $v) {
				$params['id_'.$component] = $v['id'];
				$ret[$k]['children'] = $this->getObjectsRecursive($component,$params,$order,$current_model);
			}
			return $ret;
		} else {
			// return error access to this mode
		}
	}
	
	function getTree(&$chain,$order='') {
		if (empty($chain)) return false;
		$current_model = $this->registry->users->checkAccess($chain['tbl'],array('id'=>array(),'title'=>array(),'id_'.$chain['tbl']=>array()),'access_read');
		if (!empty($current_model)) {
			$params = array();
			if (!empty($this->id))
				$params['id'] = '!='.$this->id;
			$q = $this->getQuery($current_model,$params,$chain['tbl']);
			$q .= ' ORDER BY id_'.$chain['tbl'].','.(!empty($order)?$order:'id');
			$list = $this->registry->db->get_data($q,true,'id');
			$ret = $this->getTreeRecursive($list,'id_'.$chain['tbl']);
			return $ret;
		}
	}
	
	function getTreeRecursive($list=array(),$field='',$value=NULL) {
		$ret = array();
		if (empty($list) OR empty($field)) return $ret;
		foreach ($list as $k => $v) {
			if ($v[$field]==$value) {
				$ret[$k] = $list[$k];
				unset($list[$k]);
				$ret[$k]['id'] = $k;
			}
		}
		foreach ($ret as $k => $v)
			$ret[$k]['children'] = $this->getTreeRecursive($list,$field,$k);
		return $ret;
	}
	
	// Проверяет в базе корректность таблиц моделей объекта (все модули)
	function checkModel() {
		if (empty($this->model)) return true;
		foreach ($this->model as $k => $v) {
			if (!$this->registry->db->existsTable($k)) {
				$this->registry->db->createTable($k,$v);
			} else {
				if ($alter_fields = $this->registry->db->complianceTable($k,$v))
					$this->registry->modules->fillModuleFields($this->registry->modules->existsModule($this->name,$k),$alter_fields);
			}
			$this->registry->modules->checkModule($this->name,$k,$v,!empty($this->relations[$k])?$this->relations[$k]:false);
		}
		return true;
	}
	
	function domcms($module,$mode,$action) {
		$this->module = $module;
		$this->mode = $mode;
		$this->action = $action;
		if ($id=base::getvar('id','')) { $this->id = $id; unset($id); }
		$this->page = base::getvar('page',1);
		$this->count_on_page = 20;
		$this->sortable = '';
		$this->listFields = array(
			'id' => array('type'=>'INT(255)'),
			'title' => array('type'=>'VARCHAR(255)'),
			'visible' => array('type'=>'INT(1)'),
			'sort' => array('type'=>'INT(255)'),
			'date' => array('type'=>'INT(255)'),
		);
		$this->menu = 
		$this->data = 
		$this->buttons = 
		$this->filters = 
			array();
		$this->url = array(
			'links' => array(),
			'listButtons' => array(),
			//'photos' => '/domcms/?module='.$this->module.'&mode='.$this->mode.'_photos&action=view&id_parent=%ID_PARENT%',
			'add' => '/domcms/?module='.$this->module.'&mode='.$this->mode.'&action=add',
			'edit' => '/domcms/?module='.$this->module.'&mode='.$this->mode.'&action=edit&id=%ID%',
			'delete' => '/domcms/?module='.$this->module.'&mode='.$this->mode.'&action=delete&id=%ID%',
			'module' => '/domcms/?module='.$this->module,
			'module_mode' => '/domcms/?module='.$this->module.'&mode='.$this->mode,
			'tail' => '',
		);
		if ($this->modulesChain = $this->registry->modules->getModulesChain()) {
			if (!empty($this->modulesChain['tree']))
				$this->url['add_children'] = $this->url['add'].'&id_'.$this->mode.'=%ID_PARENT%';
			$this->addCrumbs();
		}
		$this->title = $this->registry->db->get_single('SELECT DISTINCT title FROM modules WHERE class="'.$this->name.'" LIMIT 1');
		return $this->registry->modules->allow($this);
	}
	
	function edit($add=false) {
		if ($submit=base::getvar('form_submit','')) {
			if (true === $add ? $this->addObject($this->mode) : $this->updateObject($this->mode)) 
				base::redirect(strtr($this->url['edit'],array('%ID%'=>$this->id)));
		} else {
			$this->data['item'] = $this->getObject($this->mode);
			//foreach ($this->filters as $k => $v)
				//if (isset($this->data['item'][$k]))
					//if ($this->data['item'][$k] == '')
						//$this->data['item'][$k] = $v['value'];
		}
		$this->addButtons($this->modulesChain['buttons_edit']);
		//$this->addControls($this->modulesChain['controls_edit']);
		$this->addCrumb((!empty($this->data['item']['title'])?'Просмотр элемента &laquo;'.$this->data['item']['title'].'&raquo;':'Просмотр элемента'));
		if (empty($this->registry->template->file)) $this->registry->template->file = 'edit_'.$this->name.'_'.$this->mode.'.html';
	}
	
	function add() { return $this->edit(true); }
	
	function view() {
		$this->current_model = $this->registry->users->checkAccess($this->mode,$this->model[$this->mode],'access_read');
		$params = array();
		foreach($this->filters as $k => $v)
			if ($v['value']>0)
				$params = array_merge($params,array($k=>$v['value']));
		if (empty($this->modulesChain['tree']))
			$this->data['list'] = $this->getObjects($this->mode,$params,$this->sortable);
		else {
			$params['id_'.$this->mode] = NULL;
			$this->data['list'] = $this->getObjectsRecursive($this->mode,$params,$this->sortable);
		}
		$this->addButtons($this->modulesChain['buttons_view']);
		$this->addCrumb('Список элементов');
		if (empty($this->registry->template->file)) $this->registry->template->file = 'view.html';
	}
	
	function addFilter(&$chain,$all=true,$tree=false) {
		if (empty($chain)) return false;
		if ($current_model=$this->registry->users->checkAccess($chain['tbl'],$this->registry->{$chain['class']}->model[$chain['tbl']],'access_read')) {
			$this->filters['id_'.$chain['tbl']] = array(
				'value' => base::getvar('id_'.$chain['tbl'],0),
				'title' => $chain['title'],
			);
			if (empty($chain['tree'])) {
				$q = $this->getQuery(array(
					'title'=>$current_model['title'],
					'id'=>$current_model['id'],
				),array(),$chain['tbl']);
				$this->filters['id_'.$chain['tbl']]['values'] = $this->registry->db->get_list($q,false,'id');
			} else {
				$this->filters['id_'.$chain['tbl']]['values'] = $this->getTree($chain);
			}
			if ($all===true) {
				$this->filters['id_'.$chain['tbl']]['values']['NULL'] = '-';
				ksort($this->filters['id_'.$chain['tbl']]['values']);
			} else {
				if ($this->filters['id_'.$chain['tbl']]['value']===0)
					$this->filters['id_'.$chain['tbl']]['value'] = array_keys($this->filters['id_'.$chain['tbl']]['values'])[0];
			}
			if ($this->filters['id_'.$chain['tbl']]['value']!==0)
				$this->addUrlTail('id_'.$chain['tbl'],$this->filters['id_'.$chain['tbl']]['value']);
		} else {
			// return error to select
		}
	}
	
	function addCrumb($title='',$url='') {
		if (empty($this->crumbs)) $this->crumbs = array();
		if (empty($title)) return false;
		$this->crumbs[] = array('title'=>$title,'url'=>$url);
		return true;
	}
	
	function addCrumbs($chain=array()) {
		if (empty($chain)) {
			$this->addCrumb('DomCMS','/domcms/');
			$chain = $this->modulesChain;
		}
		if (!empty($chain['parents']))
			$this->addCrumbs($chain['parents']);
		$this->addCrumb($chain['title'],'/domcms/?module='.$chain['class'].'&mode='.$chain['tbl']);
	}
	
	function addMessage($message='',$status='info') {
		if (empty($message)) return false;
		if (empty($_SESSION['messages'])) $_SESSION['messages'] = array();
		$_SESSION['messages'][] = array('message'=>$message,'status'=>$status,'time'=>date('d.m.Y H:i',time()));
	}
	
	function addButton($title='',$url='',$glyphicon='',$type='btn-default') {
		$this->buttons[] = array('title'=>$title, 'url'=>$url, 'glyphicon'=>$glyphicon, 'type'=>$type);
	}
	
	function addButtons($buttons=array()) {
		if (empty($buttons)) return;
		foreach ($buttons as $k => $v) {
			switch($v) {
				case 'add': $this->addButton('Добавить',$this->url['add'].$this->url['tail'],'plus-sign','btn-primary'); break;
				case 'clear': $this->addButton('Удалить все',$this->url['module_mode'].'&action=clear'.$this->url['tail'],'trash','btn-danger'); break;
				case 'add_children': if (!empty($this->modulesChain['tree'])) $this->addButton('Добавить дочерний элемент',$this->url['add'].'&id_parent='.$this->data['item']['id'].$this->url['tail'],'plus-sign','btn-primary'); break;
			}
		}
	}
	
	function addListButton(&$chain,$glyphicon='glyphicon-list') {
		if (empty($chain)) return false;
		if ($chain['current_model']=$this->registry->users->checkAccess($chain['tbl'],$this->registry->{$chain['class']}->model[$chain['tbl']],'access_read')) {
			$this->url['listButtons'][] = array(
				'title' => $chain['title'],
				'url' => '/domcms/?module='.$chain['class'].'&mode='.$chain['tbl'].'&action=view&id_'.$this->mode.'=%ID_PARENT%',
				'glyphicon' => $glyphicon,
			);
		}
	}
	
	function addLink(&$chain='',$glyphicon='chevron-right') {
		if (empty($chain)) return false;
		if ($chain['current_model']=$this->registry->users->checkAccess($chain['tbl'],$this->registry->{$chain['class']}->model[$chain['tbl']],'access_read')) {
			$this->url['links'][] = array(
				'title' => $chain['title'],
				'url' => '/domcms/?module='.$chain['class'].'&mode='.$chain['tbl'],
				'glyphicon' => $glyphicon,
			);
		}
	}
	
	public function addMenus() {
		//$this->menu = $this->getTree($this->registry->modules->modulesRegistry['modules_menus'],'sort');
		$menu = $this->registry->db->get_data('SELECT DISTINCT mm.id, mm.id_modules, mm.id_modules_menus, mm.title, mm.icon, m.class AS module, m.tbl AS mode, mm.act AS action FROM modules_menus AS mm LEFT JOIN modules AS m ON mm.id_modules=m.id WHERE 1 ORDER BY mm.id_modules_menus,mm.sort',true);
		foreach ($menu as $k => $v) {
			if ($v['id_modules_menus']==NULL) {
				$this->menu[$v['id']] = $v;
				$this->menu[$v['id']]['children'] = array();
				unset($menu[$k]);
			}
		}
		foreach ($menu as $k => $v) {
			if (array_key_exists($v['id_modules_menus'],$this->menu)) {
				$this->menu[$v['id_modules_menus']]['children'][$v['id']] = $v;
				unset($menu[$k]);
			}
		}
	}
	
	function addUrlTail($variable,$value) {
		if (!preg_match('/'.$variable.'/',$this->url['tail']))
			$this->url['tail'] .= '&'.$variable.'='.$value;
	}
	
	// Static methods
	
	static function extend(&$object,$data) { foreach($data as $k=>$v) $object->{$k}=$v; }
	
	// Create an object if is not exists, or return his
	static function j($name,$class='') {
		static $objects;
		global $config;
		if(isset($objects[$name])) $obj=$objects[$name];
		elseif(isset($_SESSION['OBJECTS'][$name])) {
			$obj=unserialize($_SESSION['OBJECTS'][$name]);
			if(!call_user_func_array(array($obj,'restore'),array_slice(func_get_args(),2))) $obj=false;
		} elseif(class_exists($class)) {
			$obj=new $class($name,isset($config[$name])?$config[$name]:array());
			if(method_exists($obj,'init'))
				if(!call_user_func_array(array($obj,'init'),array_slice(func_get_args(),2))) $obj=false;
		}
		if($obj) $objects[$name]=$obj;
		return $obj?$obj:false;
	}
	
	// Get dynamic image filename, $name - base filename
	static function get_image_filename ( $name ) {		
		$ext = array_pop(explode( '.', $name ));				
		$ext = strtolower ( $ext );
		if( $ext == 'jpg' || $ext == 'jpeg' ) $ext = 'jpg';
		elseif( $ext == 'gif' )	$ext = 'gif';
		elseif( $ext == 'png' ) $ext = 'png';			
		elseif ( $ext == 'swf' ) $ext = 'swf';
		else return ''; // неизвестный тип файла
		return substr(md5(uniqid(rand(), true)), 0, rand(15, 20)).'.'.$ext;
	}
	
	function redirect( $url='' ) {
		if($url=='') header('Refresh:0;'); //Если url пустой, значит просто надо обновить страницу
		else { 
			$server_name = ( !empty($_SERVER['SERVER_NAME']) ) ? $_SERVER['SERVER_NAME'] : getenv('SERVER_NAME');
			$url = ( !substr($url, 0, 4) == 'http' ) ?  $server_name . $url : $url;
			header('Location: ' . $url);
		}
		exit;
	}
	
	// Returns variable from $_REQUEST array and verify datatype
	static function getvar($var,$default,$coding=true,$cookie=false,$n_index=false) {	
		if(is_array($var)) {
			$result = array();
			foreach($var as $k=>$v) {
				$result[$n_index?$v:count($result)] = base::getvar($v,is_array($default)?$default[$k]:$default,$cookie);
			}
			return $result;
		}	
		if(empty($_REQUEST[$var])) {
			if ( isset($_POST[$var]) || isset ($_GET[$var]) )
				$_REQUEST[$var] = ( isset($_POST[$var]) ) ? $_POST[$var] : $_GET[$var];		
			else
				return $default;
		}
		if(!isset($_REQUEST[$var]) || $_REQUEST[$var]=='') return $default;
		
		//Проверяем соответствие типов получаемой переменной и переменной по умолчанию
		if(is_array($_REQUEST[$var]) ^ is_array($default)) {
			return ( is_array($default) ) ? array() : $default;
		}
		$var = $_REQUEST[$var];
		if( !is_array($default) ) {
			// Принудительно устанавливаем нужный тип данных
			$type = gettype($default);
			settype($var, $type);

			if( $type == 'string' )
				if ($coding == true)
					$var = trim(htmlspecialchars($var));			

			// экранируем в зависимости от настроек php 
			return strtr((( MAGIC_QUOTES ) ? stripslashes($var) : $var),array("'"=>""));
		} else {
			return strtr($var,array("'"=>""));
		}
	}
	
}

?>