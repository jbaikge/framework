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
	public static function errorHandler ($errno, $errstr, $errfile, $errline) {
		FLog::message(array_merge(
			array(
				'type' => 'error',
				'message' => $errstr,
				'file' => $errfile,
				'line' => $errline
			),
			self::$errorMap[$errno]
		));
		if ($errno == E_RECOVERABLE_ERROR) {
			throw new RecoverableException($errstr);
		}
		return true;
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
