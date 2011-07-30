<?php
/*!
 * @todo Document FFormFactory
 * 
 * @author Jeff Wendling <jwendling@okco.com>
 * @date Wed Apr 20 15:00:00 EDT 2011
 * @version $Id$
 */
class FFormFactory {
	/*!
	 * @todo Document FFormFactory::fromClass()
	 * 
	 * @param $class
	 * @param $id Default: 0
	 * @return 
	 */
	public static function fromClass($class, $id = 0) {
		$instance = new $class($id);
		return FFormFactory::fromInstance($instance);
	}
	/*!
	 * @todo Document FFormFactory::fromInstance()
	 * 
	 * @param &$instance
	 * @return 
	 */
	public static function fromInstance(FFormProcessable &$instance) {
		return new FFormInstance($instance);
	}
	/*!
	 * @todo Document FFormFactory::make()
	 * 
	 * @param $class
	 * @return
	 */
	public static function make($class) {
		return new $class;
	}
}