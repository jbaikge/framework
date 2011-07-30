<?php
interface FObjectPopulateHooks extends FObjectHooks {
	public function prePopulate();
	public function postPopulate();
	public function failPopulate();
	public function doPopulate();
}

