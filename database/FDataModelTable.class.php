<?php
class FDataModelTable {
	private $setupComplete = false;
	protected $engine = 'InnoDB';
	protected $fields;
	protected $keys;
	protected $prefix = '';
	protected $table;

	public function __construct ($table, $fields) {
		$this->table = $table;
		$this->fields = $fields;
		if ($fields['_prefix']) $this->prefix = $fields['_prefix'];
		if ($fields['_engine']) $this->engine = $fields['_engine'];
	}
	public function setupFields () {
		if ($this->setupComplete) {
			return;
		}
		$this->keys = array(
			'index' => array(),
			'primary' => array(),
			'unique' => array(),
			'foreign' => array()
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
			}
		}
		$this->setupComplete = true;
	}
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
			$on_update = $on_delete = null;
		}
		$sql = sprintf("CREATE TABLE IF NOT EXISTS `%s` (%s) ENGINE=%s DEFAULT CHARSET=utf8",
			$this->table,
			implode(", \n", $statements),
			$this->engine
		);

		return $sql;
	}
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
			
			if ($field->prefix) $field_name = $this->fields['_prefix'] . $field_name;
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
	public function getSQL () {
		$sql = $this->getAlter();
		if ($sql === false && FDB::query("SHOW TABLES LIKE '%s'", $this->table)->count() == 0) {
			$sql = $this->getCreate();
		}
		return $sql;
	}
}
