<?php
$local = [
	'components' => array(
		'db' => [
			'class'=>'CDbConnection',
			'connectionString' => 'mysql:host=localhost;dbname=tools',
			'emulatePrepare' => true,
			'username' => 'tools',
			'password' => 'password',
			'charset' => 'utf8',
		],
	),
];

$config = require_once(dirname(__FILE__).'/../console.php');

return array_merge_recursive($config, $local);