<?php

if(!defined('IN_SITE')) exit;

class base {

	public $name='';//��� ������
	protected $registry=null;	//������ �������� ��� ��������� �������
	
	// �����������, ������� ����� ������, ������������� ��� ��������� � ����� ������
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
	
	// ���� ���������� ����� set{name} �������� ��� � �������� ��� �������� value
	// ����� ������ ������� ����
	function __set($name,$value) {
		if(method_exists($this,'set'.$name)) $this->{'set'.$name}($value);
		else $this->{$name}=$value; 
	}
	
	function __destruct() {
		$this->registry->{$this->name}=null;
		$this->registry=null;
		if($this->cache=='session') {
			if (method_exists($this,'setUrls')) $this->setUrls(true);
			$_SESSION['OBJECTS'][$this->name]=serialize($this); 
		}
	}
	
	// ���� ���������� ����� get{name} �������� ��� � ���������� ���������
	// ����� ���������� false
	function __get($name) {
		if(method_exists($this,'get'.$name)) return $this->{'get'.$name}();
		else return false;
	}
	
	// Static methods
	
	// Extends object
	static function extend(&$object,$data) {
		foreach($data as $k=>$v) $object->{$k}=$v;
	}
	
	// Create an object if is not exists, or return his
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
	
	// Get dynamic image filename, $name - base filename
	static function get_image_filename ( $name ) {		
		$ext = array_pop(explode( '.', $name ));				
		$ext = strtolower ( $ext );
		if( $ext == 'jpg' || $ext == 'jpeg' ) $ext = 'jpg';
		elseif( $ext == 'gif' )	$ext = 'gif';
		elseif( $ext == 'png' ) $ext = 'png';			
		elseif ( $ext == 'swf' ) $ext = 'swf';
		else return ''; // ����������� ��� �����
		return substr(md5(uniqid(rand(), true)), 0, rand(15, 20)).'.'.$ext;
	}
	
	// Returns variable from $_REQUEST array and verify datatype
	function getvar($var,$default,$coding=true,$cookie=false,$n_index=false) {	
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
		
		//��������� ������������ ����� ���������� ���������� � ���������� �� ���������
		if(is_array($_REQUEST[$var]) ^ is_array($default)) {
			return ( is_array($default) ) ? array() : $default;
		}
		$var = $_REQUEST[$var];
		if( !is_array($default) ) {
			// ������������� ������������� ������ ��� ������
			$type = gettype($default);
			settype($var, $type);

			if( $type == 'string' )
				if ($coding == true)
					$var = trim(htmlspecialchars($var));			

			// ���������� � ����������� �� �������� php 
			return strtr((( MAGIC_QUOTES ) ? stripslashes($var) : $var),array("'"=>""));
		} else {
			return strtr($var,array("'"=>""));
		}
	}
	
}

?>