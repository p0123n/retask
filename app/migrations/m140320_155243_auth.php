<?php

class m140320_155243_auth extends CDbMigration
{
	public function up()
	{
		$this->createTable('auth', [
			'token' => 'char(32) NOT NULL PRIMARY KEY',
			'user_id' => 'INT UNSIGNED NOT NULL',
			'status' => "enum('active', 'inactive') NOT NULL  DEFAULT 'active'",
			'created_at' => 'DATETIME NOT NULL',
			'updated_at' => 'DATETIME NOT NULL',
			'updated_by' => 'INT UNSIGNED',
		], 'ENGINE=InnoDB CHARSET=utf8');
	}

	public function down()
	{
		$this->dropTable('auth');
	}
}