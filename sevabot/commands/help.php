<?php

class help
{
	function run($attributes, $skype_login, $skype_name)
	{
		$this->attributes = $attributes;
		$this->skype_login = $skype_login;
		$this->skype_name = $skype_name;

		$var = <<<STR
* retask bot --- http://your_domain/#/dashboard

Available commands
	* retask todo --push <TEXT>[ --priority=low|normal|high][ --private]
	Creates new task in technical dept list. <TEXT> can't be multilined;
	using double quotes is not allowed (will be fixed later).
	With key "--private" the task will be visible only for you.

	* retask todo --resolve <TASK_ID>[, <TASK_ID1>[, <TASK_ID2>[...]]]
	Resolves task (pops task out of the list)

	* retask todo --list[ --private][--any]
	Lists all available tasks to be done within technical dept.
	With key "--private" only your private tasks will be on the list.
	With key "--any" any tasks will be on the list. You have to be superadmin to have such feature.

	* retask karma --push <TEXT>[ --person <SKYPE_ID>][ --status <STATUS_ID>][ --datetime <DATETIME>]
	Traces user activities on project. Available keys:
	--person    By default you trace yourself. Optional
	--status    Karma effect. By default it is "trace". Available options: trace, positive, negative.
	--datetime  The datetime, the trace should be labeled by. By default: current datetime.

	* retask karma --resolve <TRACE_ID>
	Resolves karma trace. You should not resolve each trace. It is optional command, yet it is cool
	to know – how do you spend your time?

	* retask karma --list[ --date <DATE>]
	Lists only your traces. Go visit site for details. Specify "--date" to show older traces.
	It is optional key; default value – current date.

	* retask help
	Shows this text.

Other information
	* Tasks in todo list can be exported to Jira only via web-site.
STR;

		return $var;
	}
}