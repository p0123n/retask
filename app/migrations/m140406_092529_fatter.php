<?php

class m140406_092529_fatter extends CDbMigration
{
	public function up()
	{
		$this->addColumn('profile_has_project', 'role', "enum('manager', 'teamleader', 'staff') DEFAULT 'staff' NOT NULL");
		$this->addColumn('profile_has_project', 'meta', 'TEXT');
		$this->addColumn('project', 'meta', 'TEXT');
	}

	public function down()
	{
		$this->dropColumn('profile_has_project', 'role');
		$this->dropColumn('profile_has_project', 'meta');
		$this->dropColumn('project', 'meta');
	}
}