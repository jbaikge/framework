<?php
class FObjectQueryFrom {
	private $attributes = array();
	private $preview_enabled = false;
	private $type = null;
	public function __construct ($type) {
		$this->type = $type;
	}
	public function __call ($method, $args) {
		return;
	}
	public function add ($field, $args) {
		$this->attributes[$field] = array(
			'preview'  => $this->preview_enabled,
			'archived' => 0
		);
	}
	public function preview ($on = true) {
		$this->preview_enabled = $on;
		foreach ($this->attributes as &$attribute) {
			$attribute['preview'] = $on;
		}
	}
	public function toString () {
		$from = "  objects AS " . $this->type . PHP_EOL;
		foreach (array_keys($this->attributes) as $key) {
			$from .= $this->generateJoin($key);
		}
		return $from;
	}
	private function generateJoin ($key) {
		$joinf = implode(PHP_EOL, array(
			"  LEFT JOIN object_attributes AS %s ON (",
			"    %s.object_id = %s.object_id",
			"    AND %s.attribute_key = '%s'",
			"    AND %s.attribute_archived = %d",
			"    AND %s.attribute_preview = %d",
			"  )",
			null
		));
		$join = sprintf($joinf,
			$key,
			$key, $this->type,
			$key, $key,
			$key, $this->attributes[$key]['archived'],
			$key, 0
		);
		if ($this->attributes[$key]['preview']) {
			$table = $key . '_p';
			$join .= sprintf($joinf,
				$table,
				$table, $this->type,
				$table, $key,
				$table, $this->attributes[$key]['archived'],
				$table, 1
			);
		}
		return $join;
	}
}
