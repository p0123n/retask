<?php

class m140303_163507_project extends CDbMigration
{
	public function up()
	{
		$this->createTable('project', [
			'project_id' => 'varchar(32) NOT NULL PRIMARY KEY',
			'title' => 'varchar(255) NOT NULL',
			'status' => "enum('active', 'archived', 'removed') NOT NULL  DEFAULT 'active'",
			'created_at' => 'DATETIME',
			'created_by' => 'INT UNSIGNED NOT NULL',
			'updated_at' => 'DATETIME',
			'updated_by' => 'INT UNSIGNED NOT NULL',
		], 'ENGINE=InnoDB CHARSET=utf8');
	}

	public function down()
	{
		$this->dropTable("project");
	}
}