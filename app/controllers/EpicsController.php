<?php
class EpicsController extends \components\RestBaseController
{
	public $epicsCacheId = 'retask.controllers.epics.epics';

	/**
	 * Jira interface
	 * @var \components\Jira
	 */
	public $jira;

	public function init()
	{
		$this->jira = new \models\JiraSprintReport();
		parent::init();
	}

	public function actionSprint($id)
	{
		$app = Yii::app();
		$meta = [];
		$this->epicsCacheId .= '.'.$id;

		if($app->request->getParam('force'))
		{
			$app->cache->delete($this->epicsCacheId);
			$meta = $this->getMeta($id);
		}

		$meta = $app->cache->get($this->epicsCacheId);
		if($meta === false)
		{
			$meta = $this->getMeta($id);
		}

		$startDate = null;
		$endDate = null;
		if(isset($meta['sprint']) && is_array($meta['sprint']))
		{
			if(isset($meta['sprint']['startDate']) && $meta['sprint']['startDate'])
			{
				$startDate = $meta['sprint']['startDate'];
			}
			if(isset($meta['sprint']['endDate']) && $meta['sprint']['endDate'])
			{
				$endDate = $meta['sprint']['endDate'];
			}
		}

		$this->setResponse($meta);
	}

	protected function getMeta($id, $cache = true)
	{
		$meta = $this->jira->findAll($id);
		if(is_array($meta))
		{
			$meta['count'] = sizeof($meta);
		}

		if($cache)
		{
			Yii::app()->cache->set($this->epicsCacheId, $meta, 60*60);
		}

		return $meta;
	}
}
