<?php

namespace K;

/**
 * Description of Response
 *
 * @author Thomas
 */
class Response {

	protected $bufferSize = 8192;
	protected $content;
	protected $contentType;
	protected $contentTypes = array(
		'html' => 'text/html',
		'json' => 'application/json',
		'xml' => 'text/xml'
	);
	protected $charset = 'UTF-8';
	protected $statusCode;
	protected $statusCodes = array(
		// 1xx Informational
		'100' => 'Continue',
		'101' => 'Switching Protocols',
		'102' => 'Processing',
		// 2xx Success
		'200' => 'OK',
		'201' => 'Created',
		'202' => 'Accepted',
		'203' => 'Non-Authoritative Information',
		'204' => 'No Content',
		'205' => 'Reset Content',
		'206' => 'Partial Content',
		'207' => 'Multi-Status',
		// 3xx Redirection
		'300' => 'Multiple Choices',
		'301' => 'Moved Permanently',
		'302' => 'Found',
		'303' => 'See Other',
		'304' => 'Not Modified',
		'305' => 'Use Proxy',
		//'306' => 'Switch Proxy',
		'307' => 'Temporary Redirect',
		// 4xx Client Error
		'400' => 'Bad Request',
		'401' => 'Unauthorized',
		'402' => 'Payment Required',
		'403' => 'Forbidden',
		'404' => 'Not Found',
		'405' => 'Method Not Allowed',
		'406' => 'Not Acceptable',
		'407' => 'Proxy Authentication Required',
		'408' => 'Request Timeout',
		'409' => 'Conflict',
		'410' => 'Gone',
		'411' => 'Length Required',
		'412' => 'Precondition Failed',
		'413' => 'Request Entity Too Large',
		'414' => 'Request-URI Too Long',
		'415' => 'Unsupported Media Type',
		'416' => 'Requested Range Not Satisfiable',
		'417' => 'Expectation Failed',
		'418' => 'I\'m a teapot',
		'421' => 'There are too many connections from your internet address',
		'422' => 'Unprocessable Entity',
		'423' => 'Locked',
		'424' => 'Failed Dependency',
		'425' => 'Unordered Collection',
		'426' => 'Upgrade Required',
		'449' => 'Retry With',
		'450' => 'Blocked by Windows Parental Controls',
		// 5xx Server Error
		'500' => 'Internal Server Error',
		'501' => 'Not Implemented',
		'502' => 'Bad Gateway',
		'503' => 'Service Unavailable',
		'504' => 'Gateway Timeout',
		'505' => 'HTTP Version Not Supported',
		'506' => 'Variant Also Negotiates',
		'507' => 'Insufficient Storage',
		'509' => 'Bandwidth Limit Exceeded',
		'510' => 'Not Extended',
		'530' => 'User access denied',
	);
	protected $outputFilter;
	protected $compress = false;
	protected $cache = true;

	public function __construct($content = '', $status = 200, $contentType = 'html', $charset = 'UTF-8') {
		$this->setContent($content);
		$this->setStatusCode($status);
		$this->setContentType($contentType, $charset);
	}

	public function outputFilter($filter) {
		$this->outputFilter = $filter;
	}

	public function safeHeader($header) {
		if (!headers_sent()) {
			header($header);
		}
	}

	public function setStatusCode($code) {
		if (!isset($this->statusCodes[$code])) {
			$code = 200;
		}
		$this->statusCode = $code;
		return $this;
	}

	public function redirect($location, $statusCode = 302, $force = false) {
		$this->status($statusCode);
		$this->safeHeader('Location: ' . $location);
		if ($force) {
			echo '<meta http-equiv="refresh" content="1;url=' . $location . '" />';
			echo '<script type="text/javascript">setTimeout(function() { window.location.href = \'' . $location . '\' ; }, 1000);</script>';
		}
		exit();
	}
	
	public function getBufferSize() {
		return $this->bufferSize;
	}

	public function setBufferSize($bufferSize) {
		$this->bufferSize = $bufferSize;
		return $this->bufferSize;
	}
	
	public function getContent() {
		return $this->content;
	}

	public function setContent($content) {
		$this->content = $content;
		return $this;
	}

	public function getContentType() {
		return $this->contentType;
	}

	public function setContentType($contentType = null, $charset = 'UTF-8') {
		// From predefined
		if (isset($this->contentTypes[$contentType])) {
			$this->contentType = $this->contentTypes[$contentType];
			return $this;
		}
		// Custom
		else {
			$this->contentType = $contentType;
		}
		$this->charset = $charset;
		return $this;
	}

	public function compress($flag = true) {
		$this->compress = $flag;
		return $this;
	}

	public function cache($flag = true) {
		$this->cache = $flag;
		return $this;
	}

	public function send() {
		// Send content type
		if (!$this->contentType) {
			$this->setContentType();
		}
		$this->safeHeader('Content-type: ' . $this->contentType . '; charset=' . $this->charset);

		// Send status code
		$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
		$this->safeHeader($protocol . ' ' . $this->statusCode . ' ' . $this->statusCodes[$this->statusCode]);

		// Pass output through filter
		if (!empty($this->outputFilter)) {
			$output = call_user_func($this->outputFilter, $output);
		}

		// Cache
		if (!$this->cache) {
			$this->safeHeader('Cache-Control: no-cache, must-revalidate');
			$this->safeHeader('Expires: Wed, 16 Jan 1985 03:00:00 GMT');
		}

		// Compress ?
		if ($this->compress) {
			ob_start("ob_gzhandler");
		}

		$buffer_size = $this->bufferSize;
		$length = strlen($this->content);
		for ($i = 0; $i < $length; $i += $buffer_size) {
			echo substr($this->content, $i, $buffer_size);
		}
	}

	public function __toString() {
		return $this->send();
	}

}