<?php
/*!
 * Simple phone validateion field.
 * 
 * This field provides additional options beyond those specified for
 * FFormField:
 * 
 * @li @b format Format in which to provide phone numbers. The value must be an
 * array with the first index representing an area code, the second index 
 * representing a phone number in sprintf format and the third index
 * representing what to do with an extension in sprintf format. If an extension
 * is detected, the third index is used and juxtaposed to the value of the first
 * with no spaces.
 * The default value is: array('%03d.', '%03d.%04d', ' x%s')
 * 
 * @li @b error_invalid Error message to display when an invalid email address
 * is supplied. The string supports sprintf formatting where @c %s is replaced
 * by the offending value. The default value is: Invalid phone number: %s
 * 
 * @author Jake Tews <jtews@300brand.com>
 * @date Fri Mar 30 16:14:17 EDT 2012
 */
class FPhoneField extends FFormField {
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
	public function getValue() {
		$value = parent::getValue();
		$value = trim($value);

		$bits = $this->get('format', array('%03d.', '%03d.%04d', ' x%s'));
		if (!is_array($bits)) {
			throw new InvalidArgumentException('Value for format must be an array');
		} else if (count($bits) != 3) {
			throw new InvalidArgumentException('Value for format must contain exactly 3 elements');
		}

		list($area_code, $number, $extension) = $bits;

		$digits = preg_replace(
			array('/[^\dx]/', '/x+/'),
			array('',         'x'),
			strtolower($this->getRawValue())
		);
		$matches = array();
		if (strlen($digits) < 7) {
			$value = '';
		} else if (preg_match('/^(\d{3})(\d{4})$/', $digits, $matches)) {
			$value = vsprintf($number, array_slice($matches, 1));
		} else if (preg_match('/^(\d{3})(\d{3})(\d{4})$/', $digits, $matches)) {
			$value = vsprintf($area_code . $number, array_slice($matches, 1));
		} else if (preg_match('/^(\d{3})(\d{4})x(\d+)$/', $digits, $matches)) {
			$value = vsprintf($number . $extension, array_slice($matches, 1));
		} else if (preg_match('/^(\d{3})(\d{3})(\d{4})x(\d+)$/', $digits, $matches)) {
			$value = vsprintf($area_code . $number . $extension, array_slice($matches, 1));
		} else {
			$value = '';
		}
		if ($value == '') {
			$error_message = $this->get('error_invalid', 'Invalid phone number: %s');
			$this->error($value = sprintf($error_message, $this->getRawValue()));
		}
		return $value;
	}
}
