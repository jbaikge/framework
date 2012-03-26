<?php
/*!
 * Handles storing data in the Salesforce style
 * 
 * @author Jake Tews <jtews@okco.com>
 * @author Jamie Burdette <jburdette@okco.com>
 * @date Thu Nov 17 16:44:52 EST 2011
 */
interface FObjectSalesforceStorage extends FObjectStorage {
	public function getSalesforceData();
}

class FObjectSalesforceStorageDriver extends FObjectDriver implements FObjectUpdateHooks {
	private static $all = array(
		'primary_org',
		'url',
		'first_name',
		'last_name',
		'organization',
		'title',
		'phone',
		'email',
		'address',
		'address2',
		'city',
		'state',
		'zip',
		'country'
	);
	private static $required = array(
		'last_name',
		'email',
		'primary_org',
		'url'
	);
	private $data;
	private $dom;
	private $timestamp;

	public static function getSalesforceFilesXML() {
		$dom = new FDOMDocument();
		$root = $dom->rootNode('files');
		$rdi = new RecursiveDirectoryIterator(
			$_ENV['config']['salesforce.dir'],
			FilesystemIterator::SKIP_DOTS
		);
		$fsrfi = new FSalesforceRecursiveFilterIterator($rdi);
		$rii = new RecursiveIteratorIterator($fsrfi);
		foreach ($rii as $file) {
			$node = $root->file();
			$filename = $file->getPathname();
			$filename = str_replace(SITEROOT, '', $filename);
			$filename = ltrim($filename, '/');
			$node->filename($filename);
			$node->modified($file->getCTime());
		}
		return $dom->asXML();
	}
	public function preUpdate(&$data) {
		$this->data = $this->getData();
		if ($_ENV['config']['salesforce.site'] == null) {
			throw new FObjectSalesforceException("Required configuration variable `salesforce.site' is not set.");
		}

		$key_diff = array_diff(self::$required, array_keys($this->data));
		if (count($key_diff) > 1) {
			throw new FObjectSalesforceException('One or more required data keys missing: ' . implode(', ', $key_diff));
		}
		// Store a single timestamp in case doUpdate takes longer for different
		// drivers.
		$this->timestamp = time();
		$this->dom = new FDOMDocument();
		return false;
	}
	public function doUpdate(&$data) {
		$root = $this->dom->rootNode('contact');
		$root->timestamp($this->timestamp);
		$root->id(date('YmdHis', $this->timestamp));
		$root->site($_ENV['config']['salesforce.site']);
		foreach (self::$all as $field) {
			$root->$field(array_key_exists($field, $this->data) ? $this->data[$field] : null);
		}
		return false;
	}
	public function postUpdate(&$data) {
		$filename = $this->getFilename();
		if (!@mkdir(dirname($filename), 0755, true)) {
			
		}
		file_put_contents($filename, $this->dom->asXML());
		return false;
	}
	public function failUpdate($exception) {
		if ($exception instanceof FObjectSalesforceException) {
			trigger_error('Exception: ' . $exception->getMessage(), E_USER_ERROR);
		}
	}
	private function getData() {
		$data = $this->subject->getSalesforceData();
		if (!is_array($data)) {
			throw new FObjectSalesforceException('Data returned from ' . get_class($this->subject) . '::getSalesforceData() must be an array');
		}
		return $data;
	}
	private function getFilename($index = 0) {
		// salesforce.site is expected to be the fully qualified domain name for
		// the site. Since the domain name can be in any ridiculous format, we
		// need to take the component before the TLD and use that for the name.

		// Trim the leading http://, if it exists.
		$ltrimmed = str_replace(array('http://', 'https://'), '', $_ENV['config']['salesforce.site']);
		// Trim off any trailing directory components
		list($domain_name) = explode('/', $ltrimmed);
		// Pull out the correct component of the domain name
		$domain_bits = explode('.', $domain_name);
		$site_name = $domain_bits[count($domain_bits) - 2];

		$filename = sprintf("%s/%s/%s-contact-%d_%d.xml",
			$_ENV['config']['salesforce.dir'], # Salesforce dir
			date('Y/m/d', $this->timestamp), # Date subdir
			$site_name,
			date('Ymd'),
			date('His')
		);
		return $filename;
	}
}

class FSalesforceRecursiveFilterIterator extends RecursiveFilterIterator {
	public function accept() {
		$current = $this->current();
		return $current->isDir() || ($current->isFile() && $current->getExtension() == 'xml');
	}
}

class FObjectSalesforceException extends Exception {}
