<?php
/**
 * @author Jacob Tews <jacob@webteks.com>
 * @date Mon Apr 14 17:42:06 EDT 2008
 * @version $Id$
 */
class FFieldText extends FField {
	public function error () {
		if ($this->required() && trim($this->getText()) == '') {
			return $this->label . ' must have a value.';
		} else {
			return false;
		}
	}
	public function getDefault () {
		if ($this->node->hasAttribute('value')) {
			return $this->node->getAttribute('value');
		} else {
			return '';
		}
	}
	public function getText () {
		$value = @$_{$_SERVER['REQUEST_METHOD']}[$this->getName()];
		if (get_magic_quotes_gpc()) {
			$value = stripslashes($value);
		}
		return $value;
	}
	public function getType () {
		return 'text';
	}
	public function valid () {
		return $this->error() === false;
	}
}
