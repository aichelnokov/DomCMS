<?php

if(!defined('IN_SITE')) exit;

class base {

	public $name='';//Имя модуля
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
			$_SESSION['OBJECTS'][$this->name]=serialize($this);
		}
	}
	
	// Если существует метод get{name} вызываем его и возвращаем результат иначе возвращает false
	function __get($name) {
		if(method_exists($this,'get'.$name)) return $this->{'get'.$name}(); 
		else return false;
	}
	
	function updateObject($component) {
		if ($this->current_model=$this->registry->users->checkAccess($this->mode,$this->model[$component],'access_write')) {
			$this->current_model = array_intersect_key($this->current_model,$_POST);
			$this->edit_data = array();
			foreach ($this->current_model as $k => $v) {
				$this->edit_data[$k] = base::getvar($k,$v['default']);
			}
			$this->registry->db->update($this->mode,$this->edit_data,'id='.$this->id);
			unset($this->edit_data);
			$this->addMessage('Запись успешно обновлена','success');
			return true;
		}
	}
	
	// Получение информации об объекте с заданным идентификатором
	function getQuery($model,$params) {
		$q = 'SELECT '.implode(', ',array_keys($model)).' FROM '.$this->mode;
		if ($w=$this->registry->db->build_array('SELECT',$params)) $q .= ' WHERE '.$w;	
		return $q;
	}
	
	function getObject($component) {
		if (empty($component)) return false;
		if ($this->current_model=$this->registry->users->checkAccess($this->mode,$this->model[$component],'access_read')) {
			foreach ($this->current_model as $k => $v) 
				if (preg_match('/id_/',$k)>0) echo 'Получите пожалуйста список возможных родителей<br>';
			$q = $this->getQuery($this->current_model,array('id'=>$this->id));
			$q .= ' LIMIT 1';
			return $this->registry->db->get_data($q,false);
		} // else return false
	}
	
	function getObjects($component='',$params=array(),$order='') {
		if (empty($component)) return false;
		if ($this->current_model=$this->registry->users->checkAccess($this->mode,$this->model[$component],'access_read')) {
			$q = $this->getQuery($this->current_model,$params);
			if ($order!='') $q .= ' ORDER BY '.$order;
			return $this->registry->db->get_data($q);
		} else {
			// return error access to this mode
		}
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
			$this->registry->modules->checkModule($this->name,$k,$v);
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
		$this->data = array();
		$this->url = array(
			'photos' => '/domcms/?module='.$this->module.'&mode='.$this->name.'_photos&action=view&id_parent=%ID_PARENT%',
			'add' => '/domcms/?module='.$this->module.'&mode='.$this->name.'&action=add'.(!empty($this->id_parent)?'&id_parent='.$this->id_parent:''),
			'edit' => '/domcms/?module='.$this->module.'&mode='.$this->mode.'&action=edit&id=%ID%',
			'delete' => '/domcms/?module='.$this->module.'&mode='.$this->mode.'&action=delete&id=%ID%',
			'module' => '/domcms/?module='.$this->module,
			'module_mode' => '/domcms/?module='.$this->module.'&mode='.$this->mode,
			'tail' => '',
		);
		if ($this->modulesChain = $this->registry->modules->getModulesChain($this)) {
			var_dump($this->modulesChain);
		}
			// fill $this->filters['id_parent_module']
			// fill $this->id_parent_module
			// add url_tail
			// add crumbs
		$this->addCrumb('DomCMS','/domcms/');
		$this->title = $this->registry->db->get_single('SELECT DISTINCT title FROM modules WHERE class="'.$this->name.'" LIMIT 1');
		return $this->registry->modules->allow($this);
	}
	
	function edit($add=false) {
		if ($submit=base::getvar('form_submit','')) {
			if ($add) 
				// добавление объекта, добавление сообщения и редирект %)
				$this->addObject($this->mode);
			else
				$this->updateObject($this->mode);
		}
		$this->data['item'] = $this->getObject($this->mode);
		$this->addCrumb((!empty($this->data['item']['title'])?'Просмотр элемента &laquo;'.$this->data['item']['title'].'&raquo;':'Просмотр элемента'));
		if (empty($this->registry->template->file)) $this->registry->template->file = 'edit_'.$this->name.'_'.$this->mode.'.html';
	}
	
	function add() { return $this->edit(true); }
	
	function view() {
		$this->current_model = $this->registry->users->checkAccess($this->mode,$this->model[$this->mode],'access_read');
		if ($this->current_model['id']['access_write']==1) 
			$this->addButtons('Добавить',$this->url['add'],'plus-sign');
		if ($this->module==$this->mode) {
			foreach ($this->model as $k => $v) {
				if ($k==$this->module) {
					continue;
				} else {
					$this->addLinks($k,$this->url['module'].'&mode='.$k,'chevron-right');
				}
			}
			$this->addCrumb('Список элементов');
			$this->data['list'] = $this->getObjects($this->mode,array(),'id');
		} else {
			// !!! Добавить автоматический селект
			/*foreach ($this->current_model as $k => $v) { 
				if (!empty($v['outer_keys'])) {
					if (!empty($_SESSION['filters'][$this->module][$this->mode][$k]) $this->filters[$k] = array;
				}
			}*/
			//print_r($this->current_model);
			$this->addCrumb('Список элементов');
			$this->data['list'] = $this->getObjects($this->mode,array(),'id');
		}
		if (empty($this->registry->template->file)) $this->registry->template->file = 'view.html';
	}
	
	function addCrumb($title='',$url='') {
		if (empty($this->crumbs)) $this->crumbs = array();
		if (empty($title)) return false;
		$this->crumbs[] = array('title'=>$title,'url'=>$url);
		return true;
	}
	
	function addMessage($message='',$status='info') {
		if (empty($message)) return false;
		if (empty($_SESSION['messages'])) $_SESSION['messages'] = array();
		$_SESSION['messages'][] = array('message'=>$message,'status'=>$status,'time'=>date('d-m-Y H:i',time()));
	}
	
	function addButtons($title='',$url='',$glyphicon='',$direction='float-left') {
		if (empty($this->buttons)) $this->buttons = array();
		$this->buttons[] = array('title'=>$title, 'url'=>$url, 'glyphicon'=>$glyphicon,'direction'=>$direction);
	}
	
	function addLinks($title='',$url='',$glyphicon='',$direction='float-left') {
		if (empty($this->links)) $this->links = array();
		$this->links[] = array('title'=>$title, 'url'=>$url, 'glyphicon'=>$glyphicon,'direction'=>$direction);
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