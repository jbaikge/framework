<?php
/**
 * @author Jacob Tews <jacob@webteks.com>
 * @date Sat Feb 23 19:01:21 EST 2008
 * @version $Id$
 */
class FForm {
	protected $fields;
	protected $id; ///< Form ID - Unique identifier for the form
	public function __construct ($id) {
		$this->id = $id;
	}
}
