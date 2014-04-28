<?php
namespace components;

class UserIdentity extends \CUserIdentity
{
	public function authenticate()
	{
		if(!$this->password)
		{
			throw new exceptions\Rest('Password can\'t be empty', 'input');
		}

		if(!$this->username)
		{
			throw new exceptions\Rest('Login can\'t be empty', 'input');
		}

		$model = new \models\Profile();
		$entry = $model->findByAttributes([
			'login' => $this->username,
		]);

		if(!$entry)
		{
			throw new exceptions\Rest('Login/password does not match any of our records', 'auth_matches');
		}

		if($entry->status == \models\Profile::STATUS_INACTIVE)
		{
			throw new exceptions\Rest('The account is inactive. Please get in contact with your administrator', 'auth_inactive');
		}

		if(!\CPasswordHelper::verifyPassword($this->password, $entry->password_hash))
		{
			throw new exceptions\Rest('Login/password does not match any of our records', 'auth_matches');
		}

		return $entry;
	}

}