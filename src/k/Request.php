<?php

namespace k;

class Request {

	protected $ip;
	protected $referer;
	protected $method;
	protected $domain;
	protected $isAjax;
	protected $isSecure;
	protected $isMobile;
	protected $params = array();
	protected $url;
	protected $lang = 'en';
	protected $data = array();
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

	public function __construct() {
		$this->url = trim(isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : mb_substr($_SERVER['PHP_SELF'], mb_strlen($_SERVER['SCRIPT_NAME'])), '/');

		// Detect ip
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

		// Referer
		$this->referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

		// Method
		$this->method = isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) ? strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) :
				(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET');


		if (isset($_GET['_method']) && in_array(strtoupper($_GET['_method']), $this->methods)) {
			$this->method = $_GET['_method'];
			unset($_GET['_method']);
		}

		// Data
		if ($this->method == 'POST') {
			$this->data = $_POST;
		} elseif ($this->method == 'GET') {
			$this->data = $_GET;
		} elseif ($this->method == 'PUT' || $this->method == 'DELETE') {
			$stream = fopen('php://input', 'r');
			parse_str(stream_get_contents($stream), $this->data);
			fclose($stream);
		}

		// Ajax
		$this->isAjax = (bool) (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'));

		// Https
		$this->isSecure = (!empty($_SERVER['HTTPS']) && filter_var($_SERVER['HTTPS'], FILTER_VALIDATE_BOOLEAN)) ? true : false;

		// Mobile
		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$pattern = '/' . implode('|', $this->mobileUserAgents) . '/i';
			$this->isMobile = (boolean) preg_match($pattern, $_SERVER['HTTP_USER_AGENT']);
		}

		// Domain
		$port = (isset($_SERVER['SERVER_PORT'])) ? $_SERVER['SERVER_PORT'] : null;
		if(isset($_SERVER['SERVER_NAME'])) {
			if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
				$this->domain = 'https://' . $_SERVER['SERVER_NAME'] . ($port && $port != 443 ? ':' . $port : '');
			} else {
				$this->domain = 'http://' . $_SERVER['SERVER_NAME'] . ($port && $port != 80 ? ':' . $port : '');
			}
		}

		// Language
		if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
			$accept_language = $_SERVER["HTTP_ACCEPT_LANGUAGE"];
			if (is_array($accept_language)) {
				$accept_language = $accept_language[0];
			}
			$this->lang = strtolower(substr($accept_language, 0, 2));
		}

		// Params
		$this->params = array_merge($_COOKIE, $_GET, $_POST);
	}

	public function ip() {
		return $this->ip;
	}

	public function referer() {
		return $this->referer;
	}

	public function method() {
		return $this->method;
	}

	public function isAjax() {
		return $this->isAjax;
	}

	public function isSecure() {
		return $this->isSecure;
	}

	public function isPost() {
		return $this->method() === 'POST';
	}

	public function isGet() {
		return $this->method() === 'GET';
	}

	public function isPut() {
		return $this->method() === 'PUT';
	}

	public function isDelete() {
		return $this->method() === 'DELETE';
	}

	public function isMobile() {
		return $this->isMobile;
	}

	public function params() {
		return $this->params;
	}

	public function url() {
		return $this->url;
	}

	public function data() {
		return $this->data;
	}

	public function domain() {
		return $this->domain;
	}
	
	public function lang() {
		return $this->lang;
	}
}
