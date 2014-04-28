<?php
/**
 * @todo documentation
 */
namespace components;

class Json extends \CComponent
{
	const
		ERROR_TYPE_REQUEST = 'invalid_request_error',
		ERROR_TYPE_API     = 'api_error';

	public function init()
	{
		$app = \Yii::app();
		$app->request->getCsrfToken();

//		$app->request->enableCsrfValidation = false;
//		$app->detachEventHandler('onBeginRequest', array($app->request, 'validateCsrfToken'));
		$app->attachEventHandler('onBeginRequest', array($this, 'onBeginRequest'));
	}

	/**
	 * Raised when an uncaught PHP exception occurs.
	 * @param \CExceptionEvent $event event parameter
	 */
	public function onException(\CExceptionEvent $event)
	{
		$event->handled = true;
		$statusCode = null;
		$message = null;
		$type = null;
		$soaCode = null;

		if ($event->exception instanceof \CHttpException) {
			$statusCode = $event->exception->statusCode;
			$message = $event->exception->getMessage();
			$type = self::ERROR_TYPE_REQUEST;
		}
		else
		{
			$statusCode = 200;
			$message = $event->exception->getMessage();
			$type = self::ERROR_TYPE_API;

			if($event->exception instanceof exceptions\Rest)
			{
				$soaCode = $event->exception->getSoaCode();
			}

			if($event->exception instanceof exceptions\Attribute)
			{
				$arErrors = $event->exception->getErrors();
				if(!is_array($arErrors))
				{
					$arErrors = [];
				}

				if(sizeof($arErrors) <= 0)
				{
					$message = rtrim($message, '.'). '. And we have no extra info on errors. That\'s weird...';
				}

				$message = [
					'message' => $message,
					'errors' => $arErrors,
				];
			}
		}

		$this->_setErrorHandlerError(array(
			'code' => $statusCode,
			'type' => get_class($event->exception),
			'errorCode' => $event->exception->getCode(),
			'message' => $event->exception->getMessage(),
			'file' => $event->exception->getFile(),
			'line' => $event->exception->getLine(),
			'trace' => $event->exception->getTraceAsString(),
			'traces' => $event->exception->getTrace(),
		));

		$this->sendError($type, $message, $soaCode, array(), $event->exception->getCode());
	}

	/**
	 * Raised when a PHP execution error occurs.
	 * @param \CErrorEvent $event event parameter
	 */
	public function onError(\CErrorEvent $event)
	{
		$event->handled = true;

		$this->_setErrorHandlerError(array(
			'code'    => 500,
			'type'    => $event->code,
			'message' => $event->message,
			'file'    => $event->file,
			'line'    => $event->line,
			'trace'   => '',
			'traces'  => array(),
		));

		$this->sendError(self::ERROR_TYPE_API, $event->message);
	}

	/**
	 * @param $type
	 * @param $message
	 * @param $soaCode
	 * @param array $data
	 * @param int $statusCode
	 */
	public function sendError($type, $message, $soaCode = null, array $data = array(), $statusCode = 500)
	{
		ob_clean();

		$data = array();

		// general error code
		$data['httpCode'] = $statusCode ? $statusCode : 500;
		$data['code'] = $soaCode ? $soaCode : 'unknown';
		$data['message'] = $message;

		// generate response package
		$controller = \Yii::app()->getController();
		$module = $controller ? $controller->getModule() : null;
		$moduleName = $module ? $module->getName() : 'api';

		$package = [
			'service' => $moduleName,
			'method' => \Yii::app()->request->getRequestType(),
			'parameters' => $_REQUEST,
			'response' => $data,
		];

		$meta = [
			'controller' => $controller instanceof \Controller ? get_class($controller) : null,
			'action' => (isset($controller) && ($controller instanceof \Controller) && isset($controller->action)) ? $controller->action->id : null
		];

		$error = \Yii::app()->errorHandler->getError();
		// put extra info if in debugging mode
		if(defined('YII_DEBUG') && YII_DEBUG)
		{
			unset($error['trace']);

			$package['details'] = $error;
			$package = array_merge($package, $meta);
		}

		$package['status'] = 'error';

		$this->_send($package, $error['code']);
	}

	/**
	 * @param       $data
	 * @param array $filterFields DEPRECATED
	 * @param int   $statusCode
	 */
	public function sendData($data, array $filterFields = null, $statusCode = 200)
	{
		$this->_send($data, $statusCode);
	}

	public static function getStatusPhrase($statusCode)
	{
		switch ($statusCode) {
			case 200:
				$reasonPhrase = 'OK';
				break;
			case 201:
				$reasonPhrase = 'Created';
				break;
			case 400:
				$reasonPhrase = 'Bad Request';
				break;
			case 401:
				$reasonPhrase = 'Unauthorized';
				break;
			case 403:
				$reasonPhrase = 'Forbidden';
				break;
			case 404:
				$reasonPhrase = 'Not Found';
				break;
			case 500:
				$reasonPhrase = 'Internal Server Error';
				break;
			case 502:
				$reasonPhrase = 'Bad Gateway';
				break;
			case 503:
				$reasonPhrase = 'Service Unavailable';
				break;
			case 504:
				$reasonPhrase = 'Gateway Timeout';
				break;
			default:
				$reasonPhrase = '...';
		}
		return $reasonPhrase;
	}

	/**
	 * @param \CEvent $event
	 */
	public function onBeginRequest(\CEvent $event)
	{
		$app = \Yii::app();

		$app->attachEventHandler('onException', array($this, 'onException'));
		$app->attachEventHandler('onError', array($this, 'onError'));
	}

	/**
	 * Set CErrorHandler::_error property
	 * @param array $error
	 */
	protected function _setErrorHandlerError(array $error)
	{
		$refObject = new \ReflectionObject(\Yii::app()->errorHandler);
		if ($refObject->hasProperty('_error'))
		{
			$error['traces'] = debug_backtrace();
			unset($error['traces'][0]);
			unset($error['traces'][1]);
			$refProperty = $refObject->getProperty('_error');
			$refProperty->setAccessible(true);
			$refProperty->setValue(\Yii::app()->errorHandler, $error);
		}
	}

	/**
	 * @param     $data
	 * @param int $statusCode
	 */
	protected function _send($data, $statusCode = 200)
	{
		if (!$data)
		{
			$data = new \stdClass();
		}
		$reasonPhrase = $this->getStatusPhrase($statusCode);

		header($_SERVER['SERVER_PROTOCOL'] . " {$statusCode} {$reasonPhrase}");
		header('Content-type: application/json; charset=utf-8');

		echo json_encode($data, JSON_UNESCAPED_UNICODE);

		\Yii::app()->end();
	}
}
