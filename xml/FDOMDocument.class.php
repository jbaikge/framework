<?php
/**
 * @author <Jacob Tews> jacob@webteks.com
 * @date Sun May 18 18:36:32 EDT 2008
 * @version $Id$
 */
class FDOMDocument {
	private $dom; ///< Internal DOMDocument
	/**
	 * Creates a new DOM Document with optional version and encoding.
	 *
	 * @param $version XML Version
	 * @param $encoding Document encoding string
	 */
	public function __construct ($version = null, $encoding = null) {
		$this->dom = new DOMDocument($version, $encoding);
	}
	/**
	 * Generates HTML version of Document.
	 *
	 * @return String representation of Document in HTML format
	 */
	public function asHTML () {
		return $this->dom->saveHTML();
	}
	/**
	 * Generates XML version of Document.
	 *
	 * @return String representation of Document in XML format
	 */
	public function asXML () {
		return $this->dom->saveXML();
	}
	/**
	 * Creates the Root Node of the Document. A properly formatted Document 
	 * can only have one root node. All other nodes will stem from this 
	 * node as children.
	 *
	 * @param $name Name of node
	 * @param $content Optional. Text to place within node once created
	 * @return FXMLNode Object for new node
	 */
	public function rootNode ($name, $content = null) {
		$new_node = $this->dom->appendChild($this->dom->createElement($name));
		if ($content) {
			$new_node->appendChild($this->dom->createTextNode($content));
		}
		return new FDOMNode($new_node);
	}
}

