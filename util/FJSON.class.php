<?php
/*!
 * JSON utilities for encoding and decoding.
 *
 * @author jtews@okco.com
 * @author lior@mytopia.com
 * @see http://www.php.net/manual/en/function.json-last-error.php
 */
class FJSON {
	/*!
	 * Encodes an object / array as a JSON string.
	 *
	 * @see http://www.php.net/manual/en/function.json-encode.php
	 * @param $object Object or array to encode
	 * @return string JSON-encoded string
	 */
	public static function encode ($object = null) {
		if ($object) {
			return json_encode($object);
		} else {
			return null;
		}
	}
	/*!
	 * Decodes a JSON-encoded string into a PHP variable
	 *
	 * @throws FJSONException
	 * @see http://www.php.net/manual/en/function.json-decode.php
	 * @param $json The json string being decoded.
	 * @param $assoc When TRUE, returned objects will be converted into 
	 * associative arrays.
	 * @param $depth User specified recursion depth.
	 * @param $options Coming in the future. 
	 * @return mixed Returns the value encoded in the supplied JSON as the
	 * appropriate PHP value.
	 */
	public static function decode ($json, $assoc = false, $depth = 512, $options = null) {
		if ($options !== null) {
			if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
				$result = json_decode($json, $assoc, $depth, $options);
			} else {
				trigger_error("json_decode does not support the options parameter until version 5.4.0.", E_USER_WARNING);
				$result = json_decode($json, $assoc, $depth);
			}
		} else {
			$result = json_decode($json, $assoc, $depth);
		}
		
		$errors = array(
			JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
			JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
			JSON_ERROR_SYNTAX => 'Syntax error',
			JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
			JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded',
			JSON_ERROR_NONE => null
		);
		if ($error = $errors[json_last_error()]) {
			throw new FJSONException('JSON Error: ' . $error);
		}
		return $result;
	}
}

/*!
 * Exception thrown when there is a JSON error during decoding.
 */
class FJSONException extends Exception {}
