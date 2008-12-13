<?php /* $Id$ */
/**
 * @author Jacob Tews <jacob@webteks.com>
 * @date Thu Feb  7 20:53:04 EST 2008
 *
 * Core functions of the script used primarliy behind the scenes.
 */

/**
 * @author Jacob Tews <jacob@webteks.com>
 * @date Thu Jan 31 20:31:55 EST 2008
 * @param $class_name Name of the class to load
 *
 * Whenever a statement like "new <class>" is called and that class does not 
 * exist, __autoload will attempt to search for and load the class 
 * automatically. If it cannot do so, a fatal error is returned.
 */
function __autoload ($class_name) {
	static $class_list;
	if (is_null($class_list)) {
		$GLOBALS['config']['libdir'] = dirname(dirname(__FILE__));
		$rdi = new RecursiveDirectoryIterator($GLOBALS['config']['libdir']);
		$fcf = new FrameworkClassFilter($rdi);
		$rii = new RecursiveIteratorIterator($fcf);
		foreach ($rii as $filename => $info) {
			$class_list[str_replace('.class.php', '', basename($filename))] = $filename;
		}
	}
	if (isset($class_list[$class_name])) {
		require($class_list[$class_name]);
		return true;
	}
	return false;
}
class FrameworkClassFilter extends RecursiveFilterIterator {
	private static $excluded_dirs = array(
		'.svn' => true,
		'tests' => true
	);
	public function accept () {
		$file = $this->current();
		$is_file = $file->isFile();
		$filename = $file->getFilename();
		return (
			$is_file
			&& strpos($filename, '.class.php') !== false
		) || (
			!$is_file
			&& !isset(self::$excluded_dirs[$filename])
		);
	}
}

function framework_error_handler ($errno, $errstr, $errfile, $errline) {
	global $_ERRORS;
	static $error_map = array(
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
	);
	$_ERRORS[] = array_merge($error_map[$errno], array('message' => $errstr, 'file' => $errfile, 'line' => $errline));
	return true; // Don't let the default error handler take over.
}

function ob_event_handler ($string, $mode) {
	if (FEventDispatcher::hasListeners()) {
		$listeners = FEventDispatcher::getListeners();
		foreach ($listeners as &$listener) {
			if (FString::contains($string, $listener->getCallback())) {
				$listener->run($string);
			}
		}
		return $string;
	} else {
		return false;
	}
}

function ob_framework_error_handler ($string, $mode) {
	return false;
	global $_ERRORS;
	if (count($_ERRORS)) {
		$string .= '<ol id="errorMessages">';
		foreach ($_ERRORS as &$error) {
			$lines = file($error['file']);
			$min = ($error['line'] < 3) ? 0 : $error['line'] - 3;
			$snippet = array_slice($lines, $min, 5);
			unset($lines);
			$string .= sprintf(
				'<li><span style="color:%s;">%s</span>: %s:%d<br>%s<pre>%s</pre></li>',
				$error['color'],
				$error['level'],
				str_replace($_SERVER['SITEROOT'], '', $error['file']),
				$error['line'],
				$error['message'],
				htmlspecialchars(implode('', $snippet))
			);
			unset($snippet);
		}
		$string .= '</ol>';
		return $string;
	} else {
		return $string;
	}
}

/**
 * Registered as a the shutdown function. Records the execution time of every 
 * script and stores it in /tmp/execution-times/<domain name>. If a script had 
 * URL parameters passed, they are included with the script name.
 */
function shutdown_callback () {
	$execution_time = (microtime(true) - SCRIPT_START) * 1000;
	$memory_usage = memory_get_usage();
	$handle = fsockopen('udp://127.0.0.1', $port = rand(20123,20123));
	$server_name = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'CLI';
	$monitor_data = array(
		array($server_name, $_SERVER['PHP_SELF'], 'render_time', $execution_time, 'ms'),
		array($server_name, $_SERVER['PHP_SELF'], 'memory_usage', $memory_usage, 'b')
	);
	foreach ($monitor_data as $data) {
		fwrite($handle, implode(' ', $data) . "\n");
	}
	fclose($handle);
}
