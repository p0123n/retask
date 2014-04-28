<?php
namespace components;
use Yii;

class WebUser extends \CComponent implements \IWebUser
{
	/**
	 * If true, `WebUser::$profile` will be populated based on token in cookies
	 * @var boolean
	 */
	public $autoPopulate = true;

	/**
	 * @var \models\Profile
	 */
	public $profile = null;

	private $_access = [];

	public function init()
	{
		$userToken = Helper::getUserTokenCookie();
		if(!$userToken)
		{
			return;
		}

		if($this->autoPopulate)
		{
			$this->populateByToken($userToken);
		}

		$this->createAuthRules();
		$this->assignRoles();
	}

	public function populateByToken($token)
	{
		try
		{
			$obToken = \models\Auth::model()->findByToken($token);
			if($obToken)
			{
				$this->populateByProfileId($obToken->user_id);
			}
		}
		catch(Exception $e)
		{
			Yii::log("An attempt to call `{$this->getRouteFormated()}` with token `$token` but no result. Details: " . \CVarDumper::dumpAsString($e));
		}
	}

	public function populateByProfileId($profileId)
	{
		try
		{
			$obProfile = \models\Profile::model()->findByProfileId($profileId);
			if($obProfile)
			{
				$this->populateByObject($obProfile);
			}
		}
		catch(Exception $e)
		{
			Yii::log("An attempt to call `{$this->getRouteFormated()}` having `$profileId` but no result. Details: " . \CVarDumper::dumpAsString($e));
		}
	}

	public function populateByObject(\models\Profile $obProfile)
	{
		$this->profile = $obProfile;
	}

	public function checkAccess($operation,$params=array(),$allowCaching=true)
	{
		if($allowCaching && $params===array() && isset($this->_access[$operation]))
			return $this->_access[$operation];

		$access=Yii::app()->getAuthManager()->checkAccess($operation,$this->getId(),$params);
		if($allowCaching && $params===array())
			$this->_access[$operation]=$access;

		return $access;
	}

	public function getName()
	{
		if($this->profile instanceof \models\Profile)
		{
			return $this->profile->getFullName();
		}
	}

	public function getId()
	{
		if($this->profile instanceof \models\Profile)
		{
			return $this->profile->profile_id;
		}
	}

	public function getLogin()
	{
		if($this->profile instanceof \models\Profile)
		{
			return $this->profile->login;
		}
	}

	public function getIsGuest()
	{
		return !($this->profile instanceof \models\Profile);
	}

	protected function getRouteFormated()
	{
		if(\Yii::app()->controller)
		{
			return \Yii::app()->controller->route;
		}

		return 'unknown';
	}

	public function createAuthRules()
	{
		$auth=Yii::app()->authManager;

		/**
		 * Checks if user has access to project
		 * @param array $params Accepts keys `project_id`.
		 */
		$bizAccessProjectRule = function($params)
		{
			$userId = isset($params['profile_id']) ? $params['profile_id'] : $this->getId();
			$projectId = isset($params['project_id']) && $params['project_id']
			           ? trim($params['project_id'])
			           : false;

			if(!$projectId || !$userId)
			{
				return false;
			}

			$obProfileAccess = \models\ProfileHasProject::model()->findUserRole($userId, $projectId);
			if($obProfileAccess)
			{
				return true;
			}

			// In case of impersonation check another user's superadmin feature
			if($userId != $this->getId())
			{
				$obProfile = \models\Profile::model()->selectFieldStatus()->findByProfileId($userId);
				if(!$obProfile->status != \models\Profile::STATUS_SUPERADMIN)
				{
					return false;
				}

				return true;
			}

			return $this->checkAccess('superadmin');
		};

		/**
		 * Checks if user has access to todo entry
		 * @param array $params Accepts keys `todo`.
		 */
		$bizTodoRemoveRule = function($params)
		{
			$obTodo = isset($params['todo']) ? $params['todo'] : null;
			if(!($obTodo instanceof \models\Todo))
			{
				return false;
			}

			if(
				$obTodo->status === \models\Todo::STATUS_PRIVATE
				&& $obTodo->created_by != Yii::app()->user->getId()
			)
			{
				return $this->checkAccess('superadmin');
			}

			return true;
		};

		/**
		 * Checks if user has access to karma entry
		 * @param array $params Accepts keys `karma`.
		 */
		$bizKarmaRemoveRule = function($params)
		{
			$obKarma = isset($params['karma']) ? $params['karma'] : null;
			if(!($obKarma instanceof \models\Karma))
			{
				return false;
			}

			if($obKarma->created_by != Yii::app()->user->getId())
			{
				return $this->checkAccess('superadmin');
			}

			return true;
		};

		$auth->createTask('browseProject','Viewing summary of the project', $bizAccessProjectRule);
		$auth->createTask('accessTodo','Can remove todo from the list', $bizTodoRemoveRule);
		$auth->createTask('accessKarmaPiece','Can view and proceed actions with karma piece', $bizKarmaRemoveRule);

		$role = $auth->createRole('superadmin');
		$role->addChild('browseProject');
		$role->addChild('accessTodo');
		$role->addChild('accessKarmaPiece');

		$role = $auth->createRole('user');
		$role->addChild('browseProject');
		$role->addChild('accessTodo');
		$role->addChild('accessKarmaPiece');

		$role = $auth->createRole('noob');
	}

	public function assignRoles($userId = null)
	{
		$auth=Yii::app()->authManager;
		$userId = $userId ? $userId : $this->getId();

		if($this->profile instanceof \models\Profile)
		{
			switch($this->profile->status)
			{
				case \models\Profile::STATUS_USER:
					$auth->assign('user', $userId);
				break;
				case \models\Profile::STATUS_SUPERADMIN:
					$auth->assign('superadmin', $userId);
				break;
				case \models\Profile::STATUS_NEW:
					$auth->assign('noob', $userId);
				break;
			}
		}
	}

	public function getAttributesNano()
	{
		if($this->profile instanceof \models\Profile)
		{
			return $this->profile->getAttributes([
				'first_name',
				'last_name',
				'profile_id',
			]);
		}
	}

	public function loginRequired()
	{

	}
}
