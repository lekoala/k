<?php

namespace k\fs;

/**
 * Abstract storage to allow storage accross any system
 *
 * @author lekoala
 */
interface StorageInterface {
	public function find($name);
}