<?php
require_once('components/console.php');

/**
 *
 */
trait remote
{
	// to config
	public $token = '5405dd43774c0088115aeeb2347181ca';
	public $xsrf_token = '27af012995ddf6806a6dbe36cebda2260a552a43';
	public $domain = 'http://your_domain/v1/';
	// \ to config

	function http($method, array $params = [], array $data = [], $verb = 'GET')
	{
		$url = $this->domain.ltrim($method, '/');
		if(sizeof($params))
		{
			$url_str = http_build_query($params);
			$url = $url . '?' . $url_str;
		}

		foreach($params as $k => $v)
		{
			$url = str_replace(":$k", $v, $url);
		}

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $verb);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

		curl_setopt($ch, CURLOPT_USERPWD, 'login:password');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch,CURLOPT_COOKIE, 'user_token='.$this->token.'; XSRF-TOKEN='.$this->xsrf_token);

		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'X-XSRF-TOKEN: ' . $this->xsrf_token,
			'Accept: application/json',
		));

		$result = curl_exec($ch);

		if(!$result)
		{
			throw new Exception('Communication with API error (transfer)');
		}

		$ar_data = json_decode($result, true);

		if(!$ar_data || !is_array($ar_data) || !isset($ar_data['status']) || !isset($ar_data['response']))
		{
			throw new Exception('Communication with API error (parse)');
		}

		if($ar_data['status'] === 'error')
		{
			if(is_array($ar_data['response']['message']))
			{
				$arBuffer = [];
				foreach($ar_data['response']['message']['errors'] as $field => $error)
				{
					$strError = implode(', ', $error);
					$arBuffer[] = "$field – $strError";
				}

				$ar_data['response']['message'] = $ar_data['response']['message']['message']
				                                ."\n"
				                                .implode("\n", $arBuffer)
				                                ."\n"
				                                ;
			}

			throw new Exception(sprintf('%s (%s)', $ar_data['response']['message'], $ar_data['response']['code']));
		}

		return $ar_data;
	}
}

/**
 *
 */
class controller
{
	function run($argv)
	{
		$skype_login = $argv[1];
		$skype_fullname = base64_decode($argv[2]);
		$b64command = implode(' ', array_slice($argv, 3));
		$command = substr(base64_decode($b64command), 7);

		$error = 0;
		$con   = new console();
		if( ($error = $con->parse($command)) )
		{
			// Обнаружены ошибки
			echo $con->get_error($error);
		}
		else
		{
			$command_name = $con->chunks['name'];
			$values = $con->chunks['key_values'];

			$file = dirname(__FILE__).'/commands/'.$command_name.'.php';
			if(!file_exists($file))
			{
				throw new Exception('The command is not found');
			}

			require_once $file;
			$class = new $command_name();
			$str_answer = $class->run($values, $skype_login, $skype_fullname);

			die($str_answer);
		}
	}
}

/**
 *
 */
try
{
	$controller = new controller();
	$controller->run($argv);
}
catch(Exception $e)
{
	$str = $e->getMessage();
	// latin. Will go boom on utf and such
	die('[!] ' . $str . "\n\n");
}
