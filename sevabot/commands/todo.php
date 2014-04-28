<?php
require_once dirname(__FILE__).'/../base.php';
/* @var $factory factory */

class todo
{
	use remote;
	public $attributes;
	public $skype_login;
	public $skype_name;

	function run($attributes, $skype_login, $skype_name)
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

	public function actionList()
	{
		$private_only = isset($this->attributes['private']) ? 'yes' : 'no';
		if(isset($this->attributes['any']))
		{
			$private_only = 'any';
		}

		$ar_response = $this->http('projects/:project/todos', [
			'project' => 'PROJECT_KEY',
			'skype_login' => $this->skype_login,
			'private' => $private_only,
		]);
		$ar_body = $ar_response['response'];

		if($ar_body['todos']['count'] <= 0)
		{
			return 'No todos found. Either you have no access or no tasks yet.';
		}

		$buffer = "\n * Browse \"{$ar_body['project']['title']}\" todos\n-------------------------\n";

		$line_template = "#%d %s[%s] %s @ %s\n%s\n\n";
		foreach ($ar_body['todos']['items'] as $i => $ar_todo)
		{
			$line_formatted = sprintf(
				$line_template,
				$ar_todo['todo_id'],
				($ar_todo['status'] === 'private' ? '[PRIVATE]' : ''),
				$ar_todo['priority']['value'],
				$ar_todo['created_by']['full_name'],
				$ar_todo['created_at'],
				$ar_todo['title']
			);

			$buffer .= $line_formatted;
		}

		$buffer = rtrim($buffer);
		$buffer .= "\n-------------------------\n";

		return $buffer;
	}

	public function actionPush()
	{
		$value = trim($this->attributes['push']);
		if(empty($value))
		{
			throw new Exception("Can't push todo because its summary is empty");
		}
		$private_task = isset($this->attributes['private']) ? 'yes' : 'no';

		$priority = strtolower(isset($this->attributes['priority']) ? $this->attributes['priority'] : 'normal');

		$ar_response = $this->http(
			'projects/:project/todo',
			[
				'project' => 'PROJECT_KEY',
				'skype_login' => $this->skype_login,
			],
			[
				'message' => $this->attributes['push'],
				'priority' => $priority,
				'private' => $private_task,
			],
			'POST'
		);
		$ar_body = $ar_response['response'];

		$buffer  = "\n * TODO status\n-------------------------\n";
		$buffer .= "Pushed to project \"{$ar_body['project']['title']}\"\n";
		$buffer .= sprintf(
			"#%d %s[%s] %s @ %s\n%s\n",
			$ar_body['todo']['todo_id'],
			($ar_body['todo']['status'] === 'private' ? '[PRIVATE]' : ''),
			$ar_body['todo']['priority']['value'],
			$ar_body['profile']['full_name'],
			$ar_body['todo']['created_at'],
			$ar_body['todo']['title']
		);
		$buffer .= "-------------------------\nThank you!\n";

		return $buffer;
	}

	public function actionResolve()
	{
		$value = trim($this->attributes['resolve']);
		if(empty($value))
		{
			throw new Exception("Can't resolve todo because its id is empty");
		}

		$ar_response = $this->http(
			'projects/:project/todos/:id',
			[
				'project' => 'PROJECT_KEY',
				'id' => $value,
				'skype_login' => $this->skype_login,
			],
			[],
			'DELETE'
		);
		$ar_body = $ar_response['response'];

		$buffer  = "\n * Resolve status\n-------------------------\n";

		if($ar_body['errors']['count'] <= 0)
		{
			$buffer .= "Resolved todo #{$value} in project \"{$ar_body['project']['title']}\" by {$ar_body['profile']['full_name']}\n";
			$buffer .= "-------------------------\nThank you!\n";
		}
		else
		{
			$buffer .= sprintf(
				"%s of %s task%s are succesfully resolved.\n",
				$ar_body['todos']['count']-$ar_body['errors']['count'],
				$ar_body['todos']['count'],
				($ar_body['todos']['count'] > 1 ? 's' : '')
			);

			foreach($ar_body['errors']['items'] as $error)
			{
				$buffer .= sprintf("Error: %s\n", $error[0]);
			}

			$buffer .= "-------------------------\n";
		}

		return $buffer;
	}
}