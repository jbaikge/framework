<?php
/*!
 * Interface for denoting a class which will define and process form data. Any
 * class implementing this interface instantly has access to all methods
 * available in FFormProcessableDriver.
 *
 * @author Jake Tews <jtews@okco.com>
 * @author Jeff Wendling <jwendling@okco.com>
 * @date Wed Apr 20 15:00:00 EDT 2011
 * @version $Id$
 */
interface FFormProcessable extends FObjectInterface {}

/*!
 * Form processable driver. This class's features are automatically pushed upon
 * any class implementing FFormProcessable.
 * 
 * @author Jake Tews <jtews@okco.com>
 * @author Jeff Wendling <jwendling@okco.com>
 * @date Wed Apr 20 15:00:00 EDT 2011
 * @version $Id$
 */
class FFormProcessableDriver extends FObjectDriver {
	/*!
	 * Generates an array of form field objects. This method is used
	 * primarily inside of FForm to build the fields included in a form
	 * based on the model definition in the primary class. Any options
	 * defined in a field's @b global and @b form context will be applied
	 * to each field returned by this method. This method does not have any
	 * use if called directly as all fields will be blank at all times. 
	 * 
	 * @see FField
	 * @return Array of FField subclassed objects with their options applied.
	 */
	public function makeFields() {
		$fields = array();
		$form_model = $this->subject->getModel()->form();
		foreach ($form_model as $name => $options) {
			if (isset($options['ignore']) && $options['ignore'] == true) {
				continue;
			}
			$type = 'FTextField';
			if (array_key_exists('type', $options)) {
				$type = $options['type'];
				unset($options['type']);
			}
			$field = FFormFieldFactory::make($type, $name);
			$field->setAttributes($options);
			$fields[$field->get('name')] = $field;
		}
		return $fields;
	}
}
