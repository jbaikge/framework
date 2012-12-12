<?php
/*!
 * @author Jake Tews <jtews@okco.com>
 * @date Thu Sep 29 08:59:17 EDT 2011
 */
class FCallback {
	private static $errorMap = array(
		E_ERROR => array('color' => "red", 'level' => "Error"),
		E_WARNING => array('color' => "orange", 'level' => "Warning"),
		E_PARSE => array('color' => "red", 'level' => "Parse Error"),
		E_NOTICE => array('color' => "blue", 'level' => "Notice"),
		E_CORE_ERROR => array('color' => "red", 'level' => "Core Error"),
		E_CORE_WARNING => array('color' => "orange", 'level' => "Compile Error"),
		E_COMPILE_ERROR => array('color' => "red", 'level' => "Compile Warning"),
		E_USER_ERROR => array('color' => "red", 'level' => "User Error"),
		E_USER_WARNING => array('color' => "orange", 'level' => "User Warning"),
		E_USER_NOTICE => array('color' => "blue", 'level' => "User Notice"),
		E_STRICT => array('color' => "green", 'level' => "Strict Notice"),
		E_RECOVERABLE_ERROR => array('color' => "red", 'level' => "Recoverable Error"),
		E_DEPRECATED => array('color' => "blue", 'level' => "Deprecated"),
		E_USER_DEPRECATED => array('color' => "blue", 'level' => "User Deprecated"),
	);
	public static function errorHandler ($errno, $errstr, $errfile, $errline, $errcontext) {
		FLog::message($m = array_merge(
			array(
				'type' => 'error',
				'message' => $errstr,
				'file' => $errfile,
				'line' => $errline
			),
			self::$errorMap[$errno]
		));
		// sometimes you can trigger an error before config is set up. Not sure how though.
		if (isset($_ENV['config']) && $_ENV['config']['firephp.enabled'] && $_ENV['config']['firephp.class'] != false) {
			$previous = error_reporting(E_ALL | E_STRICT);
			FirePHP::getInstance()->errorHandler($errno, $errstr, $errfile, $errline, $errcontext);
			error_reporting($previous);
		}
		if ($errno == E_RECOVERABLE_ERROR) {
			throw new RecoverableException($errstr);
		}
		return true;
	}
	// @link http://php.net/manual/en/function.set-exception-handler.php#98201
	public static function exceptionHandler($exception) {
		// these are our templates
		$traceline = "#%s %s(%s): %s(%s)";
		$msg = "UNCAUGHT EXCEPTION\n\nException:    %s\n\nMessage:      %s\n\nCalling File: %s\n\nFile:         %s:%s\n\nStack trace:\n%s\n  thrown in %s on line %s";
		//$msg = "PHP Fatal error:  Uncaught exception '%s' with message '%s' in %s:%s\nStack trace:\n%s\n  thrown in %s on line %s";

		// alter your trace as you please, here
		$trace = $exception->getTrace();
		foreach ($trace as $key => $stackPoint) {
			// I'm converting arguments to their type
			// (prevents passwords from ever getting logged as anything other than 'string')
			$trace[$key]['args'] = array_map('gettype', $trace[$key]['args']);
		}

		// build your tracelines
		$result = array();
		foreach ($trace as $key => $stackPoint) {
			$result[] = sprintf(
				$traceline,
				$key,
				$stackPoint['file'],
				$stackPoint['line'],
				$stackPoint['function'],
				implode(', ', $stackPoint['args'])
			);
		}
		// trace always ends with {main}
		$result[] = '#' . ++$key . ' {main}';

		// write tracelines into main template
		$msg = sprintf(
			$msg,
			get_class($exception),
			$exception->getMessage(),
			$_SERVER['SCRIPT_FILENAME'],
			$exception->getFile(),
			$exception->getLine(),
			implode("\n", $result),
			$exception->getFile(),
			$exception->getLine()
		);

		// Build email
		$subject = 'PHP Exception in ' . $_SERVER['SCRIPT_FILENAME'];
		mail($_ENV['config']['exception.notify'], $subject, $msg);

		header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
		header("Content-Type: text/plain");
		echo "500 Internal Server Error. Please send the following to the website administrator:\n\n";
		if ($_ENV['config']['exception.encode']) {
			echo wordwrap(base64_encode($msg), 80, "\n", true);
		} else {
			echo $msg;
		}
		die();

		return null;
	}
	public static function shutdown () {
		if (!$_ENV['config']['report.enabled']) {
			return;
		}

		$header_freq = isset($_SERVER['HTTP_X_NODE_FREQ']) ? (int)$_SERVER['HTTP_X_NODE_FREQ'] : -1;
		if (0 <= $header_freq && $header_freq <= 100) {
			$freq = $header_freq * 10;
		} else {
			$freq = $_ENV['config']['report.frequency'] * 10;
		}

		if (FLog::hasMessages() || rand(0, 999) < $freq) {
			FLog::set('execution_time', microtime(true) - START_TIME);
			FLog::set('memory_usage', (string)(memory_get_usage() - START_MEM));
			FLog::set('server_name', isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'CLI');
			FLog::set('uri', isset($_SERVER['SERVER_NAME']) ? $_SERVER['REQUEST_URI'] : $_SERVER['SCRIPT_NAME']);
			FLog::set('referer', isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
			FNodeMessenger::sendFLog();
		}
	}
}
