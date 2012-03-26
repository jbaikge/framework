<?php
class FObjectQueryOrder {
	private $fields = array();
	public function __call ($method, $args) {
		return;
	}
	public function _orderBy ($field, $direction = 'ASC') {
		if ($direction != 'DESC') {
			$direction = 'ASC';
		}
		if (isset($this->fields[$field])) {
			unset($this->fields[$field]);
		}
		$this->fields[$field] = array($field, $direction);
	}
	public function hasClauses () {
		return (bool)$this->fields;
	}
	public function __toString () {
		$orders = array();
		reset($this->fields);
		while (list($field, $direction) = current($this->fields)) {
			$orders[] = $field . ' ' . $direction;
			next($this->fields);
		}
		$order_by = implode(', ', $orders);
		return $order_by;
	}
}