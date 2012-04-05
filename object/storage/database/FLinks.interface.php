<?php

interface FLinks {}

class FLinksDriver extends FObjectDriver {
	public static function linkIds($origin_id, $target_id) {
		FDB::query("REPLACE INTO object_links SET origin_id = %d, target_id = %d", $origin_id, $target_id);
	}

	public static function unlinkIds($origin_id, $target_id) {
		FDB::query("DELETE FROM object_links WHERE origin_id = %d AND target_id = %d LIMIT 1", $origin_id, $target_id);
	}

	public static function isLinked($origin_id, $target_id) {
		return count(FDB::query("SELECT null FROM object_links WHERE origin_id = %d AND target_id = %d", $origin_id, $target_id)) > 0;
	}

	public function singleLinkWith(FObject $object) {
		self::linkIds($this->subject->id, $object->id);
	}

	public function singleUnlinkWith(FObject $object) {
		self::unlinkIds($this->subject->id, $object->id);
	}

	public function linkWith(FObject $object) {
		self::linkIds($this->subject->id, $object->id);
		self::linkIds($object->id, $this->subject->id);
	}

	public function unlinkWith(FObject $object) {
		self::unlinkIds($this->subject->id, $object->id);
		self::unlinkIds($object->id, $this->subject->id);
	}

	public function linkWithID($id) {
		self::linkIds($this->subject->id, $id);
		self::linkIds($id, $this->subject->id);
	}

	public function unlinkWithID($id) {
		self::unlinkIds($this->subject->id, $id);
		self::unlinkIds($id, $this->subject->id);
	}

	public function selectLinks($type) {
		$result = FDB::query("SELECT target_id FROM object_links WHERE origin_id = %d", $this->subject->id);
		if (method_exists($result, 'fetch_all')) {
			$ids = $result->fetch_all();
		} else {
			$ids = $result->asRow();
		}
		return FObjectQuery::select($type)->id__in($ids);
	}

	public function getLinkedTypes() {
		return FDB::query("SELECT DISTINCT object_type
			FROM objects
				LEFT JOIN object_links ON(object_id = target_id)
			WHERE origin_id = %d
				AND object_deleted = 0
			ORDER BY object_type ASC", $this->subject->id);
	}

	public function getLinks($type) {
		return self::selectLinks($type)->getObjects();
	}

	public function getLinkCount($type) {
		return count(FDB::query("SELECT NULL
			FROM objects
				LEFT JOIN object_links ON(object_id = target_id)
			WHERE origin_id = %d
				AND object_deleted = 0
				AND object_type = '%s'
			",
			$this->subject->id,
			$type
		));
	}
}
