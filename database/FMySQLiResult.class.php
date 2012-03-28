<?php
/*!
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
 * 	echo $row[0], ': ', $row[1], "\n";
 * }
 *
 * // Associative array of column names => values
 * foreach (FDB::query("SELECT id, name FROM table")->asAssoc() as $row) {
 * 	echo $row["id"], ': ', $row['name'], "\n";
 * }
 *
 * // Object representation of row (default)
 * foreach (FDB::query("SELECT id, name FROM table")->asObject() as $row) {
 * 	echo $row->id, ': ', $row->name, "\n";
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
class FMySQLiResult implements Countable, SeekableIterator {
	private $currentRow; ///< Holds the current row in the Iterator
	private $rowNum; ///< Incremented during iteration over the resultset
	private $fetchFunc; ///< Function to use when returning results
	private $fetchClassName; ///< Classname to use when using fetch_class
	private $result; ///< MySQLi Result held internally
	private $passThru = true; ///< Whether to pass iterator requests to result
	public $query; ///< SQL query represented by this result
	public function __construct($result, $mode = MYSQLI_STORE_RESULT) {
		$this->result = new mysqli_result($result, $mode);
	}
	/*!
	 * Returns all rows. If the MySQL Native Driver is installed and the row
	 * type is Assoc or Row, the native mysqli_result::fetch_all() method is
	 * used. Otherwise, all the elements are returned via normal traversal.
	 * 
	 * @return Array of all rows, based on the type specified with the ->as*
	 * method.
	 */
	public function all() {
		if ($this->passThru && method_exists($this->result, 'fetch_all')) {
			switch ($this->fetchFunc) {
				case 'fetch_assoc':
					return $this->result->fetch_all(MYSQLI_ASSOC);
				case 'fetch_row':
					return $this->result->fetch_all(MYSQLI_NUM);
				default:
					// fall down to manual generation
			}
		}
		return iterator_to_array($this);
	}
	/*!
	 * Causes the Iterator to return an Associative Array for every row
	 *
	 * @return Reference back to result
	 */
	public function &asAssoc () {
		$this->fetchFunc = 'fetch_assoc';
		$this->passThru = true;
		return $this;
	}
	/*!
	 * Causes the Iterator to return a class object specified by the 
	 * @c $classname parameter, initialized with the data from the row. The row
	 * data is always passed in as an associative array.
	 * 
	 * @return Reference back to result
	 */
	public function &asClass ($classname) {
		$this->fetchFunc = 'fetch_class';
		$this->fetchClassName = $classname;
		$this->passThru = false;
		return $this;
	}
	/*!
	 * Causes the Iterator to return a string with the field data
	 * as a CSV.
	 *
	 * @return Reference back to result
	 */
	public function &asCSV () {
		$this->fetchFunc = 'fetch_csv';
		$this->passThru = false;
		return $this;
	}
	/*!
	 * Causes the Iterator to return an Object for every row
	 *
	 * @return Reference back to result
	 */
	public function &asObject () {
		$this->fetchFunc = 'fetch_object';
		$this->passThru = true;
		return $this;
	}
	/*!
	 * Causes the Iterator to return an Unassociative Array for every row
	 *
	 * @return Reference back to result
	 */
	public function &asRow () {
		$this->fetchFunc = 'fetch_row';
		$this->passThru = true;
		return $this;
	}
	/*!
	 * Returns the number of results in the resultset.
	 *
	 * @return Number of results in this resultset
	 */
	public function count () {
		return $this->result->num_rows;
	}
	/*!
	 * Returns the current row in the resultset Iterator. This method is 
	 * not called directly.
	 *
	 * @return Array or Object representing data in the current row of the 
	 * resultset based on any of the asXxx method calls
	 * @see http://php.net/manual/en/function.current.php
	 */
	public function current () {
		return $this->currentRow = $this->fetch();
	}
	/*!
	 * Returns the current row number of the resultset. This method is not
	 * called directly.
	 *
	 * @return Returns a number between 0 and mysqli_num_rows() - 1
	 * @see http://php.net/manual/en/function.key.php
	 */
	public function key () {
		return $this->rowNum;
	}
	/*!
	 * Moves the Iterator forward. This method is called after 
	 * FMySQLiResult::valid so there is no worry of death during an 
	 * iteration. This method is not called directly.
	 *
	 * @see http://php.net/manual/en/function.next.php
	 */
	public function next () {
		//$this->currentRow = $this->fetch();
		++$this->rowNum;
	}
	/*!
	 * Places internal Iterator pointer at the beginning of the resultset.
	 * This method is called before the iteration starts, followed by 
	 * FMySQLiResult::valid. This method is not called directly.
	 *
	 * @see http://php.net/manual/en/function.rewind.php
	 */
	public function rewind () {
		$this->seek(0);
	}
	/*!
	 * Checks to see if there is another result. This is the "check"
	 * portion of the loop, where the existance of more results is 
	 * verified. This method is not called directly.
	 *
	 * @return False if there are no more results, true otherwise.
	 */
	public function valid () {
		return $this->rowNum < $this->result->num_rows;
	}
	/*!
	 * Seeks to a specified position.
	 *
	 * @param $index position to seek to.
	 */
	public function seek ($index) {
		if ($this->result->data_seek($index)) {
			$this->rowNum = $index;
		} else if ($index > 0) {
			throw new OutOfBoundsException('Index '.$index.' is invalid.');
		}
	}
	/*!
	 * Using the fetching function specified by FMySQLiResult::as*(), grabs
	 * the next row in the resultset.
	 *
	 * @return Associative Array, Indexed Array, or Object (default) 
	 * representation of result row.
	 */
	public function fetch () {
		($this->fetchFunc === null) && $this->asObject();
		$fetchFunc =& $this->fetchFunc;
		if ($this->passThru) {
			return $this->result->$fetchFunc();
		} else {
			return $this->$fetchFunc();
		}
	}
	/*!
	 * Custom implementation for fetch() to cast the result row as a class. The
	 * class is determined in a few different ways:
	 * 
	 * If ->asClass("classname") is used, that class is ALWAYS used for the row.
	 * 
	 * If ->asClass(null) is used, and the column, _class, exists, the value in
	 * _class is used.
	 * 
	 * If neither of the above exist, null is returned.
	 * 
	 * In the former two instances, data is sent to the constructor as an
	 * associative array of the row's data.
	 * 
	 * @return Object of the type defined based on the description. null if no
	 * class name is found.
	 */
	public function fetch_class () {
		$classname = $this->fetchClassName;
		$row = $this->result->fetch_assoc();
		if ($classname == null && isset($row['_class'])) {
			$classname = $row['_class'];
		} else if ($classname == null) {
			return null;
		}
		return new $classname($row);
	}
	/*!
	 * Custom implementation of a result row fetcher to return values in a CSV
	 * format. Compliments @c fetch_assoc, @c fetch_object, and @c fetch_row.
	 * 
	 * This method utilizes @c fputcsv() with default values for separator and
	 * textual encapsulation. These defaults have proven to be sufficient for
	 * everyday use and prove the most compatible amongst spreadsheet programs.
	 * 
	 * As with the other fetch_* methods, this method is not designed to be
	 * called directly. To utilize this method, consider the following:
	 * 
	 * @code
	 * $result = FDB::query("SELECT 1");
	 * $result->asCSV();
	 * echo $result->fetch();
	 * @endcode
	 * 
	 * A more concise version of the above with looping:
	 * @code
	 * $result = FDB::query("SELECT a, b FROM tbl")->asCSV();
	 * echo $result->headers();
	 * foreach ($result as $row) {
	 *     echo $row;
	 * }
	 * @endcode
	 * 
	 * @return String of this result row's values formatted as a CSV.
	 */
	public function fetch_csv () {
		static $csvh;
		if (!$csvh) {
			$csvh = fopen('php://memory', 'r+');
		}
		if ($row = $this->result->fetch_row()) {
			$csv_bytes = fputcsv($csvh, $row);
			fseek($csvh, 0);
			$row = fread($csvh, $csv_bytes);
			ftruncate($csvh, 0);
		}
		return $row;
	}
	/*!
	 * Fetches the first row of this result. This method will rewind to the
	 * beginning of a resultset, fetch the first row, and then restore the 
	 * internal pointer's position.
	 * 
	 * An example implementation:
	 * @code
	 * $counter = 0;
	 * $result = FDB::query("SELECT id, name FROM users");
	 * foreach ($result as $row) {
	 *     if (++$counter % 10 == 0) {
	 *         printf("First: %d %s", $row->id, $row->name)
	 *     }
	 *     printf("%d %d %s\n", $counter++, $row->id, $row->name);
	 * }
	 * @endcode
	 * 
	 * @see #fetch()
	 * @return Associative Array, Indexed Array, or Object (default) 
	 * representation of first result row.
	 */
	public function first () {
		$this->result->data_seek(0);
		$result = $this->fetch();
		$this->result->data_seek($this->rowNum);
		return $result;
	}
	/*!
	 * Returns the query associated with this result. Useful if the query 
	 * contained a lot of sprintf formatting.
	 * 
	 * @return Processed SQL query.
	 */
	public function getSQL () {
		return $this->query;
	}
	/*!
	 * Provides the field headers for this result. If the return type of the
	 * query is @b row (indexed array), @b assoc (associative array), or @b
	 * object, the headers are returned as an array. If the return type of the 
	 * query is @b csv, the headers are returned as a string of comma-separated
	 * values.
	 * 
	 * @return Result headers as described above.
	 */
	public function headers () {
		$fields = $this->result->fetch_fields();
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
