<?php

namespace k;

/**
 * Request
 *
 * @author lekoala
 */
class Request {

	const METHOD_OPTIONS = 'OPTIONS';
	const METHOD_GET = 'GET';
	const METHOD_HEAD = 'HEAD';
	const METHOD_POST = 'POST';
	const METHOD_PUT = 'PUT';
	const METHOD_DELETE = 'DELETE';
	const METHOD_TRACE = 'TRACE';
	const METHOD_CONNECT = 'CONNECT';

	/**
	 * @var array
	 */
	protected $inputs = null;

	/**
	 * @var array
	 */
	protected $headers = null;
	
	protected $forceAjax = false;
	protected $forceType = null;
	
	public function forceAjax($v = true) {
		$this->forceAjax = $v;
		return $this;
	}
	
	public function forceType($t = null) {
		$this->forceType = $t;
		return $this;
	}

	protected function getFromArray($var, $key, $default = null) {
		$loc = &$var;
		foreach (explode('/', $key) as $step) {
			if (isset($loc[$step])) {
				$loc = &$loc[$step];
			} else {
				return $default;
			}
		}
		return $loc;

		if (isset($var[$key])) {
			return $var[$key];
		}
		return $default;
	}

	/**
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function get($key, $default = null) {
		return $this->getFromArray($_GET, $key, $default);
	}

	/**
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function post($key, $default = null) {
		return $this->getFromArray($_POST, $key, $default);
	}

	/**
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function request($key, $default = null) {
		return $this->getFromArray($_REQUEST, $key, $default);
	}

	/**
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function server($key, $default = null) {
		return $this->getFromArray($_SERVER, $key, $default);
	}

	/**
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function cookie($key, $default = null) {
		return $this->getFromArray($_COOKIE, $key, $default);
	}

	/**
	 * @param string $key
	 * @param int $index
	 * @return bool|array
	 */
	public function file($key) {
		return $this->getFromArray($this->getFiles());
	}

	/**
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function input($key, $default = null) {
		$inputs = $this->generateInputs();
		return (isset($inputs[$key])) ? $inputs[$key] : $default;
	}

	/**
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function header($key, $default = null) {
		$headers = $this->getHeaders();
		return (isset($headers[$key])) ? $headers[$key] : $default;
	}

	/**
	 * @param string $v
	 * @param bool $all
	 * @return boolean
	 */
	public function accept($v = null, $all = false) {
		$accept = $this->server('HTTP_ACCEPT');
		if ($v === null) {
			return $accept;
		}
		if($v == $this->forceType) {
			return true;
		}
		if (!$accept) {
			return false;
		}
		$parts = explode(',', $accept);
		if (!$all) {
			return $parts[0] == $v;
		}
		foreach ($parts as $p) {
			if ($p == $v) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param bool $simple
	 * @return string
	 */
	public function language($simple = true) {
		$accept = $this->server('HTTP_ACCEPT_LANGUAGE');
		if ($simple) {
			return substr($accept, 0, 2);
		}
		return $accept;
	}

	/**
	 * @return mixed
	 */
	public function getRawInput() {
		return file_get_contents('php://input');
	}

	/**
	 * Get a fully formatted url of the request
	 * 
	 * http(s)://my.hostname.local:port/some/path(?with=querystrings)
	 * 
	 * @param bool $querystrings
	 * @return string
	 */
	public function getUrl($querystrings = true) {
		$protocol = $this->isSecure() ? 'https' : 'http';
		$server = $this->server('SERVER_NAME');
		if (($this->server('SERVER_PORT') != 80 && !$this->isSecure()) || ($this->server('SERVER_PORT') != 443 && $this->isSecure())) {
			$server .= $this->server('SERVER_PORT');
		}
		return $protocol . '://' . $server . $this->getPath($querystrings);
	}

	/**
	 * Get path part of the request
	 * 
	 * /some/path(?with=querystrings)
	 * 
	 * @param bool $querystrings
	 * @return string
	 */
	public function getPath($querystrings = true) {
		if (!$querystrings) {
			return preg_replace('#\?.*$#D', '', $this->server('REQUEST_URI'));
		}
		return $this->server('REQUEST_URI');
	}

	/**
	 * @return bool
	 */
	public function isAjax() {
		if($this->forceAjax) {
			return true;
		}
		return $this->server('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest';
	}

	/**
	 * @return bool
	 */
	public function isSecure() {
		return $this->server('HTTPS') === 'on';
	}

	/**
	 * @param string $v
	 * @return bool|string
	 */
	public function method($v = null) {
		if ($v !== null) {
			return $this->method() === strtoupper($v);
		}
		return strtoupper($this->server('REQUEST_METHOD', self::METHOD_GET));
	}

	public function in($key, $default = null, $filter = '') {
		if ($filter === '') {
			$filter = FILTER_SANITIZE_SPECIAL_CHARS;

			//smart default filter
			switch ($key) {
				case (preg_match('/page|p_?id$/', $key) ? true : false):
					$filter = FILTER_SANITIZE_NUMBER_INT;
					break;
				case 'order' :
				case 'sort' :
					$filter = array('asc', 'desc');
				case (preg_match('/_?email$/', $key) ? true : false):
					$filter = FILTER_SANITIZE_EMAIL;
					break;
				case (preg_match('/website|_?url$/', $key) ? true : false):
					$filter = FILTER_SANITIZE_URL;
					break;
			}
		}

		$v = $this->post($key);
		if (!$v) {
			$v = $this->get($key);
		}
		if ($v && $filter) {
			if (is_string($v)) {
				if (is_array($filter)) {
					if (!in_array($v, $filter)) {
						return $default;
					}
				} else {
					$v = filter_var($v, $filter);
				}
			}
		}
		if (!$v) {
			return $default;
		}
		return $v;
	}

	/**
	 * @param string $method
	 * @return array
	 */
	public function params() {
		switch ($this->method()) {
			case self::METHOD_GET:
				return $_GET;
				break;

			case self::METHOD_POST:
				return $_POST;
				break;

			default:
				return $this->getInputs();
				break;
		}
	}

	/**
	 * @return array
	 */
	protected function getInputs() {
		if ($this->inputs === null) {
			$requestInput = file_get_contents('php://input');

			switch ($this->getHeader('Content-Type')) {
				case 'application/json;charset=UTF-8':
				case 'application/json':
					$this->inputs = json_decode($requestInput, true);
					break;

				default:
					parse_str($requestInput, $this->inputs);
					break;
			}
		}

		return $this->inputs;
	}

	/**
	 * Rearrange file array in a clean way
	 * 
	 * array(
	 * 	'multiple' => array(
	 * 		array('name' => '...', ...)
	 * 	),
	 * 	'sub' => array(
	 * 		'key' => array('name' => '...') 
	 * 	),
	 * 	'simple' => array('name' => '...')
	 * )
	 * 
	 * @return array
	 */
	protected function getFiles() {
		if ($this->files === null) {
			$this->files = array();
			foreach ($_FILES as $key => $all) {
				foreach ($all as $i => $val) {
					$this->files[$i][$key] = $val;
				}
			}
		}
		return $this->files;
	}

	/**
	 * Get all headers
	 * 
	 * Sample array
	 * 
	 * array (size=8)
	 * 'Host' => string 'my.hostname.local' 
	 * 'Connection' => string 'keep-alive' (length=10)
	 * 'Cache-Control' => string 'max-age=0' (length=9)
	 * 'Accept' => string 'text/html,application/xhtml+xml,...
	 * 'User-Agent' => string 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3)...
	 * 'Accept-Encoding' => string 'gzip,deflate,sdch' (length=17)
	 * 'Accept-Language' => string 'en-US,en;q=0.8' (length=14)
	 * 'Cookie' => string 'PHPSESSID=la2k31g0mrcqe5s4q7am4vlum6' (length=36)
	 * 
	 * @return array
	 */
	protected function getHeaders() {
		if ($this->headers === null) {
			$this->headers = getallheaders();
		}

		return $this->headers;
	}

	public function __toString() {
		return $this->getUrl();
	}

}