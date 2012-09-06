<?php

namespace K;

/**
 * Request wrapper
 */
class Request {

	protected $ip;
	protected $referer;
	protected $method;
	protected $domain;
	protected $isAjax;
	protected $isSecure;
	protected $isMobile;
	protected $params;
	protected $url;
	protected $defaultLang = 'en';
	protected $lang;
	protected $data;
	protected $methods = array(
		'OPTIONS', 'GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'TRACE', 'CONNECT'
	);
	protected $mobileUserAgents = array(
		'Android', 'AvantGo', 'Blackberry', 'DoCoMo', 'iPod',
		'iPhone', 'J2ME', 'NetFront', 'Nokia', 'MIDP', 'Opera Mini',
		'PalmOS', 'PalmSource', 'Plucker', 'portalmmm',
		'ReqwirelessWeb', 'SonyEricsson', 'Symbian', 'UP\.Browser',
		'Windows CE', 'Xiino'
	);

	/**
	 * Create a new Request object
	 * @param array $options config array
	 */
	public function __construct($options = array()) {
		if (is_array($options)) {
			$this->configure($options);
		}
	}

	/**
	 * Configure the object
	 * @param array $options
	 */
	public function configure(array $options = array()) {
		foreach ($options as $k => $v) {
			$property = $k;
			$method = 'set' . ucfirst($property);
			if (method_exists($this, $method)) {
				$this->$method($v);
			} elseif (property_exists($this, $property)) {
				$this->property = $property;
			}
		}
	}

	/**
	 * Get the ip
	 * @return string
	 */
	public function ip() {
		if (!$this->ip) {
			if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$this->ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
				$this->ip = array_pop($this->ip);
			} else if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
				$this->ip = $_SERVER['HTTP_CLIENT_IP'];
			} else if (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
				$this->ip = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
			} else if (!empty($_SERVER['REMOTE_ADDR'])) {
				$this->ip = $_SERVER['REMOTE_ADDR'];
			}

			if (empty($this->ip) || filter_var($this->ip, FILTER_VALIDATE_IP) === false) {
				$this->ip = '0.0.0.0';
			}
		}
		return $this->ip;
	}

	/**
	 * Get the referer
	 * @return string
	 */
	public function referer() {
		if (!$this->referer) {
			$this->referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		}
		return $this->referer;
	}

	/**
	 * Get request method
	 * @return string
	 */
	public function method() {
		if (!$this->method) {
			$this->method = isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) ? strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) :
					(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET');

			//Allow method overriding
			if (isset($_GET['_method']) && in_array(strtoupper($_GET['_method']), $this->methods)) {
				$this->method = $_GET['_method'];
				unset($_GET['_method']);
			}
		}
		return $this->method;
	}

	/**
	 * Is ajax
	 * @return bool
	 */
	public function isAjax() {
		if (!$this->isAjax) {
			$this->isAjax = (bool) (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'));
		}
		return $this->isAjax;
	}

	/**
	 * Is secure
	 * @return bool
	 */
	public function isSecure() {
		if (!$this->isSecure) {
			$this->isSecure = (!empty($_SERVER['HTTPS']) && filter_var($_SERVER['HTTPS'], FILTER_VALIDATE_BOOLEAN)) ? true : false;
		}
		return $this->isSecure;
	}

	/**
	 * Is post
	 * @return bool
	 */
	public function isPost() {
		return $this->method() === 'POST';
	}

	/**
	 * Is get
	 * @return bool
	 */
	public function isGet() {
		return $this->method() === 'GET';
	}

	/**
	 * Is put
	 * @return bool
	 */
	public function isPut() {
		return $this->method() === 'PUT';
	}

	/**
	 * Is delete
	 * @return bool
	 */
	public function isDelete() {
		return $this->method() === 'DELETE';
	}

	/**
	 * Is mobile
	 * @return bool
	 */
	public function isMobile() {
		if (!$this->isMobile) {
			if (isset($_SERVER['HTTP_USER_AGENT'])) {
				$pattern = '/' . implode('|', $this->mobileUserAgents) . '/i';
				$this->isMobile = (boolean) preg_match($pattern, $_SERVER['HTTP_USER_AGENT']);
			}
		}
		return $this->isMobile;
	}

	/**
	 * Return all params merged (Cookies, Get, Post)
	 * @return array
	 */
	public function params() {
		if (!$this->params) {
			$this->params = array_merge($_COOKIE, $_GET, $_POST);
		}
		return $this->params;
	}

	/**
	 * Request url
	 * @return string
	 */
	public function url() {
		if (!$this->url) {
			$this->url = trim(isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : mb_substr($_SERVER['PHP_SELF'], mb_strlen($_SERVER['SCRIPT_NAME'])), '/');
		}
		return $this->url;
	}

	/**
	 * Request data (depending on the method)
	 * @return array
	 */
	public function data() {
		if (!$this->data) {
			$this->data = array();
			$method = $this->method();
			if ($method == 'POST') {
				$this->data = $_POST;
			} elseif ($method == 'GET') {
				$this->data = $_GET;
			} elseif ($method == 'PUT' || $method == 'DELETE') {
				$stream = fopen('php://input', 'r');
				parse_str(stream_get_contents($stream), $this->data);
				fclose($stream);
			}
		}
		return $this->data;
	}

	/**
	 * Domain (+ port)
	 * @return string
	 */
	public function domain() {
		if (!$this->domain) {
			$port = (isset($_SERVER['SERVER_PORT'])) ? $_SERVER['SERVER_PORT'] : null;
			if (isset($_SERVER['SERVER_NAME'])) {
				if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
					$this->domain = 'https://' . $_SERVER['SERVER_NAME'] . ($port && $port != 443 ? ':' . $port : '');
				} else {
					$this->domain = 'http://' . $_SERVER['SERVER_NAME'] . ($port && $port != 80 ? ':' . $port : '');
				}
			}
		}
		return $this->domain;
	}

	/**
	 * Request lang
	 * @return string
	 */
	public function lang() {
		if (!$this->lang) {
			$this->lang = $this->defaultLang;
			if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
				$accept_language = $_SERVER["HTTP_ACCEPT_LANGUAGE"];
				if (is_array($accept_language)) {
					$accept_language = $accept_language[0];
				}
				$this->lang = strtolower(substr($accept_language, 0, 2));
			}
		}
		return $this->lang;
	}

}