<?php
/*!
 * Provides a simple text field utilizing @c FILTER_SANITIZE_STRING.
 * 
 * @author Jake Tews <jtews@okco.com>
 * @date Tue Apr 26 13:58:57 EDT 2011
 * @version $Id$
 */
class FTextField extends FFormField {
	/*!
	 * Override FFormField::$filter default to use @c FILTER_SANITIZE_STRING
	 * instead.
	 */
	protected $filter = FILTER_SANITIZE_STRING;
}