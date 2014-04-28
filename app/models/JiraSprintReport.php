<?php
/**
 * Would be cool to rewrite that shit below
 */
namespace models;

class JiraSprintReport {

	public $sprintId;
	public $config;
	public $tempoUrl;
	public $jql;
	public $sprint;
	public $projectKey = '###PROJECT_KEY';
	public $tempoApiToken = '###TEMPO_API_TOKEN';

	public $epicTask = [];
	public $tasksIds = [];

	public $summary = [];

	const STATUS_CLOSED = 'Закрыта';
	const STATUS_COMPLETED = 'Готово к тестированию';
	const STATUS_OPEN = 'Открыта';
	const STATUS_RETURNED = 'Открыта заново';
	const STATUS_IN_PRODUCTION = 'Ушло в продакшен';
	const STATUS_TEST_FAILED = 'Тестирование не удалось';
	const STATUS_TEST_SUCCESSFUL= 'Протестировано';
	const STATUS_TEST_IN_PROGRESS= 'В тестировании';
	const STATUS_AT_WORK = 'В работе';

	// replaces original jira's values with these
	const TYPE_PROGRAMMING_JS = 'JavaScript';
	const TYPE_PROGRAMMING_HTML = 'Верстка'; // << NAMING CONVENTIONS
	const TYPE_PROGRAMMING_PHP = 'PHP';

	public function __construct()
	{
		// - Configuration -
		$this->config = [
			'username' => '###JIRA_USER_USERNAME',
			'password' => '###JIRA_USER_PASSWORD',
			'maxResults' => 100,
			'statusDoneId' => [
				6,
			],
		];

		$this->summary['total'] =
		$this->summary['frontend'] =
		$this->summary['backend'] =
		$this->summary['unknown'] = [
			'statuses' => [],
			'total' => 0,
			'opened' => 0,
			'closed' => 0,
			'seconds_worked' => 0,
			'seconds_planned' => 0,
		];
	}

	public function findAll($sprintId)
	{
		$this->sprintId = $sprintId;

		try
		{
			$iterate = 0;
			$subtasks = array();

			do
			{
				$json = $this->tasksExtract($iterate);
				static $totalTasks = false;
				if($totalTasks === false)
				{
					$totalTasks = isset($json['total']) ? $json['total'] : false;
				}

				foreach($json['issues'] as $issue)
				{
					$epicId = $issue->fields->customfield_11010;
					$this->tasksIds[$issue->key] = $issue->fields->customfield_11010;
					if(!isset($this->epicTask[$epicId]))
					{
						$this->epicTask[$epicId] = [
							'title' => $epicId,
							'issues' => [],
							'hasSubtasks' => false,
							'total' => [],
							'backend' => [],
							'frontend' => [],
							'unknown' => [],
						];

						$this->epicTask[$epicId]['total'] =
						$this->epicTask[$epicId]['backend'] =
						$this->epicTask[$epicId]['frontend'] =
						$this->epicTask[$epicId]['unknown'] = [
							'statuses' => [],
							'total' => 0,
							'opened' => 0,
							'closed' => 0,
							'seconds_worked' => 0,
							'seconds_planned' => 0,
						];
					}

					if(@$issue->fields->parent)
					{
						$subtasks[] = $issue;
						continue;
					}

					$this->pushTask($issue, $epicId);
				}

				if(!$totalTasks)
				{
					throw new \Exception('"Total tasks" is not a positive integer');
				}

				$iterate += $this->config['maxResults'];
			}
			while($totalTasks && $totalTasks > $iterate);

			foreach($subtasks as $subtask)
			{
				$parentId = $subtask->fields->parent->key;
				$epicId = @$this->tasksIds[$parentId];

				$subtask->fields->customfield_11010 = $epicId;
				$this->pushTask($subtask, $epicId, $parentId);
			}

			// grap epic extra info
			$epicIssues = $this->getIssues(array_filter(array_keys($this->epicTask)));
			foreach($epicIssues['issues'] as $issue)
			{
				$this->epicTask[$issue->key]['title'] = $issue->fields->summary;
			}

			// sort epics
			uksort($this->epicTask, function($a, $b){
				$a = array_pop(explode('-', $a));
				$b = array_pop(explode('-', $b));

				if ($a == $b)
				{
					return 0;
				}

				return ($a < $b) ? -1 : 1;
			});

			// template it
			return [
				'epics' => $this->epicTask,
				'sprint' => $this->sprint,
				'summary' => $this->summary
			];
		}
		catch(\Exception $e)
		{
			throw new \CHttpException(503, $e->getMessage());
		}
	}

	public function getTempoData($from, $to, $projectKey = null)
	{
		$unixFrom = strtotime($from);
		$unixTo = strtotime($to);

		$formatedFrom = date('Y-m-d', $unixFrom);
		$formatedTo = date('Y-m-d', $unixTo);

		if(!$formatedFrom)
		{
			throw new Exception('Illegal format of "from" date');
		}

		if(!$formatedTo)
		{
			throw new Exception('Illegal format of "to" date');
		}

		// - Tempo -
		$this->tempoUrl = 'https://###JIRA_ON_DEMAND.jira.com/plugins/servlet/tempo-getWorklog/?dateFrom='.$formatedFrom.'&dateTo='.$formatedTo.'&format=xml&addIssueDetails=true&projectKey='.$this->projectKey.'&tempoApiToken='.$this->tempoApiToken;

		$strXml = @file_get_contents($this->tempoUrl);
		if(!$strXml)
		{
			throw new Exception('Could not get tempo XML');
		}

		$obXml = @simplexml_load_string($strXml);
		if(!$obXml)
		{
			throw new Exception('Could not parse string as XML');
		}

//		var_dump($obXml);
//		die;
	}

	protected function pushTask(\stdClass $issue, $epicId, $parentId = null)
	{
		$fields = $issue->fields;
		switch($fields->customfield_10810->id)
		{
			case '10103':
				$fields->customfield_10810->value = self::TYPE_PROGRAMMING_PHP;
			break;
			case '10102':
				$fields->customfield_10810->value = self::TYPE_PROGRAMMING_JS;
			break;
		}

		$this->epicTask[$epicId]['issues'][] = [
			'id' => $issue->key,
			'parentId' => $parentId,
			'title' => $fields->summary,
			'type' => $fields->customfield_10810->value,
			'status' => $fields->status->name,
			'assignee_id' => $fields->assignee ? $fields->assignee->name : 'unknown',
			'assignee' => $fields->assignee ? $fields->assignee->displayName : '<Unassigned>',
		];

		switch ($fields->customfield_10810->value)
		{
			case self::TYPE_PROGRAMMING_JS:
			case self::TYPE_PROGRAMMING_HTML:
				$this->epicStatisticsQuantity($epicId, 'frontend', $fields->status->name);
				$this->epicStatisticsTime($epicId, 'frontend', 'planned', $fields->timeoriginalestimate);
			break;
			case self::TYPE_PROGRAMMING_PHP:
				$this->epicStatisticsQuantity($epicId, 'backend', $fields->status->name);
				$this->epicStatisticsTime($epicId, 'backend', 'planned', $fields->timeoriginalestimate);
			break;
			default:
				$this->epicStatisticsQuantity($epicId, 'unknown', $fields->status->name);
				$this->epicStatisticsTime($epicId, 'unknown', 'planned', $fields->timeoriginalestimate);
		}

		$this->epicStatisticsQuantity($epicId, 'total', $fields->status->name);
		$this->epicStatisticsTime($epicId, 'total', 'planned', $fields->timeoriginalestimate);

		if(empty($this->sprint) && $issue->fields->customfield_10510)
		{
			$rawSprintName = isset($fields->customfield_10510[0]) ? $fields->customfield_10510[0] : '';
			if($rawSprintName)
			{
				$ar = [];
				preg_match_all('/\[([^\]]+)\]/', $rawSprintName, $ar);
				$this->sprint = @$ar[1][0];
				if($this->sprint)
				{
					$raw2 = explode(',', $this->sprint);
					$this->sprint = [];
					foreach($raw2 as $r)
					{
						$raw3 = explode('=', $r);
						$this->sprint[$raw3[0]] = @$raw3[1];
					}
				}
			}
		}
	}

	protected function epicStatisticsQuantity($epicId, $section, $status)
	{
		$bClosed = in_array($status, [self::STATUS_CLOSED, self::STATUS_TEST_SUCCESSFUL, self::STATUS_COMPLETED]);

		if(!isset($this->epicTask[$epicId][$section]['statuses'][$status]))
		{
			$this->epicTask[$epicId][$section]['statuses'][$status] = 0;
		}

		$this->epicTask[$epicId][$section]['statuses'][$status]++;
		$this->epicTask[$epicId][$section][($bClosed ? 'closed' : 'opened')]++;
		$this->epicTask[$epicId][$section]['total']++;

		// summary
		if(!isset($this->summary[$section]['statuses'][$status]))
		{
			$this->summary[$section]['statuses'][$status] = 0;
		}

		$this->summary[$section]['statuses'][$status]++;
		$this->summary[$section][($bClosed ? 'closed' : 'opened')]++;
		$this->summary[$section]['total']++;
	}

	protected function epicStatisticsTime($epicId, $section, $kind, $time)
	{
		if(!in_array($kind, ['planned', 'worked']))
		{
			throw new \Exception('Unknown kind of time ' . $kind);
		}

		$this->epicTask[$epicId][$section]['seconds_'.$kind] += $time;
	}

	protected function tasksExtract($iteration = 0)
	{
		$this->jql = 'Sprint = ' . $this->sprintId . ' AND issuetype in (Bug, Подзадача, Задача)';

		/**
		 * Get all tasks via JQL first. Extract epic IDs
		 */
		$jqlUrl = sprintf(
			'https://%s:%s@###JIRA_ON_DEMAND.jira.com/rest/api/latest/search?jql=%s&startAt=%d&maxResults=%d&fields=',
			$this->config['username'],
			$this->config['password'],
			urlencode($this->jql),
			$iteration,
			$this->config['maxResults']
		);

		$info = @file_get_contents($jqlUrl);
		if(!$info)
		{
			throw new \Exception('Could not fetch JSON we are looking for');
		}

		$jsonInfo = (array)json_decode($info);

		if(empty($jsonInfo))
		{
			throw new \Exception('Could not parse api response');
		}

		return $jsonInfo;
	}

	public function getIssues(array $issueIds)
	{
		$this->jql = 'issuekey in('.implode(',', $issueIds).')';

		/**
		 * Get all tasks via JQL first. Extract epic IDs
		 */
		$jqlUrl = sprintf(
			'https://%s:%s@###JIRA_ON_DEMAND.jira.com/rest/api/latest/search?jql=%s&startAt=%d&maxResults=%d&fields=',
			$this->config['username'],
			$this->config['password'],
			urlencode($this->jql),
			0,
			$this->config['maxResults']
		);

		$info = @file_get_contents($jqlUrl);
		if(!$info)
		{
			throw new \Exception('Could not fetch JSON');
		}

		$jsonInfo = (array)json_decode($info);

		if(empty($jsonInfo))
		{
			throw new \Exception('Could not parse api response');
		}

		return $jsonInfo;
	}
}