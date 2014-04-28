<?php
namespace models;
use CActiveRecord, CDbCriteria, Yii;

/**
 * This is the model class for table "karma".
 *
 * The followings are the available columns in table 'karma':
 * @property string $karma_id
 * @property string $title
 * @property string $project_id
 * @property string $status
 * @property string $assigned_to
 * @property string $created_by
 * @property string $created_at
 * @property string $traced_at
 * @property string $resolved_at
 */
class Karma extends CActiveRecord
{
	const STATUS_TRACE    = 'trace';
	const STATUS_POSITIVE = 'positive';
	const STATUS_NEGATIVE = 'negative';
	const STATUS_RESOLVED = 'resolved';
	const STATUS_REMOVED  = 'removed';

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'karma';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('title, project_id, assigned_to, created_by', 'required'),
			array('title', 'length', 'max'=>512),
			array('project_id', 'length', 'max'=>32),
			array('status', 'length', 'max'=>8),
			array('assigned_to, created_by', 'length', 'max'=>10),
			array('created_at, traced_at, resolved_at', 'safe'),
		);
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
			'karma_id' => 'Karma',
			'title' => 'Title',
			'project_id' => 'Project',
			'status' => 'Status',
			'assigned_to' => 'Assigned To',
			'created_by' => 'Created By',
			'created_at' => 'Created At',
			'traced_at' => 'Traced At',
			'resolved_at' => 'Resolved At',
		);
	}

	public function defaultScope()
	{
		return [
			'condition' => sprintf(
				"status in ('%s', '%s', '%s', '%s')",
				self::STATUS_NEGATIVE,
				self::STATUS_POSITIVE,
				self::STATUS_RESOLVED,
				self::STATUS_TRACE
			)
		];
	}

	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public function setStatus($value, $safe = true)
	{
		if($safe && in_array($value, [
			self::STATUS_RESOLVED,
			self::STATUS_REMOVED,
		]))
		{
			Yii::log("An attempt to set status '$value' in safe mode failed. Setting status to `trace`", \CLogger::LEVEL_INFO);
			$this->status = self::STATUS_TRACE;
		}

		$this->status = $value;
		return $this;
	}

	public function getStatusFormatted()
	{
		return ucfirst($this->status);
	}

	public function getResolutionTime()
	{
		if(!$this->resolved_at)
		{
			return false;
		}

		return strtotime($this->resolved_at) - strtotime($this->traced_at);
	}

	public function setResolved()
	{
		$this->resolved_at = date('Y-m-d H:i:s');
	}

	public function saveKarma($runValidation = true, $attributes = null)
	{
		if(!$this->traced_at)
		{
			// created_at becomes non empty in \components\Blameable
			$this->traced_at = $this->created_at;
		}
		return $this->save($runValidation, $attributes);
	}

	public function findAllByOwnerId($ownerId, array $dateInterval = [])
	{
		$criteria = new CDbCriteria;

		if(sizeof($dateInterval) == 2)
		{
			$criteria->addBetweenCondition('traced_at', $dateInterval[0], $dateInterval[1]);
		}
		$criteria->compare('created_by', $ownerId);
		$criteria->order = 'traced_at ASC';

		$obItems = $this->findAll($criteria);
		return $obItems;
	}

	public function findByKarmaId($karmaId)
	{
		$karma = $this->findByPk($karmaId);

		if(!$karma)
		{
			throw new \components\exceptions\Rest("Could not find karma '$karmaId'", 'karma');
		}

		return $karma;
	}

	public function behaviors() {
		return array(
			'Blameable' => array(
				'class'=>'\components\Blameable',
				'isSoftDelete' => true,
			),
		);
	}
}
