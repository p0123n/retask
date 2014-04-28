<?php

class ProjectsController extends \components\RestBaseController
{
	public function actionProjects($projectId)
	{
		$hasAccess = Yii::app()->user->checkAccess('browseProject', [
			'project_id' => $projectId,
		]);

		if(!$hasAccess)
		{
			throw new \components\exceptions\Rest("Unfortunately, you have no access to this project", 'project_access', 403);
		}

		$project = \models\Project::model()->findByProjectId($projectId);
		$arProject = $project->getAttributes();

		// Members and roles list
		// @todo Inverse table
		$arMembersIds = [];
		$arMemberIdRole = [];

		$arObMembersIds = \models\ProfileHasProject::model()->findAllByProjectId($projectId);
		foreach ($arObMembersIds as $obMemberId)
		{
			$arMembersIds[] = $obMemberId->profile_id;
			$arMemberIdRole[$obMemberId->profile_id] = [
				'role' => $obMemberId->role,
				'role_formatted' => $obMemberId->getRoleFormated()
			];
		}

		$obProfileMicroSelector = \models\Profile::model()->selectMicro()->sortByName();
		$arObMembers = $obProfileMicroSelector->findAllByProfileId($arMembersIds);
		$arMembers = [];
		foreach($arObMembers as $obMember)
		{
			$arMember = $obMember->getAttributes($obProfileMicroSelector);
			$arMember['full_name'] = $obMember->getFullName();
			$arMember['avatar'] = $obMember->getAvatar();
			$arMember = array_merge($arMember, $arMemberIdRole[$obMember->profile_id]);

			$arMembers[] = $arMember;
		}

		$this->setResponse([
			'project' => $arProject,
			'members' => [
				'count' => sizeof($arMembers),
				'items' => $arMembers,
			]
		]);
	}

	public function actionProjectSummary($projectId)
	{
		$hasAccess = Yii::app()->user->checkAccess('browseProject', [
			'project_id' => $projectId,
		]);

		if(!$hasAccess)
		{
			throw new \components\exceptions\Rest("Unfortunately, you have no access to this project", 'project_access', 403);
		}

		$resetCache = Yii::app()->request->getParam('force', false);
		$obProject = \models\Project::model()->findByProjectId($projectId);
		// here we get boardId actually @todo

		$boardId = 71;
		$fixVersionId = 11513;

		$cacheKey = sprintf('%s.%s.%s', $obProject->project_id, $boardId, $fixVersionId);
		$arSummary = [];

		if($resetCache || ($arSummary =Yii::app()->cache->get($cacheKey)) === false)
		{
			$jira = new \models\JiraProject();
			$arSummary = $jira->findAllByBoardId($boardId);

			if(!$arSummary)
			{
				throw new \components\exceptions\Rest('Could not fetch and parse JSON data from Jira API', 'summary', 500);
			}

			Yii::app()->cache->set($cacheKey, $arSummary, 1*24*60*60);
		}

		$arReport = [
			'sprints' => [
				'count' => 0,
				'items' => [],
			],
			'epics' => [
				'count' => 0,
				'items' => [],
			],
		];

		// I have lack of time, so for sake of speeding up, I will not check for isset() or empty() of fields. @todo
		foreach($arSummary['sprints'] as $arSprint)
		{
			$arSprintFormatted = [
				'id' => $arSprint['id'],
				'title' => $arSprint['name'],
				'state' => $arSprint['state'],
				'state_formatted' => ucfirst(strtolower($arSprint['state'])),
				'startDate' => $arSprint['startDate'] !== 'None' ? $arSprint['startDate'] : false,
				'endDate' => $arSprint['endDate'] !== 'None' ? $arSprint['endDate'] : false, // I just figured out. What timezone we are speaking about? @todo
				'completeDate' => $arSprint['completeDate'] !== 'None' ? $arSprint['completeDate'] : false,
				'issuesCount' => isset($arSprint['issuesIds']) && is_array($arSprint['issuesIds']) ? sizeof($arSprint['issuesIds']) : 0,
			];

			if($arSprintFormatted['startDate'] && $arSprintFormatted['endDate'])
			{
				$arSprintFormatted['dateInterval'] = sprintf('%s - %s', $arSprintFormatted['startDate'], $arSprintFormatted['endDate']);
			}
			else
			{
				$arSprintFormatted['dateInterval'] = 'Sprint duration interval is not set yet';
			}

			if(isset($arSprint['timeRemaining']))
			{
				$arSprintFormatted['timeRemaining'] = [
					'text' => $arSprint['timeRemaining']['text'],
				];
			}

			$arReport['sprints']['items'][] = $arSprintFormatted;
		}

		foreach($arSummary['epicData']['epics'] as $arEpic)
		{
			if(!in_array($fixVersionId, $arEpic['fixVersions']))
			{
				continue;
			}

			$arEpicFormatted = [
				'key' => $arEpic['key'],
				'done' => $arEpic['done'],
				'title' => $arEpic['summary'],
			];

			if(isset($arEpic['epicStats']))
			{
				$map = [
					'notDoneEstimate',
					'doneEstimate',
					'totalEstimate',
					'percentageCompleted',
					'estimated',
					'percentageEstimated',
					'notEstimated',
					'percentageUnestimated',
					'notDone',
					'done',
					'totalIssueCount',
				];

				$arEpicFormatted['stats'] = [];

				foreach($map as $m)
				{
					$arEpicFormatted['stats'][$m] = $arEpic['epicStats'][$m];
				}
			}

			$arReport['epics']['items'][] = $arEpicFormatted;
		}

		// sort stuff
		usort($arReport['epics']['items'], function($a, $b){
			return strcasecmp($a['title'], $b['title']);
		});

		// make json_encode think it is an array, not an object
		$arReport['epics']['items'] = array_values($arReport['epics']['items']);
		$arReport['sprints']['items'] = array_values($arReport['sprints']['items']);

		$arReport['sprints']['count'] = sizeof($arReport['sprints']['items']);
		$arReport['epics']['count'] = sizeof($arReport['epics']['items']);

		$this->setResponse($arReport);
	}
}