<?php
/**
 * @todo, Todo model needs to be updated. So does controller later
 */
class TodoController extends \components\RestBaseController
{
	// Access has _only_ bot [!] @todo
	public function actionList()
	{
		$projectId = Yii::app()->request->getParam('project');

		// Get access to the project
		$arProject = \models\Project::loadProject($projectId);

		// Which notes we are looking for?
		$statusRaw = Yii::app()->request->getParam('private', 'no');

		$status = $statusRaw === 'yes'
				? \models\Todo::STATUS_PRIVATE
				: \models\Todo::STATUS_ACTIVE
				;

		if($statusRaw === 'any' && Yii::app()->user->checkAccess('superadmin'))
		{
			$status = [\models\Todo::STATUS_ACTIVE, \models\Todo::STATUS_PRIVATE];
		}

		// Find all of the todos. Yay!
		$arObTodos = \models\Todo::model()->findAllByAttributes([
			'project_id' => $projectId,
			'status' => $status
		]);

		$arTodos = [];
		$arOwners = [];

		foreach($arObTodos as $obTodo)
		{
			$arTodo = $obTodo->getAttributes();
			$arTodo['priority'] = [
				'id' => $arTodo['priority'],
				'value' => ucfirst($arTodo['priority'])
			];
			unset($arTodo['project_id']);

			$arTodos[] = $arTodo;
			$arOwners[] = $obTodo->created_by;
		}

		// get created_by people
		$arObProfiles = \models\Profile::model()->selectNano()->findAllByProfileId($arOwners);
		$arProfiles = [];
		foreach($arObProfiles as $obProfile)
		{
			$arProfiles[$obProfile->profile_id] = [
				'id' => $obProfile->profile_id,
				'full_name' => $obProfile->getFullName(),
			];
		}

		foreach ($arTodos as $i => $arTodo)
		{
			$arTodos[$i]['created_by'] = isset($arProfiles[$arTodo['created_by']])
			                           ? $arProfiles[$arTodo['created_by']]
			                           : false
			                           ;
		}

		$this->setResponse([
			'todos' => [
				'count' => sizeof($arTodos),
				'items' => $arTodos,
			],
			'project' => $arProject,
		]);
	}

	public function actionPush()
	{
		$request = Yii::app()->request;
		$projectId = $request->getParam('project');
		$private = $request->getParam('private', 'no');

		// Get access to the project
		$arProject = \models\Project::loadProject($projectId);

		// User profile
		$obProfile = Yii::app()->user->profile;
		$arProfile = Yii::app()->user->getAttributesNano();
		$arProfile['full_name'] = $obProfile->getFullName();

		// Insert todo
		$priority = trim($request->getParam('priority'));

		if(!in_array($priority, ['high', 'normal', 'low']))
		{
			throw new \components\exceptions\Rest("Unknown priority '$priority'. Allowed values are: high, normal, low", 'priority');
		}

		$status = \models\Todo::STATUS_ACTIVE;
		if($private === 'yes')
		{
			$status = \models\Todo::STATUS_PRIVATE;
		}

		$obTodo = new \models\Todo();
		$obTodo->setAttributes([
			'title' => trim($request->getParam('message')),
			'priority' => $priority,
			'project_id' => $arProject['project_id'],
		]);

		if(!$obTodo->validate())
		{
			throw new \components\exceptions\Attribute($obTodo, 'Could not add the todo. Validation of input fields failed.', 400);
		}

		$obTodo->status = $status;

		if(!$obTodo->save(false))
		{
			throw new \components\exceptions\Attribute($obTodo, 'Could not add the todo. Server error.', 500);
		}

		$arTodo = $obTodo->getAttributes();
		$arTodo['priority'] = [
			'id' => $arTodo['priority'],
			'value' => ucfirst($arTodo['priority'])
		];

		$this->setResponse([
			'todo' => $arTodo,
			'project' => $arProject,
			'profile' => $arProfile,
		]);
	}

	public function actionResolve()
	{
		$request = Yii::app()->request;
		$projectId = $request->getParam('project');
		$todoIds = $request->getParam('id');

		// make list of ids
		$arIds = explode(',', $todoIds);
		array_walk($arIds, function(&$item){
			$item = trim($item);
		});

		// Get access to the project
		$arProject = \models\Project::loadProject($projectId);

		// User profile
		$obProfile = Yii::app()->user->profile;
		$arProfile = Yii::app()->user->getAttributesNano();
		$arProfile['full_name'] = $obProfile->getFullName();

		$obTodos = \models\Todo::model()->findAllByTodoId($arIds);
		$arErrors = [];

		foreach ($obTodos as $obTodo)
		{
			// privacy
			if(!Yii::app()->user->checkAccess('accessTodo', ['todo' => $obTodo]))
			{
				$arErrors[$obTodo->todo_id] = [
					"The todo {$obTodo->todo_id} is private and does not belong to you",
					"todo_access",
					403
				];

				Yii::log($arErrors[$obTodo->todo_id][0], CLogger::LEVEL_INFO);
				continue;
			}

			if(!$obTodo->delete())
			{
				$arErrors[$obTodo->todo_id] = [
					"Could not remove todo with id {$obTodo->todo_id}",
					"todo_remove",
					500
				];

				Yii::log($arErrors[$obTodo->todo_id][0], CLogger::LEVEL_ERROR);
			}
		}

		if(sizeof($arIds) == 1 && sizeof($arErrors) > 0)
		{
			throw new \components\exceptions\Rest($arErrors[0][0], $arErrors[0][1], $arErrors[0][2]);
		}

		$this->setResponse([
			'errors'  => [
				'count' => sizeof($arErrors),
				'items' => $arErrors
			],
			'todos' => [
				'count' => sizeof($arIds),
			],
			'project' => $arProject,
			'profile' => $arProfile,
		]);
	}
}