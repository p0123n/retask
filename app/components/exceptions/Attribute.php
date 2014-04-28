<?php
namespace components\exceptions;

class Attribute extends Rest
{
	protected $errors = [];

	/**
	 * (PHP 5 &gt;= 5.1.0)<br/>
	 * Construct the exception
	 * @param $message [optional]
	 * @param $soaCode [optional]
	 * @param $code [optional]
	 * @param $previous [optional]
	 */
	public function __construct($model = null, $message = null, $code = 400, $previous = null)
	{
		$errorList = [];

		if($model instanceof \CModel)
		{
			$errorList = $model->getErrors();
//			$behaviors = $model->evaluateExpression('$this->_m;'); // sorry for this :'(
//
//			if(is_array($behaviors) && in_array(\transport\Transport::BEHAVIOR_NAME, array_keys($behaviors)))
//			{
//				$allowedErrors = [];
//				$allowedFields = $model->getErrorAttributeNames();
//				if(is_array($allowedFields))
//				{
//					foreach($allowedFields as $field)
//					{
//						if(key_exists($field, $errorList))
//						{
//							$allowedErrors[$field] = $errorList[$field];
//						}
//					}
//				}
//
//				$errorList = $allowedErrors;
//			}
		}
		elseif(is_array($model) || ($model instanceof \Traversable && !($model instanceof \CModel)))
		{
			$errorList = $model;
		}
		elseif($model instanceof \IDataProvider)
		{
			$errorList = $model->getData();
		}
		else
		{
			\Yii::log('Exception found while building AttributeException. First argument is not CModel nor array. ' . CVarDumper::dumpAsString($model), CLogger::LEVEL_ERROR);
			throw new Exception('Exception found while building AttributeException. First argument is not CModel nor array.');
		}

		$this->errors = $errorList;
		parent::__construct($message, 'input_data', $code, $previous);
	}

	public function getErrors()
	{
		return $this->errors;
	}
}