<?php

class m140408_182213_todo extends CDbMigration
{
	public function up()
	{
		$this->createTable('profile_has_skype', [
			'profile_id' => 'INT UNSIGNED NOT NULL',
			'skype_id' => 'VARCHAR(128) NOT NULL',
			'PRIMARY KEY (`profile_id`, `skype_id`)',
		], 'ENGINE=InnoDB CHARSET=utf8');

		$this->createTable('todo', [
			'todo_id' => 'INT UNSIGNED NOT NULL PRIMARY KEY auto_increment',
			'title' => 'VARCHAR(512) NOT NULL',
			'priority' => "ENUM('high', 'normal', 'low') DEFAULT 'normal' NOT NULL",
			'project_id' => 'VARCHAR(32) NOT NULL', // ....we.... need to make it int, you know
			'status' => "enum('active', 'removed') DEFAULT 'active' NOT NULL",
			'created_by' => 'INT UNSIGNED NOT NULL',
			'created_at' => 'DATETIME',
		], 'ENGINE=InnoDB CHARSET=utf8');

		$this->createIndex('idx_created_by', 'todo', 'created_by');

		$this->addColumn('profile', 'default_project', 'VARCHAR(32)');
	}

	public function down()
	{
		$this->dropTable('profile_has_skype');
		$this->dropTable('todo');
		$this->dropColumn('profile', 'default_project');
	}
}