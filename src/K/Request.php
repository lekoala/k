<?php

namespace K;

/**
 * Request utility
 */
class Request {

	protected static $ip;
	protected static $referer;
	protected static $method;
	protected static $domain;
	protected static $isAjax;
	protected static $isSecure;
	protected static $isMobile;
	protected static $params;
	protected static $url;
	protected static $defaultLang = 'en';
	protected static $lang;
	protected static $data;
	protected static $methods = array(
		'OPTIONS', 'GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'TRACE', 'CONNECT'
	);
	protected static $mobileUserAgents = array(
		'Android', 'AvantGo', 'Blackberry', 'DoCoMo', 'iPod',
		'iPhone', 'J2ME', 'NetFront', 'Nokia', 'MIDP', 'Opera Mini',
		'PalmOS', 'PalmSource', 'Plucker', 'portalmmm',
		'ReqwirelessWeb', 'SonyEricsson', 'Symbian', 'UP\.Browser',
		'Windows CE', 'Xiino'
	);

	public static function configure($config) {
		if ($config instanceof K\Config) {
			$class = end(explode('\\', get_called_class()));
			$config = $config->get($class, array());
		}
		if (is_array($config)) {
			foreach ($config as $k => $v) {
				$method = 'set' . lcfirst($k);
				if (method_exists($this, $method)) {
					self::$method($v);
				} else {
					self::$k = $v;
				}
			}
		}
	}

	/**
	 * Get the ip
	 * @return string
	 */
	public static function ip() {
		if (!self::$ip) {
			if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				self::$ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
				self::$ip = array_pop(self::$ip);
			} else if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
				self::$ip = $_SERVER['HTTP_CLIENT_IP'];
			} else if (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
				self::$ip = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
			} else if (!empty($_SERVER['REMOTE_ADDR'])) {
				self::$ip = $_SERVER['REMOTE_ADDR'];
			}

			if (empty(self::$ip) || filter_var(self::$ip, FILTER_VALIDATE_IP) === false) {
				self::$ip = '0.0.0.0';
			}
		}
		return self::$ip;
	}

	/**
	 * Get the referer
	 * @return string
	 */
	public static function referer() {
		if (!self::$referer) {
			self::$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		}
		return self::$referer;
	}

	/**
	 * Get request method
	 * @return string
	 */
	public static function method() {
		if (!self::$method) {
			self::$method = isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) ? strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) :
					(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET');

			//Allow method overriding
			if (isset($_GET['_method']) && in_array(strtoupper($_GET['_method']), self::$methods)) {
				self::$method = $_GET['_method'];
				unset($_GET['_method']);
			}
		}
		return self::$method;
	}

	/**
	 * Is ajax
	 * @return bool
	 */
	public static function isAjax() {
		if (!self::$isAjax) {
			self::$isAjax = (bool) (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'));
		}
		return self::$isAjax;
	}

	/**
	 * Is secure
	 * @return bool
	 */
	public static function isSecure() {
		if (!self::$isSecure) {
			self::$isSecure = (!empty($_SERVER['HTTPS']) && filter_var($_SERVER['HTTPS'], FILTER_VALIDATE_BOOLEAN)) ? true : false;
		}
		return self::$isSecure;
	}

	/**
	 * Is post
	 * @return bool
	 */
	public static function isPost() {
		return self::method() === 'POST';
	}

	/**
	 * Is get
	 * @return bool
	 */
	public static function isGet() {
		return self::method() === 'GET';
	}

	/**
	 * Is put
	 * @return bool
	 */
	public static function isPut() {
		return self::method() === 'PUT';
	}

	/**
	 * Is delete
	 * @return bool
	 */
	public static function isDelete() {
		return self::method() === 'DELETE';
	}

	/**
	 * Is mobile
	 * @return bool
	 */
	public static function isMobile() {
		if (!self::$isMobile) {
			if (isset($_SERVER['HTTP_USER_AGENT'])) {
				$pattern = '/' . implode('|', self::$mobileUserAgents) . '/i';
				self::$isMobile = (boolean) preg_match($pattern, $_SERVER['HTTP_USER_AGENT']);
			}
		}
		return self::$isMobile;
	}

	/**
	 * Return all params merged (Cookies, Get, Post)
	 * @return array
	 */
	public static function params() {
		if (!self::$params) {
			self::$params = array_merge($_COOKIE, $_GET, $_POST);
		}
		return self::$params;
	}

	/**
	 * Request url
	 * @return string
	 */
	public static function url() {
		if (!self::$url) {
			self::$url = trim(isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : mb_substr($_SERVER['PHP_SELF'], mb_strlen($_SERVER['SCRIPT_NAME'])), '/');
		}
		return self::$url;
	}

	/**
	 * Request data (depending on the method)
	 * @return array
	 */
	public static function data() {
		if (!self::$data) {
			self::$data = array();
			$method = self::method();
			if ($method == 'POST') {
				self::$data = $_POST;
			} elseif ($method == 'GET') {
				self::$data = $_GET;
			} elseif ($method == 'PUT' || $method == 'DELETE') {
				$stream = fopen('php://input', 'r');
				parse_str(stream_get_contents($stream), self::$data);
				fclose($stream);
			}
		}
		return self::$data;
	}

	/**
	 * Domain (+ port)
	 * @return string
	 */
	public static function domain() {
		if (!self::$domain) {
			$port = (isset($_SERVER['SERVER_PORT'])) ? $_SERVER['SERVER_PORT'] : null;
			if (isset($_SERVER['SERVER_NAME'])) {
				if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
					self::$domain = 'https://' . $_SERVER['SERVER_NAME'] . ($port && $port != 443 ? ':' . $port : '');
				} else {
					self::$domain = 'http://' . $_SERVER['SERVER_NAME'] . ($port && $port != 80 ? ':' . $port : '');
				}
			}
		}
		return self::$domain;
	}

	/**
	 * Request lang
	 * @return string
	 */
	public static function lang() {
		if (!self::$lang) {
			self::$lang = self::$defaultLang;
			if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
				$accept_language = $_SERVER["HTTP_ACCEPT_LANGUAGE"];
				if (is_array($accept_language)) {
					$accept_language = $accept_language[0];
				}
				self::$lang = strtolower(substr($accept_language, 0, 2));
			}
		}
		return self::$lang;
	}

}