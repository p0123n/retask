<?php
namespace components;
use \Yii;

class RestBaseController extends \CController
{
	public function init()
	{
		Yii::app()->user;
		parent::init();
	}
	public function setResponse($response = null)
	{
		header('Content-Type: application/json; charset=utf-8', true);

		$formatedResponse = [
			'response' => $response,
			'status' => 'success'
		];

		echo json_encode($formatedResponse, JSON_UNESCAPED_UNICODE);
		Yii::app()->end();
	}

	protected function beforeAction($action)
	{
		// $ Impersonalization
		if(Yii::app()->user->getLogin() === 'jayden.jon') // @todo supposed to be adequete if a skype bot requests the action
		{
			// Skype login
			$skypeLogin = trim(Yii::app()->request->getParam('skype_login', ''));
			$obSkypeLogin = \models\ProfileHasSkype::loadSkypeLogin($skypeLogin);

			// impersonate go go
			$obProfile = \models\Profile::model()->findByProfileId($obSkypeLogin->profile_id);
			if($obProfile)
			{
				\Yii::app()->user->populateByObject($obProfile);
			}

			\Yii::app()->user->assignRoles($obProfile->profile_id);
		}

		return parent::beforeAction($action);
	}
}