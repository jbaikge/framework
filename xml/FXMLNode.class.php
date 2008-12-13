<?php
/**
 * @author Jacob Tews <jacob@webteks.com>
 * @date Sun May 18 21:03:00 EDT 2008
 * @version $Id$
 */
class FXMLNode {
	private $node; ///< Internal DOMElement
	/**
	 * @param $node DOMElement to use as the internal node.
	 */
	public function __construct (DOMElement &$node) {
		$this->node = $node;
	}
	/**
	 * Catches calls to methods not explicitly defined in the class. When 
	 * arbitrary methods are called on this Object, it appends a DOMElement 
	 * to the internal node of the name specified by the method name. If an 
	 * argument is included in the method call, the value is used as the 
	 * content within the new node.
	 *
	 * @param $name Method name
	 * @param $args Arguments passed to method
	 * @return An FXMLNode Object for the new node
	 */
	public function __call ($name, $args) {
		$content = null;
		if (count($args)) $content = $args[0];
		return $this->appendChild($name, $content);
	}
	/**
	 * Creates a node and appends it as a child to the current node.
	 *
	 * @param $name Node name
	 * @param $content Optional. Text to place within new node
	 * @return An FXMLNode Object for the new node
	 */
	public function appendChild ($name, $content = null) {
		$new_node = $this->node->appendChild($this->node->ownerDocument->createElement($name));
		if ($content) {
			$new_node->appendChild($this->node->ownerDocument->createTextNode($content));
		}
		return new FXMLNode($new_node);
	}
	/**
	 * Appends text to the current node.
	 *
	 * @param $text Text to append to node
	 * @return Reference to current Object
	 */
	public function &appendText ($text) {
		$this->node->appendChild($this->node->ownerDocument->createTextNode($text));
		return $this;
	}
	/**
	 * Sets an attribute on the current node.
	 *
	 * @param $name Name of attribute
	 * @param $value Value of attribute
	 * @return Reference to current Object
	 */
	public function &setAttribute ($name, $value) {
		$this->node->setAttribute($name, $value);
		return $this;
	}
}
