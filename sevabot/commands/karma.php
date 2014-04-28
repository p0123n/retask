<?php
require_once dirname(__FILE__).'/../base.php';
/* @var $factory factory */

class karma
{
	use remote;
	public $attributes;
	public $skype_login;
	public $skype_name;

	function run($attributes, $skype_login, $skype_name) // @todo refactor
	{
		$this->attributes = $attributes;
		$this->skype_login = $skype_login;
		$this->skype_name = $skype_name;

		$registeredActions = [
			'list',
			'push',
			'resolve',
		];

		$gotKeys = array_keys($attributes);
		$ar_intersection = array_intersect($gotKeys, $registeredActions);

		if(sizeof($ar_intersection) <= 0)
		{
			throw new Exception(sprintf("Action is not found. Registered actions are: %s.", implode(', ', $registeredActions)));
		}

		if(sizeof($ar_intersection) > 1)
		{
			throw new Exception(sprintf("Your query matches a few actions of this command: %s. Please, select one.", implode(', ', $ar_intersection)));
		}

		$command = array_pop($ar_intersection);
		return $this->{'action'.ucfirst($command)}();
	}

	public function actionPush()
	{
		$status = isset($this->attributes['status']) ? $this->attributes['status'] : 'trace';
		$date_time = isset($this->attributes['datetime']) ? $this->attributes['datetime'] : null;
		$person = isset($this->attributes['person']) ? $this->attributes['person'] : null;
		$date_time = str_replace('/', '-', $date_time);

		$ar_response = $this->http(
			'projects/:project/karma',
			[
				'project' => 'PROJECT_KEY',
				'skype_login' => $this->skype_login,
			],
			[
				'karma' => [
					'title' => $this->attributes['push'],
					'status' => $status,
					'traced_at' => $date_time,
					'assign_to' => $person,
				],
			],
			'POST'
		);
		$ar_body = $ar_response['response'];

		$buffer = "\n * KARMA status\n-------------------------\n";
		$buffer .= "Pushed to project \"{$ar_body['project']['title']}\"\n";
		$buffer .= sprintf(
			"#%d [%s] %s @ %s\n%s\n",
			$ar_body['karma']['karma_id'],
			$ar_body['karma']['status']['value'],
			$ar_body['profile']['full_name'],
			$ar_body['karma']['traced_at'],
			$ar_body['karma']['title']
		);
		$buffer .= "-------------------------\nThank you!\n";

		return $buffer;
	}

	public function actionResolve()
	{
		$ar_response = $this->http(
			'projects/:project/karma/:id',
			[
				'project' => 'PROJECT_KEY',
				'id' => ltrim($this->attributes['resolve'], '0'),
				'skype_login' => $this->skype_login,
			],
			[],
			'POST'
		);
		$ar_body = $ar_response['response'];
		$resolution = " > resolved on {$ar_body['karma']['resolved_at']} ({$ar_body['karma']['resolution_time']})";

		$buffer = "\n * KARMA status\n-------------------------\n";
		$buffer .= "Resolved in project \"{$ar_body['project']['title']}\"\n";
		$buffer .= sprintf(
			"#%d [%s] %s @ %s%s\n%s\n",
			$ar_body['karma']['karma_id'],
			$ar_body['karma']['status']['value'],
			$ar_body['profile']['full_name'],
			$ar_body['karma']['traced_at'],
			$resolution,
			$ar_body['karma']['title']
		);
		$buffer .= "-------------------------\nThank you!\n";

		return $buffer;
	}

	public function actionList()
	{
		$date = isset($this->attributes['date']) ? $this->attributes['date'] : null;
		$date = str_replace('/', '-', $date);
		$ar_response = $this->http(
			'projects/:project/karmas',
			[
				'project' => 'PROJECT_KEY',
				'skype_login' => $this->skype_login,
				'date' => $date,
			]
		);
		$ar_body = $ar_response['response'];

		$buffer = "\n * KARMA on {$ar_body['today_date']}\n-------------------------\n";
		if($ar_body['karmas']['count'] <= 0)
		{
			$buffer .= "No records yet. Weird...\n";
		}
		foreach($ar_body['karmas']['items'] as $ar_karma)
		{
			$resolution = '';

			if($ar_karma['resolved_at'])
			{
				$resolution = " > resolved on {$ar_karma['resolved_at']} ({$ar_karma['resolution_time']})";
			}

			$buffer .= sprintf(
				"~ %s # %05d [%s] %s%s\n %s\n",
				$ar_karma['traced_at_formatted'],
				$ar_karma['karma_id'],
				$ar_karma['status']['value'],
				$ar_karma['assigned_to']['full_name'],
				$resolution,
				$ar_karma['title']
			);
		}

		$buffer .= "-------------------------\n";

		return $buffer;
	}
}