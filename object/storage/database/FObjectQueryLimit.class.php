<?php
class FObjectQueryLimit {
	private $limit = null;
	public function __call ($method, $args) {
		return;
	}
	public function _limit ($limit) {
		$this->limit = intval($limit);
	}
	public function hasClauses () {
		return $this->limit !== null;
	}
	public function __toString () {
		return sprintf("%d", $this->limit);
	}
}