<?php
namespace models;
use CActiveRecord, CActiveDataProvider, CDbCriteria, Yii;

/**
 * This is the model class for table "project".
 *
 * The followings are the available columns in table 'project':
 * @property string $project_id
 * @property string $title
 * @property string $status
 * @property string $created_at
 * @property string $created_by
 * @property string $updated_at
 * @property string $updated_by
 */
class Project extends CActiveRecord
{
	const STATUS_ACTIVE = 'active';
	const STATUS_ARCHIVED = 'archived';
	const STATUS_REMOVED = 'removed';

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'project';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('project_id, title, created_by, updated_by', 'required'),
			array('project_id', 'length', 'max'=>32),
			array('title', 'length', 'max'=>255),
			array('status', 'length', 'max'=>8),
			array('created_by, updated_by', 'length', 'max'=>10),
			array('created_at, updated_at', 'safe'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('project_id, title, status, created_at, created_by, updated_at, updated_by', 'safe', 'on'=>'search'),
		);
	}

	public function scopes()
	{
		return [
			'selectMicro' => [
				'select' => 'project_id, title, status',
			]
		];
	}

	public function defaultScope()
	{
		return [
			'condition' => "status = '".self::STATUS_ACTIVE."'"
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
			'project_id' => 'Project',
			'title' => 'Title',
			'status' => 'Status',
			'created_at' => 'Created At',
			'created_by' => 'Created By',
			'updated_at' => 'Updated At',
			'updated_by' => 'Updated By',
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

		$criteria->compare('project_id',$this->project_id,true);
		$criteria->compare('title',$this->title,true);
		$criteria->compare('status',$this->status,true);
		$criteria->compare('created_at',$this->created_at,true);
		$criteria->compare('created_by',$this->created_by,true);
		$criteria->compare('updated_at',$this->updated_at,true);
		$criteria->compare('updated_by',$this->updated_by,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return \models\Project the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public function findByProjectId($projectId)
	{
		$model = $this->findByAttributes([
			'project_id' => $projectId,
		]);

		if(!$model)
		{
			throw new \components\exceptions\Rest(sprintf("Could not find project '%s'", $projectId), 'profile');
		}

		return $model;
	}

	/**
	 * Finds projects using "IN ()" in database
	 *
	 * @param array $projectIds
	 * @return array
	 */
	public function findAllByProjectId(array $projectId)
	{
		$model = $this->findAllByAttributes([
			'project_id' => $projectId,
		]);

		return $model;
	}

	// ---------------------------------------------------------------------------------------------

	public static function loadProject($projectId)
	{
		$userId = Yii::app()->user->getId();
		Yii::trace("An attempt to load project '$projectId' by user '$userId'", 'models.Project');
		$obProjectSelector = \models\Project::model()->selectMicro();
		$obProject = $obProjectSelector->findByProjectId($projectId);

		Yii::trace("An attempt to check access of project '$projectId' for user '$userId'", 'models.Project');
		$hasAccess = Yii::app()->user->checkAccess('browseProject', [
			'project_id' => $projectId,
		]);

		if(!$hasAccess)
		{
			throw new \components\exceptions\Rest("Unfortunately, you have no access to the project '$projectId'", 'project_access', 403);
		}

		return $obProject->getAttributes($obProjectSelector);
	}
}
