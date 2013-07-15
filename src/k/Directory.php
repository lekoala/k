<?php

namespace k;

use \FilesystemIterator;

/**
 * Directory
 *
 * @link https://github.com/Kappa-app/FileSystem/blob/master/src/Kappa/FileSystem/Directory.php
 * 
 * @author lekoala
 */
class Directory extends FilesystemIterator {

	protected $dir;

	public function __construct($path, $flags = null) {
		$this->dir = $path;
		parent::__construct($path, FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::SKIP_DOTS);
	}

	public function getOriginalDir() {
		return $this->dir;
	}

	protected function recursiveEmptyDir($dirname, $self_delete = false) {
		if (is_dir($dirname))
			$dir_handle = opendir($dirname);
		if (!$dir_handle)
			return false;
		while ($file = readdir($dir_handle)) {
			if ($file != "." && $file != "..") {
				if (!is_dir($dirname . "/" . $file))
					@unlink($dirname . "/" . $file);
				else
					$this->recursiveEmptyDir($dirname . '/' . $file, true);
			}
		}
		closedir($dir_handle);
		if ($self_delete) {
			@rmdir($dirname);
		}
		return true;
	}

	public function makeEmpty() {
		return $this->recursiveEmptyDir($this->getOriginalDir());
	}

	public function rm() {
		$this->makeEmpty();
		unlink($this->getOriginalDir());
	}

}