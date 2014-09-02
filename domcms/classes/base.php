<?php

if(!defined('IN_SITE')) exit;

class base {

	public $name='';//Имя модуля
	protected $registry=null;	//Реестр объектов для ускорения доступа
	
	/*
		Конструктор, создает новый объект, устанавливает его настройки и режим работы
	*/
	public function __construct($name,$config=array()) {
		$this->name=$name;
		base::extend($this,array_merge(array(),$config));
		$this->registry=registry::get_registry();
		$this->registry->{$this->name}=null;
		$this->registry->{$this->name}=&$this;
		return true;
	}
	
	function init() {
		return true;
	}
	
	function restore() {
		$this->registry=registry::get_registry();
		$this->registry->{$this->name}=null;
		$this->registry->{$this->name}=&$this;
		if ($mode = getvar('mode','')) if($mode != $this->mode) {
			$this->mode = $mode;
		}
		return true;
	}
	
	function __set($name,$value) {
		if(method_exists($this,'set'.$name)) {//Если существует метод set{name}
			$this->{'set'.$name}($value);//Вызываем его и передаем ему параметр value
		} else {
			$this->{$name}=$value;//Иначе просто создаем поле
		}
	}
	
	function __destruct() {
		$this->registry->{$this->name}=null;
		$this->registry=null;
		if($this->cache=='session') {
			if (method_exists($this,'setUrls')) $this->setUrls(true);
			$_SESSION['OBJECTS'][$this->name]=serialize($this); 
		}
	}
	
	function __get($name) {
		if(method_exists($this,'get'.$name)) {//Если существует метод get{name}
			return $this->{'get'.$name}();//Вызываем его и возвращаем результат
		} else {
			return false;//Иначе возвращает false
		}
	}
	
	// Static methods
	
	static function extend(&$object,$data) {
		foreach($data as $k=>$v) {
			$object->{$k}=$v;
		}
	}
	
	static function j($name,$class='') {
		static $objects;
		global $config;
		if(isset($objects[$name])) {
			$obj=$objects[$name];
		} elseif(isset($_SESSION['OBJECTS'][$name])) {
			$obj=unserialize($_SESSION['OBJECTS'][$name]);
			if(!call_user_func_array(array($obj,'restore'),array_slice(func_get_args(),2))) {
				$obj=false;
			}
		} elseif(class_exists($class)) {
			$obj=new $class($name,isset($config[$name])?$config[$name]:array());
			if(method_exists($obj,'init'))
				if(!call_user_func_array(array($obj,'init'),array_slice(func_get_args(),2))) { $obj=false; echo 'object is false\'d!<br />'; }
		}
		if($obj) { $objects[$name]=$obj; } //else { echo 'object is not exist\'s<br />'; };
		return $obj?$obj:false;
	}
	
	static function get_image_filename ( $name ) {		
		$ext = array_pop(explode( '.', $name ));				
		$ext = strtolower ( $ext );
		if( $ext == 'jpg' || $ext == 'jpeg' ) {
			$ext = 'jpg';			
		}
		elseif( $ext == 'gif' ) {
			$ext = 'gif';			
		}
		elseif( $ext == 'png' ) {
			$ext = 'png';			
		}
		elseif ( $ext == 'swf' ) {
			$ext = 'swf';
		}
		else {
			return ''; // неизвестный тип файла			
		}
		return substr(md5(uniqid(rand(), true)), 0, rand(15, 20)).'.'.$ext;
	}
	
}

?>