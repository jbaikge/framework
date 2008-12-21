<?php
/**
 * Database connection handler. Supports master / slave and single-database 
 * configurations transparently. 
 *
 * Calls to FDB::query auto-negotiate whether to use the master or the slave. 
 * In single-database configurations, the designation is still made, but the 
 * connection is to the same database. This allows a site to change 
 * configurations without changing calls to the query function.
 *
 * When using a master / slave configuration, a query may be forced to run on a 
 * specific server by using the FDB::master and FDB::slave methods. Keep in 
 * mind, slaves cannot INSERT, UPDATE or DELETE.
 *
 * @author Jacob Tews <jacob@webteks.com>
 * @date Sat Feb 23 19:20:34 EST 2008
 * @version $Id$
 */
class FDB {
	private static $master; ///< Master database connection
	private static $slave; ///< Slave database connection
	private static $noSelectCheck; ///< Skip query check in FDB::slave();
	/**
	 * Do not allow an instance of this class as it is a static class.
	 */
	private function __construct () {
	}
	/**
	 * Turns auto commit on or off. This only acts on the master database 
	 * since slaves cannot perform queries requiring commits.
	 *
	 * Calling this function with @c true or no arguments will turn auto 
	 * commit on. Keep in mind that any out-standing transactions will 
	 * commit unless a rollback is issued.
	 *
	 * @param $bool True to turn auto commit on, false to turn it off
	 * @return True if successful, false otherwise
	 * @see http://php.net/manual/function.mysqli-autocommit.php
	 */
	public static function autocommit ($bool = true) {
		return self::$master->autocommit($bool);
	}
	/**
	 * Closes the database connection(s). This method is automatically 
	 * called on script shutdown and should not need to be called under 
	 * normal coding conditions. Both master and slave connections are 
	 * closed with this method.
	 * 
	 * @see http://php.net/manual/function.mysqli-close.php
	 */
	public static function close () {
		if (self::$slave !== self::$master) {
			self::$slave->close();
		}
		self::$master->close();
	}
	/**
	 * Makes changes to database permenant. This method is only useful if 
	 * FDB::autocommit is called with @c false.
	 *
	 * @return True if the commit was successful, false otherwise
	 * @see http://php.net/manual/function.mysqli-commit.php
	 */
	public static function commit () {
		return self::$master->commit();
	}
	/**
	 * Creates connections to database server(s). This method is called 
	 * before script execution to generate the required connections and 
	 * should not need to be called under normal coding conditions. The 
	 * config options used in the first argument are very important in 
	 * determining how to connect to the database(s).
	 * 
	 * For a single database setup:
	 * @li @b database.host Hostname/IP of database server
	 * @li @b database.user Username to connect to database server
	 * @li @b database.pass Password to connect to database server
	 * @li @b database.name Database to select once connected
	 *
	 * For a master / slave setup:
	 * @li @b database.master_host Hostname/IP of master database server
	 * @li @b database.master_user Username to conenct to database server
	 * @li @b database.master_pass Password to conenct to database server
	 * @li @b database.master_name Database to select once connected
	 * @li @b database.slave_host Hostname/IP of slave database server
	 * @li @b database.slave_user Username to conenct to database server
	 * @li @b database.slave_pass Password to conenct to database server
	 * @li @b database.slave_name Database to select once connected
	 *
	 * Also note, for a master / slave setup, all options except the host 
	 * that have the same value can be swapped with their single-database 
	 * counterparts (ex: leave @b database.master_user and @b 
	 * database.slave_user null and set @b database.user to use the same 
	 * user on both servers).
	 *
	 * @param $config An array with one of the sets of keys described above
	 * @see http://php.net/manual/function.mysqli-connect.php
	 */
	public static function connect ($config = null) {
		($config === null) && $config =& $_ENV['config'];
		if ($config['database.master_host'] && 
			$config['database.slave_host']) {
			// We have a Master/Slave configuration. Create a 
			// connection to each of the servers.
			self::$master = new FMySQLi(
				$config['database.master_host'],
				($config['database.master_user']) ? $config['database.master_user'] : $config['database.user'],
				($config['database.master_pass']) ? $config['database.master_pass'] : $config['database.pass'],
				$config['database.name']
			);
			if (!self::$master || mysqli_connect_errno()) {
				throw new Exception("Could not connect to master database:" . NEWLINE . mysqli_connect_error());
			}
			self::$master = new FMySQLi(
				$config['database.slave_host'],
				($config['database.slave_user']) ? $config['database.slave_user'] : $config['database.user'],
				($config['database.slave_pass']) ? $config['database.slave_pass'] : $config['database.pass'],
				$config['database.name']
			);
			if (!self::$slave || mysqli_connect_errno()) {
				throw new Exception("Could not connect to slave database:" . NEWLINE . mysqli_connect_error());
			}
		} else {
			// Just one database to accept all queries
			self::$master = new FMySQLi(
				$config['database.host'],
				$config['database.user'],
				$config['database.pass'],
				$config['database.name']
			);
			if (!self::$master || mysqli_connect_errno()) {
				throw new Exception("Could not connect to database:>" . NEWLINE . mysqli_connect_error());
			}
			self::$slave =& self::$master;
		}
	}
	/**
	 * Run query on master database.
	 *
	 * @param $sql SQL statement in sprintf format
	 * @see FDB::query for extra arguments
	 */
	public static function master ($sql) {
		// KLUDGE: Cannot use func_get_args() as an argument to a 
		// function call.
		$args = array_slice(func_get_args(), 1);
		if (count($args) && is_array($args[0])) {
			$args = $args[0];
		}
		return self::runQuery('master', $sql, $args);
	}
	/**
	 * Auto-negotiates where to run query. The arguments for this method 
	 * allow great flexibility when writing queries and offer better safety 
	 * when running queries.
	 *
	 * One-argument:
	 * @code
	 * FDB::query("SELECT * FROM table");
	 * @endcode
	 *
	 * Multiple scalar arguments (note double-percent):
	 * @code
	 * $min_id = 5;
	 * FDB::query(
	 * 	"SELECT id FROM table WHERE id > %d AND name LIKE '%s%%'",
	 * 	$min_id,
	 * 	"Smi"
	 * );
	 * @endcode
	 *
	 * Array of arguments:
	 * @code
	 * $args = array(5, "Smi");
	 * FDB::query(
	 * 	"SELECT id FROM table WHERE id > %d AND name LIKE '%s%%'",
	 * 	$args
	 * );
	 * @endcode
	 *
	 * @param $sql SQL statement in sprintf format
	 * @return FMySQLiResult object
	 * @see http://php.net/manual/function.mysqli-query.php
	 * @see http://php.net/manual/function.sprintf.php - Sprintf format
	 * reference
	 */
	public static function query ($sql) {
		// KLUDGE: Cannot use func_get_args() as an argument to a 
		// function call.
		$args = array_slice(func_get_args(), 1);
		if (count($args) && is_array($args[0])) {
			$args = $args[0];
		}
		if (FString::startsWith(ltrim($sql), 'SELECT')) {
			self::$noSelectCheck = true;
			return self::slave($sql, $args);
		} else {
			return self::master($sql, $args);
		}
	}
	/**
	 * Run query on slave database.
	 *
	 * @param $sql SQL statement in sprintf format
	 * @see FDB::query for extra arguments
	 */
	public static function slave ($sql) {
		// KLUDGE: Cannot use func_get_args() as an argument to a 
		// function call.
		if (!self::$noSelectCheck && !FString::startsWith(ltrim($sql), 'SELECT')) {
			throw new Exception("Only SELECT statements may run on the slave.");
		}
		self::$noSelectCheck = false;
		$args = array_slice(func_get_args(), 1);
		if (count($args) && is_array($args[0])) {
			$args = $args[0];
		}
		return self::runQuery('slave', $sql, $args);
	}
	/**
	 * Escapes the arguments for a SQL statement. The SQL may use any of 
	 * the standard sprintf() formatting escapes. The method call is the 
	 * same as a call to sprintf() where the format string comes first and 
	 * all arguments follow as arguments to the method.
	 *
	 * @param $sql SQL statement in sprintf format
	 * @return Escaped SQL statement.
	 */
	public static function sql ($sql) {
		$args = array_slice(func_get_args(), 1);
		if (count($args) && is_array($args[0])) {
			$args = $args[0];
		}
		foreach ($args as &$arg) {
			$arg = self::$slave->escape_string($arg);
		}
		return vsprintf($sql, $args);
	}
	/**
	 * Performs the SQL query on the appropriate server.
	 *
	 * @throws Exception if $server parameter is not 'master' or 'slave'.
	 * @param $server One of 'master' or 'slave'
	 * @param $sql SQL statement in sprintf format.
	 * @param $args Array of arguments corresponding to the sprintf SQL 
	 * statement.
	 * @return FMySQLiResult object
	 */
	private static function runQuery ($server, $sql, $args) {
		if ($server == 'slave') {
			$link =& self::$slave;
		}
		else if ($server == 'master') {
			$link =& self::$master;
		}
		else {
			throw new Exception("Invalid server name: `{$server}'");
		}
		if (!$link) {
			throw new Exception("Cannot find link to database. Are you sure you ran `FDB::connect()'?");
		}
		return $link->query(self::sql($sql, $args));
	}
}
