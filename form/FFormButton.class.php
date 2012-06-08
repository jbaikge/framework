<?php
/*!
 * FForm Button builder
 * 
 * @author Jake Tews <jtews@300brand.com>
 * @date Fri Jun  8 15:57:04 EDT 2012
 */
class FFormButton {
	protected $attributes = array(
		'type'  => 'submit',
		'value' => 'Submit',
	);
	protected static $valid_attributes = array(
		'accesskey',
		'autofocus',
		'class',
		'contenteditable',
		'contextmenu',
		'dir',
		'disabled',
		'draggable',
		'dropzone',
		'form',
		'formaction',
		'formenctype',
		'formmethod',
		'formnovalidate',
		'formtarget',
		'hidden',
		'id',
		'lang',
		'name',
		'spellcheck',
		'style',
		'tabindex',
		'title',
		'type',
		'value',
	);
	public function __construct($value) {
		$this->attributes['value'] = $value;
	}
	public function __call($method, $args) {
		if (in_array($method, self::$valid_attributes)) {
			$this->attributes[$method] = $args[0];
		}
		return $this;
	}
	public function __toString() {
		$tag = '<input ';
		foreach ($this->attributes as $k => $v) {
			$tag .= sprintf(' %s="%s"', $k, htmlize($v));
		}
		$tag .= '>';
		return $tag;
	}
	public static function make($value) {
		return new self($value);
	}
}
