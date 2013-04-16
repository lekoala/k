<?php

namespace K;

/**
 * Implements the IConfigurable interface
 */
trait TConfigure {

	/**
	 * Configure the class
	 * @param array|IConfig $options
	 * @return bool Was the options configured or not
	 */
	public static function configure($config) {
		//get class without namespace
		$class = explode('\\', get_called_class());
		$class = end($class);

		//if we have a config object, get the config values from the object
		if ($config instanceof IConfig) {
			$config = $config->get($class, array());
		}
		//if we have an array
		if (is_array($config)) {
			foreach ($config as $key => $value) {
				self::configureOption($key,$value);
			}
			return true;
		}
		return false;
	}

	/**
	 * Configure a single option of the class
	 * @param string $key
	 * @param mixed $value
	 * @return bool Was the option configured or not
	 */
	public static function configureOption($key, $value) {
		//camelCasedMethod
		$method = 'set' . ucfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
		$class = get_called_class();
		if (method_exists($class, $method)) {
			self::$method($value);
			return true;
		} elseif (property_exists($class, $key)) {
			self::$$key = $value;
			return true;
		}
		return false;
	}

}