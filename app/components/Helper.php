<?php
namespace components;

class Helper {

	/**
	 * UUID v4 random code generator
	 * @return string
	 */
	public static function uuid4()
	{
		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// 32 bits for "time_low"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			// 16 bits for "time_mid"
			mt_rand(0, 0xffff),
			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand(0, 0x0fff) | 0x4000,
			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand(0, 0x3fff) | 0x8000,
			// 48 bits for "node"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
	}

	/**
	 * Returns Gravatar avatar url
	 * @param string $email email to which user avatar should be returned
	 * @return string
	 */
	public static function getGravatar($email)
	{
		return 'http://www.gravatar.com/avatar/' . md5($email);
	}

	/**
	 * Finds cookie named "user_token" (if any) and returns its value.
	 * If no cookie found or token is weird then empty string returns.
	 * @return string
	 */
	public static function getUserTokenCookie()
	{
		$cookies = \Yii::app()->request->cookies;
		$token = '';

		if(isset($cookies['user_token']) && is_object($cookies['user_token']))
		{
			$token = $cookies['user_token']->value;
		}

		return trim($token);
	}

	public static function date($format, $timestamp = null)
	{
		$timestamp = $timestamp ? $timestamp : time();
		return date($format, $timestamp);
	}

	public static function dateDiffFormatted($seconds)
	{
		$days = floor($seconds/86400);
		$secondsInLastDay = $seconds - $days*86400;
		$buffer = [];
		if($days)
		{
			$buffer[] = "{$days}d";
		}
		$buffer[] = gmdate('G', $secondsInLastDay).'h';
		$buffer[] = sprintf('%d', gmdate('i', $secondsInLastDay)).'m';

		return implode(' ', $buffer);
	}
}