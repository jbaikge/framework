<?php
/*!
 * @author Jake Tews <jtews@okco.com>
 * @date Mon Feb 25 16:25:34 EST 2013
 * @version $Id$
 */
class FMultiSelectField extends FFormField {
	public function __construct ($name) {
		$this->flags(FILTER_FORCE_ARRAY);
		$this->error_required('Selection required');
		parent::__construct($name);
	}
}
