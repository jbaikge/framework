<?php
interface FObjectStorage {
	public function getStorageFields ();
}

abstract class FObjectStorageDriver extends FObjectDriver implements FObjectPopulateHooks, FObjectUpdateHooks {}
