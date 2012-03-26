<?php
interface FObjectStorage {}

abstract class FObjectStorageDriver extends FObjectDriver implements FObjectPopulateHooks, FObjectUpdateHooks, FObjectDeleteHooks {
	protected $creator;
	protected $previewMode = false;

	public function getCreatorID () {
		if (is_object($this->creator) && $this->creator instanceof FObject) {
			return $this->creator->id;
		} else if (is_scalar($this->creator) && intval($this->creator)) {
			return (int)$this->creator;
		} else {
			return $this->subject->creator_id;
		}
	}
	public function getPreviewMode () {
		return $this->previewMode;
	}
	public static function getStorageFields ($type) {
		$fields = $type::getModel()->storage();
		foreach ($fields as $field => $info) {
			if (isset($info['ignore']) && $info['ignore']) {
				unset($fields[$field]);
			}
		}
		return $fields;
	}
	public function hasCreator () {
		return isset($this->creator);
	}
	public function setCreator ($creator) {
		$this->creator = $creator;
	}
	public function setPreviewMode ($on) {
		$this->previewMode = (bool)$on;
	}
}
