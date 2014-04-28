<?php

class m140413_114759_todo_private extends CDbMigration
{
	public function up()
	{
		$this->alterColumn('todo', 'status', "enum('active', 'private', 'removed') DEFAULT 'active' NOT NULL");
		$this->addColumn('todo', 'updated_by', 'INT UNSIGNED');
	}

	public function down()
	{
		$this->alterColumn('todo', 'status', "enum('active', 'removed') DEFAULT 'active' NOT NULL");
		$this->dropColumn('todo', 'updated_by');
	}
}