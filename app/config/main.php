<?php

// uncomment the following to define a path alias
YiiBase::setPathOfAlias('components', dirname(__FILE__).'/../components');
YiiBase::setPathOfAlias('models', dirname(__FILE__).'/../models');

// This is the main Web application configuration. Any writable
// CWebApplication properties can be configured here.
return array(
	'basePath'=>dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
	'name'=>'Retask',
	'defaultController' => 'land',

	// preloading 'log' component
	'preload'=>array('log', 'rest'),

	// autoloading model and component classes
	'import'=>array(
		'application.models.*',
	),

	'modules'=>array(
		// uncomment the following to enable the Gii tool
		'gii'=>array(
			'class'=>'system.gii.GiiModule',
			'password'=>'Enter Your Password Here',
			// If removed, Gii defaults to localhost only. Edit carefully to taste.
			'ipFilters'=>array('127.0.0.1','::1'),
		),
	),

	// application components
	'components'=>array(
		'cache'=>array(
			'class'=>'CApcCache',
		),
		'session' => array(
			'autoStart' => false,
			'cookieMode' => 'only',
		),
		'request' => array(
			'class' => '\components\HttpRequest',
			'enableCsrfValidation' => true,
			'csrfTokenName' => 'XSRF-TOKEN',
		),
		'rest'=>array(
			'class'=>'\components\Json',
		),
		'user'=>array(
			'class' => '\components\WebUser',
		),
		'authManager' => array(
			'class' => '\components\PhpAuthManager',
		),
		'urlManager'=>array(
			'urlFormat'=>'path',
			'rules'=>array(
				array('site/signin', 'pattern' => '<version:v1>/authenticate', 'verb' => 'POST', 'parsingOnly' => true),
				array('site/signout', 'pattern' => '<version:v1>/authenticate', 'verb' => 'DELETE', 'parsingOnly' => true),
				array('site/ping', 'pattern' => '<version:v1>/authenticate', 'verb' => 'GET', 'parsingOnly' => true),
				array('projects/projectSummary', 'pattern' => '<version:v1>/project/<projectId:\w+>/summary', 'verb' => 'GET', 'parsingOnly' => true),
				array('projects/projects', 'pattern' => '<version:v1>/project/<projectId:\w+>', 'verb' => 'GET', 'parsingOnly' => true),
				array('tasks/create', 'pattern' => '<version:v1>/task', 'verb' => 'POST', 'parsingOnly' => true),

				array('todo/list', 'pattern' => '<version:v1>/projects/<project:\w+>/todos', 'verb' => 'GET', 'parsingOnly' => true),
				array('todo/push', 'pattern' => '<version:v1>/projects/<project:\w+>/todo', 'verb' => 'POST', 'parsingOnly' => true),
				array('todo/resolve', 'pattern' => '<version:v1>/projects/<project:\w+>/todos/<id>', 'verb' => 'DELETE', 'parsingOnly' => true),

				array('karma/list', 'pattern' => '<version:v1>/projects/<project:\w+>/karmas', 'verb' => 'GET', 'parsingOnly' => true),
				array('karma/push', 'pattern' => '<version:v1>/projects/<project:\w+>/karma', 'verb' => 'POST', 'parsingOnly' => true),
				array('karma/resolve', 'pattern' => '<version:v1>/projects/<project:\w+>/karma/<id>', 'verb' => 'POST', 'parsingOnly' => true),

				array('bitbucket/newCommit', 'pattern' => '<version:v1>/bitbucket/commit', 'verb' => 'GET', 'parsingOnly' => true),

				array('impersonate/index', 'pattern' => 'skype', 'parsingOnly' => true),

				'<version:v1>/<controller:\w+>/<id:\d+>'=>'<controller>/view',
				'<version:v1>/<controller:\w+>/<action:\w+>/<id:\d+>'=>'<controller>/<action>',
				'<version:v1>/<controller:\w+>/<action:\w+>'=>'<controller>/<action>',
			),
		),
		'errorHandler'=>array(
			// use 'site/error' action to display errors
			'errorAction'=>'site/error',
		),
		'log'=>array(
			'class'=>'CLogRouter',
			'routes'=>array(
				array(
					'class'=>'CFileLogRoute',
					'levels'=>'error, warning, trace, info',
				),
				// uncomment the following to show log messages on web pages
				/*
				array(
					'class'=>'CWebLogRoute',
				),
				*/
			),
		),
	),

	// application-level parameters that can be accessed
	// using Yii::app()->params['paramName']
	'params'=>array(
		// this is used in contact page
		'adminEmail'=>'webmaster@example.com',
	),
);