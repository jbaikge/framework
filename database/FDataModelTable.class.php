<?php
/*!
 * Constructs a SQL table definition for automatic instantiation of database
 * tables. Each field defined for the table must be defined using an
 * FDataModelField object.
 * 
 * SQL generation follows two paths: The first path is to generate a @c CREATE 
 * @c TABLE query with all fields defined. The second path involves generating
 * an @c ALTER @c TABLE statement to only change the fields that do not match
 * the definition. The @c ALTER @c TABLE statement will never remove fields, but
 * it will change field types and add new fields.
 * 
 * Determination of the path is handled by #getSQL() where it determines whether
 * #getCreate() or #getAlter() are to be used.
 * 
 * Example implementation:
 * @code
 * $fields = array(
 *     '_prefix' => 'my_',
 *     '_engine' => 'MyISAM',
 *     'id' => FDataModel::intPK(),
 *     'name' => FDataModel::varchar(128)->notNull()
 * );
 * 
 * $model = new FDataModelTable('my_table', $fields);
 * echo $model->getSQL();
 * @endcode
 * 
 * Output (formatted for readability):
 * @code
 * CREATE TABLE IF NOT EXISTS `my_table` (
 *     my_id INT UNSIGNED NOT NULL AUTO_INCREMENT, 
 *     my_name VARCHAR(128) NOT NULL, 
 *     PRIMARY KEY (my_id)
 * ) ENGINE=MyISAM DEFAULT CHARSET=utf8
 * @endcode
 * 
 * @author Jacob Tews <jtews@okco.com>
 * @date Sat Dec  5 17:34:15 EST 2009
 * @version $Id$
 */
class FDataModelTable {
	/*!
	 * Internal flag to determine whether #setupFields() ran
	 */
	private $setupComplete = false;
	/*!
	 * Table engine type. Default: InnoDB
	 */
	protected $engine = 'InnoDB';
	/*!
	 * Array of fields as outlined in the class documentation
	 */
	protected $fields;
	/*!
	 * Retains the keys for indexes, primary, unique, foreign, and fulltext
	 */
	protected $keys;
	/*!
	 * Default field prefix. Only applies to fields if noPrefix() was not
	 * called. Default: ""
	 */
	protected $prefix = '';
	/*!
	 * Table name
	 */
	protected $table;
	/*!
	 * Initializes a table definition to begin defining the SQL required to
	 * either create or alter the table.
	 * 
	 * @param $table Table name
	 * @param $fields Array of field definitions with each key defining the
	 * field name and the value a FDataModelField object.
	 */
	public function __construct ($table, $fields) {
		$this->table = $table;
		$this->fields = $fields;
		if (array_key_exists('_prefix', $fields)) $this->prefix = $fields['_prefix'];
		if (array_key_exists('_engine', $fields)) $this->engine = $fields['_engine'];
	}
	/*!
	 * Establishes the internal structure of the table's indexes and keys. This
	 * method does not have a return but modifies the #$keys field according to
	 * each fields' definitions.
	 * 
	 * It is typically called by other methods to ensure the proper indexes
	 * have been established before continuing processing.
	 * 
	 * @return null
	 */
	public function setupFields () {
		if ($this->setupComplete) {
			return;
		}
		$this->keys = array(
			'index' => array(),
			'primary' => array(),
			'unique' => array(),
			'foreign' => array(),
			'fulltext' => array()
		);
		foreach ($this->fields as $field_name => &$field) {
			if ($field instanceof FDataModelField) {
				$prefixed_name = (($field->prefix) ? $this->prefix : '') . $field_name;
				// Indexes
				if ($field->index) {
					$key_name = $field->indexName;
					if ($key_name == '') $key_name = 'idx_' . $prefixed_name;
					$this->keys['index'][$key_name][] = $prefixed_name;
				}
				// Primary Keys
				if ($field->primary) {
					$this->keys['primary'][] = $prefixed_name;
				}
				// Unique Keys
				if ($field->unique) {
					$key_name = $field->uniqueName;
					if ($key_name == '') $key_name = 'unq_' . $prefixed_name;
					$this->keys['unique'][$key_name][] = $prefixed_name;
				}
				// Foreign Keys
				if ($field->foreignKeyField) {
					$key_name = $field->foreignKeyName;
					if ($key_name == '') $key_name = 'fk_' . $this->table . '_' . $prefixed_name;
					$this->keys['foreign'][$key_name][] = array(
						'field' => $prefixed_name,
						'reference' => $field->foreignKeyField,
						'table' => $field->foreignKeyTable,
						'delete' => $field->foreignKeyDelete,
						'update' => $field->foreignKeyUpdate
					);
				}
				// Fulltext Keys
				if (count($field->fulltext)) {
					foreach ($field->fulltext as $fulltext) {
						if ($fulltext === null) {
							$fulltext = 'ft_' . $this->table;
						}
						$this->keys['fulltext'][$fulltext][] = $prefixed_name;
					}
				}
			}
		}
		$this->setupComplete = true;
	}
	/*!
	 * Generates the proper @c CREATE @c TABLE syntax for the defined table.
	 * 
	 * Possible errors thrown:
	 * @li "ON UPDATE does not match for all fields" - Triggered when a
	 * composite foreign key is established and all @c ON @c UPDATE definitions
	 * do not match.
	 * @li "ON DELETE does not match for all fields" - Triggered when a
	 * composite foreign key is established and all @c ON @c DELETE definitions
	 * do not match.
	 * 
	 * @return String with entire @c CREATE @c TABLE statement
	 */
	public function getCreate () {
		$this->setupFields();
		$statements = array();
		foreach ($this->fields as $name => &$field) {
			if ($field instanceof FDataModelField) {
				$statements[] = sprintf("%s", $field->getDefinition($name, $this->prefix));
			}
		}
		if ($this->keys['primary']) {
			$statements[] = sprintf("PRIMARY KEY (%s)", implode(', ', $this->keys['primary']));
		}
		foreach ($this->keys['index'] as $key_name => $fields) {
			$statements[] = sprintf("INDEX `%s` (%s)", $key_name, implode(', ', $fields));
		}
		foreach ($this->keys['unique'] as $key_name => $fields) {
			$statements[] = sprintf("UNIQUE `%s` (%s)", $key_name, implode(', ', $fields));
		}
		foreach ($this->keys['foreign'] as $key_name => $fields) {
			$on_update = $on_delete = null;
			$local_fields = array();
			$reference_fields = array();
			foreach ($fields as $field) {
				$local_fields[] = $field['field'];
				$reference_fields[] = $field['reference'];
				if ($on_update != '' && $on_update != $field['update']) {
					trigger_error('ON UPDATE does not match for all fields');
				}
				if ($on_delete != '' && $on_delete != $field['delete']) {
					trigger_error('ON DELETE does not match for all fields');
				}
				$on_update = $field['update'];
				$on_delete = $field['delete'];
				$reference_table = $field['table'];
			}
			$statements[] = sprintf("CONSTRAINT `%s` FOREIGN KEY (%s) REFERENCES `%s` (%s) ON UPDATE %s ON DELETE %s",
				$key_name,
				implode(', ', $local_fields),
				$reference_table,
				implode(', ', $reference_fields),
				$on_update,
				$on_delete
			);
		}
		foreach ($this->keys['fulltext'] as $key_name => $fields) {
			$statements[] = sprintf("FULLTEXT `%s` (%s)", $key_name, implode(',', $fields));
		}
		$sql = sprintf("CREATE TABLE IF NOT EXISTS `%s` (%s) ENGINE=%s DEFAULT CHARSET=utf8",
			$this->table,
			implode(", \n", $statements),
			$this->engine
		);

		return $sql;
	}
	/*!
	 * Generates the @c ALTER @c TABLE query for a table with a modified
	 * structure, if necessary.
	 * 
	 * If the table does not already exist, the method falls out early with a 
	 * @c false return.
	 * 
	 * If the structure defined is the same as the structure of the existing
	 * table, the method will complete with a @c false return.
	 * 
	 * @return @c ALTER @c TABLE query if applicable, @c false otherwise.
	 */
	public function getAlter () {
		$info_result = FDB::query(
			"SELECT NULL FROM information_schema.TABLES WHERE TABLE_SCHEMA = '%s' AND TABLE_NAME = '%s'",
			$_ENV['config']['database.name'],
			$this->table
		);
		if ($info_result->count() == 0) {
			// Table does not exist. No further action required.
			return false;
		}
		$column_result = FDB::query(
			"SELECT COLUMN_NAME, ORDINAL_POSITION, COLUMN_DEFAULT, IS_NULLABLE, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, NUMERIC_PRECISION, NUMERIC_SCALE, EXTRA
			FROM information_schema.COLUMNS
			WHERE TABLE_SCHEMA = '%s' AND TABLE_NAME = '%s'
			ORDER BY ORDINAL_POSITION",
			$_ENV['config']['database.name'],
			$this->table
		);

		$live_columns = array();
		foreach ($column_result as $row) {
			$live_columns[$row->COLUMN_NAME] = $row;
		}
		
		$statements = array();
		$previous_field = null;
		foreach ($this->fields as $field_name => &$field) {
			// Skip settings fields:
			if ($field_name[0] == '_') continue;
			
			if ($field->prefix) $field_name = $this->prefix . $field_name;
			if (isset($live_columns[$field_name])) {
				// Field exists, verify attributes
			} else {
				// Field does not exist, determine where to add
				if ($previous_field) {
					$position = sprintf('AFTER `%s`', $previous_field);
				} else {
					$position = 'FIRST';
				}
				// $field_name already has $this->prefix applied
				$statements[] = sprintf(
					"ADD COLUMN %s %s",
					$field->getDefinition($field_name),
					$position
				);
			}
			$previous_field = $field_name;
		}
		if ($statements) {
			return sprintf("ALTER TABLE `%s` %s", $this->table, implode(', ', $statements));
		} else {
			return false;
		}
	}
	/*!
	 * Fetches necessary SQL to operate on the defined table. If the table
	 * already exists and needs a structural update, an @c ALTER @c TABLE
	 * statement is returned. If the table does not exist, a @c CREATE @c TABLE
	 * statement is returned. If the table exists and there is nothing to
	 * change, @c false is returned.
	 * 
	 * @return Query or @c false as describe above
	 */
	public function getSQL () {
		$sql = $this->getAlter();
		if ($sql === false && FDB::query("SHOW TABLES LIKE '%s'", $this->table)->count() == 0) {
			$sql = $this->getCreate();
		}
		return $sql;
	}
}
