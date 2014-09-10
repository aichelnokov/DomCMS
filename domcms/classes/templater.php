<?php

if(!defined('IN_SITE')) exit;

class J_Templater extends base {
	protected $data = array('debug'=>'');
	public $file = '';
	public $text = '';
	public $path = '';
	
	function init() {
		return parent::init();
	}
	
	public function __set($var,$value) {
		$this->data[$var] = $value;
		return $value;
	}
	
	public function &__get($var) {
		if(!isset($this->data[$var])) $this->data[$var]=array();
		return $this->data[$var];
	}
	
	public function &getVariables() {
		return $this->data;
	}
	
	public function render( $return=false, $error='error.html' ) {		
		require_once(__DIR__.'\twig.php');	
		$twig = new J_Twig();
		if(!isset($twig->paths['main']))
			$twig->paths['main'] = ROOT_DIR.$this->path;
		if (class_exists('debug'))
			if (method_exists($this->registry->debug,'toTemplate'))
				$this->registry->debug->toTemplate();
		if (!file_exists(ROOT_DIR.$this->path.$this->file))
			$this->file = $error;
		
		if($this->file) if($return) return $twig->getHTMLFromFile($this->file,$this->getVariables()); else echo $twig->getHTMLFromFile($this->file,$this->getVariables());
		if($this->text) if($return) return $twig->getHTMLFromText($this->text,$this->getVariables()); else echo $twig->getHTMLFromText($this->text,$this->getVariables());	
	}
	
}

?>
