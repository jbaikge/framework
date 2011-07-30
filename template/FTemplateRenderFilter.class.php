<?php
/*!
 * Interface for a rendering filter used with output buffering. Any class
 * specified in the @c templates.filters configuration array must implement this
 * interface.
 * 
 * A single method is provided, #filter(), where the entire contents of the
 * output buffer is passed in to be operated on. The contents should then be
 * returned to be passed into another render filter or to the browser.
 * 
 * Example Implementation:
 * @code
 * class MyRenderFilter implements FTemplateRenderFilter {
 *     public function filter ($content) {
 *         return str_replace($content, 'monkey', 'banana');
 *     }
 * }
 * @endcode
 * 
 * In the above example, any string "monkey" is replaced with "banana" before
 * the contents are pushed to the browser.
 * 
 * @package template
 * @author Jacob Tews <jacob@webteks.com>
 * @date Sun Dec 14 09:28:02 EST 2008
 * @version $Id$
 */
interface FTemplateRenderFilter {
	/*!
	 * Method to alter or enhance the contents passed to it. Implementations of
	 * this method should accept a string of contents to operate on, then
	 * return those contents for further processing or to send to the browser.
	 * 
	 * The alterations should be minimal, if any are to be made at all, as this
	 * method will run with every page rendering. 
	 * 
	 * Possible implementations could include:
	 * @li Modifying anchor tags with links to external sites to open in a new
	 * browser window
	 * @li Automatically adding information to the header or footer of a page
	 * @li Capturing content before it leaves to save for later processing such
	 * as a search or indexing
	 * 
	 * @param $content String contents intended for the browser. Contents may
	 * come from other filter results.
	 * @return String contents modified per this method's implementation
	 */
	public function filter ($content);
}
