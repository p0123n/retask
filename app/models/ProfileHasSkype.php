<?php
namespace models;
use CActiveRecord, CActiveDataProvider, CDbCriteria;

/**
 * This is the model class for table "profile_has_skype".
 *
 * The followings are the available columns in table 'profile_has_skype':
 * @property string $profile_id
 * @property string $skype_id
 */
class ProfileHasSkype extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'profile_has_skype';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('profile_id, skype_id', 'required'),
			array('profile_id', 'length', 'max'=>10),
			array('skype_id', 'length', 'max'=>128),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('profile_id, skype_id', 'safe', 'on'=>'search'),
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
			'profile_id' => 'Profile',
			'skype_id' => 'Skype',
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
		$criteria->compare('skype_id',$this->skype_id,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return ProfileHasSkype the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	// ---------------------------------------------------------------------------------------------

	public static function loadSkypeLogin($login)
	{
		if(empty($login))
		{
			throw new \components\exceptions\Rest("Skype login is empty", 'login_empty');
		}

		$obLink = \models\ProfileHasSkype::model()->findByAttributes([
			'skype_id' => $login,
		]);

		if(!$obLink)
		{
			throw new \components\exceptions\Rest("Skype login '$login' is unregistered in " . Yii::app()->name, 'login');
		}

		return $obLink;
	}
}
