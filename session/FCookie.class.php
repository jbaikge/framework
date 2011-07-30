<?php
/*!
 * Basic cookie handler. The data stored in the cookies by this class are 
 * checksummed to prevent fiddling. There are a number of components controlled
 * by this class to ensure stable cookie management.
 * 
 * In order to control a cookie's values, four components are utilized:
 * @li Authorization key
 * @li Data
 * @li Expiration
 * @li Digest
 * 
 * The @em authorization @em key is an optional element that should be derived 
 * and remain unique to each individual user. The authorization key should be an
 * encrypted version of a password salt, if one is used. This way, if a user
 * acquires another user's cookie maliciously, a user who changes their password
 * will automatically invalidate that cookie.
 * 
 * The @em data is a JSON-encoded string containing all data passed in via 
 * #get() with structure retention and flexibility. This structure is used to
 * provide communication flexibility between the PHP cookie and allow JavaScript
 * to access the cookie. Note that if JavaScript modifies the cookie, it will
 * immediately invalidate the entire set of data.
 * 
 * The @em expiration is a unix timestamp of when the cookie will expire. Once
 * the date passes, the entire cookie will become invalid and a new, blank one
 * generated. If the cookie is accessed before the expiration, the expiration
 * is reset to the current time plus the duration of the cookie to live. The 
 * default duration is two weeks.
 * 
 * The @em digest combines all of the elements above it to ensure that the 
 * cookie read is valid. If the digest does not match up, the cookie is 
 * invalidated and a new one automatically generated.
 * 
 * Credit where credit is due: The techniques used are derived from an essay on
 * Hardened Stateless Cookies by Steven J. Murdoch from the University of
 * Cambridge.
 * 
 * @see http://www.cl.cam.ac.uk/users/sjm217/
 * @author Jake Tews <jtews@okco.com>
 * @date Wed Jan 27 14:05:37 EST 2010
 * @version $Id$
 */
class FCookie {
	private static $auth; ///< Authorization key
	private static $autoSend; ///< Automatically send updates (boolean)
	private static $data; ///< Cookie data as an object
	private static $expire; ///< Expiration as a Unix timestamp
	/*!
	 * Initializes the cookie instance. The data is first read from existing
	 * cookies passed from the browser's headers. Once read, they are validated
	 * against the digest to ensure integrity. If a cookie is found to be
	 * invalid, an exception is thrown.
	 * 
	 * If no cookie previously existed, a new one is created and the expiration
	 * is automatically set to two weeks.
	 * 
	 * @throws CookieException
	 */
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
		// Check for invalid digest
		if (isset($_COOKIE['digest']) && $_COOKIE['digest'] != null && $_COOKIE['digest'] != self::generateDigest()) {
			throw new CookieException("Invalid Digest");
		}
		// Check for missing digest (Only applies if there has been
		// data, otherwise continue to build new cookie)
		if (isset($_COOKIE['data']) && !isset($_COOKIE['digest']) || (isset($_COOKIE['digest']) && !$_COOKIE['digest'])) {
			throw new CookieException("Invalid Digest");
		}
		self::expire('2 weeks');
	}
	/*!
	 * Gets and sets the authorization key for this cookie instance. If a key
	 * is provided as an argument, it replaces the existing key and resends the
	 * new cookie elements (as long as auto-send is on).
	 * 
	 * With and without arguments, the existing authorization key is returned.
	 * 
	 * An example implementation:
	 * @code
	 * try {
	 *     FCookie::initialize();
	 * } catch (FCookieException $fce) {
	 *     FCookie::clear();
	 * }
	 * $_user = User::getByAuthKey(FCookie::authorizationKey());
	 * if (!$_user->user_id) {
	 *     header('Location: login.php');
	 *     exit;
	 * }
	 * @endcode
	 * 
	 * @param $auth Optional. The authorization key to use for this cookie. 
	 * Default: @c null.
	 * @return The existing authorization key.
	 */
	public static function authorizationKey ($auth = null) {
		if ($auth !== null) {
			self::$auth = $auth;
			if (self::$autoSend) {
				self::send();
			}
		}
		return self::$auth;
	}
	/*!
	 * Sets the auto-send feature. This feature causes the cookie headers to get
	 * sent every time a modification is made to the cookie data. When storing
	 * large amounts of data in cookies using the #set() method, it is often
	 * better to call #autoSend(false) before all of the sets, then call #send()
	 * once all of the data has been pushed into the cookie handler.
	 * 
	 * The default behavior is to automatically send data on every call. If this
	 * needs to be changed, do so after the call to #initialize() as this
	 * setting is established there.
	 * 
	 * @param $autoSend Optional. Sets the auto-send feature on or off. Default:
	 * @c true.
	 */
	public static function autoSend ($autoSend = true) {
		self::$autoSend = $autoSend;
	}
	/*!
	 * Empties the cookie of all data. If auto-send is enabled, the cookies will
	 * be reset in the browser as well.
	 */
	public static function clear () {
		self::$auth = null;
		self::$data = new stdClass();
		self::$expire = null;
		if (self::$autoSend) {
			self::send();
		}
	}
	/*!
	 * Gets and sets the expiration for this cookie instance. If an expiration 
	 * is provided, it is converted to a timestamp and used. The format for 
	 * describing a duration is the same format interpreted by strtotime(). A
	 * few samples of such valid durations:
	 * @li 2 weeks
	 * @li 1 hour
	 * @li 20 seconds
	 * @li 1 hour 30 minutes
	 * @li 1 month 2 weeks 3 days 1 hour 30 minutes
	 * @li next tuesday
	 * 
	 * Additionally, exact dates may be given:
	 * @li April 20
	 * 
	 * @param $expire Optional. Duration which to expire as described above.
	 * Default: @c null.
	 * @return Timstamp of expiration date and time
	 */
	public static function expire ($expire = null) {
		if ($expire !== null) {
			self::$expire = strtotime($expire);
			if (self::$autoSend) {
				self::send();
			}
		}
		return self::$expire;
	}
	/*!
	 * Retrieves the value for a previously defined key stored in the cookie.
	 * 
	 * @param $key Key of data to retrieve
	 * @return Data corresponding to key or null if it does not exist.
	 */
	public static function get ($key) {
		if (property_exists(self::$data, $key)) {
			return self::$data->$key;
		} else {
			return null;
		}
	}
	/*!
	 * Sets a value in the cookie and assigns it to the provided key. If the
	 * auto-send feature is enabled, an updated cookie will be sent to the
	 * browser immediately.
	 * 
	 * @param $key Key to store data with
	 * @param $value Value to store with key
	 */
	public static function set ($key, $value) {
		self::$data->$key = $value;
		if (self::$autoSend) {
			self::send();
		}
	}
	/*!
	 * Sends all elements of the cookie instance to the browser. Currently, all
	 * cookies are bound to the webroot of the site they belong to. This allows
	 * a site that actually resides in a subdirectory to have its own set of
	 * cookies separate from another subdirectory.
	 * 
	 * If this method is called after output has already reached the screen,
	 * PHP will issue a warning and the cookie data will not store properly.
	 */
	public static function send () {
		$data = json_encode(self::$data);
		setcookie('auth', self::$auth, 0, WEBROOT . '/');
		setcookie('data', $data, 0, WEBROOT . '/');
		setcookie('digest', self::generateDigest(), 0, WEBROOT . '/');
		setcookie('expire', self::$expire, 0, WEBROOT . '/');
	}
	/*!
	 * Generates the digest for the cookie using the site secret and all other
	 * elements of the cookie. If a site secret, set in the webroot using
	 * @c $config['secret'], is not set, the site secret becomes a random
	 * number. This behavior causes every cookie to fail digest verification and
	 * never retain data.
	 * 
	 * @return SHA1 digest of all data elements and the site secret.
	 */
	private static function generateDigest () {
		$data = json_encode(self::$data);
		$digest_buffer = $data . $_ENV['config']['secret'] . self::$expire . $_ENV['config']['secret'] . self::$auth;
		return sha1($digest_buffer);
	}
}

/*!
 * Exception thrown by various FCookie operations.
 * 
 * @author Jake Tews <jtews@okco.com>
 * @date Wed Jan 27 14:05:37 EST 2010
 * @version $Id$
 */
class CookieException extends Exception {}
