<?php
/*!
 * Defines an object model. The model is defined with an array of FField objects
 * passed into the constructor. From there, anything in need of a definition 
 * of an object's data model can retrieve an instance of this class by calling
 * getModel() on it.
 * 
 * @see FField
 * @author Jeff Wendling <jwendling@okco.com>
 * @date Wed Jun  1 23:31:42 EDT 2011
 * @version $Id$
 */
class FModelManager {
	/*!
	 * Array of fields, each defined as an FField instance.
	 */
	private $fields;
	/*!
	 * Constructs a new model manager.
	 * 
	 * @param $fields A zero-indexed array with each element an instance of
	 * FField
	 */
	public function __construct($fields) {
		if ($fields instanceof FField) {
			$this->fields = func_get_args();
		} else {
			$this->fields = $fields;
		}
	}
	/*!
	 * Fetches the field names of the field set inside this object.
	 * 
	 * Example:
	 * @code
	 * $model = new FModelManager(array(
	 *     FField::make('one'),
	 *     FField::make('two')
	 * ));
	 * 
	 * print_r($model->getFieldNames());
	 * @endcode
	 * 
	 * Output:
	 * @code
	 * Array
	 * (
	 *     [0] => one
	 *     [1] => two
	 * )
	 * @endcode
	 * 
	 * @return Array containing each field name as an element.
	 */
	public function getFieldNames() {
		$names = array();
		foreach ($this->fields as $field) {
			$names[] = $field->getName();
		}
		return $names;
	}
	/*!
	 * Magic method override to retrieve field structures based on their
	 * context. Any arbitrary method call to a model instance is passed through
	 * as an argument to FField::getData().
	 * 
	 * The FField::getData() method is called on every FField object in the
	 * model and the results are combined into an array with the field name the
	 * index and an array of any field options as the value.
	 * 
	 * @b Note: This method is never called directly. This method is called
	 * when no other method is available to call a requested instance method
	 * (see example).
	 * 
	 * Example:
	 * @code
	 * $model = new FDataModel(array(
	 *     FField::make('one')
	 *         ->length(30)
	 *         ->sub_options()
	 *             ->title('Field One'),
	 *     FField::make('two')
	 *         ->optional(true)
	 * ));
	 * 
	 * // Print the options in the global context:
	 * print_r($model->global());
	 * 
	 * // Print the options in the sub context. Note that these include those
	 * // in the global context:
	 * print_r($model->sub());
	 * @encode
	 * 
	 * Output:
	 * @code
	 * Array
	 * (
	 *     [one] => Array
	 *         (
	 *             [length] => 30
	 *         )
	 *     [two] => Array
	 *         (
	 *             [optional] => 1
	 *         )
	 * )
	 * 
	 * Array
	 * (
	 *     [one] => Array
	 *         (
	 *             [length] => 30
	 *             [title] => Field One
	 *         )
	 *     [two] => Array
	 *         (
	 *             [optional] => 1
	 *         )
	 * )
	 * @endcode
	 * 
	 * @see FField::getData()
	 * @param $method Method name
	 */
	public function __call($method, $args) {
		$data = array();
		foreach ($this->fields as $field) {
			$data[$field->getName()] = $field->getData($method);
		}
		return $data;
	}
	/*!
	 * Appends a field to the current FModelManager instance.
	 * 
	 * @param $field An instance of FField
	 */
	public function append(FField $field) {
		$this->fields[] = $field;
	}
	/*!
	 * Removes a field by name.
	 * 
	 * @param $name Name of field to remove
	 * @return Reference back to this model instance, for chaining.
	 */
	public function &remove($name) {
		foreach ($this->fields as $i => $field) {
			if ($field->getName() == $name) {
				unset($this->fields[$i]);
			}
		}
		$this->fields = array_values($this->fields);
		return $this;
	}
}
