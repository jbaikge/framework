<?php
/*!
 * @author Jake Tews <jtews@okco.com>
 * @author Mark Litcfhield <mlitchfield@okco.com>
 * @date
 * @version $Id$
 */
class FRadioGroup extends FFormField {
	public function __construct ($name) {
		$this->error_required('Selection required');
		parent::__construct($name);
	}
}
