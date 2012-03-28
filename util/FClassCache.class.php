<?php
/*!
 * @author Jacob Tews <jacob@webteks.com>
 * @date Thu Jan 31 20:31:55 EST 2008
 * @param $class_name Name of the class to load
 *
 * Whenever a statement like "new <class>" is called and that class does not 
 * exist, __autoload will attempt to search for and load the class 
 * automatically. If it cannot do so, a fatal error is returned.
 */
class FClassCache {
	private static $classes;
	private static $loaded = array();
	public static function autoload ($class) {
		if (!self::$classes) {
			self::load();
		}
		if (!isset(self::$classes[$class]) || !is_array(self::$classes[$class]) || !file_exists(self::$classes[$class]['filename'])) {
			self::reload();
		}
		if (isset(self::$classes[$class])) {
			$info =& self::$classes[$class];
			require($info['filename']);
			self::$loaded[$class] = true;
			$modified = filemtime($info['filename']);
			if ($modified != $info['modified'] || $info['interfaces'] === false) {
				self::rebuildInterfaces($class);
				self::rebuildParents($class);
				self::clearObservers($class);
				self::$classes[$class]['modified'] = $modified;
				self::save();
			}
			return true;
		}
		return false;
	}
	public static function classExists ($class) {
		if (!self::$classes) {
			self::load();
		}
		if (class_exists($class, false)) {
			return true;
		} else if (isset(self::$classes[$class])) {
			return true;
		} else {
			return false;
		}
	}
	public static function clear () {
		self::$classes = null;
		@unlink($_ENV['config']['cache.class_list']);
	}
	public static function getInterfaces ($class) {
		if (!self::$classes) {
			self::load();
		}
		// Pull cached interfaces from auto-loaded classes:
		if (isset(self::$classes[$class]) && self::$classes[$class]['interfaces'] !== false) {
			$interfaces = self::$classes[$class]['interfaces'];
		}
		// Pull interfaces from inline-classes:
		else {
			$interfaces = class_implements($class);
		}
		return $interfaces;
	}
	public static function getParents ($class) {
		if (!self::$classes) {
			self::load();
		}
		if (!isset(self::$loaded[$class])) {
			self::autoload($class);
		}
		// Pull cached parents from auto-loaded classes:
		if (isset(self::$classes[$class])) {
			$parents = self::$classes[$class]['parents'];
		}
		// Pull parents from inline-classes:
		else {
			$parents = class_parents($class);
		}
		return $parents;
	}
	public static function getObservers ($class) {
		return isset(self::$classes[$class]) ? self::$classes[$class]['observers'] : false;
	}
	public static function hasInterface ($class, $interface) {
		return in_array($interface, self::getInterfaces($class));
	}
	public static function hasParent ($class, $parent) {
		return in_array($parent, self::getParents($class));
	}
	public static function lastModified ($class) {
		if (!self::$classes) {
			self::load();
		}
		$modified = 0;
		if (isset(self::$classes[$class])) {
			if (!isset(self::$loaded[$class])) {
				self::autoload($class);
			}
			$modified = self::$classes[$class]['modified'];
		}
		return $modified;
	}
	public static function register () {
		spl_autoload_register(array(__CLASS__, 'autoload'));
	}
	public static function storeObservers ($class, array $observers) {
		if (isset(self::$classes[$class])) {
			self::$classes[$class]['observers'] = $observers;
			self::save();
		}
	}
	private static function clearObservers ($class) {
		self::$classes[$class]['observers'] = false;
		foreach (self::$classes as &$info) {
			if (isset($info['interfaces'][$class])) {
				$info['observers'] = false;
			}
		}
	}
	private static function getClassList () {
		$class_list = array();
		$rdi = new RecursiveDirectoryIterator(
			$_ENV['config']['library.dir'],
			RecursiveDirectoryIterator::FOLLOW_SYMLINKS
		);
		$fcf = new FClassFilter($rdi);
		$rii = new RecursiveIteratorIterator($fcf);
		foreach ($rii as $filename => $info) {
			$class_name = str_replace(FClassFilter::$extensions, '', basename($filename));
			$class_list[$class_name] = array(
				'filename' => $filename,
				'modified' => $info->getMTime(),
				'interfaces' => false,
				'parents' => false,
				'observers' => false
			);
		}
		return $class_list;
	}
	private static function load () {
		if (!file_exists($_ENV['config']['cache.class_list'])) {
			self::$classes = self::getClassList();
			self::save();
		} else {
			$class_list =& self::$classes;
			include($_ENV['config']['cache.class_list']);
		}
	}
	private static function rebuildInterfaces ($class) {
		self::$classes[$class]['interfaces'] = class_implements($class, false);
	}
	private static function rebuildParents ($class) {
		self::$classes[$class]['parents'] = class_parents($class, false);
	}
	private static function reload () {
		@unlink($_ENV['config']['cache.class_list']);
		self::load();
	}
	private static function save () {
		$contents = '<?php $class_list = '.var_export(self::$classes, true).';';
		file_put_contents($_ENV['config']['cache.class_list'], $contents);
		@chmod($_ENV['config']['cache.class_list'], 0666);
	}
}

class FClassFilter extends RecursiveFilterIterator {
	public static $extensions = array('.class.php', '.interface.php');
	private static $extension_count = 2; // num elements in $extensions

	public function accept () {
		$file = $this->current();
		$filename = $file->getFilename();
		$accept = false;
		if ($file->isFile()) {
			for ($i = 0; $i < 2 && !$accept; $i++) {
				$substr = substr($filename, -strlen(self::$extensions[$i]));
				$accept |= strcmp($substr, self::$extensions[$i]) == 0;
			}
		} else {
			$accept = !isset($_ENV['config']['class_filter.excluded'][$filename]);
		}
		return $accept;
	}
}
