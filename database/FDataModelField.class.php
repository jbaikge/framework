<?php
/*!
 * Defines a database field's attributes. This class is should not get 
 * instantiated on its own, but rather utilized through FDataModel.
 *
 * @author Jake Tews <jtews@okco.com>
 */
class FDataModelField {
	public $autoIncrement = false; ///< @c AUTO_INCREMENT
	public $default; ///< @c DEFAULT (value)
	public $enumValues; ///< Array of values for @c ENUM type
	public $foreignKeyDelete; ///< Value for @c ON @c DELETE: @c CASCADE/RESTRICT/NULL
	public $foreignKeyField; ///< Field name for @c FOREIGN @c KEY
	public $foreignKeyName; ///< Unique identifier for foreign key (autogen if blank)
	public $foreignKeyTable; ///< Table containing foreign key column
	public $foreignKeyUpdate; ///< Value for @c ON @c DELETE: @c CASCADE/RESTRICT/NULL
	public $fulltext = array(); ///< Columns included in a @c FULLTEXT index
	public $index = false; ///< @c INDEX/KEY
	public $indexName; ///< Unique name of index (auto-gen if blank)
	public $length; ///< Length of field
	public $null = true; ///< @c NULL/NOT @c NULL
	public $precision; ///< Precision value for @c DECIMAL field types
	public $prefix = true; ///< Column prefix
	public $primary = false; ///< @c PRIMARY @c KEY
	public $type; ///< Column datatype
	public $unique = false; ///< @c UNIQUE
	public $uniqueName; ///< Unique name of index (autogen if blank)
	public $unsigned = false; ///< @c UNSIGNED for numeric field types
	public $zeroFill = false; ///< @c ZEROFILL for numeric field types
	/*!
	 * Sets the AUTO_INCREMENT flag.
	 *
	 * @return Reference to this object
	 */
	public function &autoIncrement () {
		$this->autoIncrement = true;
		return $this;
	}
	/*!
	 * Sets the default value for this field.
	 *
	 * @param $default Default value to use for this field.
	 * @return Reference to this object
	 */
	public function &def ($default) {
		$this->default = "'{$default}'";
		return $this;
	}
	/*!
	 * Sets a foreign key reference for this field. The default definition with
	 * the minimum arguments defines a foreign key as [table].[column] ON 
	 * @c UPDATE @c SET @c NULL @c ON @c DELETE @c SET @c NULL. Setting the 
	 * @c $onUpdate or @c $onDelete parameters to @c CASCADE, @c RESTRICT, or 
	 * @c SET @c NULL will change this behavior.
	 *
	 * @param $table Table name containing foreign key column
	 * @param $field Column name of field in foreign key table
	 * @param $onUpdate Optional. @c ON @c UPDATE instruction. Default: @c SET 
	 * @c NULL
	 * @param $onDelete Optional. @c ON @c DELETE instruction. Default: @c SET 
	 * @c NULL
	 * @param $name Optional. Foreign key identifier. Default: null (autogen)
	 * @return Reference to this object
	 */
	public function &foreignKey ($table, $field, $onUpdate = 'SET NULL', $onDelete = 'SET NULL', $name = null) {
		$this->foreignKeyName = $name;
		$this->foreignKeyTable = $table;
		$this->foreignKeyField = $field;
		$this->foreignKeyUpdate = $onUpdate;
		$this->foreignKeyDelete = $onDelete;
		return $this;
	}
	/*!
	 * Adds this field to a @c FULLTEXT index. Note that the table engine must
	 * be MyISAM in order to effectively use this feature. This method may be
	 * called multiple times to be included in different @c FULLTEXT indexes. 
	 * When called with no arguments, an index name is automatically generated.
	 * 
	 * @param $group_name Optional. Name of the index. Default: null (autogen)
	 * @return Reference to this object
	 */
	public function &fulltext ($group_name = null) {
		if (!in_array($group_name, $this->fulltext)) {
			$this->fulltext[] = $group_name;
		}
		return $this;
	}
	/*!
	 * Sets this field to have an @c INDEX applied. To create a composite index,
	 * give two fields the same name for their index. Additionally, to give an
	 * index a custom name, simply supply it in the first argument.
	 * 
	 * @param $name Optional. Index name. Default: null (autogen)
	 * @return Reference to this object
	 */
	public function &index ($name = null) {
		$this->index = true;
		$this->indexName = $name;
		return $this;
	}
	/*!
	 * Sets the default value for a @c TIMESTAMP field to @c CURRENT_TIMESTAMP.
	 * This disables the combined automatic date for insert and update by
	 * limiting the field to only setting the date and never updating it when
	 * a row of data is updated.
	 * 
	 * @see #updateOnly()
	 * @return Reference to this object
	 */
	public function &insertOnly () {
		if ($this->type == 'TIMESTAMP') {
			$this->default = 'CURRENT_TIMESTAMP';
		}
		return $this;
	}
	/*!
	 * Disables the automatic prefix on the field name. If a field name prefix
	 * is defined, using @c _prefix, a call to this method will disable setting
	 * the prefix for this field. This method is handy in situations where a 
	 * foreign key is concerned.
	 * 
	 * @return Reference to this object
	 */
	public function &noPrefix () {
		$this->prefix = false;
		return $this;
	}
	/*!
	 * Sets this field as @c NOT @c NULL.
	 * 
	 * @return Reference to this object
	 */
	public function &notNull () {
		$this->null = false;
		return $this;
	}
	/*!
	 * Sets this field as the @c PRIMARY @c KEY. There can only be one primary
	 * key per table.
	 * 
	 * Composite primary keys are not supported at this time.
	 * 
	 * @return Reference to this object
	 */
	public function &primary () {
		$this->primary = true;
		return $this;
	}
	/*!
	 * Sets this field's @c UNIQUE index. Similarly to FDataModelField::index(),
	 * composite unique indexes are possible by using the same name for each
	 * field included in the unique index.
	 * 
	 * @see #index()
	 * @param $name Optional. Index name. Default: null (autogen)
	 */
	public function &unique ($name = null) {
		$this->unique = true;
		$this->uniqueName = $name;
		return $this;
	}
	/*!
	 * Sets this field's @c UNSIGNED flag. Used primarily for numeric fields to
	 * disallow negative numbers.
	 * 
	 * @b Note: There is no check to automatically determine if the field is
	 * numeric when applying this option. Setting a @c VARCHAR field to @c
	 * UNSIGNED may have unforseen consequences.
	 * 
	 * @return Reference to this object
	 */
	public function &unsigned () {
		$this->unsigned = true;
		return $this;
	}
	/*!
	 * Sets the default value of a @c TIMESTAMP field to zero, or no initial
	 * value, and causes the field to update itself with the current timestamp
	 * if the row in which it resides ever changes.
	 * 
	 * Since the default value is zero, this does not mean an initial value
	 * may be provided or a specific value provided upon update to override
	 * this behavior during an @c INSERT or @c UPDATE.
	 * 
	 * @see #insertOnly()
	 * @return Reference to this object
	 */
	public function &updateOnly () {
		if ($this->type == 'TIMESTAMP') {
			$this->default = '0 ON UPDATE CURRENT_TIMESTAMP';
		}
		return $this;
	}
	/*!
	 * Sets the @c ZEROFILL option. Only applicable to numeric fields.
	 * 
	 * @return Reference to this object
	 */
	public function &zeroFill () {
		$this->zeroFill = true;
		return $this;
	}
	/*!
	 * Assembles all options for a field and produces the field definition.
	 * Currently, there is no syntax check in place to think for the programmer.
	 * If an invalid set of options is specified, a SQL error will result when
	 * the table-building process is running.
	 * 
	 * @param $name The field name
	 * @param $prefix Optional. The field prefix. Default: ""
	 * @return The fully assembled field definition SQL
	 */
	public function getDefinition ($name, $prefix = '') {
		$definition = '';
		if ($this->prefix) {
			$definition .= $prefix;
		}
		$definition .= $name;

		$definition .= ' ' . $this->type;
		if ((int)$this->length) {
			$definition .= '(' . (int)$this->length;
			if ((int)$this->precision) {
				$definition .= ', ' . (int)$this->precision;
			}
			$definition .= ')';
		}
		else if ($this->enumValues) {
			$definition .= "('" . implode("','", $this->enumValues) . "')";
		}
		if ($this->unsigned) $definition .= ' UNSIGNED';
		if ($this->zeroFill) $definition .= ' ZEROFILL';
		if (!$this->null) $definition .= ' NOT NULL';
		if ($this->default) $definition .= ' DEFAULT ' . $this->default;
		if ($this->autoIncrement) $definition .= ' AUTO_INCREMENT';
		return $definition;
	}
}
