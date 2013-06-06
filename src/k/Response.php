<?php

namespace k;

use \Closure;

/**
 * Versatile regular request / ajax response holder
 * 
 * The response can be
 * - Plain html
 * - A json response
 * - A file
 * 
 * @author lekoala
 */
class Response {

	const CONTENT_TYPE_HTML = 'text/html';
	const CONTENT_TYPE_JSON = 'application/json';
	const CONTENT_TYPE_XML = 'text/xml';
	const CONTENT_TYPE_JAVASCRIPT = 'text/javascript';

	/**
	 * @var string
	 */
	protected $body = '';

	/**
	 * @var array
	 */
	protected $data = array();

	/**
	 * @var string
	 */
	protected $file;

	/**
	 * @var string
	 */
	protected $filename;

	/**
	 * @var string
	 */
	protected $contentType = self::CONTENT_TYPE_HTML;

	/**
	 * @var string
	 */
	protected $charset = 'UTF-8';

	/**
	 * @var boolean
	 */
	protected $compress = false;

	/**
	 * @var boolean
	 */
	protected $cache = true;

	/**
	 * @var array
	 */
	protected $headers = array();

	/**
	 * @var array
	 */
	protected $cookies = array();

	/**
	 * @var string
	 */
	protected $statusCode;

	/**
	 * @param   array $config
	 */
	public function __construct($config = array()) {
		$keys = array('contentType', 'compress', 'cache', 'charset');
		foreach ($keys as $k) {
			if (isset($config[$k])) {
				$this->$k = $config[$k];
			}
		}
	}

	/**
	 * @param   string|array|object    $body  Response body
	 */
	public function body($body) {
		if (!is_string($body)) {
			$body = json_encode($body, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
			$this->type(self::CONTENT_TYPE_JSON);
		}
		$this->body = $body;
		return $this;
	}

	public function cache($flag = true) {
		$this->cache = $flag;
		return $this;
	}

	public function compress($flag = true) {
		$this->outputCompress = $flag;
		return $this;
	}

	/**
	 * Sets the response content type.
	 * 
	 * @access  public
	 * @param   string  $contentType  Content type
	 * @param   string  $charset      (optional) Charset
	 */
	public function type($contentType, $charset = null) {
		$this->contentType = $contentType;

		if ($charset !== null) {
			$this->charset = $charset;
		}
		return $this;
	}

	/**
	 * Sets the response charset.
	 * 
	 * @access  public
	 * @param   string  $charset  Charset
	 */
	public function charset($charset) {
		$this->charset = $charset;
		return $this;
	}

	/**
	 * @param int $status
	 * @return static
	 */
	public function status($status) {
		$this->statusCode = $status;
		return $this;
	}

	/**
	 * Sets a response header.
	 * 
	 * @access  public
	 * @param   string  $name   Header name
	 * @param   string  $value  Header value
	 */
	public function header($name, $value = null) {
		if ($value === null) {
			$pos = strpos($name, ':');
			$value = trim(substr($name, $pos + 1));
			$name = substr($name, 0, $pos);
		}
		$this->headers[$name] = $value;
		return $this;
	}

	public function getHeader($name) {
		if (isset($this->headers[$name])) {
			return $this->headers[$name];
		}
	}

	public function clearHeaders() {
		$this->headers = array();
		return $this;
	}

	public function data($data) {
		$this->data = $data;
	}

	public function addData($k, $data) {
		if (!isset($this->data[$k]) || !is_array($this->data[$k])) {
			$this->data[$k] = array();
		}
		$this->data[$k][] = $data;
	}

	public function clearData() {
		$this->data = array();
	}

	/**
	 * @param string $name
	 * @param string $value 	[optional]
	 * @param int $expire 		[optional]
	 * @param string $path 		[optional]
	 * @param string $domain 	[optional]
	 * @param bool $secure 		[optional]
	 * @param bool $httponly 	[optional]
	 * @return $this
	 */
	public function cookie($name, $value = null, $expire = 0, $path = null, $domain = null, $secure = null, $httponly = true) {
		//allow time to be set as string (like +1 week)
		if ($expire && !is_numeric($expire)) {
			$expire = strtotime($expire);
		}

		//make sure domain is set correctly
		if (!empty($domain)) {
			// Fix the domain to accept domains with and without 'www.'. 
			if (strtolower(substr($domain, 0, 4)) == 'www.') {
				$domain = substr($domain, 4);
			}

			// Add the dot prefix to ensure compatibility with subdomains 
			if (substr($domain, 0, 1) != '.') {
				$domain = '.' . $domain;
			}

			// Remove port information. 
			$port = strpos($domain, ':');

			if ($port !== false) {
				$domain = substr($domain, 0, $port);
			}
		}

		$this->cookies[] = array(
			'name' => $name,
			'value' => $value,
			'expire' => $expire,
			'path' => $path,
			'domain' => $domain,
			'secure' => $secure,
			'httponly' => $httponly,
		);

		return $this;
	}

	public function getCookie($name) {
		if (isset($this->cookies[$name])) {
			return $this->cookies[$name];
		}
	}

	public function clearCookies() {
		$this->cookies = array();
		return $this;
	}

	public function file($filename, $name = null, $force = false) {
		$contentType = 'application/octet-stream';
		if (!$force) {
			if (function_exists('finfo_open')) {
				$info = finfo_open(FILEINFO_MIME_TYPE);
				$contentType = finfo_file($info, $this->file);
				finfo_close($info);
			}
		}
		if (!$name) {
			$name = urlencode(basename($filename));
		}
		$this->filename = $name;
		$this->type($contentType);
	}

	/**
	 * Sends response headers.
	 * 
	 * @access  protected
	 */
	public function sendHeaders() {
		if (headers_sent()) {
			return false;
		}
		if ($this->statusCode !== null) {
			http_response_code($this->statusCode);
		}

		$contentType = $this->contentType;
		//if we send text, specify charset
		if (stripos($contentType, 'text/') === 0 || in_array($contentType, array('application/json', 'application/xml'))) {
			$contentType .= '; charset=' . $this->charset;
		}
		header('Content-Type: ' . $contentType);
		foreach ($this->headers as $name => $value) {
			header($name . ': ' . $value);
		}
	}

	protected function sendCookies() {
		foreach ($this->cookies as $cookie) {
			setcookie($cookie['name'], $cookie['value'], $cookie['expire'], $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly']);
		}

		return $this;
	}

	protected function isJson() {
		return $this->contentType === self::CONTENT_TYPE_JSON;
	}

	public function redirect($url, $code = 302, $html = false) {
		if ($this->isJson()) {
			$this->data['location'] = $url;
			return;
		}
		$this->status($code);
		$this->header('Location', $url);
		if ($html) {
			$this->htmlRedirect($url);
		}
		$this->send();
	}

	public function htmlRedirect($url) {
		$this->body = '<meta http-equiv="refresh" content="1;url=' . $url . '" />
			<script type="text/javascript">
				if (top.location != location) { 
					top.location.href = "' . $url . '"
				}
				else {
					location.href = "' . $url . '"
				}
			</script>';
		return $this;
	}

	public function redirectBack() {
		if ($this->isJson()) {
			//you can't go "back" with ajax
			return $this;
		}

		$url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
		if (isset($_SESSION['_back_url'])) {
			$url = $_SESSION['_back_url'];
		}
		if (isset($_GET['_back_url'])) {
			$url = $_GET['_back_url'];
		}

		if ($url) {
			$this->redirect($url);
		} else {
			$this->redirect('/');
		}
	}

	/**
	 * @link http://stackoverflow.com/questions/2021882/is-my-implementation-of-http-conditional-get-answers-in-php-is-ok/2049723#2049723
	 * @param string $etag
	 * @param int $mtime
	 * @return bool
	 */
	public function isModified($etag = null, $mtime = 0) {
		if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
			if ($_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
				return false;
			}
			if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
				if ($mtime && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $mtime) {
					return false;
				}
			}
		}
		return true;
	}

	protected function getJsonError() {
		switch (json_last_error()) {
			case JSON_ERROR_NONE:
				return 'No errors';
			case JSON_ERROR_DEPTH:
				return 'Maximum stack depth exceeded';
			case JSON_ERROR_STATE_MISMATCH:
				return 'Underflow or the modes mismatch';
			case JSON_ERROR_CTRL_CHAR:
				return 'Unexpected control character found';
			case JSON_ERROR_SYNTAX:
				return 'Syntax error, malformed JSON';
			case JSON_ERROR_UTF8:
				return 'Malformed UTF-8 characters, possibly incorrectly encoded';
			default:
				return 'Unknown error';
		}
	}

	/**
	 * Send output to browser.
	 *
	 * @access  public
	 * @param   int     $statusCode  (optional) HTTP status code
	 */
	public function send($statusCode = null) {
		$this->status($statusCode);

		if ($this->file) {
			$filename = $this->file;
			$filesize = filesize($filename);

			$this->header('Content-Length', filesize($this->file));
			$this->header('Content-disposition: attachment; filename="' . $filename . '"');
			if ($this->getHeader('Content-type') === 'application/octet-stream') {
				$this->header('Content-Transfer-Encoding: binary');
			}
			$this->headers('Pragma', 'public');
			$this->headers('Expires', 0);
			$this->headers('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
			$this->sendHeaders();

			$chunksize = 4096;
			if ($filesize > $chunksize) {
				$in = fopen($filename, 'rb');
				$out = fopen('php://output', 'wb');

				$offset = 0;
				while (!feof($in)) {
					$offset += stream_copy_to_stream($in, $out, $chunksize, $offset);
				}

				fclose($out);
				fclose($in);
			} else {
				// stream_copy_to_stream behaves() strange when filesize > chunksize.
				// Seems to never hit the EOF.
				// On the other handside file_get_contents() is not scalable. 
				// Therefore we only use file_get_contents() on small files.
				echo file_get_contents($filename);
			}
			exit();
		}

		$this->sendHeaders();
		$this->sendCookies();

		if ($this->isJson()) {
			$this->body = json_encode($this->data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
			if (empty($this->body)) {
				$this->body = '{error:"' . $this->getJsonError() . '"}';
			}
		}

		if ($this->body !== '') {

			//etag
			if ($this->cache === true) {

				$etag = '"' . sha1($this->body) . '"';
				header('ETag: ' . $etag);

				if (!$this->isModified($etag)) {
					http_response_code(304);
					return; // Don't send any output
				}
			}

			//compress
			if ($this->compress) {
//				ob_start('ob_gzhandler');
			}

			echo $this->body;
		}
		exit();
	}

	/**
	 * Get the response as a string;
	 *
	 * @access  public
	 * @return  string
	 */
	public function __toString() {
		return $this->body;
	}

}