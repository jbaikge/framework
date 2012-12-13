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
		global $argv;
		// these are our templates
		$types = array('email', 'screen');
		$traceline = "#%s %s(%s): %s(%s)";
		$template = "UNCAUGHT EXCEPTION\n\n"
			. "Exception:    %s\n\n"
			. "Message:      %s\n\n"
			. "Calling File: %s\n\n"
			. "Calling URL:  %s\n\n"
			. "File:         %s:%s\n\n"
			. "Stack trace:\n"
			. "%s\n"
			. "  thrown in %s on line %s\n";

		// alter your trace as you please, here
		$trace = $exception->getTrace();
		foreach ($trace as &$stack_point) {
			// Convert arguments to their type (prevents passwords from ever
			// getting logged as anything other than 'string')
			$stack_point['email'] = array_map(function($v) { return var_export($v, true); }, $stack_point['args']);
			$stack_point['screen'] = array_map('gettype', $stack_point['args']);
		}

		// build your tracelines
		$result = array();
		foreach ($trace as $key => $stack_point) {
			foreach ($types as $type) {
				$result[$type][] = sprintf(
					$traceline,
					$key,
					$stack_point['file'],
					$stack_point['line'],
					$stack_point['function'],
					implode(', ', $stack_point[$type])
				);
			}
		}
		// trace always ends with {main}
		foreach ($types as $type) {
			$result[$type][] = '#' . ++$key . ' {main}';
		}

		// write tracelines into main template
		$messages = array();
		foreach ($types as $type) {
			$messages[$type] = sprintf(
				$template,
				get_class($exception),
				$exception->getMessage(),
				$_SERVER['SCRIPT_FILENAME'],
				isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ("CLI: " . implode(' ', $argv)),
				$exception->getFile(),
				$exception->getLine(),
				implode("\n", $result[$type]),
				$exception->getFile(),
				$exception->getLine()
			);
		}

		// Build email
		$subject = 'PHP Exception in ' . $_SERVER['SCRIPT_FILENAME'];
		mail($_ENV['config']['exception.notify'], $subject, $messages['email']);

		if (isset($_SERVER['SERVER_PROTOCOL'])) {
			header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
			header("Content-Type: text/plain");
			echo "500 Internal Server Error. Please send the following to the website administrator:\n\n";
		}
		if ($_ENV['config']['exception.encode']) {
			echo wordwrap(base64_encode($messages['screen']), 80, "\n", true);
		} else {
			echo $messages['screen'];
		}
		trigger_error(sprintf("%s [%s]", get_class($exception), $exception->getMessage()), E_USER_ERROR);
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
