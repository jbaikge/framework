<?php
interface FObjectDeleteHooks extends FObjectHooks {
	public function preDelete (&$data);
	public function postDelete (&$data);
	public function failDelete ($exception);
	public function doDelete (&$data);
}
