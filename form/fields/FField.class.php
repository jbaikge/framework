<?php
/**
 * Defines the structure for a form field. The interface sets up the functions 
 * every form field should have.
 *
 * @author Jacob Tews <jacob@webteks.com>
 * @date Sat Feb 23 15:35:02 EST 2008
 * @version $Id$
 */
abstract class FField {
	protected $node; ///< DOMElement node for this field
	/**
	 * Returns an error message if there is one. If there is no error, this 
	 * method should return null.
	 *
	 * @return An error message or null if there is no error.
	 */
	public abstract function error();
	/**
	 * Returns the default value for a field.
	 *
	 * @return Default value for field or an emptry string if unable to determine.
	 */
	public abstract function getDefault();
	/**
	 * Unescapes field for on-screen display or email usage.
	 * 
	 * @return Text representation of field's value
	 */
	public abstract function getText();
	public abstract function getType();
	/**
	 * Determines whether or not the field's value passes validation 
	 * requirements.
	 *
	 * @return True if field is valid, false otherwise
	 */
	public abstract function valid();
	/**
	 */
	public function __construct (DOMElement &$node) {
		$this->dom =& $node->ownerDocument;
		$this->node = $node;
	}
	/**
	 * Returns a String representation of this field.
	 *
	 * @return String representation of this field
	 */
	public function __toString () {
		return $this->getText();
	}
	public function getId () {
		if ($this->node->hasAttribute('id')) {
			return $this->node->getAttribute('id');
		} else {
			return '';
		}
	}
	/**
	 * Returns the human-readable name of the field. This is handy for 
	 * displaying in emails or error messages. If a label is not supplied 
	 * for the field, underscores are removed and words capitalized.
	 *
	 * @return Human-readable name of field
	 */
	public function getLabel () {
		$xpath = new DOMXpath($this->dom);
		$nodeset = $xpath->evaluate('//label[@for="' . $this->getId() . '"]');
		if ($nodeset->length) {
			return $nodeset->item(0)->textContent;
		} else {
			return '';
		}
	}
	/**
	 * Returns the name of the field.
	 *
	 * @return Internal name of the field
	 */
	public function getName () {
		return $this->node->getAttribute('name');
	}
	/**
	 * Returns required state of the field. This is likely to be overridden 
	 * to make this field's requirement dependent upon input and such.
	 *
	 * @return True if required, False otherwise.
	 */
	public function required () {
		if ($this->node->hasAttribute('required')) {
			return $this->node->getAttribute('required') == 'true';
		} else {
			return false;
		}
	}
}
