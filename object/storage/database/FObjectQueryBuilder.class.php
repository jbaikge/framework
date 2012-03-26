<?php
class FObjectQueryBuilder implements IteratorAggregate, Countable {
	private $query_components;
	private $preview;
	private $resultCache;
	private $type;

	public function __construct ($type, $preview = null) {
		FObjectViewBuilder::buildIfExpired($type);
		$this->type = $type;
		if ($preview === null) {
			$this->preview = preview_mode();
		} else {
			$this->preview = $preview;
		}
		$this->query_components = array(
			'from'  => new FObjectQueryFrom($type, $this->preview),
			'where' => new FObjectQueryWhere($type, $this->preview),
			'order' => new FObjectQueryOrder($type, $this->preview),
			'limit' => new FObjectQueryLimit($type, $this->preview)
		);
	}

	public function &__call ($method, $args) {
		// Determine if the $method is actually a special field and not a
		// special function.
		$special_field = false;
		$special_field |= FString::startsWith($method, '_added');
		$special_field |= FString::startsWith($method, '_updated');

		// Handle special methods (prefixed with _)
		if ($method[0] == '_' && !$special_field) {
			foreach ($this->query_components as &$component) {
				call_user_func_array(array($component, $method), $args);
			}
		} else {
			if (strpos($method, '__') !== false) {
				list($field, $operator) = explode('__', $method, 2);
			} else {
				$field = $method;
				$operator = 'eq';
			}
			$args = array_flatten($args);
			foreach ($this->query_components as &$component) {
				$component->add($field, $operator, $args);
			}
		}
		$this->resultCache = null;
		return $this;
	}

	public function __toString () {
		$clauses = array(
			"SELECT *",
			"FROM",
			$this->query_components['from'],
		);
		if ($this->query_components['where']->hasClauses()) {
			$clauses[] = "WHERE";
			$clauses[] = $this->query_components['where'];
		}
		if ($this->query_components['order']->hasClauses()) {
			$clauses[] = "ORDER BY";
			$clauses[] = $this->query_components['order'];
		}
		if ($this->query_components['limit']->hasClauses()) {
			$clauses[] = "LIMIT";
			$clauses[] = $this->query_components['limit'];
		}
		$sql = implode(PHP_EOL, $clauses);
		return $sql;
	}

	public function count() {
		return count($this->getResults());
	}

	public function getIterator () {
		return $this->getResults();
	}

	public function getResults () {
		if ($this->resultCache == null) {
			$this->resultCache = FDB::query(str_replace('%', '%%', $this))->asClass($this->type);
		}
		return $this->resultCache;
	}

	public function getObject () {
		return (count($this) > 0) ? $this->getResults()->fetch() : false;
	}

	public function getObjects () {
		return iterator_to_array($this);
	}
}
