<?php
/*!
 * Simple date validateion field.
 * 
 * This field provides additional options beyond those specified for
 * FFormField:
 * @li @b error_invalid Error message to display when an invalid date
 * is supplied. The string supports sprintf formatting where @c %s is replaced
 * by the offending value. The default value is: Invalid date: %s
 * 
 * @author Jake Tews <jtews@okco.com>
 * @date Tue Apr 17 17:16:26 EDT 2012
 */
class FDateField extends FFormField {
	/*!
	 * Override FFormField::$filter default to use @c FILTER_CALLBACK
	 * instead.
	 */
	protected $filter = FILTER_CALLBACK;
	/*!
	 * Overrides the default constructor to force this filter to use the
	 * FTextField template instead.
	 */
	public function __construct ($name) {
		$this->flags(array('options' => 'strtotime'));
		parent::__construct($name);
	}
	/*!
	 * Adds additional validation to the default FFormField::validate() by
	 * informing the user if the date they entered is invalid.
	 * 
	 * @see FFormField::validate()
	 * @return Array with tuple as defined by FFormField::validate()
	 */
	public function validate () {
		list($valid, $value) = parent::validate();
		if (!$valid) {
			$error_message = $this->get('error_invalid', 'Invalid date: %s');
			$this->error($value = sprintf($error_message, $this->getRawValue()));
		} else {
			$value = FString::date($value, $this->get('format', FString::DATE_MYSQL));
		}
		return array($valid, $value);
	}
}
