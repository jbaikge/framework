<?php
/*!
 * @author Jake Tews <jtews@okco.com>
 * @author Mark Litcfhield <mlitchfield@okco.com>
 * @date
 * @version $Id$
 */
class FCheckboxGroup extends FFormField {
	public function __construct ($name) {
		$this->flags(FILTER_FORCE_ARRAY);
		$this->error_required('Selection required');
		parent::__construct($name);
	}
}
