<?php
/*!
 * Simple email validateion field.
 * 
 * This field provides additional options beyond those specified for
 * FFormField:
 * @li @b error_invalid Error message to display when an invalid email address
 * is supplied. The string supports sprintf formatting where @c %s is replaced
 * by the offending value. The default value is: Invalid E-Mail addresss: %s
 * 
 * @author Jake Tews <jtews@okco.com>
 * @date Thu Apr 28 09:45:59 EDT 2011
 */
class FEmailField extends FFormField {
	/*!
	 * Override FFormField::$filter default to use @c FILTER_VALIDATE_EMAIL
	 * instead.
	 */
	protected $filter = FILTER_VALIDATE_EMAIL;
	/*!
	 * Overrides the default constructor to force this filter to use the
	 * FTextField template instead.
	 */
	public function __construct ($name) {
		if (!isset($this->template)) {
			$this->template = $_ENV['config']['templates.form.field.dir'] . DS . 'FTextField.html.php';
		}
		parent::__construct($name);
	}
	/*!
	 * Adds additional validation to the default FFormField::validate() by
	 * informing the user if the email address they entered is invalid.
	 * 
	 * @see FFormField::validate()
	 * @return Array with tuple as defined by FFormField::validate()
	 */
	public function validate () {
		list ($valid, $value) = parent::validate();
		if (!$valid && $value != $this->getRawValue()) {
			$valid = false;
			$error_message = $this->get('error_invalid', 'Invalid Email address: %s');
			$this->error($value = sprintf($error_message, $this->getRawValue()));
		}
		return array($valid, $value);
	}
}
