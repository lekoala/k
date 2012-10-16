<?php

namespace K;

/**
 * Configurable interface. Implement with TConfigure trait.
 */
interface IConfigurable {
	public static function configure($config);
	public static function configureOption($key,$value);
}
