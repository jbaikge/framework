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
spl_autoload_register('autoload');
function autoload ($class_name) {
	static $class_list;
	if ($class_list === null) {
		$f = $_ENV['config']['cache.class_list'];
		file_exists($f) && include($f);
		unset($f);
	}
	if (!isset($class_list[$class_name]) || !file_exists($class_list[$class_name])) {
		$class_list = generate_class_list();
	}
	if (isset($class_list[$class_name])) {
		require($class_list[$class_name]);
		return true;
	}
	return false;
}
function generate_class_list () {
	$class_list = array();
	$rdi = new RecursiveDirectoryIterator($_ENV['config']['library.dir']);
	$fcf = new FrameworkClassFilter($rdi);
	$rii = new RecursiveIteratorIterator($fcf);
	foreach ($rii as $filename => $info) {
		$class_list[str_replace(array('.class.php', '.interface.php'), '', basename($filename))] = $filename;
	}
	file_put_contents(
		$_ENV['config']['cache.class_list'], 
		'<?php $class_list = ' . var_export($class_list, true) . ';'
	);
	@chmod($_ENV['config']['cache.class_list'], 0666);
	return $class_list;
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
			&& (
				strpos($filename, '.class.php') !== false
				|| strpos($filename, '.interface.php') !== false
			)
		) || (
			!$is_file
			&& !isset(self::$excluded_dirs[$filename])
		);
	}
}

function t ($str) {
	echo str_replace(array('<br>', '<br />'), '', htmlize($str));
}
function e () {
	$args = func_get_args();
	echo htmlize(implode('', $args));
}
function htmlize () {
	$str = '';
	foreach (func_get_args() as $arg) {
		$str .= $arg;
	}
	// Save unnecessary calls if the string's blank:
	if ($str == '') return '';

	$htmlized = htmlspecialchars($str, ENT_COMPAT, $_ENV['config']['html.content_type'], false);
	if (version_compare(PHP_VERSION,'5.3.0', '>=')) {
		$str = nl2br($htmlized, $_ENV['config']['html.xhtml']);
	} else if ($_ENV['config']['html.xhtml'])  {
		$str = nl2br($htmlized);
	} else {
		$str = str_replace('<br />', '<br>', nl2br($htmlized));
	}
	return $str;
}
function photo_size($photo, $max_width, $max_height, $return_value = "w")
{
	$dimensions = @getimagesize($photo);
	$width = $dimensions[0];
	$height = $dimensions[1];
  
	if($width > $max_width || $height > $max_height)
  { 
	  if($width > $max_width)
	{
		$height = $height*$max_width/$width;
		$width = $max_width;
	  }
	
	  if($height > $max_height)
	{
		$width = $width*$max_height/$height;
		$height = $max_height;
	  }
	}
  
	if($return_value == "w") { $image_dimension = $width; } else { $image_dimension = $height; }
  
	return round($image_dimension, 2);
 }
if (!function_exists('money_format')) {
	function money_format ($format, $number) {
		$regex  = '/%((?:[\^!\-]|\+|\(|\=.)*)([0-9]+)?'.
				  '(?:#([0-9]+))?(?:\.([0-9]+))?([in%])/';
		if (setlocale(LC_MONETARY, 0) == 'C') {
			setlocale(LC_MONETARY, '');
		}
		$locale = localeconv();
		preg_match_all($regex, $format, $matches, PREG_SET_ORDER);
		foreach ($matches as $fmatch) {
			$value = floatval($number);
			$flags = array(
				'fillchar'  => preg_match('/\=(.)/', $fmatch[1], $match) ?
							   $match[1] : ' ',
				'nogroup'   => preg_match('/\^/', $fmatch[1]) > 0,
				'usesignal' => preg_match('/\+|\(/', $fmatch[1], $match) ?
							   $match[0] : '+',
				'nosimbol'  => preg_match('/\!/', $fmatch[1]) > 0,
				'isleft'	=> preg_match('/\-/', $fmatch[1]) > 0
			);
			$width	  = trim($fmatch[2]) ? (int)$fmatch[2] : 0;
			$left	   = trim($fmatch[3]) ? (int)$fmatch[3] : 0;
			$right	  = trim($fmatch[4]) ? (int)$fmatch[4] : $locale['int_frac_digits'];
			$conversion = $fmatch[5];

			$positive = true;
			if ($value < 0) {
				$positive = false;
				$value  *= -1;
			}
			$letter = $positive ? 'p' : 'n';

			$prefix = $suffix = $cprefix = $csuffix = $signal = '';

			$signal = $positive ? $locale['positive_sign'] : $locale['negative_sign'];
			switch (true) {
				case $locale["{$letter}_sign_posn"] == 1 && $flags['usesignal'] == '+':
					$prefix = $signal;
					break;
				case $locale["{$letter}_sign_posn"] == 2 && $flags['usesignal'] == '+':
					$suffix = $signal;
					break;
				case $locale["{$letter}_sign_posn"] == 3 && $flags['usesignal'] == '+':
					$cprefix = $signal;
					break;
				case $locale["{$letter}_sign_posn"] == 4 && $flags['usesignal'] == '+':
					$csuffix = $signal;
					break;
				case $flags['usesignal'] == '(':
				case $locale["{$letter}_sign_posn"] == 0:
					$prefix = '(';
					$suffix = ')';
					break;
			}
			if (!$flags['nosimbol']) {
				$currency = $cprefix .
							($conversion == 'i' ? $locale['int_curr_symbol'] : $locale['currency_symbol']) .
							$csuffix;
			} else {
				$currency = '';
			}
			$space  = $locale["{$letter}_sep_by_space"] ? ' ' : '';

			$value = number_format($value, $right, $locale['mon_decimal_point'],
					 $flags['nogroup'] ? '' : $locale['mon_thousands_sep']);
			$value = @explode($locale['mon_decimal_point'], $value);

			$n = strlen($prefix) + strlen($currency) + strlen($value[0]);
			if ($left > 0 && $left > $n) {
				$value[0] = str_repeat($flags['fillchar'], $left - $n) . $value[0];
			}
			$value = implode($locale['mon_decimal_point'], $value);
			if ($locale["{$letter}_cs_precedes"]) {
				$value = $prefix . $currency . $space . $value . $suffix;
			} else {
				$value = $prefix . $value . $space . $currency . $suffix;
			}
			if ($width > 0) {
				$value = str_pad($value, $width, $flags['fillchar'], $flags['isleft'] ?
						 STR_PAD_RIGHT : STR_PAD_LEFT);
			}

			$format = str_replace($fmatch[0], $value, $format);
		}
		return $format;
	}
}
/*
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
 */
/**
 * Registered as a the shutdown function. Records the execution time of every 
 * script and stores it in /tmp/execution-times/<domain name>. If a script had 
 * URL parameters passed, they are included with the script name.
 */
/*
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
 */
