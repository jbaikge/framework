<?php
/**
 * Database model handler. Takes in database table definitions to pull
 * out any necessary queries required to match the database to the supplied 
 * model.
 * 
 * Example database model:
 * @code
 * $tables = array(
 * 	'my_table' => array(
 * 		'_prefix' => "my_table_",
 * 		'_engine' => "InnoDB",
 * 		'id' => FDataModel::intPK()
 * 	)
 * );
 * $model = new FDataModel($tables);
 * foreach ($model->getQueries() as $table => $query) {
 * 	printf("%s:\n%s\n\n", $table, $query);
 * }
 * @endcode
 * 
 * @author Jacob Tews <jtews@okco.com>
 * @date Sat Dec  5 17:34:15 EST 2009
 * @version $Id$
 */
class FDataModel {
	protected static $modelTables = array(); ///< Collection of FDataModelTable objects
	protected static $tableQueries = array(); ///< Collection of queries to run after tables are created

	/**
	 * Prevent object instantiation
	 */
	private function __construct () {
	}

	/**
	 * Initializes database model by converting all table definition arrays 
	 * to FDataModelTable objects.
	 *
	 * @param $data_model Array of table models
	 */
	public static function setModel ($data_model) {
		self::$modelTables = array();
		foreach ($data_model as $table_name => &$model) {
			self::$modelTables[$table_name] = new FDataModelTable($table_name, $model);
		}
	}
	public static function setTableQueries ($table_queries) {
		self::$tableQueries = $table_queries;
	}
	/**
	 * Gathers all necessary SQL to convert the database from its current 
	 * state to the state defined by the user's database model.
	 *
	 * @return Array of queries in the form 'table' => "query".
	 */
	public static function getQueries () {
		if (!is_array(self::$modelTables)) {
			return array();
		}
		$queries = array();
		foreach (self::$modelTables as $table_name => &$model) {
			$sql = $model->getSQL();
			if ($sql) {
				$queries[$table_name] = $sql;
				if (array_key_exists($table_name, self::$tableQueries) && strpos($sql, 'CREATE') === 0) {
					foreach (self::$tableQueries[$table_name] as $index => &$table_query) {
						$queries[$table_name . '_' . $index] = $table_query;
					}
				}
			}
		}
		return $queries;
	}
	/**
	 * @c BIGINT field.
	 *
	 * @param $length Optional. Length of data in field.
	 * @return FDataModelField Object with properties described above
	 */
	public static function bigint ($length = null) {
		$field = self::int($length);
		$field->type = 'BIGINT';
		return $field;
	}
	/**
	 * @c BIGINT primary key field with the following properties:
	 * @li @c UNSIGNED
	 * @li @c NOT @c NULL
	 * @li @c PRIMARY @c KEY
	 * @li @c AUTO_INCREMENT
	 *
	 * @param $length Optional. Length of data in field.
	 * @return FDataModelField Object with properties described above
	 */
	public static function bigintPK ($length = null) {
		$field = self::intPK($length);
		$field->type = 'BIGINT';
		return $field;
	}
	/**
	 * @c CHAR field.
	 *
	 * @param $length Optional. Length of data in field.
	 * @return FDataModelField Object with properties described above
	 */
	public static function char ($length = null) {
		$field = new FDataModelField();
		$field->type = 'CHAR';
		$field->length = $length;
		return $field;
	}
	/**
	 * @c DATE field.
	 *
	 * @return FDataModelField Object with properties described above
	 */
	public static function date () {
		$field = new FDataModelField();
		$field->type = 'DATE';
		return $field;
	}
	/**
	 * @c DATETIME field.
	 *
	 * @return FDataModelField Object with properties described above
	 */
	public static function datetime () {
		$field = new FDataModelField();
		$field->type = 'DATETIME';
		return $field;
	}
	/**
	 * @c INT field.
	 *
	 * @param $length Optional. Length of data in field.
	 * @return FDataModelField Object with properties described above
	 */
	public static function int ($length = null) {
		$field = new FDataModelField();
		$field->type = 'INT';
		$field->length = $length;
		return $field;
	}
	/**
	 * @c INT primary key field with the following properties:
	 * @li @c UNSIGNED
	 * @li @c NOT @c NULL
	 * @li @c PRIMARY @c KEY
	 * @li @c AUTO_INCREMENT
	 *
	 * @param $length Optional. Length of data in field.
	 * @return FDataModelField Object with properties described above
	 */
	public static function intPK ($length = null) {
		$field = self::int($length);
		$field->autoIncrement();
		$field->notNull();
		$field->primary();
		$field->unsigned();
		return $field;
	}
	/**
	 * @c LONGTEXT field.
	 *
	 * @return FDataModelField Object with properties described above
	 */
	public static function longtext () {
		$field = self::text();
		$field->type = 'LONGTEXT';
		return $field;
	}
	/**
	 * @c TEXT field.
	 *
	 * @return FDataModelField Object with properties described above
	 */
	public static function text () {
		$field = new FDataModelField();
		$field->type = 'TEXT';
		return $field;
	}
	/**
	 * @c TIMESTAMP field.
	 *
	 * Keep in mind: this field type is allowed to utilize the @c FDataModelField::insertOnly() and the @c 
	 * FDataModelField::updateOnly() methods.
	 *
	 * @return FDataModelField Object with properties described above
	 */
	public static function timestamp () {
		$field = new FDataModelField();
		$field->type = 'TIMESTAMP';
		return $field;
	}
	/**
	 * @c TINYINT field.
	 *
	 * @param $length Optional. Length of data in field.
	 * @return FDataModelField Object with properties described above
	 */
	public static function tinyint ($length = null) {
		$field = self::int($length);
		$field->type = 'TINYINT';
		return $field;
	}
	/**
	 * @c TINYINT primary key field with the following properties:
	 * @li @c UNSIGNED
	 * @li @c NOT @c NULL
	 * @li @c PRIMARY @c KEY
	 * @li @c AUTO_INCREMENT
	 *
	 * @param $length Optional. Length of data in field.
	 * @return FDataModelField Object with properties described above
	 */
	public static function tinyintPK ($length = null) {
		$field = self::intPK($length);
		$field->type = 'TINYINT';
		return $field;
	}
	/**
	 * @c VARCHAR field.
	 *
	 * @param $length Optional. Length of data in field.
	 * @return FDataModelField Object with properties described above
	 */
	public static function varchar ($length = null) {
		$field = new FDataModelField();
		$field->type = 'VARCHAR';
		$field->length = $length;
		return $field;
	}
}
