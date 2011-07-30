<?php
class FObjectQueryBuilder {
	private $from;
	private $preview = false;
	private $type;
	private $where;

	public function __construct ($type, $preview = false) {
		$this->type = $type;
		$this->where = new FObjectQueryWhere($type);
		$this->from = new FObjectQueryFrom($type);
	}

	public function &__call ($method, $args) {
		// Handle special methods (prefixed with _)
		if (strpos($method, '_') === 0) {
			call_user_func_array(array($this->from, $method), $args);
			call_user_func_array(array($this->where, $method), $args);
		} else {
			if (strpos($method, '__') !== false) {
				list($field, $operator) = explode('__', $method, 2);
			} else {
				$field = $method;
				$operator = 'eq';
			}
			$args = array_flatten($args);
			$this->from->add($field, $args);
			$this->where->add($field, $operator, $args);
		}
		return $this;
	}
}
