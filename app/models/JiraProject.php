<?php
namespace models;

class JiraProject extends \CModel
{
	protected $greenhopperApi = 'https://###JIRA_ON_DEMAND.jira.com/rest/greenhopper/1.0/xboard/plan/backlog/data.json?rapidViewId=%s';
	protected $login = '';
	protected $password = '';

	const SPRINT_CLOSED = 'CLOSED';
	const SPRINT_ACTIVE = 'ACTIVE';
	const SPRINT_FUTURE = 'FUTURE';

	public function attributeNames()
	{
		return [
			'epics' => 'Epics',
			'sprints' => 'Sprints',
		];
	}

	public function setCredentials($login, $password)
	{
		$this->login = $login;
		$this->password = $password;

		return $this;
	}

	public function findAllByBoardId($boardId)
	{
		$arJson = $this->query($boardId);
		return $arJson;
	}

	protected function query($boardId)
	{
		$url = sprintf($this->greenhopperApi, $boardId);

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_USERPWD, "###JIRA_USER_USERNAME:###JIRA_USER_PASSWORD");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

		$result = curl_exec($ch);

		return json_decode($result, true);
	}
}