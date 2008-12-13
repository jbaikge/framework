<?php
class FDOMForm implements FEventListener {
	private $docType;
	private $dom;
	private $fields;
	private $formDom;
	private $id;
	private $removeDocType;
	public function __construct () {
		$this->docType = '<!DOCTYPE HTML PUBLIC '
			. '"-//W3C//DTD HTML 4.01//EN" '
			. '"http://www.w3.org/TR/html4/loose.dtd">'
			. "\n";
		$this->removeDocType = false;
	}
	public function getCallback () {
		return '<form';
	}
	public function run (&$template) {
		if (!preg_match('/<form[^>]+process-with=/', $template)) {
			// Nothing to do here - none of the form classes will
			// be called
			return false;
		}
		$this->setDom($template);
		foreach ($this->getFormNodes() as $node) {
			$this->callForm($node);
		}
		if ($this->removeDocType) {
			$template = str_replace($this->docType, '', $this->dom->saveHTML());
		} else {
			$template = $this->dom->saveHTML();
		}
	}
	private function callForm (&$node) {
		$class_name = $node->getAttribute('process-with');
		$data = null;
		$id = null;
		if ($node->hasAttribute('id')) {
			$id = $node->getAttribute('id');
		} else {
			trigger_error(sprintf('Form to be processed with [%s] does not have an ID attribute.', $class_name), E_USER_NOTICE);
		}
		if ($node->hasAttribute('method')) {
			if (strtolower($node->getAttribute('method')) == 'get') {
				$data =& $_GET;
			} else {
				$data =& $_POST;
			}
		} else {
			trigger_error(sprintf('No method attribute in form tag with process-with: %s. Using REQUEST.', $class_name), E_USER_NOTICE);
			$data =& $_REQUEST;
		}
		if (!class_exists($class_name)) {
			trigger_error(sprintf('Form processing class does not exist: %s.', $class_name), E_USER_WARNING);
			return false;
		}
		$fields = $this->getFields($node);
		$form = new $class_name($fields, $id);
		if (!($form instanceof FForm)) {
			trigger_error(sprintf('Form processing class does not implement FForm: %s.', $class_name), E_USER_WARNING);
			return false;
		}
		// Loop through each field and match up the field name with a 
		// posted variable. If they all match we have a submission and 
		// call FForm::run. If they don't, then we just run 
		// FForm::populate.
		foreach ($fields as $key => &$field) {

		}
	}
	private function getFormNodes () {
		$xpath = new DOMXPath($this->dom);
		return $xpath->evaluate('//form[@process-with]');
	}
	private function getFields (&$formNode) {
		$xpath = new DOMXPath($this->dom);
		$nodes = $xpath->evaluate('//*[@name and (name()="input" or name()="textarea" or name()="select")]', $formNode);
		$fields = array();
		foreach ($nodes as $node) {
			$field = new FFieldText($node);
			$fields[$field->getName()] =& $field;
		}
		return $fields;
	}
	private function setDom (&$template) {
		$this->dom = new DOMDocument();
		if (!FString::startsWith($template, '<!DOCTYPE')) {
			trigger_error('No DOCTYPE specified, adding one temporarily', E_USER_NOTICE);
			$this->dom->loadHTML($this->docType . $template);
		} else {
			$this->dom->loadHTML($template);
		}
	}
}
