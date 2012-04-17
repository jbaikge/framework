<?php
interface FObjectDatabaseStorage extends FObjectStorage {}

class FObjectDatabaseStorageDriver extends FObjectStorageDriver {
	protected $id;
	protected $new;

	public static function getStorageFields ($type) {
		return array_filter($type::getModel()->storage_database(), function($item) {
			return !(isset($item['ignore']) && $item['ignore']);
		});
	}

	public function preDelete (&$data) {
		FDB::query("START TRANSACTION");
	}
	/*!
	 * Recursively deletes an object with it's children.
	 */
	public function doDelete (&$data) {
		$ids = array(
			array(get_class($this->subject), $this->subject->id)
		);
		while (!empty($ids)) {
			list($type, $id) = array_pop($ids);
			$children = FDB::query("
				SELECT object_type, object_id
				FROM objects
				WHERE object_parent_id = %d
					AND object_deleted = 0
			", $id)->asRow();
			if ($children->count() > 0) {
				$ids = array_merge(iterator_to_array($children), $ids);
			}
			FDB::query("UPDATE objects SET object_deleted = 1 WHERE object_id = %d", $id);
			if (FDB::query("SHOW TABLES LIKE 'q_%s'", $type)->count() > 0) {
				FDB::query("DELETE FROM q_%s WHERE id = %d", $type, $id);
				FDB::query("DELETE FROM qp_%s WHERE id = %d", $type, $id);
			}
		}
	}
	public function postDelete (&$data) {
		FDB::query("COMMIT");
	}
	public function failDelete ($exception) {
		FDB::query("ROLLBACK");
	}

	public function prePopulate (&$data) {
		if (!is_array($data)) {
			$data = array();
		}
	}
	public function doPopulate (&$data) {
		if ($this->subject->getPreviewMode()) {
			$row = FObjectQuery::select(get_class($this->subject))
				->id($this->subject->initialized_id)
				->getResults()
				->asAssoc()
				->fetch();
		} else {
			$cache = FDB::query(
				"SELECT cache FROM object_caches WHERE object_id = %d",
				$this->subject->initialized_id
			)->asRow()->fetch();
			$decoded = json_decode($cache[0], true);
			if ($decoded != null && json_last_error() == JSON_ERROR_NONE) {
				$row = $decoded;
			} else {
				$row = $this->fetchDirect($this->subject->initialized_id);
			}
		}
		if (!is_array($row)) {
			return false;
		}
		$data = array_merge(
			$data,
			$row
		);
	}
	public function postPopulate (&$data) {
		// NOOP
	}
	public function failPopulate ($exception) {
		trigger_error("There was an error retrieving data: " . $exception->getMessage(), E_USER_WARNING);
	}

	public function preUpdate (&$data) {
		FDB::query("START TRANSACTION");
		if (isset($this->subject->id)) {
			$this->new = false;
		} else {
			FDB::query("INSERT INTO objects SET object_type = '%s'",
				get_class($this->subject)
			);
			$this->subject->id = FDB::insertId();
			$this->new = true;
		}
		$this->id = $this->subject->id;
	}
	public function doUpdate (&$data) {
		$fields = self::getStorageFields(get_class($this->subject));
		if ($this->new) {
			$field_list = array_keys($fields);
		} else {
			$changed_keys = array_keys($this->subject->getChanges());
			$field_list = array_intersect($changed_keys, array_keys($fields));
			if (!$this->getPreviewMode() && !empty($field_list)) {
				$this->archiveFields($field_list);
			}
		}
		$this->clearPreviewData();
		$this->setLinks();
		if (!empty($field_list)) {
			$this->insert($field_list);
		}
		$this->updateCacheTable(true);
		if (!$this->getPreviewMode()) {
			$this->setCache($this->id);
			$this->updateCacheTable(false);
		}
	}
	public function postUpdate (&$data) {
		FDB::query("COMMIT");
			$this->subject->resetChanges();
	}
	public function failUpdate ($exception) {
		FDB::query("ROLLBACK");
		if ($this->new) {
			unset($this->subject->id);
		}
		throw new FObjectDatabaseStorageException("There was a problem saving data.", 0, $exception);
	}
	/*!
	 * @param $id ID of object to initialize
	 * @param $valid_type Either: A single valid class name, or: An array of
	 * valid class names
	 * @param $use_id Use the ID to init the object instead of the cache
	 * @return null if invalid ID or no object found, object of appropriate type
	 * otherwise
	 */
	public static function fromID($id, $valid_type = null, $use_id = false) {
		if ($id == 0 || $id == null) {
			return null;
		}
		$result = FDB::query("SELECT object_type, cache
			FROM objects
				LEFT JOIN object_caches USING(object_id)
			WHERE objects.object_id = %d",
			$id
		);
		if (count($result) == 0) {
			return null;
		} else {
			list($type, $cache) = $result->asRow()->fetch();
			if (!empty($valid_types) && ($valid_type != $type || !in_array($type, $valid_type))) {
				throw new InvalidArgumentException("Object for {$id} is not a valid type: " . var_export($valid_type, true));
			}
			if ($use_id) {
				return new $type($id);
			} else {
				return new $type($cache);
			}
		}
	}

	protected function archiveFields(array $field_list) {
		$in_list = implode(', ', $this->quoteArray($field_list));
		FDB::query("
			UPDATE attributes
			SET attribute_archived = 1
			WHERE attribute_key IN({$in_list})
				AND object_id = %d
			",
			$this->id
		);
	}
	protected function clearPreviewData () {
		FDB::query("
			DELETE FROM attributes
			WHERE object_id = %d
				AND attribute_preview = 1
			",
			$this->id
		);
	}
	protected function fetchDirect($id) {
		$data = FDB::query("SELECT
				object_id AS id,
				object_parent_id AS parent_id,
				object_creator_id AS creator_id,
				object_added AS _added,
				'0000-00-00 00:00:00' AS _updated
			FROM objects
			WHERE object_id = %d LIMIT 1
			",
			$id
		)->asAssoc()->fetch();
		if (!is_array($data)) {
			return null;
		}

		$attributes = FDB::query("SELECT
				attribute_key,
				attribute_value,
				attribute_added
			FROM attributes
			WHERE object_id = %d
				AND attribute_archived = 0
				AND attribute_preview = 0
			",
			$id
		)->asRow();
		foreach ($attributes as $attribute) {
			list($k, $v, $added) = $attribute;
			$data[$k] = $v;
			$data['_updated'] = max($data['_updated'], $added);
		}

		FDB::query("REPLACE INTO object_caches SET object_id = %d, cache = '%s'",
			$id,
			json_encode($data)
		);
		return $data;
	}
	protected function insert (array $field_list) {
		$columns = array(
			'object_id' => "%d",
			'attribute_key' => "'%s'",
			'attribute_value' => "'%s'",
			'attribute_preview' => "'%d'"
		);
		if ($this->hasCreator()) {
			$columns['attribute_creator_id'] = "'%d'";
		}

		$creator_id = $this->getCreatorID();
		$database_attributes = self::getStorageFields(get_class($this->subject));
		$values = array();
		foreach ($field_list as $field) {
			$data = array(
				$this->id, // object_id
				$field, // key
				$this->subject->$field, // value
				$this->getPreviewMode() // preview
			);
			if ($this->hasCreator()) {
				$data[] = $creator_id; // creator_id
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
			if (isset($database_attributes[$field]['cast'])) {
				$col_copy = $columns;
				$col_copy['attribute_value'] = sprintf(
					"CAST('%%s' AS %s)",
					$database_attributes[$field]['cast']
				);
				$columnf = "(" . implode(', ', $col_copy) . ")";
			} else {
				$columnf = "(" . implode(', ', $columns) . ")";
			}
			$values[] = FDB::sql($columnf, $data);
		}
		$sql = "INSERT INTO attributes ("
			. implode(', ', array_keys($columns))
			. ") VALUES "
			. implode(', ', $values);
		FDB::query(str_replace('%', '%%', $sql));
	}
	protected function quoteArray (array $arr) {
		foreach ($arr as &$str) {
			$str = $this->quoteString($str);
		}
		return $arr;
	}
	protected function quoteString ($str) {
		return FDB::sql("'%s'", $str);
	}
	protected function setCache ($id) {
		$cache = self::getStorageFields(get_class($this->subject));
		foreach ($cache as $field => &$value) {
			$value = $this->subject->$field;
		}
		$cache['id'] = $id;
		$cache['parent_id'] = $this->subject->parent_id;
		$cache['creator_id'] = $this->subject->getCreatorID();
		if (isset($this->subject->_added)) {
			$cache['_added'] = $this->subject->_added;
		} else {
			$cache['_added'] = date(FString::DATE_MYSQL);
		}
		$cache['_updated'] = date(FString::DATE_MYSQL);
		FDB::query(
			"REPLACE INTO object_caches SET cache = '%s', object_id = %d",
			json_encode($cache),
			$this->id
		);
	}
	protected function setLinks () {
		// Database column => potential field names on the object
		$links = array(
			'object_creator_id' => $this->getCreatorID(),
			'object_parent_id' => $this->subject->parent_id,
		);
		$sets = array();
		// Check with isset so we don't accidentally null out a value when
		// it doesn't exist.
		foreach ($links as $column => $value) {
			// Don't need FDB::sql()'s escaping for ints and nulls. Just using
			// sprintf to save memory and ms.
			if ($value === null) {
				$sets[] = sprintf("%s = NULL", $column);
			} else {
				$sets[] = sprintf("%s = %d", $column, $value);
			}
		}
		if (!empty($sets)) {
			FDB::query(
				"UPDATE objects SET " . implode(', ', $sets) . " WHERE object_id = %d",
				$this->id
			);
		}
	}
	protected function updateCacheTable ($preview) {
		if (!$_ENV['config']['fobject.qtables']) {
			return;
		}
		$type = get_class($this->subject);
		// Don't want to try to shove data in tables that don't [yet] exist.
		if (FDB::query("SHOW TABLES LIKE 'q_%s'", $type)->count() == 0) {
			return;
		}
		$field_names = array_keys(self::getStorageFields($type));
		$names = implode(', ', array_merge($field_names, array('_updated', 'id', 'parent_id', 'creator_id')));
		$value_arr = array();
		foreach ($field_names as $field) {
			$value_arr[] = FDB::sql("'%s'", $this->subject->$field);
		}
		$value_arr[] = 'NOW()';
		$value_arr[] = intval($this->id);
		$value_arr[] = intval($this->subject->parent_id) > 0 ? intval($this->subject->parent_id) : "NULL";
		$value_arr[] = intval($this->subject->creator_id) > 0 ? intval($this->subject->creator_id) : "NULL";
		$values = implode(', ', $value_arr);
		$table = (($preview) ? 'qp_' : 'q_') . $type;
		FDB::query("INSERT IGNORE INTO {$table} (id, _added) VALUES (%d, NOW())", $this->id);
		$a = FDB::query("REPLACE INTO {$table} ({$names}) VALUES ({$values})");
	}
}

class FObjectDatabaseStorageException extends Exception {}
