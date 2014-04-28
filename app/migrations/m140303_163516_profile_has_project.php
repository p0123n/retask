<?php

class m140303_163516_profile_has_project extends CDbMigration
{
	public function up()
	{
		$this->createTable('profile_has_project', [
			'profile_id' => 'INT UNSIGNED NOT NULL',
			'project_id' => 'varchar(32) NOT NULL',
			'created_by' => 'INT UNSIGNED NOT NULL',
			'updated_by' => 'INT UNSIGNED NOT NULL',
			'created_at' => 'DATETIME NOT NULL',
			'PRIMARY KEY (`profile_id`, `project_id`)',
		], 'ENGINE=InnoDB CHARSET=utf8');
	}

	public function down()
	{
		$this->dropTable('profile_has_project');
	}
}