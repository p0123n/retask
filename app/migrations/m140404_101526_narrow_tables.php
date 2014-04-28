<?php
/**
 * The goal is to make of `profile_has_project` a "narrow" table
 */
class m140404_101526_narrow_tables extends CDbMigration
{
	public function up()
	{
		$this->dropColumn('profile_has_project', 'created_by');
		$this->dropColumn('profile_has_project', 'updated_by');
		$this->dropColumn('profile_has_project', 'created_at');
		$this->addColumn('profile_has_project', 'status', "enum('active', 'archived', 'removed') NOT NULL  DEFAULT 'active'"); // denormalization. For science.
	}

	public function down()
	{
		$this->dropColumn('profile_has_project', 'status');
		$this->addColumn('profile_has_project', 'created_by', 'INT UNSIGNED NOT NULL');
		$this->addColumn('profile_has_project', 'updated_by', 'INT UNSIGNED NOT NULL');
		$this->addColumn('profile_has_project', 'created_at', 'DATETIME');
	}
}