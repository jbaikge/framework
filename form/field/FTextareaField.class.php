<?php
/*!
 * Provides a text area field with configurable rows and columns.
 * 
 * This field provides the following additional options beyond those available
 * to FTextField:
 * @li @b cols Number of columns to use for the textarea tag. This corresponds
 * to the textarea tag's attribute @c cols. Default: 40.
 * @li @b rows Number of rows to use for the textarea tag. This corresponds
 * to the textarea tag's attribute @c rows. Default: 4.
 * 
 * @author Jake Tews <jtews@okco.com>
 * @date Wed Jun 22 15:48:19 EDT 2011
 * @version $Id$
 */
class FTextareaField extends FTextField {
	/*!
	 * Fetches the number of columns for this field. Used primarily in the
	 * template for building the field HTML.
	 * 
	 * @return Value of @b cols option.
	 */
	public function getCols () {
		return $this->get('cols', 40);
	}
	/*!
	 * Fetches the number of rows for this field. Used primarily in the
	 * template for buildin the field HTML.
	 * 
	 * @return Value of the @b rows option.
	 */
	public function getRows () {
		return $this->get('rows', 4);
	}
}