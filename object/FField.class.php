<?php
/*!
 * Defines attributes for a field within an FObject's model. Options are
 * arbitrary method calls where the option key is the method name and the
 * option value is the method's first (and only) argument.
 * 
 * @date Wed Jun 01 23:31:42 EDT 2011
 * @author Jake Tews <jtews@okco.com>
 * @author Jeff Wendling <jwendling@okco.com>
 */
class FField {
	private $name;
	private $context = 'global';
	private $data = array('global' => array());
	/*!
	 * Construct a new FField. This method is never called directly. See
	 * FField::make() for more information.
	 * 
	 * @see FField::make()
	 * @param $name Name of the field
	 */
	public function __construct ($name) {
		$this->name = $name;
	}
	/*!
	 * Handles arbitrary method calls. The arbitrary method calls serve a 
	 * special purpose by setting values and establishing the context for where
	 * those values go.
	 * 
	 * Methods ending in _options():<br>
	 * Any method called ending in _options() changes the context for 
	 * subsequent calls on the object. Arguments are ignored. If the method name
	 * contains additional underscores, those are later converted to sub-
	 * contexts.
	 * 
	 * All other methods:<br>
	 * The method names act as the field option names and the value is defined
	 * by the first (and only) argument.
	 * 
	 * This method is not called directly; only when a user calls a method which
	 * is not previously defined.
	 * 
	 * @see FField::getData()
	 * @param $method Method name.
	 * @param $args Arguments passed into method.
	 */
	public function __call ($method, $args) {
		if (FString::endsWith($method, '_options')) {
			$this->context = substr($method, 0, strrpos($method, '_'));
		} else {
			!isset($args[0]) && trigger_error("Must provide exactly one argument to {$method}.", E_USER_ERROR);
			$value = $args[0];
			$this->data[$this->context][$method] = $value;
		}
		return $this;
	}
	/*!
	 * Retrieves the option/value pairs set on this field in a given context. If
	 * no argument is supplied for the parameter @c $context, global is assumed.
	 * The values for a given context are determined by merging the global
	 * context's values with each sub-context's values until the path to the
	 * specified context is reached.
	 * 
	 * For example, given the following initialization:
	 * @code
	 * $field = FField::make('myField')
	 *     ->length(16) // Global context
	 *     ->storage_options() // Change context to "storage"
	 *         ->type('text')
	 *     ->storage_xml_options() // Change context to "xml" under "storage"
	 *         ->length(1024)
	 *         ->cdata(true);
	 * @endcode
	 * 
	 * The values for the global context, @c $field->getData()
	 * @li length: 16
	 * 
	 * The values for the storage context, @c $field->getData('storage')
	 * @li length = 16
	 * @li type = text
	 * 
	 * The values for the xml context under storage, @c 
	 * $field->getData('storage_xml')
	 * @li length = 1024
	 * @li type = text
	 * @li cdata = true
	 * 
	 * @param $context Context to retrieve options for. (Default: global)
	 * @return array Associative array of all valid options set on this field.
	 */
	public function getData ($context = 'global') {
		$data = $this->data['global'];
		$context .= '_';
		$pos = -1;
		while ($pos = strpos($context, '_', $pos + 1)) {
			$sub_context = substr($context, 0, $pos);
			if (array_key_exists($sub_context, $this->data)) {
				$data = array_merge($data, $this->data[$sub_context]);
			}
		}
		return $data;
	}
	/*!
	 * Retrieves the name of this field.
	 * 
	 * @return Field name
	 */
	public function getName () {
		return $this->name;
	}
	/*!
	 * Creates a new FField instance.
	 * 
	 * Why use this instead of @c new @c FField()?<br>
	 * @c FField::make() allows the programmer to immediately start calling
	 * methods on the returned FField instance.
	 * 
	 * @return FField instance
	 */
	public static function make ($name) {
		return new FField($name);
	}
}
