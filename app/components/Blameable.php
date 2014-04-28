<?php
/**
 * Blameable/Timestampable implementation
 *
 * @category Behavior
 * @author Nikolai Besschetnov <lumos.white@gmail.com>
 * @version 2.0
 */
namespace components;

class Blameable extends \CActiveRecordBehavior
{
	public $createdByColumn = 'created_by';
	public $updatedByColumn = 'updated_by';
	public $createdAtColumn = 'created_at';
	public $updatedAtColumn = 'updated_at';
	public $isSoftDelete = false;

	public function beforeValidate($event)
	{
		if(isset(\Yii::app()->user))
		{
			$availableColumns = array_keys($this->owner->tableSchema->columns);

			if($this->owner->isNewRecord && empty($this->owner->{$this->createdByColumn}))
				if(in_array($this->createdByColumn, $availableColumns))
					$this->owner->{$this->createdByColumn} = \Yii::app()->user->getId();

			if($this->owner->isNewRecord && empty($this->owner->{$this->createdAtColumn}))
				if(in_array($this->createdAtColumn, $availableColumns))
					$this->owner->{$this->createdAtColumn} = date('Y-m-d H:i:s');

			if(in_array($this->updatedByColumn, $availableColumns))
				$this->owner->{$this->updatedByColumn} = \Yii::app()->user->getId();

			if(in_array($this->updatedAtColumn, $availableColumns))
				$this->owner->{$this->updatedAtColumn} = date('Y-m-d H:i:s');
		}

		return parent::beforeValidate($event);
	}

	public function beforeDelete($event)
	{
		if(!$this->isSoftDelete)
		{
			return parent::beforeDelete($event);
		}

		if(isset(\Yii::app()->user))
		{
			$availableColumns = array_keys($this->owner->tableSchema->columns);

			if(in_array($this->updatedByColumn, $availableColumns))
				$this->owner->{$this->updatedByColumn} = \Yii::app()->user->getId();

			if(in_array($this->updatedAtColumn, $availableColumns))
				$this->owner->{$this->updatedAtColumn} = date('Y-m-d H:i:s');

			try
			{
				$this->owner->save();
			}
			catch(Exception $e) {}
		}

		return parent::beforeDelete($event);
	}
}
