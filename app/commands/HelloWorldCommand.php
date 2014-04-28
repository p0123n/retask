<?php
class HelloWorldCommand extends CConsoleCommand
{
	public function run($args)
	{
		echo "Command succeeded\n";
	}
}