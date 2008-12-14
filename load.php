<?php /* $Id$ */
/**
 * @author Jacob Tews <jacob@webteks.com>
 * @date Thu Feb  7 20:53:04 EST 2008
 *
 * Performs the minimum required functions to get the current page ready to 
 * start doing something.
 */

define('NEWLINE', isset($_ENV['SHELL']) ? "\n" : '<br>');
define('DS', DIRECTORY_SEPARATOR);

require(dirname(__FILE__) . '/functions.php');
#ob_start('ob_framework_error_handler');
#ob_start('ob_event_handler');
#set_error_handler('framework_error_handler');
#register_shutdown_function("shutdown_callback");

// Include path and siteroot:
$include_path = $file_path = dirname(__FILE__);
while (!file_exists($file_path . "/webroot.conf.php") && $file_path != ($tmp_path = dirname($file_path))) {
	$include_path .= PATH_SEPARATOR . ($file_path = $tmp_path);
}

ini_set('include_path', $include_path);
define('SITEROOT', $file_path);

// Determine webroot:

unset($include_path, $file_path, $tmp_path); ///< Clean up used variables so they don't show up in userland

///////////////////////////////////////////////////////////////////////////////
// Default configuration options:
///////////////////////////////////////////////////////////////////////////////
$_ENV['config']['cache.dir']               = SITEROOT . DS . 'cache';
$_ENV['config']['cache.class_list']        = '.private' . DS . 'class_list.php';
$_ENV['config']['database.auto_connect']   = true;
$_ENV['config']['database.master_host']    = null;
$_ENV['config']['database.master_user']    = null;
$_ENV['config']['database.master_pass']    = null;
$_ENV['config']['database.slave_host']     = null;
$_ENV['config']['database.slave_user']     = null;
$_ENV['config']['database.slave_pass']     = null;
$_ENV['config']['database.host']           = null;
$_ENV['config']['database.user']           = null;
$_ENV['config']['database.pass']           = null;
$_ENV['config']['database.name']           = null;
$_ENV['config']['library.dir']             = SITEROOT . DS . 'lib';
$_ENV['config']['templates.base_template'] = 'templates/base.html.php';
$_ENV['config']['templates.filters']       = array('FWebrootFilter');

///////////////////////////////////////////////////////////////////////////////
// Merge in the configuration options specified in the webroot:
///////////////////////////////////////////////////////////////////////////////
if (isset($config) && is_array($config)) {
	$_ENV['config'] = array_merge($_ENV['config'], $config);
}

///////////////////////////////////////////////////////////////////////////////
// Post-merge processing:
///////////////////////////////////////////////////////////////////////////////
$_ENV['config']['cache.class_list']        = $_ENV['config']['cache.dir'] . DS . $_ENV['config']['cache.class_list'];

///////////////////////////////////////////////////////////////////////////////
// Sanity checks:
///////////////////////////////////////////////////////////////////////////////
if (!is_dir($_ENV['config']['cache.dir'])) {
	throw new Exception('Cache directory [' . $_ENV['config']['cache.dir'] . '] does not exist.');
}
if (!is_writeable($_ENV['config']['cache.dir'])) {
	throw new Exception('Cache directory [' . $_ENV['config']['cache.dir'] . '] is not writeable.');
}
if (is_dir($_ENV['config']['cache.dir'] . DS . '.svn')) {
	throw new Exception('Cache directory [' . $_ENV['config']['cache.dir'] . '] should not be under version control.');
}
if (!is_dir($_ENV['config']['cache.dir'] . DS . '.private')) {
	mkdir($_ENV['config']['cache.dir'] . DS . '.private');
	chmod($_ENV['config']['cache.dir'] . DS . '.private', 0700);
	file_put_contents(
		$_ENV['config']['cache.dir'] . DS . '.private' . DS . '.htaccess',
		"order deny,allow\ndeny from all"
	);
}
