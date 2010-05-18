<?php
class FCookie {
	private static $auth;
	private static $autoSend;
	private static $data;
	private static $expire;

	public static function initialize () {
		if (isset($_COOKIE['auth'])) {
			self::$auth = $_COOKIE['auth'];
		}
		self::$autoSend = true;
		if (isset($_COOKIE['data'])) {
			self::$data = json_decode(stripslashes($_COOKIE['data']));
		} else {
			self::$data = new stdClass();
		}
		if (isset($_COOKIE['expire'])) {
			self::$expire = $_COOKIE['expire'];
		}
		if (isset($_COOKIE['digest']) && $_COOKIE['digest'] != null && $_COOKIE['digest'] != self::generateDigest()) {
			throw new CookieException("Invalid Digest");
		}
		self::expire('2 weeks');
	}

	public static function authorizationKey ($auth = null) {
		if ($auth !== null) {
			self::$auth = $auth;
			if (self::$autoSend) {
				self::send();
			}
		}
		return self::$auth;
	}
	public static function autoSend ($autoSend = true) {
		self::$autoSend = $autoSend;
	}
	public static function clear () {
		self::$auth = null;
		self::$data = new stdClass();
		self::$expire = null;
		if (self::$autoSend) {
			self::send();
		}
	}
	public static function expire ($expire = null) {
		if ($expire !== null) {
			self::$expire = strtotime($expire);
			if (self::$autoSend) {
				self::send();
			}
		}
		return self::$expire;
	}
	public static function get ($key) {
		if (property_exists(self::$data, $key)) {
			return self::$data->$key;
		} else {
			return null;
		}
	}
	public static function set ($key, $value) {
		self::$data->$key = $value;
		if (self::$autoSend) {
			self::send();
		}
	}
	public static function send () {
		$data = json_encode(self::$data);
		setcookie('auth', self::$auth, 0, WEBROOT . '/');
		setcookie('data', $data, 0, WEBROOT . '/');
		setcookie('digest', self::generateDigest(), 0, WEBROOT . '/');
		setcookie('expire', self::$expire, 0, WEBROOT . '/');
	}
	private static function generateDigest () {
		$data = json_encode(self::$data);
		$digest_buffer = $data . $_ENV['config']['secret'] . self::$expire . $_ENV['config']['secret'] . self::$auth;
		return sha1($digest_buffer);
	}
}

class CookieException extends Exception {};
