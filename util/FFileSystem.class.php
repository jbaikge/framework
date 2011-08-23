<?php
/*!
 * Beefed up filesystem functions. These methods are meant to aide in handling 
 * common filesystem tasks.
 *
 * @author Jacob Tews <jacob@webteks.com>
 * @date Fri Mar  7 21:50:22 EST 2008
 * @version $Id$
 */
class FFileSystem {
	/*!
	 * Check if a file exists in the include_path. If the file is found in the 
	 * include_path or absolute path, the full, resolved, path is returned. If
	 * the file is not found, @c false is returned instead.
	 *
	 * A common implementation of this method:
	 * @code
	 * if ($filename = FFileSystem::fileExists('path/to/my/file.txt')) {
	 *     // do something with $filename
	 * }
	 * @endcode
	 *
	 * @param $filename Full or partial filename
	 * @return Full file path if found, @c false otherwise
	 */
	static function fileExists ($filename) {
		if (file_exists($filename)) {
			return $filename;
		}
		$paths = explode(PATH_SEPARATOR, get_include_path());
		foreach ($paths as &$path) {
			$fullpath = $path . DIRECTORY_SEPARATOR . $filename;
			if (file_exists($fullpath)) {
				return realpath(str_replace('//', '/', $fullpath));
			}
		}
		return false;
	}
}
