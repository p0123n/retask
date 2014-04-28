<?php
namespace models;
use CActiveRecord, CActiveDataProvider, CDbCriteria;

/**
 * This is the model class for table "profile_has_project".
 *
 * The followings are the available columns in table 'profile_has_project':
 * @property string $profile_id
 * @property string $project_id
 * @property string $status
 * @property string $role
 * @property string $meta
 */
class ProfileHasProject extends CActiveRecord
{
	const STATUS_ACTIVE = 'active';
	const STATUS_ARCHIVED = 'archived';
	const STATUS_REMOVED = 'removed';

	const ROLE_STAFF = 'staff';
	const ROLE_TEAMLEADER = 'teamleader';
	const ROLE_MANAGER = 'manager';

	const ACCESS_ARCHIVE = 'view_archive';
	const ACCESS_DEPLOY = 'deploy';
	const ACCESS_CREATE_TASK = 'create_task';
	const ACCESS_VIEW_TASK = 'view_task';

	public $arMetaJson = [];

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'profile_has_project';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('profile_id, project_id', 'required'),
			array('profile_id', 'length', 'max'=>10),
			array('project_id', 'length', 'max'=>32),
			array('status', 'length', 'max'=>8),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('profile_id, project_id, status', 'safe', 'on'=>'search'),
		);
	}

	public function defaultScope()
	{
		return [
			'condition' => "status = '".self::STATUS_ACTIVE."'"
		];
	}

	public function scopes()
	{
		return [
			'selectMicro' => [
				'select' => 'profile_id, project_id, status',
			],
			'selectNano' => [
				'select' => 'profile_id, project_id',
			],
		];
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'profile_id' => 'Profile',
			'project_id' => 'Project',
			'status' => 'Status',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search()
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('profile_id',$this->profile_id,true);
		$criteria->compare('project_id',$this->project_id,true);
		$criteria->compare('status',$this->status,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return ProfileHasProject the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * Finds project Ids for specified $userId.
	 *
	 * @param integer $userId
	 * @return array
	 */
	public function findAllByProfileId($userId)
	{
		$criteria = new \CDbCriteria;
		$criteria->compare('profile_id', $userId);

		$items = $this->findAll($criteria);
		return $items;
	}

	/**
	 * Finds user ids by specified project id
	 *
	 * @todo Inverse table
	 * @param string $projectId
	 * @return array
	 */
	public function findAllByProjectId($projectId)
	{
		$criteria = new \CDbCriteria;
		$criteria->compare('project_id', $projectId);

		$items = $this->findAll($criteria);
		return $items;
	}

	public function findAllUserRoles($userId, $projectId)
	{
		$arUserId = !is_array($userId) && $userId ? [$userId] : $userId;
		$arProjectId = !is_array($projectId) && $projectId ? [$projectId] : $projectId;

		$criteria = new \CDbCriteria;
		if(is_array($arUserId) && sizeof($arUserId))
		{
			$criteria->addInCondition('profile_id', $arUserId);
		}

		if(is_array($arProjectId) && sizeof($arProjectId))
		{
			$criteria->addInCondition('project_id', $arProjectId);
		}

		$criteria->compare('status', sprintf("<> '%s'", self::STATUS_REMOVED));

		return $this->findAll($criteria);
	}

	public function findUserRole($userId, $projectId)
	{
		$arUserRoles = $this->findAllUserRoles($userId, $projectId);
		if(sizeof($arUserRoles))
		{
			return array_pop($arUserRoles);
		}

		return false;
	}

	/**
	 * Checks if user has access to particular project.
	 *
	 * Possible keys are
	 *
	 * * view_archive – Can access project if it is archived
	 * * deploy       – Can deploy
	 * * create_task  – Can create tasks
	 * * view_task    – Can see Agile sprint/epics reports
	 *
	 * @param type $key
	 * @return boolean
	 */
	public function hasAccess($key)
	{
		if($this->status == self::STATUS_REMOVED)
		{
			return false;
		}

		$arJson = $this->meta ? @json_decode($this->meta, true) : false;
		if($arJson)
		{
			$this->arMetaJson = $arJson;
		}

		if($this->status == self::STATUS_ARCHIVED && !$this->accessProbeBoolean('view_archive'))
		{
			return false;
		}

		return $this->accessProbeBoolean($key);
	}

	public function getAllDeployServers()
	{
		$arServers = $this->arMetaJson['deploy_servers'];
		if(!is_array($arServers))
		{
			$arServers = [];
		}

		return $arServers;
	}

	public function getRoleFormated()
	{
		return ucfirst($this->role);
	}

	protected function accessProbeBoolean($key)
	{
		if(!isset($this->arMetaJson[$key]))
		{
			return false;
		}

		return $this->arMetaJson[$key] === true;
	}
}
