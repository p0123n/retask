<?php
namespace models;
use CActiveRecord, CActiveDataProvider, CDbCriteria, Yii;

/**
 * This is the model class for table "auth".
 *
 * The followings are the available columns in table 'auth':
 * @property string $token
 * @property string $user_id
 * @property string $status
 * @property string $created_at
 * @property string $updated_at
 * @property string $updated_by
 */
class Auth extends CActiveRecord
{
	const STATUS_ACTIVE = 'active';
	const STATUS_INACTIVE = 'inactive';
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'auth';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('token, user_id, created_at, updated_at', 'required'),
			array('token', 'length', 'max'=>32),
			array('user_id, updated_by', 'length', 'max'=>10),
			array('status', 'length', 'max'=>8),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('token, user_id, status', 'safe', 'on'=>'search'),
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
			'token' => 'Token',
			'user_id' => 'User',
			'status' => 'Status',
			'created_at' => 'Created At',
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

		$criteria->compare('token',$this->token,true);
		$criteria->compare('user_id',$this->user_id,true);
		$criteria->compare('status',$this->status,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Auth the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * Finds all active tokens for <code>$userId</code> and deactivates them.
	 * And then saves changes. Function writes <code>Clogger::LEVEL_INFO</code> logs on quantity of
	 * updated tokens.
	 *
	 * @param integer $userId Tokens of which users should we deactivate?
	 * @return \models\Auth
	 */
	public function saveUserTokensAsInactive($userId)
	{
		$criteria = new CDbCriteria;
		$criteria->compare('user_id', $userId);
		$criteria->compare('status', self::STATUS_ACTIVE);

		$count = $this->updateAll([
			'status' => self::STATUS_INACTIVE
		], $criteria);

		Yii::log("$count tokens for $userId were deactivated");

		return $this;
	}

	/**
	 * Creates random token for <code>$userId</code>
	 * @param \models\Auth $token
	 * @param type $userId
	 * @return type
	 */
	public function saveUserToken($userId)
	{
		$token = new Auth();

		$token->token = md5(\components\Helper::uuid4());
		$token->user_id = $userId;

		$token->save();

		return $token;
	}

	/**
	 * Finds user profile by token (which should be active)
	 * @param string $token
	 * @return \models\Profile
	 * @throws \components\exceptions\Rest
	 */
	public function findByToken($token)
	{
		$model = $this->findByAttributes([
			'token' => $token,
			'status' => self::STATUS_ACTIVE
		]);

		if(!$model)
		{
			throw new \components\exceptions\Rest("Token '$token' is not found", 'token');
		}

		return $model;
	}

	// ---------------------------------------------------------------------------------------------

	public function behaviors() {
		return array(
			'Blameable' => array(
				'class'=>'\components\Blameable',
			),
		);
	}

	public function getToken()
	{
		return $this->token;
	}
}
