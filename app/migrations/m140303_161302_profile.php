<?php

class m140303_161302_profile extends CDbMigration
{
	public function up()
	{
		$this->createTable('profile', [
			'profile_id' => 'INT UNSIGNED NOT NULL PRIMARY KEY auto_increment',
			'password_hash' => 'varchar(255) NOT NULL',
			'login' => 'varchar(128) NOT NULL',
			'email' => 'varchar(255) NOT NULL',
			'first_name' => 'varchar(255) NOT NULL',
			'last_name' => 'varchar(255) NOT NULL',
			'created_at' => 'DATETIME',
			'updated_at' => 'DATETIME',
			'updated_by' => 'INT UNSIGNED NOT NULL',
			'status' => "enum('new', 'user', 'superadmin', 'inactive') NOT NULL  DEFAULT 'new'",
		], 'ENGINE=InnoDB CHARSET=utf8');

		$this->createIndex('idx_email', 'profile', 'email', true);
		$this->createIndex('idx_login', 'profile', 'login', true);

		$this->insert('profile', [
			'profile_id' => 1,
			'password_hash' => CPasswordHelper::hashPassword('password'),
			'login' => 'admin',
			'email' => 'no-reply@example.org',
			'first_name' => 'Hello',
			'last_name' => 'World',
			'created_at' => gmdate('Y-m-d H:i:s'),
			'updated_at' => gmdate('Y-m-d H:i:s'),
			'updated_by' => 1,
			'status' => 'superadmin',
		]);
	}

	public function down()
	{
		$this->dropTable("profile");
	}
}