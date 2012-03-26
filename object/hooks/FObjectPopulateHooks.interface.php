<?php
interface FObjectPopulateHooks extends FObjectHooks {
	public function prePopulate (&$data);
	public function postPopulate (&$data);
	public function failPopulate ($exception);
	public function doPopulate (&$data);
}

