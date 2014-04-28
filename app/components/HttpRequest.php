<?php
namespace components;

class HttpRequest extends \CHttpRequest
{
		/**
	 * Performs the CSRF validation.
	 * This is the event handler responding to {@link CApplication::onBeginRequest}.
	 * The default implementation will compare the CSRF token obtained
	 * from a cookie and from a POST field. If they are different, a CSRF attack is detected.
	 * @param CEvent $event event parameter
	 * @throws CHttpException if the validation fails
	 */
	public function validateCsrfToken($event)
	{
		if ($this->getIsPostRequest() ||
			$this->getIsPutRequest() ||
			$this->getIsDeleteRequest())
		{
			$cookies=$this->getCookies();
			$userToken = isset($_SERVER['HTTP_X_XSRF_TOKEN']) ? $_SERVER['HTTP_X_XSRF_TOKEN'] : null;

			if (!empty($userToken) && $cookies->contains($this->csrfTokenName))
			{
				$cookieToken=$cookies->itemAt($this->csrfTokenName)->value;
				$valid=$cookieToken===$userToken;
			}
			else
				$valid = false;
			if (!$valid)
				throw new \CHttpException(400,\Yii::t('yii','The CSRF token could not be verified.'));
		}
	}
}
