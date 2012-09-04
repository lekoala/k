<?php

namespace K;

use \Exception;

class Router {

	public static $routes = array(
		'(:controller!alpha)?/(:action!alpha)?/(:id!digit)?/(:extra!slug)?' => 'main'
	);
	
	protected $url;
	protected $expressions = array(
		'word' => '[a-zA-Z0-9-_]+',
		'alnum' => '[a-zA-Z0-9]+',
		'alpha' => '[a-zA-Z]+',
		'digit' => '[0-9]+',
		'slug' => '[a-zA-Z0-9-_\/]+',
		'date' => '[0-9]{4}-[0-9]{2}-[0-9]{2}',
	);
	protected $map;
	protected $params;
	protected $prefix;
	protected $prefixRule;
	protected static $logs;

	function __construct($url = null, $routes = null) {
		if ($url) {
			$this->setUrl($url);
		}
		if ($routes) {
			$this->setRoutes($routes);
		}
	}

	function setUrl($url) {
		$this->url = $url;
		return $this;
	}

	function setRoutes($routes) {
		$this->routes = $routes;
		return $this;
	}

	function setPrefix($name, $rule = 'word') {
		$this->prefix = $name;
		$this->prefixRule = $rule;
	}

	/**
	 * Route the url and match it to a set of routes
	 * @return RouterResult
	 */
	function find() {
		$url = $this->url;
		$url = trim($url, '/');

		$foundMap = false;
		$allParams = array();

		if ($this->prefix) {
			$urlParts = explode('/', $url);
			$prefixRule = $this->transformRegex($this->prefixRule);
			if (!empty($urlParts) && preg_match($prefixRule, $urlParts[0])) {
				$allParams[$this->prefix] = array_shift($urlParts);
				$url = implode('/', $urlParts);
			}
		}

		foreach ($this->routes as $route => $map) {
			$regex = $this->transformRule($route);

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

		// Ensure all params are always there
		foreach ($allParams as $param) {
			if (!isset($params[$param])) {
				$params[$param] = '';
			}
		}

		$result = new Router\Result();
		$result->handler = $foundMap;
		$result->params = $params;
		$result->regex = $regex;
		$result->url = $url;
		return $result;
	}

	/**
	 * Transform a router rule to a valid regex
	 * 
	 * @param string $rule
	 * @return string 
	 */
	protected function transformRule($rule) {
		// Escape slashes
		$regex = str_replace('/', '\/', $rule);

		// Friendlier named expressions
		$regex = preg_replace('/:([a-z]*)/', '?P<$1>', $regex);
		preg_match_all('/<([a-z]*)>/i', $regex, $matches);
		if (!empty($matches)) {
			$allParams = $matches[1];
		}
		// If not specified, type is a word
		$regex = str_replace('>)', '>!word)', $regex);

		// Friendlier expressions
		foreach ($this->expressions as $expression => $repl) {
			$regex = str_replace('!' . $expression, $repl, $regex);
		}

		// Optional slashes
		$regex = str_replace('?\/', '?\/?', $regex);

		// Add start and end
		$regex = '^' . $regex . '\/?$';
		
		return $regex;
	}

}

class RouterResult {

	public $handler;
	public $params;
	public $regex;
	public $url;

	public function __toString() {
		return 'Url ' . $this->url . ' was matched with ' . htmlentities($this->regex) . ' => handler : ' . $this->handler . ' ' . json_encode($this->params);
	}

}