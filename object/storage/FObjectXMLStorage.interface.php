<?php
interface FObjectXMLStorage extends FObjectStorage {}

class FObjectXMLStorageDriver extends FObjectStorageDriver {
	private $dom;
	private $filename = '/tmp/lol.xml'; // some derrived name somehow..
	public function prePopulate() {
		$this->open();
	}
	public function postPopulate() {
		$this->save();
	}
	public function failPopulate() {
		unset($this->dom);
	}
	public function doPopulate() {
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
	public function preUpdate() {
		$this->open();
	}
	public function doUpdate () {
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
		foreach (array_merge(array('id'), $this->subject->getStorageFields()) as $key) {
			$node
				->appendChild($this->dom->createElement($key))
				->appendChild($this->dom->createTextNode($this->subject->$key));
		}
	}
	public function postUpdate() {
		$this->save();
	}
	public function failUpdate() {
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
		$this->dom = new DOMDocument();
		$this->dom->formatOutput = true;
		if (file_exists($this->filename)) {
			$this->dom->load($this->filename);
		} else {
			$this->dom->appendChild($root = $this->dom->createElement('root'));
		}
	}
	private function save () {
		$this->dom->formatOutput = true;
		$this->dom->save($this->filename);
	}
}
