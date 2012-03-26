<?php
interface FObjectUpdateHooks extends FObjectHooks {
	public function preUpdate (&$data);
	public function postUpdate (&$data);
	public function failUpdate ($exception);
	public function doUpdate (&$data);
}

