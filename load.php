<?php /* $Id$ */
/**
 * @author Jacob Tews <jacob@webteks.com>
 * @date Thu Feb  7 20:53:04 EST 2008
 *
 * Performs the minimum required functions to get the current page ready to 
 * start doing something.
 */

define('START_TIME', microtime(true));
define('START_MEM', memory_get_usage());
define('NEWLINE', isset($_ENV['SHELL']) ? "\n" : '<br>');
define('DS', DIRECTORY_SEPARATOR);
setlocale(LC_ALL, array('en_US', 'en_US.UTF-8'));

require(dirname(__FILE__) . '/functions.php');

// Register class autoloader:
require(dirname(__FILE__) . '/util/FClassCache.class.php');
FClassCache::register();

#ob_start('ob_framework_error_handler');
#ob_start('ob_event_handler');
#set_error_handler('framework_error_handler');
#register_shutdown_function("shutdown_callback");

// Include path and siteroot:
$included_files = get_included_files();
$script_path = array_shift($included_files);
$include_path = $file_path = dirname($script_path);
while (!file_exists($file_path . "/webroot.conf.php") && $file_path != ($tmp_path = dirname($file_path))) {
	$include_path .= PATH_SEPARATOR . ($file_path = $tmp_path);
}

ini_restore('include_path');
ini_set('include_path', $include_path . PATH_SEPARATOR . ini_get('include_path'));
define('SITEROOT', $file_path);

// Determine webroot:
if (!isset($_SERVER['PHP_SELF'])) $_SERVER['PHP_SELF'] = $script_path;
if (!isset($_SERVER['PATH_INFO'])) $_SERVER['PATH_INFO'] = '';
define('WEBROOT', substr($_SERVER['PHP_SELF'], 0, -strlen(substr($script_path, strlen(SITEROOT)) . $_SERVER['PATH_INFO'])));

unset($include_path, $file_path, $tmp_path, $script_path, $included_files); ///< Clean up used variables so they don't show up in userland

///////////////////////////////////////////////////////////////////////////////
// Default configuration options:
///////////////////////////////////////////////////////////////////////////////
/**
 * Cache Directory
 */
$_ENV['config']['cache.dir']               = SITEROOT . DS . 'cache';
/**
 * Cached Class to File Mapping
 */
$_ENV['config']['cache.class_list']        = '.private' . DS . 'class_list.php';
/**
 * Cached database view builds
 */
$_ENV['config']['cache.object_view_list']  = '.private' . DS . 'view_list.php';
$_ENV['config']['class_filter.excluded']   = array('.svn' => true, 'tests' => true);
/**
 * Automatically connect to database on page load
 */
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
$_ENV['config']['database.definition']     = null;
/**
 * Private directories that should not have access from a web browser
 */
$_ENV['config']['directories.private']     = array();
/**
 * Public directories that should be writeable by the application
 */
$_ENV['config']['directories.writeable']   = array();
/**
 * Content type of rendered HTML
 */
$_ENV['config']['html.content_type']       = 'UTF-8';
/**
 * Determine whether the output will be HTML or XHTML
 */
$_ENV['config']['html.xhtml']              = false;
/**
 * Directory where class definition files are located
 */
$_ENV['config']['library.dir']             = SITEROOT . DS . 'lib';
$_ENV['config']['library.dir.signals']     = $_ENV['config']['library.dir'] . DS . 'signals';
/**
 * Enable or disable the use of the reporting node network
 */
$_ENV['config']['report.enabled']          = true;
/**
 * Likelihood that a status report will be sent to the network. Value is a
 * percentage from 0.0 - 100.0.
 */
$_ENV['config']['report.frequency']        = 10.0;
$_ENV['config']['report.cache']            = array(&$_ENV['config']['cache.dir'], '.private', 'node_servers.php');
/**
 * Override the server cache with your own. Make sure the contents look like
 * this:
 * <?php
 * $servers = array(
 *     'mine' => array(
 *         'host' => 'localhost',
 *         'ports' => array(8000, 8010)
 *     )
 * );
 */
$_ENV['config']['report.my_cache']         = array(&$_ENV['config']['cache.dir'], '.private', 'my_node_servers.php');
/**
 * Use q* Tables: Only applies if using FObject and implementing
 * FObjectDatabaseStorage
 * Turns on/off the use of the q_* and the qp_* compliments to the view tables,
 * v_* and vp_*. Using these tables increases performance, however it will also
 * effectively double the size of the database. These are still experimental and
 * may not always work. Use with caution.
 */
$_ENV['config']['fobject.qtables']         = false;
/**
 * FObjectSalesforceStorage driver configuration options
 */
$_ENV['config']['salesforce.dir']          = array($_ENV['config']['cache.dir'], 'salesforce');
$_ENV['config']['salesforce.site']         = null;
/**
 * Secret string used to gain access to certain diagnostic tools. If undefined 
 * in user-defined configuraiton, set to rand() to prevent unwarranted access.
 */
$_ENV['config']['secret']                  = rand();
$_ENV['config']['session.db_host']         =& $_ENV['config']['database.host'];
$_ENV['config']['session.db_user']         = null;
$_ENV['config']['session.db_pass']         = null;
$_ENV['config']['session.db_name']         = null;
$_ENV['config']['session.use_db']          = false;
/**
 * Calendar template directory.
 */
$_ENV['config']['templates.calendar.dir']  = array(&$_ENV['config']['library.dir'], 'framework', 'calendar', 'templates');
/**
 * Base template. Used when nothing is defined for FTemplate::render()
 */
$_ENV['config']['templates.base_template'] = 'templates/base.html.php';
/**
 * Form and Field template directories.
 */
$_ENV['config']['templates.form.dir']      = array(&$_ENV['config']['library.dir'], 'framework', 'form', 'templates');
$_ENV['config']['templates.form.field.dir'] = array(&$_ENV['config']['library.dir'], 'framework', 'form', 'field', 'templates');
/**
 * Filters to run before returning content in FTemplate::render(). They are 
 * run in the same order they are provided in the array.
 */
$_ENV['config']['templates.filters']       = array('FWebrootFilter');
/**
 * Default date / time format settings. These can be overridden by webroot if 
 * necessary. Format matches the format defined in the PHP date() documentation.
 * @see http://php.net/manual/en/function.date.php
 */
$_ENV['config']['format.date']             = 'M j, Y';
$_ENV['config']['format.time']             = 'g:i a';
$_ENV['config']['format.datetime']         = $_ENV['config']['format.date'] . ' ' . $_ENV['config']['format.time'];
/**
 * Various modes
 */
$_ENV['config']['mode.preview']            = false;

///////////////////////////////////////////////////////////////////////////////
// Merge in the configuration options specified in the webroot:
///////////////////////////////////////////////////////////////////////////////
if (isset($config) && is_array($config)) {
	foreach ($config as $key => $value) {
		$_ENV['config'][$key] = $value;
	}
	unset($key, $value);
}

///////////////////////////////////////////////////////////////////////////////
// Sanity checks:
///////////////////////////////////////////////////////////////////////////////

foreach (array_merge(array($_ENV['config']['cache.dir']), $_ENV['config']['directories.writeable']) as $dir) {
	if (!is_dir($dir) && !mkdir($dir)) {
		throw new Exception('Directory [' . $dir . '] does not exist. Could not automatically create.');
	}
	if (!is_writeable($dir) && !chmod($dir, 755)) {
		throw new Exception('Directory [' . $dir . '] is not writeable.');
	}
}
if (is_dir($_ENV['config']['cache.dir'] . DS . '.svn')) {
	throw new Exception('Cache directory [' . $_ENV['config']['cache.dir'] . '] should not be under version control.');
}
if (!is_dir($_ENV['config']['cache.dir'] . DS . '.private')) {
	mkdir($_ENV['config']['cache.dir'] . DS . '.private');
	chmod($_ENV['config']['cache.dir'] . DS . '.private', 0777);
	file_put_contents(
		$_ENV['config']['cache.dir'] . DS . '.private' . DS . '.htaccess',
		"order deny,allow\ndeny from all"
	);
}

///////////////////////////////////////////////////////////////////////////////
// Post-merge processing:
///////////////////////////////////////////////////////////////////////////////
$_ENV['config']['cache.class_list']        = $_ENV['config']['cache.dir'] . DS . $_ENV['config']['cache.class_list'];
$_ENV['config']['cache.object_view_list']  = $_ENV['config']['cache.dir'] . DS . $_ENV['config']['cache.object_view_list'];
$_ENV['config']['firephp.class']           = FFileSystem::fileExists('FirePHPCore/FirePHP.class.php');

///////////////////////////////////////////////////////////////////////////////
// Instantiate global helpers:
///////////////////////////////////////////////////////////////////////////////
if ($_ENV['config']['database.auto_connect']) {
	FDB::connect();
}

if ($_ENV['config']['database.auto_connect'] && $_ENV['config']['session.use_db']) {
	if ($_ENV['config']['session.db_host'] == '' && $_ENV['config']['database.master_host'] != '') {
		$_ENV['config']['session.db_host'] = $_ENV['config']['database.master_host'];
	}
	new FDBSessionHandler();
}

if ($_ENV['config']['firephp.class'] != false) {
	include($_ENV['config']['firephp.class']);
	// Register to false out the exception throwing.
	FirePHP::getInstance(true)->registerErrorHandler(false);
}
if ($_ENV['config']['report.enabled']) {
	set_error_handler(array('FCallback', 'errorHandler'));
	register_shutdown_function(array('FCallback', 'shutdown'));
}

$implode_keys = array(
	'templates.form.dir',
	'templates.form.field.dir',
	'templates.calendar.dir',
	'report.cache',
	'report.my_cache',
	'salesforce.dir',
);
foreach ($implode_keys as $key) {
	if (is_array($_ENV['config'][$key])) {
		$_ENV['config'][$key] = implode(DS, $_ENV['config'][$key]);
	}
}
unset($implode_keys, $key);

///////////////////////////////////////////////////////////////////////////////
// Secret Call processing:
///////////////////////////////////////////////////////////////////////////////
$secret_calls = filter_input_array(INPUT_GET, array(
	'GET_SALESFORCE'          => FILTER_UNSAFE_RAW,
	'UPDATE_DATABASE'         => FILTER_UNSAFE_RAW,
	'UPDATE_FOBJECT_DATABASE' => FILTER_UNSAFE_RAW,
	'CLEAR_CLASS_CACHE'       => FILTER_UNSAFE_RAW,
	'UPDATE_SERVER_LIST'      => FILTER_UNSAFE_RAW,
));
switch ($_ENV['config']['secret']) {
	case $secret_calls['GET_SALESFORCE']:
		if (interface_exists('FObjectSalesforceStorage')) {
			header('Content-Type: text/xml');
			echo FObjectSalesforceStorageDriver::getSalesforceFilesXML();
			exit;
		}
		break;
	case $secret_calls['UPDATE_FOBJECT_DATABASE']:
		FObjectQuery::updateStructure();
		break;
	case $secret_calls['UPDATE_DATABASE']:
		sync_database();
		break;
	case $secret_calls['CLEAR_CLASS_CACHE']:
		FClassCache::clear();
		break;
	case $secret_calls['UPDATE_SERVER_LIST']:
		FNodeMessenger::refreshServerList();
		break;
}
unset($secret_calls);
