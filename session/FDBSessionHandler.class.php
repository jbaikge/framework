<?php
class FDBSessionHandler {
	private $conn;
	public function __construct () {
		$this->register();
	}
	public function register () {
		session_set_save_handler(
			array(&$this, "open"), 
			array(&$this, "close"),
			array(&$this, "read"),
			array(&$this, "write"),
			array(&$this, "destroy"),
			array(&$this, "gc")
		);
	}
	public function createTable () {
		return mysql_query("
		CREATE TABLE `sessions` (
			`session_id` varchar(100) NOT NULL default '',
			`session_data` text NOT NULL,
			`expires` int(11) NOT NULL default '0',
			PRIMARY KEY  (`session_id`)
		) TYPE=MyISAM;
		", $this->conn);
	}
	public function open( $save_path, $session_name ) {
		$this->conn = mysql_connect(
			$_ENV['config']['session.db_host'], 
			$_ENV['config']['session.db_user'], 
			$_ENV['config']['session.db_pass']
		) or die('Could not connect to database for session handling');
		if (!mysql_select_db($_ENV['config']['session.db_name'], $this->conn)) {
			mysql_query('CREATE DATABASE ' . $_ENV['config']['session.db_name'], $this->conn);
			mysql_select_db('session', $this->conn) or die('Unable to select the session database');
		}
		// Don't need to do anything. Just return TRUE.
		return true;
	}
	public function close() {
		mysql_close($this->conn);
		return true;
	}
	public function read( $id ) {
		$data = '';
		$newid = mysql_real_escape_string($id, $this->conn);
		$sql = "SELECT `session_data` FROM `sessions` WHERE `session_id` = '$newid' AND `expires` > UNIX_TIMESTAMP()";
		$rs = mysql_query($sql, $this->conn) or $this->createTable() or die(mysql_error());
		$a = mysql_num_rows($rs);
		if($a > 0) {
			list($data) = mysql_fetch_row($rs);
		}
		return $data;
	}
	public function write( $id, $data ) {
		$lifetime = ini_get('session.gc_maxlifetime');
		$newid = mysql_real_escape_string($id, $this->conn);
		$newdata = mysql_real_escape_string($data, $this->conn);
		$sql = "REPLACE `sessions` (`session_id`,`session_data`,`expires`) VALUES('$newid', '$newdata', UNIX_TIMESTAMP() + $lifetime)";
		$rs = mysql_query($sql, $this->conn) or die(mysql_error());
		return TRUE;
	}
	public function destroy( $id ) {
		$newid = mysql_real_escape_string($id, $this->conn);
		$sql = "DELETE FROM `sessions` WHERE `session_id` = '$newid'";
		mysql_query($sql, $this->conn) or die(mysql_error());
		return TRUE;
	}
	public function gc() {
		$sql = 'DELETE FROM `sessions` WHERE `expires` < UNIX_TIMESTAMP();';
		mysql_query($sql, $this->conn) or die(mysql_error());
		return true;
	}
}
