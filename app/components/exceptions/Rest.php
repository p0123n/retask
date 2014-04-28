<?php
namespace components\exceptions;

class Rest extends \Exception
{
	protected $soaCode = '';

	/**
	 * (PHP 5 &gt;= 5.1.0)<br/>
	 * Construct the exception
	 * @param $message [optional]
	 * @param $soaCode [optional]
	 * @param $code [optional]
	 * @param $previous [optional]
	 */
	public function __construct($message = null, $soaCode = null, $code = 400, $previous = null)
	{

		if (!is_string($message))
		{
			throw new Exception('Only string messages are allowed', $code, $this);
		}

		\Yii::log("$message ($soaCode, $code)", \CLogger::LEVEL_ERROR);

		$this->soaCode = $soaCode;
		parent::__construct($message, $code, $previous);
	}

	public function getSoaCode()
	{
		return $this->soaCode;
	}
}