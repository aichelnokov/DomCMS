<?php

if(!defined('IN_SITE')) exit;

class users extends base {
	
	public $browser		= ''; //Браузер пользователя
	public $ip			= 0; //IP адрес (понадобится для банов и геотаргетинга)
	public $enter_time	= 0; //Время входа, чтобы считать как долго пользователь на сайте
	public $page		= ''; //Адрес на какой он странице
	public $error		= '';
	public $cache 		= 'session';
	
	protected $model = array (
		'users' => array(
			'id' => array('type'=>'INT(255)','flags'=>'UNSIGNED NOT NULL AUTO_INCREMENT','inner_keys'=>'PRIMARY'),
			'login' => array('type'=>'VARCHAR(255)','default'=>''),
			'password' => array('type'=>'VARCHAR(255)','default'=>''),
			'active' => array('type'=>'INT(1)','default'=>1,'flags'=>'UNSIGNED NOT NULL'),
			'name' => array('type'=>'VARCHAR(255)','default'=>''),
			'phone' => array('type'=>'VARCHAR(255)','default'=>''),
			'mail' => array('type'=>'VARCHAR(255)','default'=>''),
			'address' => array('type'=>'VARCHAR(255)','default'=>''),
			'postcode' => array('type'=>'INT(8)','default'=>0,'flags'=>'UNSIGNED NOT NULL'),
			'sex' => array('type'=>'INT(1)','default'=>1,'flags'=>'UNSIGNED NOT NULL'),
			'birdthdate' => array('type'=>'INT(255)','default'=>0,'flags'=>'UNSIGNED NOT NULL'),
			'about' => array('type'=>'TEXT','default'=>''),
		),
		'users_groups' => array(
			'id_users' => array('type'=>'INT(255)','flags'=>'UNSIGNED','outer_keys'=>'users(id) ON UPDATE CASCADE ON DELETE CASCADE'),
			'id_groups' => array('type'=>'INT(255)','flags'=>'UNSIGNED','outer_keys'=>'groups(id) ON UPDATE CASCADE ON DELETE SET NULL'),
		),
	);
	
	function restore() {
		parent::restore();
		$this->init();
		return true;
	}
	
	function init() {
		if(empty($_SESSION['OBJECTS'][$this->name])) { //Если сессии пользователя еще нет - добываем данные о нем
			$this->browser = ( !empty($_SERVER['HTTP_USER_AGENT']) ) ? htmlspecialchars($_SERVER['HTTP_USER_AGENT']) : '';
			$this->enter_time = time();
			$this->forwarded_for = ( !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '';
			$this->ip = ( !empty($_SERVER['REMOTE_ADDR']) ) ? $_SERVER['REMOTE_ADDR'] : '';
			$this->page = ( !empty($_SERVER['PHP_SELF']) ) ? $_SERVER['PHP_SELF'] : getenv('PHP_SELF');
			if( !$this->page )	$this->page = ( !empty($_SERVER['REQUEST_URI']) ) ? $_SERVER['REQUEST_URI'] : getenv('REQUEST_URI');
		}
		if (base::getvar('signout',false)!==false) $this->signout();
		elseif (base::getvar('signin',false)!==false) { if ($this->signin()) { base::redirect($_SERVER['REQUEST_URI']); } }
		elseif (base::getvar('signup',false)!==false) $this->signup();
		else $this->check();
		$this->getAccess();
		return parent::init();
	}
	
	function signout() {
		$this->id=0;
		setcookie ( 'domcms', '', 0, '/' );
		unset($_COOKIE['domcms']);
		session_destroy();
		base::redirect("/domcms/");
	}
	
	function signin() {
		$login = base::getvar ( 'login', '' );
		$pass =  base::getvar ( 'password', '' );
		$mem = base::getvar ( 'remember', false );
		return $this->full_signin($login,$pass,$mem);
	}
	
	function full_signin($login,$password,$remember) {
		$db = $this->registry->db;
		if ( !empty($login) ) {
			$row = $db->get_data( "SELECT * FROM users WHERE login = '".$login."' AND password = '".md5($password)."'",false );
			if ( $row ) {
				if ( !$row['active'] ) {
					$this->registry->template->error = "Пользователь заблокирован!";
				} else {
					base::extend($this,$row);
					if ( $remember ) {
						$time = time() + 60 * 60 * 24 * 30;
						setcookie ( 'domcms', $row['id'].'|||'.$row['login'], $time, '/' );
					}
				}
			} else {
				$this->registry->template->error = "Введенные данные неверны!";
			}
		}
		return !empty($this->id);
	}
	
	//Проверяет залогинен ли пользователь
	function check() {
		if(!empty($this->id)) return true;
		else if( !empty ($_COOKIE['domcms']) ) {
			$arr = explode ( '|||', $_COOKIE['domcms'] );
			if ( count ( $arr ) == 2 ) {
				$row = $this->registry->db->get_data ( "SELECT * FROM users WHERE login = '".$arr[1]."' AND id = '".$arr[0]."'",false );
				if ( $row ) {
					if ( !$row['active'] ) {
						$this->registry->template->error="Пользователь заблокирован!";
					} else {
						return base::extend($this,$row);
					}
				} else {
					$this->registry->template->error="Введенные данные неверны!";
				}
			}
		}
		return false;
	}
	
	function isValid() {
		if(isset($this->id) && isset($this->active)) {
			if($this->active==1) return true;
		}
		return false;
	}
	
	function getAccess() {
		if ($this->isValid())
			$temp = $this->registry->db->get_data('SELECT g.name, ug.id_groups AS id 
				FROM users_groups AS ug 
				RIGHT JOIN groups AS g ON g.id=ug.id_groups 
				WHERE ug.id_users='.$this->id,false);
		else
			$temp = $this->registry->db->get_data('SELECT g.id, g.name FROM groups AS g WHERE g.id=1',false);
		if (!empty($temp)) {
			$this->groups = $temp['name'];
			$this->id_groups = $temp['id'];
		}
		/*$temp = $this->registry->db->get_list('SELECT DISTINCT g_a.name_table FROM groups_access AS g_a WHERE g_a.id_groups='.$this->id_groups,false);
		$this->access = array();
		foreach ($temp as $k => $v) {
			$this->access[$v] = array();
			$temp1 = $this->registry->db->get_list('SELECT DISTINCT g_a.name_field FROM groups_access AS g_a WHERE g_a.id_groups='.$this->id_groups.' AND g_a.name_table="'.$v.'"',false);
			foreach ($temp1 as $k1 => $v1) {
				$this->access[$v][$v1] = $this->registry->db->get_data('SELECT DISTINCT g_a.access_read, g_a.access_write, g_a.access_delete FROM groups_access AS g_a WHERE g_a.id_groups='.$this->id_groups.' AND g_a.name_table="'.$v.'" AND g_a.name_field="'.$v1.'"',false);
			}
			unset($temp1);
		}
		unset($temp);*/
		return true;
	}
	
}

$users = base::j('users','users');
?>