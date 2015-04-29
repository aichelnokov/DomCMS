<?php

$model = array (
	'modules' => array(
		'id' => array('type'=>'INT(255)','flags'=>'UNSIGNED NOT NULL AUTO_INCREMENT','inner_keys'=>'PRIMARY'),
		'class' => array('type'=>'VARCHAR(255)','default'=>''),
		'title' => array('type'=>'VARCHAR(255)','default'=>''),
		'tbl' => array('type'=>'VARCHAR(255)','default'=>''),
		'fields_view' => array('type'=>'VARCHAR(255)','default'=>'title'), // id, title, login, date, city, sort, visible, lang
		'buttons_view' => array('type'=>'VARCHAR(255)','default'=>'add'), // add, clear
		'controls_view' => array('type'=>'VARCHAR(255)','default'=>'edit delete'), // edit, visible, delete, add_children
		'buttons_edit' => array('type'=>'VARCHAR(255)','default'=>'add'), // add, add_children, photos, videos, ...
		'controls_edit' => array('type'=>'VARCHAR(255)','default'=>'edit add clear delete'), // edit add clear delete
	),
	'modules_fields' => array(
		'id' => array('type'=>'INT(255)','flags'=>'UNSIGNED NOT NULL AUTO_INCREMENT','inner_keys'=>'PRIMARY'),
		'id_modules' => array('type'=>'INT(255)','flags'=>'UNSIGNED','outer_keys'=>'modules(id) ON UPDATE CASCADE ON DELETE CASCADE'),
		'name' => array('type'=>'VARCHAR(255)','default'=>''),
		'title' => array('type'=>'VARCHAR(255)','default'=>''),
	),
	'modules_menus' => array(
		'id' => array('type'=>'INT(255)','flags'=>'UNSIGNED NOT NULL AUTO_INCREMENT','inner_keys'=>'PRIMARY'),
		'id_modules_menus' => array('type'=>'INT(255)','flags'=>'UNSIGNED NOT NULL','outer_keys'=>'modules_menus(id) ON UPDATE CASCADE ON DELETE CASCADE'),
		'id_modules' => array('type'=>'INT(255)','flags'=>'UNSIGNED','outer_keys'=>'modules(id) ON UPDATE CASCADE ON DELETE CASCADE'),
		'title' => array('type'=>'VARCHAR(255)','default'=>''),
		'act' => array('type'=>'VARCHAR(255)','default'=>''),
		'sort' => array('type'=>'INT(255)','default'=>0,'flags'=>'UNSIGNED NOT NULL'),
		'icon' => array('type'=>'VARCHAR(255)','default'=>''),
	),
);

?>