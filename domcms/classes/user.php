<?php

if(!defined('IN_SITE')) exit;

class J_User extends base {
	
	public $browser		= ''; //Браузер пользователя
	public $ip			= 0; //IP адрес (понадобится для банов и геотаргетинга)
	public $enter_time	= 0; //Время входа, чтобы считать как долго пользователь на сайте
	public $page		= ''; //Адрес на какой он странице
	public $error		= '';
	public $cache 		= 'session';
	
	function restore() {
		parent::restore();
		$this->init();
		return true;
	}
	
	function init() {
		global $db;
		
		if(empty($_SESSION['OBJECTS']['user'])) { //Если сессии пользователя еще нет - добываем данные о нем
			$this->browser = ( !empty($_SERVER['HTTP_USER_AGENT']) ) ? htmlspecialchars($_SERVER['HTTP_USER_AGENT']) : '';
			$this->enter_time = time();
			$this->forwarded_for = ( !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '';
			$this->ip = ( !empty($_SERVER['REMOTE_ADDR']) ) ? $_SERVER['REMOTE_ADDR'] : '';
			$this->page = ( !empty($_SERVER['PHP_SELF']) ) ? $_SERVER['PHP_SELF'] : getenv('PHP_SELF');
			if( !$this->page )	$this->page = ( !empty($_SERVER['REQUEST_URI']) ) ? $_SERVER['REQUEST_URI'] : getenv('REQUEST_URI');
		}
		
		if (base::getvar('signout',false)!==false) $this->signout();
		elseif (base::getvar('signin',false)!==false) $this->signin();
		elseif (base::getvar('signup',false)!==false) $this->signup();
		else $this->check();
		
		return true;
	}
	
	function signout() {
		$this->id=0;
		setcookie ( 'j_user', '', 0, '/' );
		unset($_COOKIE['j_user']);
		session_destroy();
		redirect("/");
	}
	
	function signup() {
		$login = getvar ( 'login', '' );
		$pass =  getvar ( 'password', '' );
		$mem = getvar ( 'remember', false );
		return !$this->check()?$this->full_signin($login,$pass,$mem):false;
	}
	
	function full_signin($login,$password,$remember) {
		$db = $this->registry->db;
		if ( !empty($login) ) {
			$row = $db->get_data( "SELECT * FROM users WHERE login = '".$login."' AND password = '".md5($password)."'",false );
			if ( $row ) {
				if ( !$row['active'] ) {
					$this->error = "Пользователь заблокирован!";
				} else {
					extend($this,$row);
					if(empty($this->lang)) $this->lang = 'ru';
					if ( $remember ) {
						$time = time() + 60 * 60 * 24 * 30;
						setcookie ( 'j_user', $row['id'].'|||'.$row['login'], $time, '/' );
					}
				}
			} else {
				$this->error = "Введенные данные неверны!";
			}
		}
		return !empty($this->id);
	}
	
	//Проверяет залогинен ли пользователь
	function check() {
		if(!empty($this->id)) return true;
		else if( !empty ($_COOKIE['j_user']) ) {
			$arr = explode ( '|||', $_COOKIE['j_user'] );
			if ( count ( $arr ) == 2 ) {
				$row = $this->registry->db->get_data ( "SELECT * FROM users WHERE user_login = '".$arr[1]."' AND id = '".$arr[0]."'",false );
				if ( $row ) {
					if ( !$row['user_active'] ) {
						$this->error="Пользователь заблокирован!";
					} else {
						if(empty($this->lang)) $this->lang = 'ru';
						return extend($this,$row);
					}
				} else {
					$this->error="Введенные данные неверны!";
				}
			}
		}
		return false;
	}
	
	function isValid() {
		if(isset($this->id) && isset($this->user_active)) {
			if($this->user_active==1) return true;
		}
		return false;
	}
}

$user = base::j('user','J_User');
?>