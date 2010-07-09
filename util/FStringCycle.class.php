<?php
/**
 * Cycles through passed values. Every time this Object is cast to a String, 
 * the next passed value is returned.
 *
 * This particular object is very handy for alternating rows in a table.
 *
 * Showing the Cycle Object in action:
 * @code
 * $cycle = new Cycle('row1', 'row2');
 * echo $cycle; // "row1"
 * echo $cycle; // "row2"
 * echo $cycle . $cycle; // "row1row2"
 * @endcode
 *
 * @see http://aidanlister.com/repos/v/Cycle.php Original concept and class.
 * @author Aidan Lister <aidan@php.net>
 * @author Jacob Tews <jacob@webteks.com>
 * @date Sat Mar  8 11:22:55 EST 2008
 * @version $Id$
 */
class FStringCycle {
	private $index; ///< Current index of strings
	private $numStrings; ///< Number of strings passed
	private $strings; ///< Passed strings to cycle through
	/**
	 * Sets the Strings to cycle through. Takes one String per argument.
	 */
	public function __construct () {
		$this->strings = func_get_args();
		$this->numStrings = count($this->strings);
		$this->index = -1;
	}
	/**
	 * Convert this Object to a String. Always returns the next string in
	 * the cycle.
	 * 
	 * @return Next string in the cycle
	 */
	public function __toString () {
		return (++$this->index < $this->numStrings)
			? $this->strings[$this->index]
			: $this->strings[$this->index = 0];
	}
	/**
	 * Resets the internal pointer for the cycle. Handy if using the 
	 * same cycle class for multiple instances.
	 */
	public function reset () {
		$this->index = -1;
	}
}
