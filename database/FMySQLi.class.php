<?php
/**
 * Overrides the native mysqli class in PHP. The primary purpose of this object 
 * is to return FMySQLiResult and FMySQLiStatement objects on calls to query 
 * and prepare, respectively. Due to the behavior of the FDB class, this object 
 * will most likely never make it to userland.
 *
 * @see http://php.net/manual/en/ref.mysqli.php - Complete MySQLi reference
 *
 * @author Jacob Tews <jacob@webteks.com>
 * @date Sat Mar  1 21:24:55 EST 2008
 * @version $Id$
 */
class FMySQLi extends mysqli {
	/**
	 * Performs a query on the database. The behavior here is different 
	 * from the native PHP implementation as an Exception is thrown instead 
	 * of returning false.
	 *
	 * @throws Exception if the query causes an error.
	 * @param $query The query string
	 * @param $resultmode One of the following constants:
	 * @li @b MYSQLI_STORE_RESULT For using buffered queries (default)
	 * @li @b MYSQLI_USE_RESULT For using unbuffered queries. Be careful 
	 * when using this as calling subsequent queries without calling 
	 * MySQLiResult::free will throw a 'Commansd out of sync' error.
	 * @return For SELECT statements, a FMySQLiResult object; for INSERT, 
	 * UPDATE and DELETE, True. Returns False on failure.
	 * @see http://php.net/manual/en/function.mysqli-query.php
	 */
	public function query ($query, $resultmode = MYSQLI_STORE_RESULT) {
		if (!$this->real_query($query)) {
			/// @todo Put an exception or some form of error 
			/// handling here
			throw new Exception($this->error);
		}
		return new FMySQLiResult($this, $resultmode);
	}
	/**
	 * Prepares a query for execution. Uses the MySQL format for preparing 
	 * queries as well as the MySQL engine for prepared queries. Prepared 
	 * statements run once will encounter huge performance hits while 
	 * statements run multiple times with different arguments will see 
	 * performance increases.
	 *
	 * @param $query Query string in MySQL prepared statement format
	 * @return A FMySQLiStatement object or False on failure
	 * @see http://php.net/manual/en/function.mysqli-prepare.php
	 */
	public function prepare ($query) {
		return new FMySQLiStatement($this, $query);
	}
}
