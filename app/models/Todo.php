<?php
namespace models;
use CActiveRecord, CActiveDataProvider, CDbCriteria;

/**
 * This is the model class for table "todo".
 *
 * The followings are the available columns in table 'todo':
 * @property string $todo_id
 * @property string $title
 * @property string $priority
 * @property string $project_id
 * @property string $status
 * @property string $created_by
 * @property string $created_at
 */
class Todo extends CActiveRecord
{
	const STATUS_ACTIVE = 'active';
	const STATUS_REMOVED = 'removed';
	const STATUS_PRIVATE = 'private';

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'todo';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('title, created_by, project_id', 'required'),
			array('title', 'length', 'max'=>512),
			array('priority', 'length', 'max'=>6),
			array('created_by', 'length', 'max'=>10),
			array('created_at', 'safe'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('todo_id, title, priority, created_by, created_at', 'safe', 'on'=>'search'),
		);
	}

	public function defaultScope()
	{
		return [
			'condition' => sprintf("status in ('%s', '%s')", self::STATUS_ACTIVE, self::STATUS_PRIVATE),
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
			'todo_id' => 'Todo',
			'title' => 'Title',
			'project_id' => 'Project Id',
			'status' => 'Status',
			'priority' => 'Priority',
			'created_by' => 'Created By',
			'created_at' => 'Created At',
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

		$criteria->compare('todo_id',$this->todo_id,true);
		$criteria->compare('title',$this->title,true);
		$criteria->compare('priority',$this->priority,true);
		$criteria->compare('created_by',$this->created_by,true);
		$criteria->compare('created_at',$this->created_at,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Todo the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public function findAllByTodoId($ids)
	{
		$models = $this->findAllByAttributes([
			'todo_id' => $ids
		]);

		return $models;
	}

	public function findByTodoId($id)
	{
		$model = $this->findByAttributes([
			'todo_id' => $id
		]);

		if(!$model)
		{
			throw new \components\exceptions\Rest("Could not find todo with id $id", "todo");
		}

		return $model;
	}

	public function delete()
	{
		if(!$this->getIsNewRecord())
		{
			\Yii::trace(get_class($this).'.delete()','system.db.ar.CActiveRecord');
			if($this->beforeDelete())
			{
				$result=$this->updateByPk($this->getPrimaryKey(), [
					'status' => self::STATUS_REMOVED
				]) > 0;
				$this->afterDelete();
				return $result;
			}
			else
				return false;
		}
		else
			throw new \CDbException(Yii::t('yii','The active record cannot be deleted because it is new.'));
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
