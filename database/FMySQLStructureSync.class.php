<?php
/*!
 * @author Jacob Tews <jacob@webteks.com>
 * @date Sun Dec 14 12:39:07 EST 2008
 * @version $Id$
 */
class FMySQLStructureSync {
	private $file_contents;
	public function __construct ($sql_path = null) {
		($sql_path === null) && $sql_path = $_ENV['config']['database.definition'];
		$this->file_contents = file_get_contents($sql_path);
	}
	public function addMissingFields () {
	}
	public function addMissingTables () {
		$existing_tables = array();
		foreach (FDB::query('SHOW TABLES')->asRow() as $row) {
			$existing_tables[] = $row[0];
		}
		return $this->createTables($existing_tables);
	}
	public function removeMissingTables () {
	}
	public function removeMissingFields () {
	}
	private function createTables ($existing_tables) {
		$query_count = 0;
		$sql_file =& $this->file_contents;
		FDB::query('SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0');
		for ($cmd = strtok($sql_file, ';'); $cmd !== false; $cmd = strtok(';')) {
			$sql = trim($cmd);
			list($table) = sscanf($sql, 'CREATE TABLE %s');
			if ($table === null) {
				list($table) = sscanf($sql, 'INSERT INTO %s');
			}
			if ($table === null) continue;
			$table = str_replace('`', '', $table);
			if (!in_array($table, $existing_tables)) {
				FDB::query($sql);
				++$query_count;
			}
		}
		FDB::query('SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS');
		return $query_count;
	}
}
