<?php
/**
 * Beefed up filesystem functions. These methods are meant to aide in handling 
 * common filesystem tasks.
 *
 * @author Jacob Tews <jacob@webteks.com>
 * @date Fri Mar  7 21:50:22 EST 2008
 * @version $Id$
 */
class FFileSystem {
	/**
	 * Check if a file exists in the include_path.
	 *
	 * @param $filename Full or partial filename
	 * @return Full file path if found, false otherwise
	 */
	static function fileExists ($filename) {
		if (file_exists($filename)) {
			return $filename;
		}
		$paths = explode(PATH_SEPARATOR, get_include_path());
		foreach ($paths as &$path) {
			$fullpath = $path . DIRECTORY_SEPARATOR . $filename;
			if (file_exists($fullpath)) {
				return str_replace('//', '/', $fullpath);
			}
		}
		return false;
	}
}
