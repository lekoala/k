<?php

namespace k\http;

/**
 * Wrap http related functionnalities
 */
class Http {

	protected $get;
	protected $post;
	protected $cookie;
	protected $files;
	protected $server;
	protected $rawData;
	protected $ajax;
	protected $secure;
	protected $method;
	protected $httpCodes = array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		102 => 'Processing',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-Status',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		306 => 'Switch Proxy',
		307 => 'Temporary Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		418 => 'I\'m a teapot',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Failed Dependency',
		425 => 'Unordered Collection',
		426 => 'Upgrade Required',
		449 => 'Retry With',
		450 => 'Blocked by Windows Parental Controls',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		506 => 'Variant Also Negotiates',
		507 => 'Insufficient Storage',
		509 => 'Bandwidth Limit Exceeded',
		510 => 'Not Extended'
	);

	public function __construct() {
		$this->setGet($_GET);
		$this->setPost($_POST);
		$this->setCookie($_COOKIE);
		$this->setFiles($_FILES);
		$this->setServer($_SERVER);
	}

	public function getGet() {
		return $this->get;
	}

	public function setGet(array $get) {
		$this->get = $get;
		return $this;
	}

	public function getPost() {
		return $this->post;
	}

	public function setPost(array $post) {
		$this->post = $post;
		return $this;
	}

	public function getCookie() {
		return $this->cookie;
	}

	public function setCookie(array $cookie) {
		$this->cookie = $cookie;
		return $this;
	}

	public function getFiles() {
		return $this->files;
	}

	public function setFiles(array $files) {
		$this->files = $files;
		return $this;
	}

	public function getServer() {
		return $this->server;
	}

	public function setServer(array $server) {
		$this->server = $server;
		return $this;
	}

	public function get($key, $default = null) {
		return $this->_get('get', $key, $default);
	}

	public function post($key, $default = null) {
		return $this->_get('post', $key, $default);
	}

	public function server($key, $default = null) {
		$key = strtoupper($key);
		if (isset($this->server[$key])) {
			return $this->server[$key];
		}
		return $default;
	}

	public function accept($v = null, $all = false) {
		$accept = $this->server('http_accept');
		if ($v === null) {
			return $v;
		}
		if (!$accept) {
			return true;
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

	public function acceptLanguage($simple = true) {
		$accept = $this->server('http_accept_language');
		if ($simple) {
			return substr($accept, 0, 2);
		}
		return $accept;
	}

	public function in($key, $default = null, $filter = '') {
		if ($filter === '') {
			$filter = FILTER_SANITIZE_SPECIAL_CHARS;

			//smart default filter
			switch ($key) {
				case 'id' :
				case self::endsWith($key, '_id'):
				case 'page' :
				case 'p' :
					$filter = FILTER_SANITIZE_NUMBER_INT;
					break;
				case 'order' :
				case 'sort' :
					$filter = array('asc', 'desc');
				case 'email':
				case self::endsWith($key, '_email'):
					$filter = FILTER_SANITIZE_EMAIL;
					break;
				case 'url':
				case self::endsWith($key, '_url'):
				case 'website' :
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

	protected function _get($var, $key, $default = null) {
		$var = $this->$var;

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

	public function isAjax() {
		if ($this->ajax === null) {
			$this->ajax = $this->server('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest';
		}
		return $this->ajax;
	}

	public function isSecure() {
		if ($this->secure === null) {
			$this->secure = $this->server('HTTPS') === 'on';
		}
		return $this->secure;
	}

	public function isPost() {
		return $this->getMethod() === 'POST';
	}

	public function isGet() {
		return $this->getMethod() === 'GET';
	}

	public function isPut() {
		return $this->getMethod() === 'PUT';
	}

	public function isDelete() {
		return $this->getMethod() === 'DELETE';
	}

	public function getMethod() {
		if ($this->method === null) {
			$this->method = strtoupper($this->server('HTTP_X_HTTP_METHOD_OVERRIDE', $this->server('REQUEST_METHOD', 'GET')));
		}
		return $this->method;
	}

	public function getUrl($querystrings = true) {
		$protocol = $this->isSecure() ? 'https' : 'http';
		$server = $this->server('server_name');
		if (($this->server('server_port') != 80 && !$this->isSecure()) || ($this->server('server_port') != 443 && $this->isSecure())) {
			$server .= $this->server('server_port');
		}
		return $protocol . '://' . $server . $this->getPath($querystrings);
	}

	public function getPath($querystrings = true) {
		if (!$querystrings) {
			return preg_replace('#\?.*$#D', '', $this->server('request_uri'));
		}
		return $this->server('request_uri');
	}

	public function getRawData() {
		if ($this->rawData === null) {
			$this->rawData = file_get_contents('php://input');
		}
		return $this->rawData;
	}

	public function redirect($url, $code = 302, $html = false) {
		$this->header('Location', $url);
		if ($html) {
			$this->htmlRedirect($url);
		}
		exit();
	}

	public function htmlRedirect($url) {
		echo '<meta http-equiv="refresh" content="1;url=' . $url . '" />';
		echo '<script type="text/javascript">
				if (top.location != location) { 
					top.location.href = "' . $url . '"
				}
				else {
					location.href = "' . $url . '"
				}
			</script>';
		exit($url);
	}

	public function redirectBack() {
		$url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
		if (isset($_SESSION['_back_url'])) {
			$url = $_SESSION['_back_url'];
		}
		if (isset($_GET['_back_url'])) {
			$url = $_GET['_back_url'];
		}

		if ($url != $currentUrl) {
			$this->redirect($url);
		}
	}

	public function header($key, $value = null, $check = false) {
		if ($check && !headers_sent()) {
			if ($value !== null) {
				$key = $key . ': ' . $value;
			}
			header($key);
		}
		return $this;
	}

	public function error($code = 404) {
		$message = isset($this->httpCodes[$code]) ? $this->httpCodes[$code] : 'Unknown';
		header($this->server('server_protocol', 'HTTP/1.1') . " $code $message");
		exit();
	}

	protected static function endsWith($haystack, $needle, $case = true) {
		$expectedPosition = strlen($haystack) - strlen($needle);

		if ($case) {
			return strrpos($haystack, $needle, 0) === $expectedPosition;
		}

		return strripos($haystack, $needle, 0) === $expectedPosition;
	}

}
