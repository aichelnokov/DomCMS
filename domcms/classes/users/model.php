<?php

public $model = array(
	'users' => array(
		'id' => array('type'=>'INT(255)','flags'=>'UNSIGNED NOT NULL AUTO_INCREMENT','inner_keys'=>'PRIMARY'),
		'id_groups' => array('type'=>'INT(255)','flags'=>'UNSIGNED','outer_keys'=>'groups(id) ON UPDATE CASCADE ON DELETE SET NULL'),
		'login' => array('type'=>'VARCHAR(255)','default'=>''),
		'password' => array('type'=>'VARCHAR(255)','default'=>''),
		'active' => array('type'=>'INT(1)','default'=>1,'flags'=>'UNSIGNED NOT NULL'),
		'firstname' => array('type'=>'VARCHAR(255)','default'=>''),
		'phone' => array('type'=>'VARCHAR(255)','default'=>''),
		'mail' => array('type'=>'VARCHAR(255)','default'=>''),
		'address' => array('type'=>'VARCHAR(255)','default'=>''),
		'postcode' => array('type'=>'INT(8)','default'=>0,'flags'=>'UNSIGNED NOT NULL'),
		'sex' => array('type'=>'INT(1)','default'=>1,'flags'=>'UNSIGNED NOT NULL'),
		'birdthdate' => array('type'=>'INT(255)','default'=>0,'flags'=>'UNSIGNED NOT NULL'),
		'about' => array('type'=>'TEXT','default'=>''),
	),
);

public $relations = array(
	'users' => array(
		'groups' => array('id_groups','id')
	)
);

?>