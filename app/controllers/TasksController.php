<?php

class TasksController extends \components\RestBaseController
{
	public function actionCreate()
	{
		$rawTask = Yii::app()->request->getParam('task');

		if(!isset($rawTask['base']['description']) || empty($rawTask['base']['description']))
		{
			$rawTask['base']['description'] = 'Unknown';
		}

		if(!isset($rawTask['base']['acceptance']) || empty($rawTask['base']['acceptance']))
		{
			$rawTask['base']['acceptance'] = 'Unknown';
		}

		$description = "h4. Task statement
{$rawTask['base']['description']}

h4. Acceptance terms
{$rawTask['base']['acceptance']}

Results must be pushed to git branch {{{$rawTask['base']['git_branch']}}}
";

		$arTask = [
			'summary' => $rawTask['base']['summary'],
			'description' => $description,
			'dueDate' => $rawTask['base']['due'],
			'epicLink' => $rawTask['base']['epic'],
		];

		switch($rawTask['base']['work_type'])
		{
			case 'javascript':
				$arTask['workType'] = '10102';
				break;
			default:
				$arTask['workType'] = '10103'; // php
		}

		$data = [
			'fields' => [
				'project' => [
					'key' => '###PROJECT_KEY',
				],
				'summary' => $arTask['summary'],
				'description' => $arTask['description'],
				'duedate' => $arTask['dueDate'],
				'assignee' => [
					'name' => '###JIRA_USER_USERNAME',
				],
				'issuetype' => [
					'name' => 'Задача',
				],
				'fixVersions' => [
					[
						'id' => '11513',
					],
				],
				'customfield_10810' => [
					'id' => $arTask['workType'],
				],
				'customfield_11010' => $arTask['epicLink'],
			],
		];

		$dataString = json_encode($data);

		$ch = curl_init('https://###JIRA_ON_DEMAND.jira.com/rest/api/latest/issue/');
		curl_setopt($ch, CURLOPT_USERPWD, "###JIRA_USER_USERNAME:###JIRA_USER_PASSWORD");
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Accept: application/json',
			'Content-Length: ' . strlen($dataString))
		);

		$result = curl_exec($ch);
		header("Content-type: text/plain");

		$arTask = json_decode($result, true);
		if(empty($arTask))
		{
			throw new \components\exceptions\Rest('Could not create task', 'task');
		}

		$this->setResponse([
			'task' => [
				'key' => $arTask['key'],
			]
		]);
	}
}