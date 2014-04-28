<?php
namespace components;

class PhpAuthManager extends \CPhpAuthManager
{
	public function executeBizRule($bizRule,$params,$data)
	{
		if($bizRule instanceof \Closure)
		{
			return $bizRule($params, $data);
		}

		return $bizRule==='' || $bizRule===null || ($this->showErrors ? eval($bizRule)!=0 : @eval($bizRule)!=0);
	}
}
