<?php

namespace k\fs;

use \FilesystemIterator;
use \InvalidArgumentException;

/**
 * Directory
 *
 * @author lekoala
 */
class Directory extends FilesystemIterator {

	protected $dir;
	
	public function __construct($path, $flags = null) {
		$this->dir = $path;
		parent::__construct($path, \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS);
	}
	
	public function getOriginalDir() {
		return $this->dir;
	}

}