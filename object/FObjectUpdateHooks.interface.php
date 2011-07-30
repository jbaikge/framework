<?php
interface FObjectUpdateHooks extends FObjectHooks {
	public function preUpdate();
	public function postUpdate();
	public function failUpdate();
	public function doUpdate();
}

