<?php
class KarmaController extends \components\RestBaseController
{
	// Access has _only_ bot [!] @todo
	public function actionPush()
	{
		$r = Yii::app()->request;
		$projectId = $r->getParam('project');
		$arKarma = $r->getParam('karma', []);
		if(!is_array($arKarma))
		{
			throw new \components\exceptions\Rest("POST `karma` key is not an array", 'input_data', 400);
		}

		// Get access to the project
		$arProject = \models\Project::loadProject($projectId);
		$arKarma['project_id'] = $arProject['project_id'];

		// Of whom are we speaking?
		$arKarma['assigned_to'] = Yii::app()->user->getId();
		if(isset($arKarma['assign_to']))
		{
			$obSkypeLogin = \models\ProfileHasSkype::loadSkypeLogin($arKarma['assign_to']);
			$arKarma['assigned_to'] = $obSkypeLogin->profile_id;
		}

		// Append trace to karma database
		$obKarma = new \models\Karma();
		$obKarma->setAttributes($arKarma);

		if(!$obKarma->validate())
		{
			throw new \components\exceptions\Attribute($obKarma, 'Could not add the trace. Validation of input fields failed.', 400);
		}

		// An attempt to set valid karma status (it should not be resolved/removed). Method does so for us.
		$arStatusChoices = [
			\models\Karma::STATUS_NEGATIVE,
			\models\Karma::STATUS_POSITIVE,
			\models\Karma::STATUS_TRACE,
		];

		if(!in_array($obKarma->status, $arStatusChoices))
		{
			throw new \components\exceptions\Rest(
				"Unknown status {$obKarma->status}. Possible values are " . implode(', ', $arStatusChoices)
			);
		}
		$obKarma->setStatus($obKarma->status);

		if(!$obKarma->saveKarma(false))
		{
			throw new \components\exceptions\Attribute($obKarma, 'Could not add the trace. Server error.', 500);
		}

		// Format api trace object
		$arResponseKarma = $obKarma->getAttributes();
		$obSelector = \models\Profile::model()->selectNano();
		$obAssignee = $obSelector->findByProfileId($obKarma->assigned_to);
		$arResponseKarma['assigned_to'] = $obAssignee->getAttributes($obSelector);
		$arResponseKarma['assigned_to']['full_name'] = $obAssignee->getFullName();
		$arResponseKarma['status'] = [
			'id' => $arResponseKarma['status'],
			'value' => $obKarma->getStatusFormatted()
		];

		$this->setResponse([
			'karma' => $arResponseKarma,
			'profile' => Yii::app()->user->getAttributesNano(),
			'project' => $arProject
		]);
	}

	public function actionList()
	{
		$r = Yii::app()->request;
		$projectId = $r->getParam('project');
		$date = $r->getParam('date', '');

		// Get access to the project
		$arProject = \models\Project::loadProject($projectId);

		// Format interval
		if(empty($date) || strtotime($date) === false)
		{
			$date = \components\Helper::date('Y-m-d');
		}

		$arIntervals= ["$date 00:00:00", "$date 23:59:59"]; // http://www.php.net/manual/en/function.checkdate.php ?

		$obKarmas = \models\Karma::model()->findAllByOwnerId(Yii::app()->user->getId(), $arIntervals);
		$arKarmas = [];
		$arProfileIds = [];

		foreach($obKarmas as $obKarma)
		{
			$arKarma = $obKarma->getAttributes();

			$arKarma['traced_at_formatted'] = \components\Helper::date('H:i', strtotime($arKarma['traced_at']));
			$arKarma['status'] = [
				'id' => $arKarma['status'],
				'value' => $obKarma->getStatusFormatted()
			];

			$resolutionTime = $obKarma->getResolutionTime();
			$arKarma['resolution_time'] = false;
			if($resolutionTime)
			{
				$arKarma['resolution_time'] = \components\Helper::dateDiffFormatted($resolutionTime);
			}

			$arProfileIds[] = $arKarma['assigned_to'];
			$arKarmas[] = $arKarma;
		}

		$obSelector = \models\Profile::model()->selectNano();
		$obProfiles = $obSelector->findAllByProfileId($arProfileIds);
		$arProfiles = [];

		foreach($obProfiles as $obProfile)
		{
			$arProfiles[$obProfile->profile_id] = $obProfile->getAttributes($obSelector);
			$arProfiles[$obProfile->profile_id]['full_name'] = $obProfile->getFullName();
		}

		foreach($arKarmas as $i => $arKarma)
		{
			$arKarmas[$i]['assigned_to'] = isset($arProfiles[$arKarma['assigned_to']])
			                             ? $arProfiles[$arKarma['assigned_to']]
			                             : false
			                             ;
		}

		$this->setResponse([
			'karmas' => [
				'count' => sizeof($arKarmas),
				'items' => $arKarmas,
			],
			'today_date' => \components\Helper::date('d, l', strtotime($date)),
			'project' => $arProject,
		]);
	}

	public function actionResolve()
	{
		$r = Yii::app()->request;
		$projectId = $r->getParam('project');
		$karmaId = $r->getParam('id', '');

		// Get access to the project
		$arProject = \models\Project::loadProject($projectId);

		$obKarma = \models\Karma::model()->findByPk($karmaId);
		if(!Yii::app()->user->checkAccess('accessKarmaPiece', ['karma' => $obKarma]))
		{
			throw new \components\exceptions\Rest("This karma piece '$karmaId' is not yours", 'karma');
		}

		$obKarma->setResolved();

		if(!$obKarma->validate())
		{
			throw new \components\exceptions\Attribute($obKarma, 'Could not add the trace. Validation of input fields failed.', 400);
		}

		if(!$obKarma->saveKarma(false))
		{
			throw new \components\exceptions\Attribute($obKarma, 'Could not add the trace. Server error.', 500);
		}

		// Format api trace object
		$arResponseKarma = $obKarma->getAttributes();
		$obSelector = \models\Profile::model()->selectNano();
		$obAssignee = $obSelector->findByProfileId($obKarma->assigned_to);
		$arResponseKarma['assigned_to'] = $obAssignee->getAttributes($obSelector);
		$arResponseKarma['assigned_to']['full_name'] = $obAssignee->getFullName();
		$arResponseKarma['status'] = [
			'id' => $arResponseKarma['status'],
			'value' => $obKarma->getStatusFormatted()
		];

		$resolutionTime = $obKarma->getResolutionTime();
		$arResponseKarma['resolution_time'] = false;
		if($resolutionTime)
		{
			$arResponseKarma['resolution_time'] = \components\Helper::dateDiffFormatted($resolutionTime);
		}

		$this->setResponse([
			'karma' => $arResponseKarma,
			'profile' => Yii::app()->user->getAttributesNano(),
			'project' => $arProject
		]);
	}
}