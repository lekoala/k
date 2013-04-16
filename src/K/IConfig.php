<?php

namespace K;

/**
 * Config object interface
 */
interface IConfig {
	public function load($file);
	public function get($key,$default);
}