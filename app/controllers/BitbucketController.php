<?php

class BitbucketController extends \components\RestBaseController
{
	public function actionNewCommit()
	{
		mail('lumos.white@gmail.com', 'yay', 'it works');
	}
}