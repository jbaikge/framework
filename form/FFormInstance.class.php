<?php
/*!
 * @todo Document FFormInstance
 *
 * @author Jeff Wendling <jwendling@okco.com>
 * @date Wed Apr 20 15:00:00 EDT 2011
 * @version $Id$
 */
class FFormInstance extends FForm {
	private $instance; ///< Reference to 
	/*!
	 * @todo Document FFormInstance::__construct()
	 *
	 * @param $instance Instance of object to operate on. Must implement
	 * FFormProcessable.
	 */
	public function __construct(FFormProcessable &$instance) {
		$this->instance =& $instance;
		$this->data = $instance->getData();
		$this->loadFields();
		$this->_cacheValid(false);
		parent::__construct();
	}
	public function &getInnerInstance() {
		return $this->instance;
	}
	/*!
	 * Calls makeFields() on the internal FFormProcessable instance.
	 * 
	 * @see FFormProcessable::makeFields()
	 * @see FForm::makeFields()
	 * @return Array of FField subclassed objects with their options applied
	 */
	public function makeFields() {
		return $this->instance->makeFields();
	}
	/*!
	 * Returns the original FFormProcessable object with all incoming, valid, 
	 * data applied.
	 * 
	 * @see FForm::populate()
	 * @return Object with all 
	 */
	public function &populatedObject() {
		return parent::populate($this->instance);
	}
	public function rebuildFields () {
		parent::rebuildFields();
		$this->load($this->instance->getData());
	}
}
