<?php
/*!
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
	/*!
	 * Adds a single table to the overall data model definition. The @c $model
	 * parameter must be an array and define the fields for the data model 
	 * as described above. The model is converted into an FDataModelTable 
	 * object and added to the collection of tables for the overall model.
	 * 
	 * @param $table_name Name of the table to add
	 * @param $model Array defining the fields for a table
	 */
	public static function addTable ($table_name, array $model) {
		self::$modelTables[$table_name] = new FDataModelTable($table_name, $model);
	}
	/*!
	 * Adds queries to a specific table to be run once all tables in the
	 * database have been initialized. Note that the @c $queries parameter
	 * must be an array, even if there is only one query required to
	 * initialize a table.
	 * 
	 * @param $table_name Name of the table to add queries to
	 * @param $queries Array of queries to add to the table
	 */
	public static function addTableQueries ($table_name, array $queries) {
		self::$tableQueries[$table_name] = $queries;
	}
	/*!
	 * Initializes database model by adding the table definitions to the 
	 * model's table collection.
	 *
	 * @see FDataModel::addTable()
	 * @param $data_model Array of table models
	 * @return @c null
	 */
	public static function setModel ($data_model) {
		self::$modelTables = array();
		foreach ($data_model as $table_name => $model) {
			self::addTable($table_name, $model);
		}
	}
	/*!
	 * Sets any initializing queries that follow the database schema creation.
	 *
	 * @param $table_queries Array of queries with each index a table name and
	 * each value an array of queries.
	 * @return @c null
	 */
	public static function setTableQueries ($table_queries) {
		if (!$table_queries) {
			return;
		}
		foreach ($table_queries as $table => $queries) {
			self::addTableQueries($table, $queries);
		}
	}
	/*!
	 * Gathers all necessary SQL to convert the database from its current 
	 * state to the state defined by the user's database model.
	 *
	 * @return Array of queries in the form 'table' => "query".
	 */
	public static function getQueries () {
		/*if (!is_array(self::$modelTables)) {
			return array();
		}*/
		$creates = array();
		$initializers = array();
		foreach (self::$modelTables as $table_name => &$model) {
			$sql = $model->getSQL();
			if ($sql) {
				$creates[$table_name] = $sql;
				if (array_key_exists($table_name, self::$tableQueries) && strpos($sql, 'CREATE') === 0) {
					foreach (self::$tableQueries[$table_name] as $index => &$table_query) {
						$initializers[$table_name . '_' . $index] = $table_query;
					}
				}
			}
		}
		$queries = array_merge($creates, $initializers);
		return $queries;
	}
	/*!
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
	/*!
	 * @c BIGINT foriegn key field with the following properties:
	 * @li @c UNSIGNED
	 * @li @c NOT @c NULL
	 * @li noPrefix
	 *
	 * @param $length Optional. Length of data in field.
	 * @return FDataModelField Object with properties described above
	 */
	public static function bigintFK ($length = null) {
		$field = self::bigint($length);
		$field->unsigned();
		$field->notNull();
		$field->noPrefix();
		return $field;
	}
	/*!
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
	/*!
	 * @c TEXT field.
	 *
	 * @return FDataModelField Object with properties described above
	 */
	public static function blob () {
		$field = new FDataModelField();
		$field->type = 'BLOB';
		return $field;
	}
	/*!
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
	/*!
	 * @c DATE field.
	 *
	 * @return FDataModelField Object with properties described above
	 */
	public static function date () {
		$field = new FDataModelField();
		$field->type = 'DATE';
		return $field;
	}
	/*!
	 * @c DATETIME field.
	 *
	 * @return FDataModelField Object with properties described above
	 */
	public static function datetime () {
		$field = new FDataModelField();
		$field->type = 'DATETIME';
		return $field;
	}
	
	public static function double($length = 6, $precision = 2) {
		$field = new FDataModelField();
		$field->type = 'DOUBLE';
		$field->precision = $precision;
		$field->length = $length;
		return $field;
		
	}
	/*!
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
	/*!
	 * @c INT foreign key field with the following properties:
	 * @li @c UNSIGNED
	 * @li @c NOT @c NULL
	 * @li noPrefix
	 *
	 * @param $length Optional. Length of data in field.
	 * @return FDataModelField Object with properties described above
	 */
	public static function intFK ($length = null) {
		$field = self::int($length);
		$field->noPrefix();
		$field->notNull();
		$field->unsigned();
		return $field;
	}
	/*!
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
	/*!
	 * @c LONGTEXT field.
	 *
	 * @return FDataModelField Object with properties described above
	 */
	public static function longtext () {
		$field = self::text();
		$field->type = 'LONGTEXT';
		return $field;
	}
	/*!
	 * @c TEXT field.
	 *
	 * @return FDataModelField Object with properties described above
	 */
	public static function text () {
		$field = new FDataModelField();
		$field->type = 'TEXT';
		return $field;
	}
	/*!
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
	/*!
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
	/*!
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
	/*!
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
