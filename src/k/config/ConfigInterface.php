<?php

namespace k\config;

/**
 * ConfigInterface
 *
 * @author lekoala
 */
interface ConfigInterface {

	public function get($key, $default = null);

}