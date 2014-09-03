<?php

if(!defined('IN_SITE')) exit;

// Класс работы с MySQL версии 4.1 и выше

class debug extends base {
	
	public $debug = array();
	public $start_execution_time;
	
	function init() {
		$this->start_execution_time = microtime(true);
		$_SESSION['debug'] = base::getvar('debug',false);
		define('DEBUG',$_SESSION['debug']);
		return parent::init();
	}
	
	function addCommonDebug($text) {
		$this->addDebug($text,0,'Общее');
	}
	
	function addDebug($text,$time=0,$tab='',$critical_times=array(200),$additional=array()) {
		if (!DEBUG) return false;
		$time = round($time*1000,3);
		$critical_level = 0;
		sort($critical_times);
		foreach($critical_times as $v) {
			if($time>$v) $critical_level++;
		}
		if(!is_string($text)) $text = (string) $text;
		$this->debug[$tab][] = array(
			'critical_level' => $critical_level,
			'data' => array_merge(
				array(
					'time'=>$time.'&nbsp;мсек',
					'text'=>$text
				),
				$additional
			)
		);
	}
	
	function toTemplate() {
		if(DEBUG) {
			$this->addDebug('Время выполнения скрипта',microtime(true)-$this->start_execution_time,'Общее');
			$this->registry->template->debug = $this->registry->debug->debug;
		}
	}
	
}

$debug = base::j('debug','debug');

?>