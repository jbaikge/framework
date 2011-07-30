<?php
class FObjectBase {
	private $observers = array('hooks' => array(), 'methods' => array());
	private $observerInstances = array();
	private $subject;
	public function __construct(FObject &$subject) {
		$this->subject =& $subject;
	}
	public function __call($method, $args) {
		$this->buildObservers();
		if (array_key_exists($method, $this->observers['methods'])) {
			return call_user_func_array(array($this->getInstance($this->observers['methods'][$method]), $method), $args);
		}
		trigger_error('Call to undefined method ' . get_class($this) . '::' . $method . '()', E_USER_ERROR);
	}
	public final function attachObserver ($class_name) {
		static $reflected_classes = array();
		if (!class_exists($class_name)) {
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
			if (strpos($method, '__') === 0) {
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
				// If two drivers have the same method, the last one will
				// override
				$this->observers['methods'][$method] = $class_name;
			}
		}
	}
	protected final function buildObservers () {
		if (!$this->observers['hooks'] && !$this->observers['methods']) {
			foreach (class_implements($this->subject) as $interface) {
				$this->attachObserver($interface . 'Driver');
			}
		}
	}
	public final function individualHook ($type, $hook, $data = null) {
		$this->buildObservers();
		$hook = ucwords($hook);
		if (!array_key_exists($type, $this->observers['hooks'][$hook])) {
			return;
		}
		$classes = $this->observers['hooks'][$hook][$type];
		$hook_func = $type . $hook;
		foreach ($classes as $class_name) {
			call_user_func(array($this->getInstance($class_name), $hook_func), $data);
		}		
	}
	protected final function &getInstance ($class_name) {
		if (!array_key_exists($class_name, $this->observerInstances)) {
			$this->observerInstances[$class_name] = new $class_name($this->subject);
		}
		return $this->observerInstances[$class_name];
	}
	public function hasHooks($method) {
		$this->buildObservers();
		return array_key_exists(ucwords($method), $this->observers['hooks']);
	}
	public function hasMethod ($method) {
		$this->buildObservers();
		return method_exists($this->subject, $method)
			|| method_exists($this, $method)
			|| array_key_exists($method, $this->observers['methods']);
	}
	public function update () {
		return $this->subject->id;
	}
	public function populate ($id) {
		$this->subject->id = $id;
	}
}
