<?php
class FObjectViewBuilder {
	private static $viewList = array();
	private $fields;
	private $previewTable;
	private $type;
	private $viewTable;
	public function __construct ($type) {
		if (is_object($type) && $type instanceof FObject) {
			$this->type = get_class($type);
		} else if (is_string($type) && FClassCache::hasParent($type, 'FObject') && FClassCache::hasInterface($type, 'FObjectDatabaseStorage')) {
			$this->type = $type;
		} else {
			throw new InvalidArgumentException('Object must subclass FObject and implement FObjectDatabaseStorage');
		}
		$this->viewTable = 'v_' . $this->type;
		$this->previewTable = 'vp_' . $this->type;
	}
	public static function buildIfExpired ($type) {
		if (self::viewExpired($type)) {
			self::getInstance($type)->buildViews();
		}
	}
	public function getFieldList ($preview = false) {
		$fields = array_merge(
			array(
				'id',
				'parent_id',
				'creator_id',
			),
			$this->getObjectFieldNames(),
			array(
				'_added',
				'_updated'
			)
		);
		$list = '(' . implode(', ', $fields) . ')';
		return $list;
	}
	public function getFromClause ($preview = false) {
		$froms = array(
			"FROM objects AS " . $this->type
		);
		foreach ($this->getObjectFieldNames() as $field) {
			$froms[] = $this->getJoin($field, $preview);
		}
		$from = implode(PHP_EOL, $froms);
		return $from;
	}
	public static function getInstance ($type) {
		return new FObjectViewBuilder($type);
	}
	public static function getLastModified ($type = null) {
		self::getViewList();
		if (isset(self::$viewList[$type])) {
			return self::$viewList[$type]['modified'];
		} else {
			return 0;
		}
	}
	public function getLiveQuery () {
		return $this->getViewQuery(false);
	}
	public function getPreviewQuery () {
		return $this->getViewQuery(true);
	}
	public function getSelectClause ($preview = false) {
		$fields = array(
			$this->type . '.object_id',
			$this->type . '.object_parent_id',
			$this->type . '.object_creator_id',
		);
		$date_fields = array();

		foreach ($this->getObjectFields() as $field => $attributes) {
			if ($preview) {
				$definition = sprintf('COALESCE(ap_%1$s.attribute_value, a_%1$s.attribute_value)', $field);
				$date_fields[] = sprintf('COALESCE(ap_%1$s.attribute_added, a_%1$s.attribute_added)', $field);
			} else {
				$definition = 'a_' . $field . '.attribute_value';
				$date_fields[] = 'a_' . $field . '.attribute_added';
			}
			// Support the MySQL CAST function to cast the BLOBs to a 
			// specific data type. Valid ones here:
			// BINARY[(N)]
			// CHAR[(N)]
			// DATE
			// DATETIME
			// DECIMAL[(M[,D])]
			// SIGNED [INTEGER]
			// TIME
			// UNSIGNED [INTEGER]
			if (isset($attributes['cast'])) {
				$definition = sprintf('CAST(%s AS %s)', $definition, $attributes['cast']);
			}
			$fields[] = $definition;
		}
		$fields[] = $this->type . '.object_added';
		switch (count($date_fields)) {
			case 0:
				$fields[] = 'object_added';
				break;
			case 1:
				$fields[] = $date_fields[0];
				break;
			default:
				array_walk($date_fields, function(&$f) { $f = "IFNULL({$f}, '0000-00-00')"; });
				$fields[] = 'GREATEST(' . implode(', ', $date_fields) . ')';
		}
		$select = 'SELECT ' . implode(', ' . PHP_EOL . '  ', $fields);
		return $select;
	}
	public function getWhereClause ($preview = false) {
		$wheres = array(
			'WHERE',
			'  object_deleted = 0',
			'  AND object_type = \'' . $this->type . '\'',
		);
		$where = implode(PHP_EOL, $wheres);
		return $where;
	}
	public function buildViews () {
		$queries = array(
			$this->getViewQuery(false),
			$this->getViewQuery(true)
		);
		if ($_ENV['config']['fobject.qtables']) {
			$queries = array_merge(array(
					$this->getDropQuery(false),
					$this->getTableQuery(false),
					$this->getDropQuery(true),
					$this->getTableQuery(true),
				),
				$this->getAlterQueries(false),
				$this->getAlterQueries(true)
			);
		}
		foreach ($queries as $query) {
			FDB::query($query);
		}
		$this->updateModified();
	}
	public function viewExists ($preview = false) {
		$result = FDB::query("SHOW TABLES LIKE '%s'", $this->getTable($preview));
		return $result->count() > 0;
	}
	public static function viewExpired ($type) {
		return (!empty(self::$viewList) && !isset(self::$viewList[$type]))
			|| FClassCache::lastModified($type) > self::getLastModified($type);
	}

	private function getAlterQueries ($preview) {
		$table = (($preview) ? 'qp_' : 'q_') . $this->type;
		$queries = array("ALTER TABLE {$table} ADD PRIMARY KEY(id)");
		foreach ($this->getObjectFields() as $field => $options) {
			if (isset($options['cast']) && !in_array($options['cast'][0], array('C', 'B'))) {
				$queries[] = FDB::sql("ALTER TABLE %s ADD INDEX(`%s`)", $table, $field);
			}
		}
		return $queries;
	}
	private function getDropQuery ($preview) {
		$table = (($preview) ? 'qp_' : 'q_') . $this->type;
		return 'DROP TABLE IF EXISTS ' . $table;
	}
	private function getJoin ($field, $preview = false) {
		$joins = array();
		$joinf = implode(PHP_EOL, array(
			'  LEFT JOIN attributes AS %1$s ON (',
			'    %1$s.object_id = %2$s.object_id',
			'    AND %1$s.attribute_key = \'%3$s\'',
			'    AND %1$s.attribute_archived = %4$d',
			'    AND %1$s.attribute_preview = %5$d',
			'  )',
		));
		$joins[] = sprintf($joinf,
			'a_' . $field, $this->type, $field, 0, 0
		);
		if ($preview) {
			$joins[] = sprintf($joinf,
				'ap_' . $field, $this->type, $field, 0, 1
			);
		}
		return implode(PHP_EOL, $joins);
	}
	private function getObjectFieldNames () {
		return array_keys($this->getObjectFields());
	}
	private function getObjectFields () {
		if (!$this->fields) {
			// Sneaky fucker trick to preload FObjectDatabaseStorage:
			interface_exists('FObjectDatabaseStorage');
			$this->fields = FObjectDatabaseStorageDriver::getStorageFields($this->type);
		}
		return $this->fields;
	}
	private function getTable ($preview) {
		return ($preview) ? $this->previewTable : $this->viewTable;
	}
	private function getTableQuery ($preview) {
		$table = (($preview) ? 'qp_' : 'q_') . $this->type;
		$sql = implode(PHP_EOL, array(
			'CREATE TABLE ' . $table . '',
			'ENGINE=MyISAM',
			'SELECT * FROM ' . $this->getTable($preview)
		));
		return $sql;
	}
	private static function getViewList () {
		if (self::$viewList) {
			return self::$viewList;
		}
		if (file_exists($_ENV['config']['cache.object_view_list'])) {
			include($_ENV['config']['cache.object_view_list']);
			self::$viewList = $view_list;
		} else {
			self::$viewList = array();
		}
		return self::$viewList;
	}
	private function getViewQuery ($preview) {
		$view_name = $this->getTable($preview);
		$sql = implode(PHP_EOL, array(
			"CREATE OR REPLACE VIEW {$view_name}",
			$this->getFieldList($preview),
			"AS",
			$this->getSelectClause($preview),
			$this->getFromClause($preview),
			$this->getWhereClause($preview)
		));
		return $sql;
	}
	private function updateModified () {
		self::$viewList[$this->type]['modified'] = time();
		$this->updateViewList();
	}
	private function updateViewList () {
		file_put_contents(
			$_ENV['config']['cache.object_view_list'],
			'<?php $view_list = ' . var_export(self::$viewList, true) . ';'
		);
		@chmod($_ENV['config']['cache.object_view_list'], 0666);
	}
}

