<?php

namespace k;

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
		return $this->_get('server', $key, $default);
	}

	public function in($key, $filter = null, $default = null) {
		
	}

	protected function _get($var, $key, $default = null) {
		$key = strtoupper($key);
		$var = $this->$var;
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

}
