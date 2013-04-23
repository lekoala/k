<?php

namespace k;

/**
 * Request wrapper
 */
class Request {

	protected $get;
	protected $post;
	protected $cookie;
	protected $files;
	protected $server;
	
	protected $rawData;
	protected $ajax;
	protected $secure;
	protected $method;

	public function __construct($get = null, $post = null, $cookie = null, $files = null, $server = null) {
		if ($get === null) {
			$get = $_GET;
		}
		if ($post === null) {
			$post = $_POST;
		}
		if ($cookie === null) {
			$cookie = $_COOKIE;
		}
		if ($files === null) {
			$files = $_FILES;
		}
		$this->setGet($get);
		$this->setPost($post);
		$this->setCookie($cookie);
		$this->setFiles($files);
		$this->setServer($server);
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
		return $this->_get('get',$key,$default);
	}
	
	public function post($key, $default = null) {
		return $this->_get('post',$key,$default);
	}
	
	public function server($key, $default = null) {
		return $this->_get('server',$key,$default);
	}
	
	public function in($key, $filter = null, $default = null) {
		
	}

	protected function _get($var,$key,$default = null) {
		$key = strtoupper($key);
		if(isset($this->$var[$key])) {
			return $this->$var[$key];
		}
		return $default;
	}
	
	public function isAjax() {
		if($this->ajax === null) {
			$this->ajax = $this->server('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest';
		}
		return $this->ajax;
	}
	
	public function isSecure() {
		if($this->secure === null) {
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
		if($this->method === null) {
			$this->method = strtoupper($this->server('HTTP_X_HTTP_METHOD_OVERRIDE',$this->server('REQUEST_METHOD','GET')));
		}
		return $this->method;
	}
	
	public function getUrl($querystrings = true) {
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

}
