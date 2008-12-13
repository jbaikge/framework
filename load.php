<?php /* $Id$ */
/**
 * @author Jacob Tews <jacob@webteks.com>
 * @date Thu Feb  7 20:53:04 EST 2008
 *
 * Performs the minimum required functions to get the current page ready to 
 * start doing something.
 */

define('NEWLINE', isset($_ENV['SHELL']) ? "\n" : '<br>');
if (!defined('__DIR__')) define('__DIR__', dirname(__FILE__)); // 5.3, I see you.

require(__DIR__ . '/functions.php');
#ob_start('ob_framework_error_handler');
#ob_start('ob_event_handler');
#set_error_handler('framework_error_handler');
#register_shutdown_function("shutdown_callback");

$include_path = $file_path = dirname($_SERVER['SCRIPT_FILENAME']);
while (!file_exists($file_path . "/webroot.conf.php") && $file_path != ($tmp_path = dirname($file_path))) {
	$include_path .= PATH_SEPARATOR . ($file_path = $tmp_path);
}
ini_set('include_path', $include_path);

unset($include_path, $file_path, $tmp_path); ///< Clean up used variables so they don't show up in userland
