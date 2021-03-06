<?php
/*!
 * @todo Document FForm
 * 
 * @author Jake Tews <jtews@okco.com>
 * @author Jeff Wendling <jwendling@okco.com>
 * @date Wed Apr 20 15:00:00 EDT 2011
 * @version $Id$
 */
abstract class FForm extends FFormUtils {
	protected $buttons = array();
	protected $data = array(); ///< Unfiltered (raw) data
	protected $cleanData = array(); ///< Filtered and validated data
	/*!
	 * @todo Document FForm::makeFields().
	 * 
	 * @see FForm::getFields()
	 * @return Array of all fields (Subclassing FField) defined for this form
	 */
	public abstract function makeFields();
	/*!
	 * @todo Document FForm::__construct()
	 */
	public function __construct() {
		if (!$this->template) {
			$this->template = $_ENV['config']['templates.form.dir'] . DS . __CLASS__ . '.html.php';
		}
		$this->buttons = array(
			FFormButton::make('Submit')->class('FSubmitButton'),
		);
	}
	/*!
	 * Returns the array of buttons.
	 * 
	 * @return array of FFormButton objects
	 */
	public function getButtons() {
		return $this->buttons;
	}
	/*!
	 * Cached call to FForm::makeFields(). Use this instead of 
	 * FForm::makeFields() as it preserves a cache of the fields for quicker
	 * execution.
	 * 
	 * @see FForm::makeFields()
	 * @return Array of all fields (Subclassing FField) defined for this form
	 */
	public function getFields() {
		if (!$this->get('_fieldCache', false)) {
			$this->_fieldCache($this->makeFields());
		}
		return $this->get('_fieldCache');
	}
	/*!
	 * @todo Document FForm::load()
	 * 
	 * @param $data Optional. Array of data to (pre-)populate form fields
	 * @return Reference to the current FForm instance
	 */
	public function &load($data = null) {
		if ($data === null) {
			$this->data = filter_input_array($this->inputType(), $this->getFilters());
		} else {
			$this->data = filter_var_array($data, $this->getFilters());
		}
		// Add a condition for including file uploads.
		if (isset($_FILES)) {
			// Clear out empty uploads
			foreach ($_FILES as $field => $file_data) {
				if (empty($file_data['name'])) {
					unset($_FILES[$field]);
				}
			}
			$this->data = array_merge((array)$this->data, $_FILES);
		}
		$this->loadFields();
		$this->_cacheValid(false);
		return $this;
	}
	/*!
	 * @todo Document FForm::loadAndValidate()
	 * 
	 * @see FForm::load()
	 * @see FForm::valid()
	 * @param $data
	 * @param $cache Optional. @c true to cache the valid state of the entire
	 * form; @c false to re-check every time. Default: @c true
	 * @return Result of FForm::valid()
	 */
	public function loadAndValidate ($data = null, $cache = true) {
		return $this->load($data)->valid($cache);
	}
	/*!
	 * @todo Document FForm::populate()
	 * 
	 * @param &$instance
	 * @return Same object that was passed in as an argument, but with data 
	 * populated.
	 */
	public function &populate(&$instance) {
		if (!$this->valid()) {
			throw new FormException("Can't update an instance with an invalid form. Did you call " . get_class($this) . "::valid() first?");
		}
		foreach ($this->cleanData as $key => $value) {
			$instance->$key = $value;
		}
		return $instance;
	}
	public function rebuildFields () {
		$this->_fieldCache(false);
	}
	/*!
	 * Sets the button(s) for the form.
	 * 
	 * @param ... Buttons to use on the form. Takes an arbitrary number of
	 * FFormButton
	 */
	public function setButtons() {
		$buttons = array_flatten(func_get_args());
		array_walk($buttons, function(&$b) {
			if (!($b instanceof FFormButton)) {
				throw new FormException('Button must be an instance of FFormButton. ' . get_class($b) . ' found.');
			}
		});
		$this->buttons = $buttons;
	}
	/*!
	 * @todo Document FForm::valid()
	 * 
	 * @param $cache
	 * @return 
	 */
	public function valid($cache = true) {
		if ($this->get('_cacheValid', false)) {
			return $this->get('_cacheValid');
		}
		// Kill the cache if there's no data, whether we're caching or not
		// Don't want valid(false) called when _cacheValid is true
		if (!$this->data) {
			$this->_cacheValid(false);
			return false;
		}
		$all_valid = true;
		foreach ($this->getFields() as $field) {
			list($valid, $data) = $field->validate();
			if ($valid) {
				$this->cleanData[$field->getName()] = $data;
			} else {
				$all_valid = false;
			}
		}
		if ($cache) {
			$this->_cacheValid($all_valid);
		}
		return $all_valid;
	}
	/*!
	 * @todo Document FForm::getFilters()
	 * 
	 * @return
	 */
	protected function getFilters() {
		$filters = array();
		foreach ($this->getFields() as $field) {
			$filters[$field->getName()] = array(
				'filter' => FILTER_CALLBACK,
				'options' => array(__CLASS__, 'filterRaw')
			);
		}
		return $filters;
	}
	/*!
	 * @todo Document FForm::inputType()
	 * 
	 * @return
	 */
	protected function inputType() {
		return strtolower($this->get('method', 'post')) === 'post' ? INPUT_POST : INPUT_GET;
	}
	/*!
	 * @todo Document FForm::loadFields()
	 * 
	 * @return
	 */
	protected function loadFields() {
		if (!$this->data) {
			return;
		}
		foreach ($this->getFields() as $field) {
			if (array_key_exists($field->getName(), $this->data)) {
				$field->load($this->data[$field->getName()]);
			} else {
				$field->load(null);
			}
		}
	}
	/*!
	 * @todo Document FForm::filterRaw()
	 * 
	 * @param $value Value to not filter
	 * @return Same value passed in as an argument
	 */
	private function filterRaw ($value) {
		return $value;
	}
}

class FormException extends Exception {}
