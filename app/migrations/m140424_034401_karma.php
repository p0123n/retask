<?php

class m140424_034401_karma extends CDbMigration
{
	public function up()
	{
		$this->createTable('karma', [
			'karma_id' => 'INT UNSIGNED NOT NULL PRIMARY KEY auto_increment',
			'title' => 'VARCHAR(512) NOT NULL',
			'project_id' => 'VARCHAR(32) NOT NULL', // ....we.... need to make it int, you know
			'status' => "enum('trace', 'positive', 'negative', 'resolved','removed') DEFAULT 'trace' NOT NULL",
			'assigned_to' => 'INT UNSIGNED NOT NULL',
			'created_by' => 'INT UNSIGNED NOT NULL',
			'created_at' => 'DATETIME',
			'traced_at' => 'DATETIME',
			'resolved_at' => 'DATETIME',
		], 'ENGINE=InnoDB CHARSET=utf8');

		$this->createIndex('idx_created_by', 'karma', 'created_by');
	}

	public function down()
	{
		$this->dropTable('karma');
	}
}