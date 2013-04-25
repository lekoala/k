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

	protected $path;
	
	public function __construct($path, $flags = null) {
		$this->path = $path;
		parent::__construct($path, $flags);
	}
	
	public function __toString() {
		return $this->path;
	}

}