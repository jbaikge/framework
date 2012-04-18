<?php
/*!
 * Base form field class. Defines the basic functions required for any and all
 * form fields, to be overridden to make new types of form fields with their
 * own validation rules.
 * 
 * Templates for fields are built per each different field type and reside in
 * the directory defined by the global site config: @c templates.form.field.dir.
 * Templates in that directory are for @b framework fields only. The filenames 
 * for each template should be the classname followed by the .html.php
 * extension. The user may override the template with one of their own by
 * setting the @b template option as described below.
 * 
 * Templates should provide proper labelling, error reporting, reposting, and
 * identification. Templates should @b not define any graphical or layout
 * definition, but rather supply CSS classes to give greater flexibility.
 * 
 * Options for form fields are set by calling an arbitrary method on the
 * instantiated object and supplying the option's value as the argument.
 * 
 * The following example will set the optional option to true on a basic form
 * field. When extending FFormField or any other FFormField object, it is
 * important to document what options are available and what types of values
 * are expected.
 * 
 * @code
 * FFormFieldFactory::make('FFormField', 'my_field')->optional(true);
 * @endcode
 * 
 * Supported Options:
 * @li @b default Default value if none specified on initial submission.
 * Default: ""
 * @li @b error_max_length Error message to display when a user
 * enters content which exceeds the value of @c max_length. Default: "Value must
 * be %d characters or fewer"
 * @li @b error_required Error message to display when a user does not provide a
 * value on a non-@c optional field. Default: "Value required"
 * @li @b filter Typically set by an extending class, defines which filter to
 * use on incoming data. Default: @c FILTER_DEFAULT
 * @li @b flags Typically set by an extending class, defines which filter flags
 * to use on incoming data. Default: @c array()
 * @li @b id Default: value of @b name
 * @li @b label Default: value of @b name with underscores turned to spaces and
 * words capitalized
 * @li @b max_length Default: 0
 * @li @b name Field name. Default: Value passed into constructor
 * @li @b subLabel Extra instructional text which appears near the field.
 * Typically this text contains extra instruction not inferred by the user,
 * such as a date field with the subLabel "Leave blank to use today's date."
 * 
 * @author Jake Tews <jtews@okco.com>
 * @author Jeff Wendling <jwendling@okco.com>
 * @date Tue Apr 26 13:58:57 EDT 2011
 * @version $Id$
 */
class FFormField extends FFormUtils {
	public $hidden = false; ///< Whether the field should be hidden from display
	private $raw; ///< Raw value of field
	private $value; ///< Sanitized value of field
	/*!
	 * Creates a new form field. Any subclass implementing its own constructor
	 * should be sure to call this constructor.
	 * 
	 * @b Note: Subclasses intending to define their own template or filter
	 * should call this constructor after those values are established.
	 * 
	 * @param $name Name of the field.
	 */
	public function __construct($name) {
		$this->name($name);
		if (!$this->template) {
			$this->template = $_ENV['config']['templates.form.field.dir'] . DS . get_class($this) . '.html.php';
		}
		if (isset($this->filter)) {
			$this->filter($this->filter);
		}
	}
	/*!
	 * Returns the error string for this field. If there is no error, @c false
	 * is returned instead.
	 * 
	 * @return Error string or false if none.
	 */
	public function getError() {
		return $this->get('error', false);
	}
	/*!
	 * Returns the unique identifier for this field. Typically resides in the 
	 * @c id attribute of the field. If no value is explicitly specified during
	 * option setting, the field name is used instead.
	 * 
	 * Note: The value should be unique across all other fields within a form.
	 * 
	 * @return Unique identifier.
	 */
	public function getId () {
		return $this->get('id', $this->get('name'));
	}
	/*!
	 * Returns the field label. If a label is not explicitly specified during
	 * option setting, this value is derrived from the name of the field by 
	 * replacing any underscores in the name with spaces, then capitalizing all
	 * words.
	 * 
	 * @return Field label
	 */
	public function getLabel () {
		return $this->get('label', ucwords(str_replace('_', ' ', $this->getName())));
	}
	/*!
	 * Returns the name of the field.
	 * 
	 * @return Field name
	 */
	public function getName() {
		return $this->get('name');
	}
	/*!
	 * Fetches the raw value of the field.
	 * 
	 * @return Raw value of field.
	 */
	public function getRawValue () {
		return $this->raw;
	}
	/*!
	 * Returns the sub-label or instructional text associated with a field. The
	 * value returned is set using the @b subLabel option during creation.
	 *
	 * @return Field sub-label
	 */
	public function getSubLabel () {
		return $this->get('subLabel');
	}
	/*!
	 * Generates the filtered value for this field. The filter and any flags may
	 * be set by calling FFormField::filter(FILTER_<type>) and
	 * FFormField::flags(array(<flags>)). The filter acts upon the raw value
	 * supplied to the field.
	 * 
	 * Note: The filtering only occurs once - if the filtered value hasn't 
	 * already been set. If the raw value changes after this method is called, 
	 * the old value may be returned.
	 * 
	 * @return Filtered value of field.
	 */
	public function getValue() {
		if ($this->value === null) {
			$flags = $this->get('flags', FILTER_FLAG_NONE);
			$this->value = filter_var(
				$this->getRawValue(),
				$this->get('filter', FILTER_DEFAULT),
				$flags
			);
			if ($flags & FILTER_FORCE_ARRAY && isset($this->value[0]) && $this->value[0] === '') {
				$this->value = array();
			}
		}
		return $this->value;
	}
	/*!
	 * Loads raw value for the field.
	 * 
	 * @param $data Raw data for field.
	 * @return Reference back to this object
	 */
	public function &load($data) {
		$this->raw = $data;
		$this->value = null;
		$this->error(false);
		return $this;
	}
	/*!
	 * Convenience method to call FFormField::load() followed by
	 * FFormField::validate(). Returns the result of FFormField::validate().
	 * 
	 * @see FFormField::load()
	 * @see FFormField::validate()
	 * 
	 * @param $data Data to load
	 * @return FFormField::validate()'s response
	 */
	public function loadAndValidate($data) {
		return $this->load($data)->validate();
	}
	/*!
	 * Validates the value of the field. Subclasses overriding this method
	 * should call this method first to cover the following basic validation
	 * rules:
	 * 
	 * @li Default value check (Set with the @c default option)
	 * @li Optional value check (Set with the @c optional option)
	 * @li Max length check (Set with the @c max_length option)
	 * 
	 * The returned array contains two values based on whether an error was 
	 * encountered:
	 * 
	 * No errors:
	 * @li @c true
	 * @li Cleaned data
	 * 
	 * Error:
	 * @li @c false
	 * @li Error message
	 * 
	 * @return Array tuple with the structure defined above
	 */
	public function validate() {
		if ($this->get('default') !== null && $this->getRawValue() == null) {
			$this->load($this->get('default'));
		}
		// Optional Test:
		// If optional: skip
		// If raw value is blank: error
		// If validated value is exactly false (from filter failing): error
		if (!$this->get('optional', false) && ($this->getRawValue() == null || $this->getValue() === false)) {
			$this->error($this->get('error_required', 'Value required'));
		}
		$max_length = intval($this->get('max_length'));
		if ($max_length && strlen($this->getValue()) > $max_length) {
			$this->error(sprintf($this->get('error_max_length', 'Value must be %d characters or fewer'), $max_length));
		}
		return array(
			$this->getError() ? false : true,
			$this->getError() ? $this->getError() : $this->getValue()
		);
	}
}
