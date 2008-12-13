<?php
/**
 * A collection of string manipulation methods. These methods are refined 
 * versions of common tasks and should be the fastest possible way to 
 * accomplish many common tasks that usually utilize inefficient methodology.
 *
 * @author Jacob Tews <jacob@webteks.com>
 * @date Sat Mar  8 09:36:08 EST 2008
 * @version $Id$
 */

class String {
	/**
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
	/**
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
}
