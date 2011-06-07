<?php
class FSignal {
	private static $method_cache = array();
	private static $instance_cache = array();
	private static $has_loaded = false;

	public static function register($class) {
		foreach (get_class_methods($class) as $method) {
			self::$method_cache[$method][] = $class;
		}
	}

	private static function getInstance($class) {
		if (!array_key_exists($class, self::$instance_cache)) {
			self::$instance_cache[$class] = new $class;
		}
		return self::$instance_cache[$class];
	}

	public static function signal($signal, $data) {
		if (!self::$has_loaded) { self::load(); }

		if (array_key_exists($signal, self::$method_cache)) {
			foreach (self::$method_cache[$signal] as $class) {
				self::getInstance($class)->$signal($data);
			}
		}
	}

	private static function load() {
		foreach (glob($_ENV['config']['library.dir.signals'] . '/*.class.php') as $filename) {
			$parts = explode('.', $filename);
			self::register($parts[0]);
		}
		self::$has_loaded = true;
	}
}