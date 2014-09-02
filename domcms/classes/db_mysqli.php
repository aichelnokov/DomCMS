<?php

if(!defined('IN_SITE')) exit;

// Класс работы с MySQL версии 4.1 и выше

$config['db'] = array(
	'server'=>'localhost',
	'user'=>'root',
	'password'=>'pass',
	'database'=>'domcms',
	'default_charset'=>'utf8'
);

class db_mysqli extends base {
	private $connect_id;
	private $query_result;
	private $total_queries = 0;

	protected $user = '';
	protected $password = '';
	protected $port = false;
	protected $server = '';
	protected $database = '';
	protected $default_charset='';
	
	function connected() {
		return $this->connection_id!==false;
	}
	
	function init() {
		$this->connect( $this->database );
		if($this->default_charset) $this->query( 'SET NAMES '.$this->default_charset );
		return parent::init();
	}
	
	function __destruct() {
		$this->close();
	}
	
	/**
	* Установка подключения к БД
	*/
	function connect()
	{
		//$this->server = $this->server.(($port===false)?'':':'.$this->port);
		$this->connect_id = mysqli_connect($this->server, $this->user, $this->password, $this->database );
		return ( $this->connect_id && $this->database ) ? true /*$this->connect_id*/ : false;
	}

	/**
	* Закрытие текущего подключения
	*/
	function close()
	{
		return ( $this->connect_id ) ? mysqli_close($this->connect_id) : false;
	}

	/**
	* Число запросов к БД (для отладки)
	*/
	function total_queries()
	{
		return $this->total_queries;
	}
	//есть строки в результате запроса
	function is_result ( $query_id = false ) {
		if( $query_id === false ) {
			$query_id = $this->query_result;
		}
		return ( $query_id !== false ) ? mysqli_num_rows ($query_id) : false;
	}

	function get_single($query,$default=false) {
		$res=$this->query($query);
		$result=$this->fetchall($res);
		if($result) return array_shift($result[0]);
		return $default;
	}
	
	/*
	 * Выполняет выборку и возвращает результат. Если указан multiple, то обязательно вернется массив результатов,
	 * даже если он один array(0=>row1,1=>row2), иначе есди результат один, то вернется только он array(field1=>val1,field2=>val2)
	 */
	function get_data($query,$multiple=true,$field=false) {
		$res=$this->query($query);
		$result=$this->fetchall($res,$field);
		if((!$multiple)&&(count($result)==1)) return $result[0];
		return $result;
	}
	
	/*
	 * Выполняет выборку и возвращает результат. Если указан multiple, то обязательно вернется массив результатов,
	 * даже если он один array(0=>row1,1=>row2), иначе есди результат один, то вернется только он array(field1=>val1,field2=>val2)
	 */
	function get_list($query,$field_value=false,$field_key=false) {
		$res=$this->query($query);
		$result=$this->fetchlist($res,$field_value,$field_key);
		return $result;
	}
	
	/**
	* Выполнение запроса к БД
	*/
	function query( $query = '', $error_mode = 1 )
	{
		if($this->connect_id!==false) {
			$t=microtime(true);
			if( $query )
			{
				$this->query_result = false;
				$this->total_queries++;
				if( $this->query_result === false ) {
					if( ( $this->query_result = mysqli_query($this->connect_id, $query) ) === false && $error_mode ) {
						$this->error($query);
					}
				}
			} else {
				return false;
			}
			if(!empty($_SESSION['debug']))
				if ($_SESSION['debug']) 
					addDebug($query,microtime(true)-$t,'mysql',array(200,500,2000));
			return ( $this->query_result ) ? $this->query_result : false;
		}
		return false;
	}

	function get_error () {
		return mysqli_error ();
	}

	/**
	* Выборка
	*/
	function fetchrow($query_id = false)
	{
		if( $query_id === false ) {
			$query_id = $this->query_result;
		}
		return ( $query_id !== false ) ? mysqli_fetch_assoc($query_id) : false;
	}
	function getrow($query_id = false)
	{
		return fetchrow($query_id);
	}

	/**
	* Заносим полученные данные в цифровой массив
	*
	* @param string $field Поле, по которому создавать массив
	*/
	function fetchall($query_id = false, $field = false)
	{
		if( empty($query_id) ) $query_id = $this->query_result;
		if( !empty($query_id) ) {
			$result = array();
			while( $row = $this->fetchrow($query_id) ) {
				if( $field !== false ) {
					$result[$row[$field]] = $row;
				} else {
					$result[] = $row;
				}
			}
			$this->freeresult($query_id);
			return $result;
		}
		return false;
	}
	
	function fetchlist($query_id=false, $fv=false, $fk=false)
	{
		if( $query_id === false ) $query_id = $this->query_result;
		if( $query_id !== false ) {
			$result = array();
			while( $row = $this->fetchrow($query_id) ) {
				if($fv!==false) $v=$row[$fv]; else $v=array_shift($row); //Получаем значение
				if($fk!==false) $result[$row[$fk]]=$v; else $result[]=$v; //Добавляем в список
			}
			$this->freeresult($query_id);
			return $result;
		}
		return false;
	}

	/**
	* Освобождение памяти
	*/
	function freeresult($query_id = false)
	{
		if( $query_id === false )
		{
			$query_id = $this->query_result;
		}

		return mysqli_free_result($query_id);
	}
	
	function update_id($table,$data,$id) {
		return $this->update($table,$data,array('id'=>$id));
	}
	
	function update($table,$data,$where) {
		$values=array();
		foreach( $data as $key => $value ) {
			$values[]=$key.'='.$this->check_value($value);
		}
		$wheres=array();
		if(is_array($where)) {
			foreach( $where as $key => $value ) {
				$wheres[]=$key.'='.$this->check_value($value);
			}
		}
		return $this->query('UPDATE '.$table.' SET '.implode(',',$values).' WHERE '.(count($wheres)>0?implode(' AND ',$wheres):$where));
	}
	
	function insert($table,$data) {
		$keys=array_keys($data);
		if(empty($keys)) return false;
		if(is_numeric($keys[0])) { //Если первый ключ - число, значит там массив записей на вставку
			$keys=array_keys($data[$keys[0]]); //Ключами делаем ключи первой записи
			$values=array();
			foreach($data as $v) {
				$values[]=implode(',',$this->check_value($v));
			}
		} else {
			$values=array(implode(',',$this->check_value($data)));
		}
		if($this->query('INSERT INTO '.$table.'('.implode(',',$keys).') VALUES ('.implode('),(',$values).')')===false) return false;
		if(is_numeric($keys[0])) return $this->insert_id();
		return true;
	}
	
	function insert_list($table,$array,$field_name) {
		if(!is_array($array)) $array=array($array);
		$res=array();
		foreach($array as $v) $res[]=array($field_name=>$v);
		return $this->insert($table,$res);
	}
	
	/**
	* Преобразование массива в строку
	* и выполнение запроса
	*/
	function build_array($query, $data = false)
	{
		if( !is_array($data)) {
			return false;
		}
		$fields = $values = array();
		if( $query == 'INSERT' ) {
			foreach( $data as $key => $value ) {
				$fields[] = $key;
				$values[] = $this->check_value($value);
			}
			$query = ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
		} elseif( $query == 'SELECT' || $query == 'UPDATE' ) {
			foreach( $data as $key => $value ) {
				$values[] = $key . ' = ' . $this->check_value($value);
			}
			$query = implode( ( $query == 'UPDATE' ) ? ', ' : ' AND ', $values);
		}
		return $query;
	}

	/**
	* Сверяем тим переменной и её значение,
	* строки также экранируем
	*/
	function check_value($value)
	{
		if($value===0) return $value;
		if( $value===null || $value=='NULL' ) {
			return 'NULL';
		} elseif(is_bool($value)) {
			intval($value);
		} elseif( is_string($value) ) {		
			return "'" . $this->escape($value) . "'";
		} elseif(is_array($value)) {
			$result=array();
			foreach($value as $k=>$v) {
				$result[$k]=$this->check_value($v);
			}
			return $result;
		} else
			return $value;
	}

	/**
	* Затронутые поля
	*/
	function affected_rows()
	{
		return ( $this->connect_id ) ? mysqli_affected_rows($this->connect_id) : false;
	}

	/**
	* ID последнего добавленного элемента
	*/
	function insert_id() {
		return ( $this->connect_id ) ? mysqli_insert_id($this->connect_id) : false;
	}

	/**
	* Экранируем символы
	*/
	function escape($message) {
		return mysqli_real_escape_string( $message);
	}

	/**
	* SQL ошибки передаём нашему обработчику
	*/
	function error($sql = '')
	{
		global $custom_error_style, $user;

		$code = ( $this->connect_id ) ? mysqli_errno($this->connect_id) : mysqli_connect_errno();
		$custom_error_style = true;
		$message = ( $this->connect_id ) ? mysqli_error($this->connect_id) : mysqli_connect_error();

		$message = '<br /><b style="color: red">Ошибка SQL</b>:<br /><blockquote>Код ошибки: <b>' . $code . '</b>.<br />Текст ошибки: «<b>' . $message . '</b>».<br />';
		$message .= ( $sql ) ? '<br /><b>SQL запрос:</b> ' . htmlspecialchars($sql) . '<br />' : '';
		$message .= '</blockquote>';

		/**
		* Стандартные настройки сервера не позволяют выводить
		* сообщение об ошибке длиной более 1024 символов
		*/
		if( strlen($message) >= 1024 )
		{
			global $message_long_error;

			$message_long_error = $message;
			print($message);
			trigger_error(false, E_USER_ERROR);
		}

		trigger_error($message, E_USER_ERROR);

		return $result;
	}
}

$db = base::j('db','db_mysqli');

?>