<?php
/*!
 * A collection of string manipulation methods. These methods are refined 
 * versions of common tasks and should be the fastest possible way to 
 * accomplish many common tasks that usually utilize inefficient methodology.
 *
 * @author Jacob Tews <jacob@webteks.com>
 * @date Sat Mar  8 09:36:08 EST 2008
 * @version $Id$
 */

class FString {
	const DATE_MYSQL = 'Y-m-d H:i:s'; ///< MySQL-formatted date string
	/*!
	 * Determine a string's existence within another string. A blank needle 
	 * will always cause this method to return true.
	 *
	 * @param $haystack The string to search
	 * @param $needle The string to search with
	 * @param $case_insensitive @b Optional True to ignore case, false 
	 * otherwise. (Default: false)
	 * @return True if @c $needle is inside @c $haystack, false otherwise
	 */
	public static function contains ($haystack, $needle, $case_insensitive = false) {
		if ($needle == '') {
			return true;
		}
		if ($case_insensitive) {
			return stripos($haystack, $needle) !== false;
		} else {
			return strpos($haystack, $needle) !== false;
		}
	}
	public static function labelize ($string) {
		return ucwords(str_replace('_', ' ', $string));
	}
	/*!
	 * Determine whether a string starts with another string.
	 *
	 * @see http://blog.modp.com/2007/10/php-string-startswith-let-me-count-ways.html
	 * @param $source The string to check
	 * @param $prefix The string to check with
	 * @param $case_insensitive @b Optional True to ignore case, false 
	 * otherwise. (Default: false)
	 * @return True if @c $source starts with @c $prefix, false otherwise
	 */
	public static function startsWith ($source, $prefix, $case_insensitive = false) {
		if ($case_insensitive) {
			return strncasecmp($source, $prefix, strlen($prefix)) == 0;
		} else {
			return strncmp($source, $prefix, strlen($prefix)) == 0;
		}
	}
	/*!
	 * Determine whether a string ends with another string.
	 *
	 * @see http://snipplr.com/view/13213/check-if-a-string-ends-with-another-string/
	 * @param $source The string to check
	 * @param $suffix The string to check with
	 * @param $case_insensitive @b Optional True to ignore case, false
	 * otherwise. (Default: false)
	 * @return True if @c $source ends with @c $prefix, false otherwise
	 */
	public static function endsWith ($source, $suffix, $case_insensitive = false) {
		$substr = substr($source, strlen($source) - strlen($suffix));
		if ($case_insensitive) {
			return strcasecmp($substr, $suffix) == 0;
		} else {
			return strcmp($substr, $suffix) == 0;
		}
	}
	/*!
	 * Formats any given date into the standard format defined by the
	 * configuration directives format.date and format.datetime.
	 *
	 * @param $date A string, timestamp or DateTime instance representing a date
	 * @return The date, formatted per the configuration of format.date or
	 * format.datetime
	 */
	public static function date ($date, $format_override = null) {
		if ($date === null || $date === false || $date === '') {
			return null;
		}
		// Safety check 
		if ($format_override == '') $format_override = null;
		if ($date instanceof DateTime) {
			$date = strtotime($date->format('Y-m-d H:i:s'));
		} else if ((string)(int)$date != $date) {
			$date = strtotime($date);
		}
		if ($format_override === null && date('His', $date) == 0) {
			return date($_ENV['config']['format.date'], $date);
		} else if ($format_override === null) {
			return date($_ENV['config']['format.datetime'], $date);
		} else {
			return date($format_override, $date);
		}
	}
}
