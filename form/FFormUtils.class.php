<?php
class FFormUtils {
	private $attributes = array(); ///< Internal attributes
	protected $template; ///< Default template path
	/*!
	 * Magic method to allow chain-setting attributes.
	 *
	 * @param $method Method name called
	 * @param $args All arguments passed in
	 * @return Reference to $this
	 */
	public function __call ($method, $args) {
		$this->attributes[$method] = $args[0];
		return $this;
	}
	/*!
	 * Fetches the value for a given attribute. If an attribute of this object
	 * does not exist, then the default is used, as specified by the second
	 * parameter.
	 *
	 * @param $attribute Name of the attribute to fetch
	 * @param $default (null) Default value to use if the attribute does not 
	 * exist
	 * @return Value of attribute, or the default value if it does not exist
	 */
	public function get ($attribute, $default = null) {
		return array_key_exists($attribute, $this->attributes) ? $this->attributes[$attribute] : $default;
	}
	/*!
	 * Fetches the path to the template used to represent this object, for use 
	 * with FTemplate::fetch(). The value is determined by first checking the 
	 * template attribute, set using $object->template(), then defaults to the 
	 * value of $this->template;
	 *
	 * @return Path to template
	 */
	public function getTemplate () {
		return $this->get('template', $this->template);
	}
	public function set ($attribute, $value = null) {
		$this->attributes[$attribute] = $value;
	}
	public function setAttributes (array $attributes) {
		$this->attributes = array_merge($this->attributes, $attributes);
	}
}
