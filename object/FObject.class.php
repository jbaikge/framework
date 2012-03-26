<?php

interface FObjectInterface {
	public static function getModel();
}

abstract class FObject implements Serializable, FObjectInterface {
	private $changes = array();
	private $data = array();
	private $observers = array('hooks' => array(), 'methods' => array());
	private $observerInstances = array();
	public function __construct($data = null) {
		$this->autoData($data);
	}
	public function __call($method, $args) {
		$this->buildObservers();
		if ($this->hasHooks($method)) {
			return $this->callHook($method);
		} else if (array_key_exists($method, $this->observers['methods'])) {
			return call_user_func_array(array($this->getObserverInstance($this->observers['methods'][$method]), $method), $args);
		} else if (method_exists($this, $method)) {
			return call_user_func_array(array($this, $method), $args);
		}
		new Exception('Call to undefined method ' . get_class($this) . '::' . $method . '()');
	}
	public function __get ($key) {
		return isset($this->data[$key]) ? $this->data[$key] : null;
	}
	public function __isset ($key) {
		return array_key_exists($key, $this->data);
	}
	public function __set ($key, $value) {
		if (isset($this->data[$key], $this->changes[$key])) {
			if ($this->changes[$key] == $value) {
				unset($this->changes[$key]);
			}
		} else if (isset($this->data[$key]) && !isset($this->changes[$key])) {
			if ($this->data[$key] != $value) {
				$this->changes[$key] = $this->data[$key];
			}
		} else {
			$this->changes[$key] = null;
		}
		return $this->data[$key] = $value;
	}
	public function __unset ($key) {
		if (isset($this->data[$key]) && !isset($this->changes[$key])) {
			$this->changes[$key] = $this->data[$key];
		} else if (!isset($this->changes[$key])) {
			$this->changes[$key] = null;
		}
		unset($this->data[$key]);
	}
	/*!
	 * Attaches an observer class (driver) to bring in it's extra methods and
	 * hooks. Only the class name is required as the methods and drivers are
	 * derived through reflection.
	 * 
	 * If a class implements FObjectHooks, or any subclass or interface of it,
	 * any method defined with a name beginning with pre-, do-, post-, or fail-
	 * will be added as a hook. 
	 * 
	 * For example, if driver1 defines the methods preEcho, doEcho, postEcho, 
	 * failEcho and driver2 defines the methods doEcho, all methods will be 
	 * added and grouped under the hook "echo". 
	 * 
	 * For methods, any method defined in a class not prefixed with "__" will
	 * be included and available for calling.
	 * 
	 * @param $class_name String name of the class to attach observers for
	 * @return void
	 */
	public final function attachObserver ($class_name) {
		static $reflected_classes = array();
		if (!FClassCache::classExists($class_name)) {
			return;
		}
		if (!array_key_exists($class_name, $reflected_classes)) {
			$reflected_classes[$class_name] = new ReflectionClass($class_name);
		}
		if ($reflected_classes[$class_name]->isAbstract()) {
			// We don't want your stinking abstract base classes in here.
			return;
		}
		foreach (get_class_methods($class_name) as $method) {
			if (FString::startsWith($method, '__')) {
				continue;
			}
			$is_hook = false;
			$reflection_method = new ReflectionMethod($class_name, $method);

			if ($reflection_method->isAbstract()) {
				// We don't want your stinking abstract methods in here.
				continue;
			}

			try {
				try {
					$prototype = $reflection_method->getPrototype();
				} catch (ReflectionException $re) {
					$prototype = $reflection_method;
				}
				if (!array_key_exists($prototype->class, $reflected_classes)) {
					$reflected_classes[$prototype->class] = new ReflectionClass($prototype->class);
				}
				$reflection =& $reflected_classes[$prototype->class];
				if ($reflection->implementsInterface('FObjectHooks')) {
					// Creates a tuple of (type, name) for the hook method.
					// If the method isn't prefixed properly, we know it's
					// not a true hook.
					$hook_bits = preg_split("/^(pre|post|do|fail)/", $method, 2, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
					$is_hook = count($hook_bits) == 2;
				}
			} catch (ReflectionException $re) {}
			
			if ($is_hook) {
				list($type, $name) = $hook_bits;
				$this->observers['hooks'][$name][$type][] = $class_name;
			} else {
				// If two drivers have the same method, the one at the end of
				// the inheritance change will be the one chosen.
				if (isset($this->observers['methods'][$method])) {
					$original_class = $this->observers['methods'][$method];
					if ($class_name instanceof $original_class) {
						$this->observers['methods'][$method] = $class_name;
					}
				} else {
					$this->observers['methods'][$method] = $class_name;
				}
			}
		}
	}
	public function autoData ($data) {
		if (is_object($data)) {
			$data = get_object_vars($data);
		}
		// Instantiate from a view table
		if (is_array($data) && isset($data['_cache'])) {
			$data = $data['_cache'];
		}
		if (is_string($data)) {
			$json = json_decode($data, true);
			if (json_last_error() == JSON_ERROR_NONE) {
				$data = $json;
			}
		}
		if (is_array($data)) {
			$this->data = $data;
		} else if ($data) {
			$this->data['initialized_id'] = $data;
			$this->data = $this->populate();
		}
	}
	/*!
	 * 
	 */
	private final function buildObservers () {
		if (!$this->observers['hooks'] && !$this->observers['methods']) {
			$cached_observers = FClassCache::getObservers($class = get_class($this));
			if ($cached_observers !== false) {
				$this->observers = $cached_observers;
			} else {
				foreach (class_implements($this) as $interface) {
					$this->attachObserver($interface . 'Driver');
				}
				FClassCache::storeObservers($class, $this->observers);
			}
		}
	}
	public function callHook ($name) {
		$retval = null;
		try {
			$this->individualHook('pre', $name, $retval);
			$this->individualHook('do', $name, $retval);
			$this->individualHook('post', $name, $retval);
			return $retval;
		} catch (Exception $e) {
			$this->individualHook('fail', $name, $e);
		}
		return $retval;
	}
	public function getChanges () {
		return $this->changes;
	}
	public function getData () {
		return $this->data;
	}
	/*!
	 * Fetches an observer (driver) instance. This implementation is an 
	 * on-demand retrieval which only creates a new instance when it is 
	 * requested.Instances are cached to increase efficiency. Instances are 
	 * constructed with @c $this as the only argument.
	 * 
	 * @param $class_name Name of the class to fetch or create an instance for
	 * @return Instance of requested class
	 */
	private final function &getObserverInstance ($class_name) {
		if (!array_key_exists($class_name, $this->observerInstances)) {
			$this->observerInstances[$class_name] = new $class_name($this);
		}
		return $this->observerInstances[$class_name];
	}
	/*!
	 * Check to see if a hook exists. Checks within the available observers
	 * (drivers) to see if a hook is implemented.
	 * 
	 * @param $hook Hook name to check for
	 * @return @c true if hook exists, @c false otherwise
	 */
	public final function hasHooks($hook) {
		$this->buildObservers();
		return array_key_exists(ucwords($hook), $this->observers['hooks']);
	}
	/*!
	 * Check to see if a method exists. Checks within the available observers
	 * (drivers) to see if a method exists. However, these checks are not 
	 * limited to the driver, this method also checks within the subject and
	 * this instance to see if the method exists.
	 * 
	 * @param $method Method name to check for
	 * @return @c true if method exists, @c false otherwise
	 */
	public final function hasMethod ($method) {
		$this->buildObservers();
		return method_exists($this, $method)
			|| array_key_exists($method, $this->observers['methods']);
	}
	/*!
	 * Calls a specific hook type on a set of hooks. The hook type is one of,
	 * @b pre, @b do, @b post or @b fail. The hook base name is also passed to
	 * 
	 * If a hook method returns @c true, the remaining hooks of the same type
	 * are skipped. If the method returns @c false, or nothing, hook processing
	 * continues.
	 * 
	 * @param $type Hook type to call (pre, do, post, fail)
	 * @param $hook Hook base name (ex: update, populate)
	 * @param &$data Optional. Modifiable data which is passed into each hook
	 * function and returned once all hooks have run. Default: @c null
	 * @return void
	 */
	protected final function individualHook ($type, $hook, &$data = null) {
		$this->buildObservers();
		$hook = ucwords($hook);
		if (!array_key_exists($type, $this->observers['hooks'][$hook])) {
			return;
		}
		$classes = $this->observers['hooks'][$hook][$type];
		$hook_func = $type . $hook;
		foreach ($classes as $class_name) {
			if ($this->getObserverInstance($class_name)->$hook_func($data)) {
				break;
			}
		}		
	}
	public final function resetChanges () {
		$this->changes = array();
	}
	public function serialize () {
		return json_encode($this->data);
	}
	public function unserialize ($serialized) {
		$this->data = json_decode($serialized, true);
	}
}
