<?php

class SiteController extends \components\RestBaseController
{
	public function actionError()
	{
		if (($error=Yii::app()->errorHandler->error))
		{
			$this->setResponse($error);
		}

		$this->forward('index');
	}

	public function actionTest()
	{
		$this->setResponse([
			'hello' => 'world',
		]);
	}

	/**
	 * Method's goal is executing of 3 tasks in a row:
	 *
	 * * User authentication based on `login` and `password`
	 * * Creating token and assosicating it with just signed in user
	 * * Fetching user's active projects
	 *
	 * Existing user tokens will be deactivated (only 1 active session is legal yet).
	 *
	 * @api-method POST
	 * @api-path /authenticate
	 * @api-param-in string requried $login User token
	 * @api-param-in array required $password User profile
	 * @api-param-out array required $projects User's active projects
	 * @api-param-out integer required $projects[count] Quantity of the projects
	 * @api-param-out array required $projects[items] An array of projects
	 * @api-param-out string required $token Fresh token to be used for futher communications
	 * @api-error input Incorrect/Invalid data were supplied as input to the method
	 * @api-error auth_matches No user was found with login/password supplied
	 * @api-error auth_inactive The user was found, but it is inactive, so there is no way for success
	 */
	public function actionSignin()
	{
		// Process with authentication
		$app = Yii::app();
		$username = $app->request->getParam('login');
		$password = $app->request->getParam('password');

		$identity = new \components\UserIdentity($username, $password);
		$obProfile  = $identity->authenticate();

		$arRawProfile = $obProfile->getAttributes();
		$arSafeAttributes = array_fill_keys($obProfile->getSafeAttributeNamesOnSelect(), 1); // @todo CActiveRecord scope

		$arProfile = array_intersect_key($arRawProfile, $arSafeAttributes);
		$arProfile['full_name'] = $obProfile->getFullName();
		$arProfile['avatar'] = $obProfile->getAvatar();
		$arProfile['status_formatted'] = ucfirst($arProfile['status']);

		// Deactivate any other user tokens first
		$obAuthModel = new \models\Auth();
		$obAuthModel->saveUserTokensAsInactive($arProfile['profile_id']);

		// Then generate new token
		/* @var $token \models\Auth */
		$token = $obAuthModel->saveUserToken($arProfile['profile_id']);

		if(sizeof($token->getErrors()))
		{
			throw new \components\exceptions\Attribute($token->getErrors(), "Could not save token {$token->token} for user {$arProfile['profile_id']}");
		}

		// Populate WebUser info
		Yii::app()->user->populateByObject($obProfile);
		Yii::app()->user->createAuthRules();
		Yii::app()->user->assignRoles();

		// What projects user has access to?
		$arProjects = $this->findUserProjects($arProfile['profile_id']);

		// Yay! We are done. Great job everyone!
		$this->setResponse([
			'profile' => $arProfile,
			'projects' => [
				'count' => sizeof($arProjects),
				'items' => $arProjects,
			],
			'token' => $token->getToken(),
		]);
	}

	/**
	 * Signs user out of the system. Method's goal is to deactivate any active user token.
	 * Cookie `user_token` should be deleted by frontend.
	 *
	 * @api-method DELETE
	 * @api-path /authenticate
	 * @api-error token Token was not found
	 * @api-error profile User assosicated with token was not found
	 */
	public function actionSignout()
	{
		$token = \components\Helper::getUserTokenCookie();

		if(!$token)
		{
			Yii::log("An attempt to call SiteController::actionSignout method having empty token");
			$this->setResponse();
		}

		// Let's find user id which is so well hidden under token
		$obToken = \models\Auth::model()->findByToken($token);

		// Fetch user profile
		$obProfile = \models\Profile::model()->findByProfileId($obToken->user_id);

		// We truly believe all user tokens will be deactivated (lol :)
		\models\Auth::model()->saveUserTokensAsInactive($obProfile->profile_id);

		// Guess we are done.
		$this->setResponse();
	}

	/**
	 * Gets token and processes it. Checks wether token active or expired, fetches
	 * user profile which is assosicated with the token
	 *
	 * @api-method GET
	 * @api-path /authenticate
	 * @api-param-out array required $profile User profile
	 * @api-param-out array required $projects User's active projects
	 * @api-param-out integer required $projects[count] Quantity of the projects
	 * @api-param-out array required $projects[items] An array of projects
	 * @api-error token Token was not found
	 * @api-error profile User assosicated with token was not found
	 */
	public function actionPing()
	{
		// Check the token
		$token = \components\Helper::getUserTokenCookie();
		$obToken = \models\Auth::model()->findByToken($token);

		// Fetch user profile
		$obProfile = \models\Profile::model()->findByProfileId($obToken->user_id);
		$arSafeAttributes = array_fill_keys($obProfile->getSafeAttributeNamesOnSelect(), 1);
		$arRawProfile = $obProfile->getAttributes();

		$arProfile = array_intersect_key($arRawProfile, $arSafeAttributes);
		$arProfile['full_name'] = $obProfile->getFullName();
		$arProfile['avatar'] = $obProfile->getAvatar();
		$arProfile['status'] = $arProfile['status'];
		$arProfile['status_formatted'] = ucfirst($arProfile['status']);

		// Here comes the projects
		$arProjects = $this->findUserProjects($arProfile['profile_id']);

		// Annnd we are done.
		$this->setResponse([
			'profile' => $arProfile,
			'projects' => [
				'count' => sizeof($arProjects),
				'items' => $arProjects,
			],
		]);
	}

	/**
	 * Fetches active projects for user `$userId`
	 *
	 * @param integer $userid
	 * @return array An array of projects (some basic information for each of these)
	 * @throws \components\exceptions\Rest
	 */
	protected function findUserProjects($userid)
	{
		$arProjects = [];

		if(Yii::app()->user->checkAccess('superadmin'))
		{
			$obMicroSelector = \models\Project::model()->selectMicro();
			$obProjects = $obMicroSelector->findAll();
			foreach($obProjects as $obProject)
			{
				$arProjects[] = $obProject->getAttributes($obMicroSelector);
			}

			return $arProjects;
		}


		$arProjectIds = [];
		$obProfileHasProjects = \models\ProfileHasProject::model()
		                      ->selectNano()
		                      ->findAllByProfileId($userid);

		foreach($obProfileHasProjects as $obProfileHasProject)
		{
			$arProjectIds[] = $obProfileHasProject->project_id;
		}

		try
		{
			if(sizeof($arProjectIds) <= 0)
			{
				throw new \components\exceptions\Rest(sprintf("No projects are assosicated with the user '%s'", $userid), 'no_projects', 404);
			}

			$obMicroSelector = \models\Project::model()->selectMicro();
			$obProjects = $obMicroSelector->findAllByProjectId($arProjectIds);
			foreach($obProjects as $obProject)
			{
				$arProjects[] = $obProject->getAttributes($obMicroSelector);
			}
		}
		catch(Exception $e)
		{
			Yii::log($e->getMessage(), CLogger::LEVEL_INFO);
		}

		return $arProjects;
	}
}