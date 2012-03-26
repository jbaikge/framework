<?php
interface FObjectXMLStorage extends FObjectStorage {
	public function getXMLStorageFilename();
}

class FObjectXMLStorageDriver extends FObjectStorageDriver {
	private $dom;
	public function preDelete (&$data) {
		$this->open();
	}
	public function doDelete (&$data) {
		$xpath = new DOMXpath($this->dom);
		$query = sprintf(
			'/root/node[class="%s" and id="%d"]',
			get_class($this->subject),
			$this->subject->id
		);
		$nodelist = $xpath->query($query);
		if ($nodelist->length == 1) {
			$this->dom->documentElement->removeChild($node);
		} else if ($nodelist->length == 0) {
			throw new FObjectXMLStorageNodeDoesNotExistException($query);
		} else if ($nodelist->length > 1) {
			throw new FObjectXMLStorageTooManyNodesException($query, $nodelist->length, 1);
		}
	}
	public function postDelete (&$data) {
		$this->save();
	}
	public function failDelete ($exception) {
		unset($this->dom);
		switch (get_class($exception)) {
			case 'FObjectXMLStorageNodeDoesNotExistException':
				trigger_error($exception->getMessage(), E_USER_NOTICE);
				break;
			case 'FObjectXMLStorageTooManyNodesException':
				trigger_error($exception->getMessage(), E_USER_WARNING);
				break;
		}
	}
	public function prePopulate (&$data) {
		$this->open();
	}
	public function postPopulate (&$data) {
		$this->save();
	}
	public function failPopulate ($exception) {
		unset($this->dom);
	}
	public function doPopulate (&$data) {
		$xpath = new DOMXpath($this->dom);
		$query = sprintf(
			'/root/node[class="%s" and id="%d"]/*',
			get_class($this->subject),
			$this->subject->id
		);
		foreach ($xpath->query($query) as $node) {
			$key = $node->nodeName;
			$this->subject->$key = $node->nodeValue;
		}
	}
	public function preUpdate (&$data) {
		$this->open();
	}
	public function doUpdate (&$data) {
		$nodes = array();
		if ($this->subject->id) {
			$xpath = new DOMXpath($this->dom);
			$query = sprintf(
				'/root/node[class="%s" and id="%d"]',
				get_class($this->subject),
				$this->subject->id
			);
			foreach ($xpath->query($query) as $node) {
				$this->dom->documentElement->removeChild($node);
			}
		} else {
			$this->subject->id = $this->getNextID();
		}
		$node = $this->dom->createElement('node');
		$node->appendChild($this->dom->createElement('class', get_class($this->subject)));
		$this->dom->documentElement->appendChild($node);
		$fields = FObjectStorageDriver::getStorageFields(get_class($this->subject));
		$field_names = array_keys($fields);
		foreach (array_merge(array('id'), $field_names) as $key) {
			$node
				->appendChild($this->dom->createElement($key))
				->appendChild($this->dom->createTextNode($this->subject->$key));
		}
	}
	public function postUpdate (&$data) {
		$this->save();
	}
	public function failUpdate ($exception) {
		unset($this->dom);
	}
	private function getNextID () {
		$max = 0;
		$xpath = new DOMXpath($this->dom);
		$query = sprintf('/root/node[class="%s"]/id', get_class($this->subject));
		foreach ($xpath->query($query) as $node) {
			$max = max($max, $node->nodeValue);
		}
		return $max + 1;
	}
	private function open () {
		$filename = $this->subject->getXMLStorageFilename();
		$this->dom = new DOMDocument();
		$this->dom->formatOutput = true;
		if (file_exists($filename)) {
			$this->dom->load($filename);
		} else {
			$this->dom->appendChild($root = $this->dom->createElement('root'));
		}
	}
	private function save () {
		$filename = $this->subject->getXMLStorageFilename();
		$this->dom->formatOutput = true;
		$this->dom->save($filename);
	}
}

class FObjectXMLStorageNodeDoesNotExistException extends Exception {
	public function __construct ($query) {
		parent::__construct("Could not find node represented by `{$query}'.");
	}
}
class FObjectXMLStorageTooManyNodesException extends Exception {
	public function __construct ($query, $expect, $found) {
		parent::__construct("When searching for `{$query}', expected {$expect}, but found {$found}.");
	}
}
