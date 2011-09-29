<?php
/*!
 * @author Jeff Wendling <jwendling@okco.com>
 * @author Jake Tews <jtews@okco.com>
 * @date Thu Sep 29 07:28:05 EDT 2011
 */
class FLog {
	private static $data = array();
	public static function getData () {
		return self::$data;
	}
	public static function hasMessages () {
		return isset(self::$data['messages']);
	}
	/*!
	 * @throws InvalidArgumentException
	 */
	public static function set ($key, $value) {
		if ($key == 'messages') {
			throw new InvalidArgumentException("Cannot use key, 'messages'.");
		}
		self::$data[$key] = $value;
	}
	public static function message (array $message) {
		self::$data['messages'][sha1(json_encode($message))] = $message;
	}
}
