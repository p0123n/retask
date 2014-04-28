<?php
namespace components;
use \Yii;

class FrontController extends \CController
{
	public $layout='//layouts/landing';

	public function json($response)
	{
		header('Content-Type: application/json; charset=utf-8', true);
		echo json_encode($response, JSON_UNESCAPED_UNICODE);
		Yii::app()->end();
	}
}