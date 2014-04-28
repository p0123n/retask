<?php
namespace models;
use CActiveRecord, CActiveDataProvider, CDbCriteria;

/**
 * This is the model class for table "profile".
 *
 * The followings are the available columns in table 'profile':
 * @property string $profile_id
 * @property string $password_hash
 * @property string $email
 * @property string $login
 * @property string $first_name
 * @property string $last_name
 * @property string $created_at
 * @property string $updated_at
 * @property string $updated_by
 * @property string $status
 */
class Profile extends CActiveRecord
{
	const STATUS_NEW = 'new';
	const STATUS_USER = 'user';
	const STATUS_SUPERADMIN = 'superadmin';
	const STATUS_INACTIVE = 'inactive';

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'profile';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('password_hash, email, first_name, last_name, updated_by', 'required'),
			array('password_hash, email, first_name, last_name', 'length', 'max'=>255),
			array('updated_by, status', 'length', 'max'=>10),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('profile_id, password_hash, email, login, first_name, last_name, created_at, updated_at, updated_by, status', 'safe', 'on'=>'search'),
		);
	}

	public function defaultScope()
	{
		return [
			'condition' => sprintf("status in ('%s', '%s')", self::STATUS_SUPERADMIN, self::STATUS_USER)
		];
	}

	public function scopes()
	{
		return [
			'selectMicro' => [
				'select' => 'profile_id, login, email, first_name, last_name, status',
			],
			'selectNano' => [
				'select' => 'profile_id, first_name, last_name',
			],
			'selectFieldStatus' => [
				'select' => 'profile_id, status',
			],
			'sortByName' => [
				'order' => 'first_name, last_name ASC',
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
			'password_hash' => 'Password Hash',
			'email' => 'Email',
			'login' => 'Login',
			'first_name' => 'First Name',
			'last_name' => 'Last Name',
			'created_at' => 'Created At',
			'updated_at' => 'Updated At',
			'updated_by' => 'Updated By',
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
		$criteria->compare('password_hash',$this->password_hash,true);
		$criteria->compare('email',$this->email,true);
		$criteria->compare('login',$this->login,true);
		$criteria->compare('first_name',$this->first_name,true);
		$criteria->compare('last_name',$this->last_name,true);
		$criteria->compare('created_at',$this->created_at,true);
		$criteria->compare('updated_at',$this->updated_at,true);
		$criteria->compare('updated_by',$this->updated_by,true);
		$criteria->compare('status',$this->status,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Profile the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public function findAllByProfileId($userId)
	{
		$models = $this->findAllByAttributes([
			'profile_id' => $userId,
		]);

		return $models;
	}

	public function findByProfileId($userId)
	{
		$models = $this->findAllByProfileId($userId);

		if(!is_array($models) || sizeof($models) <= 0 || !($models[0] instanceof self))
		{
			throw new \components\exceptions\Rest("Could not find user profile '$userId'", 'profile');
		}

		return array_pop($models);
	}

	public function getSafeAttributeNamesOnSelect()
	{
		return [
			'profile_id',
			'login',
			'status',
			'email',
			'first_name',
			'last_name',
			'created_at',
			'updated_at',
			'updated_by',
		];
	}

	public function getFullName()
	{
		return sprintf('%s %s', $this->first_name, $this->last_name);
	}

	public function getAvatar()
	{
		return \components\Helper::getGravatar($this->email);
	}
}
