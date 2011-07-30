<?php
/*!
 * Very simple select field implementation. Built upon FFormField, the
 * template will provide a select list with an option selected when the
 * posted value matches the @b value or the first value in the provided
 * options array.
 * 
 * @author Jake Tews <jtews@okco.com>
 * @author Mark Litchfield <mlitchfield@okco.com>
 * @date
 * @version $Id$
 */
class FSelectField extends FFormField {
	public function validate () {
		list($valid, $value) = parent::validate();
		if($valid && !array_key_exists($value, $this->get('options'))) {
			$valid = false;
			$this->error($value = $this->get('option_invalid', 'Invalid option selected')); 
		}
		return array($valid, $value);
	}
}
