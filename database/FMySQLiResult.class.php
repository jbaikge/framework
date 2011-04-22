<?php
/**
 * Overrides the native mysqli_result PHP class. An instance of this object is 
 * returned after issuing a FDB::query call.
 *
 * Since this object extends the mysqli_result class, all available methods in 
 * the native class are available in this object. Some functions may get 
 * aliases to make reading and writing code easier while some functions may get 
 * reimplementations.
 *
 * The Iterator implementation allows for more flexible and compact coding of
 * database result iteration. The implementation allows the same query to run
 * through multiple @c foreach loops as it resets itself before each loop.
 *
 * Exercising the functionality of the Iterator implementation:
 * @code
 * foreach (FDB::query("SELECT id, name FROM table") as $num => $row) {
 * 	printf('Row %d has the user named %s, who has the ID %d<br>',
 * 		$num,
 * 		$row->name,
 * 		$row->id
 * 	);
 * }
 * @endcode
 *
 * Changing the return type of the result:
 * @code
 * // Unassociative array of values:
 * foreach (FDB::query("SELECT id, name FROM table")->asRow() as $row) {
 * 	var_dump($row);
 * }
 *
 * // Associative array of column names => values
 * foreach (FDB::query("SELECT id, name FROM table")->asAssoc() as $row) {
 * 	var_dump($row);
 * }
 *
 * // Object representation of row (default)
 * foreach (FDB::query("SELECT id, name FROM table")->asObject() as $row) {
 * 	var_dump($row);
 * }
 * @endcode
 *
 * Using the same result with different return types:
 * @code
 * $result = FDB::query("SELECT id, name FROM table");
 * $result->asRow();
 *
 * // This will use the result in "row" mode:
 * foreach ($result as $row) {
 * 	echo $row[1] . "\n";
 * }
 * 
 * // Now use the result as an Object:
 * foreach ($result->asObject() as $row) {
 * 	echo $row->id . "\n";
 * }
 * @endcode
 *
 * @author Jacob Tews <jacob@webteks.com>
 * @date Sat Mar  1 21:39:06 EST 2008
 * @version $Id$
 * @see http://php.net/manual/en/ref.mysqli.php
 */
class FMySQLiResult extends mysqli_result implements Countable, SeekableIterator {
	private $currentRow; ///< Holds the current row in the Iterator
	private $rowNum; ///< Incremented during iteration over the resultset
	private $fetchFunc; ///< Function to use when returning results
	public $query; ///< SQL query represented by this result
	/**
	 * Causes the Iterator to return an Associative Array for every row
	 *
	 * @return Reference back to result
	 */
	public function &asAssoc () {
		$this->fetchFunc = 'fetch_assoc';
		return $this;
	}
	/**
	 * Causes the Iterator to return a string with the field data
	 * as a CSV.
	 *
	 * @return Reference back to result
	 */
	public function &asCSV () {
		$this->fetchFunc = 'fetch_csv';
		return $this;
	}
	/**
	 * Causes the Iterator to return an Object for every row
	 *
	 * @return Reference back to result
	 */
	public function &asObject () {
		$this->fetchFunc = 'fetch_object';
		return $this;
	}
	/**
	 * Causes the Iterator to return an Unassociative Array for every row
	 *
	 * @return Reference back to result
	 */
	public function &asRow () {
		$this->fetchFunc = 'fetch_row';
		return $this;
	}
	/**
	 * Returns the number of results in the resultset.
	 *
	 * @return Number of results in this resultset
	 */
	public function count () {
		return $this->num_rows;
	}
	/**
	 * Returns the current row in the resultset Iterator. This method is 
	 * not called directly.
	 *
	 * @return Array or Object representing data in the current row of the 
	 * resultset based on any of the asXxx method calls
	 * @see http://php.net/manual/en/function.current.php
	 */
	public function current () {
		return $this->currentRow;
	}
	/**
	 * Returns the current row number of the resultset. This method is not
	 * called directly.
	 *
	 * @return Returns a number between 0 and mysqli_num_rows() - 1
	 * @see http://php.net/manual/en/function.key.php
	 */
	public function key () {
		return $this->rowNum;
	}
	/**
	 * Moves the Iterator forward. This method is called after 
	 * FMySQLiResult::valid so there is no worry of death during an 
	 * iteration. This method is not called directly.
	 *
	 * @see http://php.net/manual/en/function.next.php
	 */
	public function next () {
		$this->currentRow = $this->fetch();
		++$this->rowNum;
	}
	/**
	 * Places internal Iterator pointer at the beginning of the resultset.
	 * This method is called before the iteration starts, followed by 
	 * FMySQLiResult::valid. This method is not called directly.
	 *
	 * @see http://php.net/manual/en/function.rewind.php
	 */
	public function rewind () {
		$this->data_seek($this->rowNum = 0);
		$this->currentRow = $this->fetch();
	}
	/**
	 * Checks to see if there is another result. This is the "check"
	 * portion of the loop, where the existance of more results is 
	 * verified. This method is not called directly.
	 *
	 * @return False if there are no more results, true otherwise.
	 */
	public function valid () {
		return $this->rowNum < $this->num_rows;
	}
	/**
	 * Seeks to a specified position.
	 *
	 * @param $index position to seek to.
	 */
	public function seek ($index) {
		if ($this->data_seek($index)) {
			$this->rowNum = $index;
			$this->currentRow = $this->fetch();
		} else {
			throw new OutOfBoundsException('Index '.$index.' is invalid.');
		}
	}
	/**
	 * Using the fetching function specified by FMySQLiResult::as*(), grabs
	 * the next row in the resultset.
	 *
	 * @return Associative Array, Index Array, or Object (default) 
	 * representation of result row.
	 */
	public function fetch () {
		($this->fetchFunc === null) && $this->asObject();
		$fetchFunc =& $this->fetchFunc;
		return $this->$fetchFunc();
	}
	/**
	 */
	public function fetch_csv () {
		static $csvh;
		if (!$csvh) {
			$csvh = fopen('php://memory', 'r+');
		}
		if ($row = $this->fetch_row()) {
			$csv_bytes = fputcsv($csvh, $row);
			fseek($csvh, 0);
			$row = fread($csvh, $csv_bytes);
			ftruncate($csvh, 0);
		}
		return $row;
	}
	/**
	 */
	public function first () {
		$this->data_seek(0);
		$result = $this->fetch();
		$this->data_seek($this->rowNum);
		return $result;
	}
	/**
	 * Returns the query associated with this result. Useful if the query contained a lot of sprintf formatting.
	 * 
	 * @return Processed SQL query.
	 */
	public function getSQL () {
		return $this->query;
	}
	/**
	 */
	public function headers () {
		$fields = $this->fetch_fields();
		$headers = array();
		foreach ($fields as &$field) {
			$headers[] = $field->name;
		}
		if ($this->fetchFunc == 'fetch_csv') {
			$csvh = fopen('php://memory', 'r+');
			$csv_bytes = fputcsv($csvh, $headers);
			fseek($csvh, 0);
			$headers = fread($csvh, $csv_bytes);
			fclose($csvh);
		}
		return $headers;
	}
}
