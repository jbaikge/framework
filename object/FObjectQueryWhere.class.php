<?php
class FObjectQueryWhere {
	private static $operator_map = array(
		'between' => array('BETWEEN', 2, ' AND '),
		'eq'      => array('=', 1, null),
		'gt'      => array('>', 1, null),
		'gte'     => array('>=', 1, null),
		'in'      => array('IN', PHP_INT_MAX, ', '),
		'like'    => array('LIKE', 1, null),
		'lt'      => array('<', 1, null),
		'lte'     => array('<=', 1, null),
		'ne'      => array('!=', 1, null),
		'regexp'  => array('REGEXP', 1, null),
	);
	private $preview = false;
	private $type;
	private $where_tail;
	private $where_structure = array();
	private $where_stack = array();

	public function __construct ($type) {
		$this->type = $type;
		$this->where_tail =& $this->where_structure;
		$this->add($type . '.object_type', 'eq', array($type));
		$this->add($type . '.object_deleted', 'eq', array(0));
	}

	public function __call ($method, $args) {
		return;
	}

	public function add ($field, $operator = 'eq', $args) {
		if (in_array($field, array('and', 'or'))) {
			$this->addGlue($field, $operator);
		} else {
			$this->autoGlue();
			if (!FString::startsWith($field, $this->type . '.')) {
				$field .= '.attribute_value';
			}
			$this->addClause($field, $operator, $args);
		}
	}

	public function toString () {
		return $this->compressClauses($this->where_structure);
	}

	public function _startGroup () {
		$this->autoGlue();
		$this->where_stack[] =& $this->where_tail;
		$this->where_tail[] = array();
		end($this->where_tail);
		$this->where_tail =& $this->where_tail[key($this->where_tail)];
	}

	public function _endGroup () {
		end($this->where_stack);
		$this->where_tail =& $this->where_stack[key($this->where_stack)];		
		array_pop($this->where_stack);
	}

	private function addClause ($field, $operator, $args) {
		list($real_operator, $max_args, $arg_glue) = self::$operator_map[$operator];
		if (count($args) > $max_args) {
			throw new FObjectQueryMaxArgsException(count($args) . ' given, expecting ' . $max_args . ' for operator: ' . $real_operator);
		}
		$clause_prefix = $field . ' ' . $real_operator;
		if ($max_args == 1) {
			 $clause = FDB::sql("{$clause_prefix} '%s'", $args[0]);
		} else {
			$escaped_values = array();
			foreach ($args as $arg) {
				$escaped_values[] = FDB::sql("'%s'", $arg);
			}
			$value = implode($arg_glue, $escaped_values);
			if ($real_operator == 'IN') {
				$value = '(' . $value . ')';
			}
			$clause = $clause_prefix .' ' . $value;
		}
		$this->where_tail[] = $clause;
	}

	private function addGlue ($type, $operator) {
		$this->where_tail[] = strtoupper($type);
	}

	private function autoGlue () {
		$end = end($this->where_tail);
		if (!in_array($end, array('AND', 'OR', array()))) {
			$this->where_tail[] = 'AND';
		}
	}

	private function &last (&$array) {
		if (!is_array($array)) {
			return null;
		}
		if (!$array) {
			return null;
		}
		end($array);
		return $array[key($array)];
	}

	private function compressClauses (array $clauses, $depth = 1) {
		$where = '';
		$padding = str_repeat('  ', $depth);
		foreach ($clauses as $clause) {
			if (is_array($clause)) {
				$where .= $padding . '(' . PHP_EOL
					. $this->compressClauses($clause, $depth + 1)
					. $padding . ')' . PHP_EOL;
			} else {
				$where .= $padding . $clause . PHP_EOL;
			}
		}
		return $where;
	}
}

class FObjectQueryMaxArgsException extends Exception {}
