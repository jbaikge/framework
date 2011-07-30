<?php
/*!
 * Static utility class for building FField objects.
 * 
 * @author Jake Tews <jtews@okco.com>
 * @author Jeff Wendling <jwendling@okco.com>
 * @date Tue Apr 26 13:58:57 EDT 2011
 * @version $Id$
 */
class FFormFieldFactory {
	/*!
	 * Instantiates a new form field of type @c $class with the name @c $name.
	 * 
	 * @param $class Class name to use when creating a new form field
	 * @param $name Name of the field to use when constructing the new form
	 * field
	 */
	public static function make($class, $name) {
		return new $class($name);
	}
}