<?php
/**
 * CLI input file for different environments. Which are:
 * - prod
 * - local
 * - dev
 * - stage
 * - test
 *
 * @category   Core
 * @subpackage Application
 * @author     Nikolai Besschetnov <lumos.white@gmail.com>
 */

$yiic=dirname(__FILE__).'/../../vendor/yiisoft/yii/framework/yiic.php';
$config = call_user_func(function() use ($argv){
	// test if such config exists
	$environments = ['prod', 'dev', 'local', 'stage', 'test'];
	$cliEnv = @$argv[1];

	if(empty($cliEnv) || !in_array($cliEnv, $environments))
	{
		throw new Exception('Environment key is undefined');
	}

	$config = dirname(__FILE__).'/../config/env/console_'.$cliEnv.'.php';
	if(!file_exists($config))
	{
		throw new Exception('Config file `' . $config . '` does not exists');
	}

	// return path to the config
	return $config;
});

// cleanup environment variable
unset($argv[1]);
$argv = array_values($argv);
$argc--;

// yii will use server arg*
$_SERVER['argv'] = $argv;
$_SERVER['argc'] = $argc;

require_once($yiic);
