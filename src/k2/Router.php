<?php

namespace k;

use \Exception;

class Router {

	use TConfigure;

	protected static $defaultRoutes = array(
		'(:controller!alpha)?/(:action!alpha)?/(:id!digit)?/(:extra!slug)?' => 'main'
	);
	protected static $expressions = array(
		'word' => '[a-zA-Z0-9-_]+',
		'lang' => '[a-zA-Z]{2}',
		'isolang' => '[a-zA-Z]{3}',
		'alnum' => '[a-zA-Z0-9]+',
		'alpha' => '[a-zA-Z]+',
		'digit' => '[0-9]+',
		'slug' => '[a-zA-Z0-9-_\/]+',
		'date' => '[0-9]{4}-[0-9]{2}-[0-9]{2}',
	);
	protected $routes;

	public function __construct($routes = null) {
		if (!$routes) {
			$this->routes = self::$defaultRoutes;
		}
	}

	/**
	 * Match an url with the current routes
	 * @return array
	 */
	public function match($url) {
		$url = trim($url, '/');
		$url = strtok($url, '?');

		$foundMap = false;
		$foundPrefix = null;

		foreach ($this->routes as $route => $map) {
			$regex = self::transformRule($route);

			if (preg_match("/$regex/i", $url, $matches)) {
				$foundMap = $map;
				$params = array();
				foreach ($matches as $k => $v) {
					// Store named params
					if (!is_numeric($k)) {
						$params[$k] = $v;
					}
				}
				break;
			}
		}

		if (!$foundMap) {
			throw new Exception('URL ' . $url . ' not found');
		}

		//make sure all params are present
		preg_match_all('/<([a-z]*)>/i', $regex, $matches);
		if (!empty($matches)) {
			foreach ($matches[1] as $k) {
				if (!isset($params[$k])) {
					$params[$k] = '';
				}
			}
		}


		$handler = $foundMap;

		return compact('handler', 'params');
	}

	/**
	 * Transform a router rule to a valid regex
	 * 
	 * @param string $rule
	 * @return string 
	 */
	protected static function transformRule($rule) {
		// Escape slashes
		$regex = str_replace('/', '\/', $rule);

		// Friendlier named expressions
		$regex = preg_replace('/:([a-z]*)/', '?P<$1>', $regex);

		// If not specified, type is a word
		$regex = str_replace('>)', '>!word)', $regex);

		// Friendlier expressions
		foreach (self::$expressions as $expression => $repl) {
			$regex = str_replace('!' . $expression, $repl, $regex);
		}

		// A slash before an optional argument should be optional
		$regex = str_replace('?\/', '?\/?', $regex);

		// Add start and end
		$regex = '^' . $regex . '\/?$';

		return $regex;
	}

}