<?php
abstract class FObjectDriver {
	protected $subject;
	public function __construct (&$subject) {
		$this->subject = $subject;
	}
}
