<?php

class LandController extends \components\FrontBaseController
{
	public $jsAppFiles = [];

	public function actionIndex()
	{
		$this->scanJsFiles(dirname(__FILE__).'/../../web/app/controllers', 'jsAppFiles');
		$this->scanJsFiles(dirname(__FILE__).'/../../web/app/controllers/pages', 'jsAppFiles');
		$this->scanJsFiles(dirname(__FILE__).'/../../web/app/services', 'jsAppFiles');

		$this->render('index');
	}

	protected function scanJsFiles($scanFolder, $classVariable)
	{
		foreach (new DirectoryIterator($scanFolder) as $file)
		{
			if ($file->isFile() === true && $file->getExtension() == "js")
			{
				array_push(
					$this->$classVariable,
					substr(strstr($file->getPath(), '../../'), 9).'/'.$file->getBasename()
				);
			}
		}
	}
}