<?php
class FDataModelField {
	public $autoIncrement = false;
	public $default;
	public $enumValues;
	public $foreignKeyDelete;
	public $foreignKeyField;
	public $foreignKeyName;
	public $foreignKeyTable;
	public $foreignKeyUpdate;
	public $fulltext = array();
	public $index = false;
	public $indexName;
	public $length;
	public $null = true;
	public $precision;
	public $prefix = true;
	public $primary = false;
	public $type;
	public $unique = false;
	public $uniqueName;
	public $unsigned = false;
	public $zeroFill = false;

	public function &autoIncrement () {
		$this->autoIncrement = true;
		return $this;
	}
	public function &def ($default) {
		$this->default = "'{$default}'";
		return $this;
	}
	public function &foreignKey ($table, $field, $onUpdate = 'SET NULL', $onDelete = 'SET NULL', $name = null) {
		$this->foreignKeyName = $name;
		$this->foreignKeyTable = $table;
		$this->foreignKeyField = $field;
		$this->foreignKeyUpdate = $onUpdate;
		$this->foreignKeyDelete = $onDelete;
		return $this;
	}
	public function &fulltext ($group_name = null) {
		if (!in_array($group_name, $this->fulltext)) {
			$this->fulltext[] = $group_name;
		}
		return $this;
	}
	public function &index ($name = null) {
		$this->index = true;
		$this->indexName = $name;
		return $this;
	}
	public function &insertOnly () {
		if ($this->type == 'TIMESTAMP') {
			$this->default = 'CURRENT_TIMESTAMP';
		}
		return $this;
	}
	public function &noPrefix () {
		$this->prefix = false;
		return $this;
	}
	public function &notNull () {
		$this->null = false;
		return $this;
	}
	public function &primary () {
		$this->primary = true;
		return $this;
	}
	public function &unique ($name = null) {
		$this->unique = true;
		$this->uniqueName = $name;
		return $this;
	}
	public function &unsigned () {
		$this->unsigned = true;
		return $this;
	}
	public function &updateOnly () {
		if ($this->type == 'TIMESTAMP') {
			$this->default = '0 ON UPDATE CURRENT_TIMESTAMP';
		}
		return $this;
	}
	public function &zeroFill () {
		$this->zeroFill = true;
	}
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
