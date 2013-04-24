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

	public function __construct($path) {
		if(!is_dir($path)) {
			throw new InvalidArgumentException('Path should be a dir');
		}
		parent::__construct($path);
	}

}