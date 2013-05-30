<?php

namespace k\app;

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

	/**
	 * Body
	 *
	 * @var string
	 */
	protected $body = '';

	/**
	 * Json data
	 * 
	 * @var type 
	 */
	protected $data = array();

	/**
	 * Path to file
	 * 
	 * @var string
	 */
	protected $file;

	/**
	 * Name of the file
	 * 
	 * @var string 
	 */
	protected $filename;

	/**
	 * Response content type.
	 * 
	 * @var string
	 */
	protected $contentType = 'text/html';

	/**
	 * Response charset.
	 * 
	 * @var string
	 */
	protected $charset = 'UTF-8';

	/**
	 * Json response or not
	 * 
	 * @var bool
	 */
	protected $json = false;

	/**
	 * Compress output?
	 * 
	 * @var boolean
	 */
	protected $compress = true;

	/**
	 * Enable cache?
	 * 
	 * @var boolean
	 */
	protected $cache = true;

	/**
	 * Response headers.
	 * 
	 * @var array
	 */
	protected $headers = array();
	
	/**
	 * Status header
	 * 
	 * @var string
	 */
	protected $status;
	
	protected $mimetypes = array(
		"aif" => "audio/x-aiff",
		"aifc" => "audio/x-aiff",
		"aiff" => "audio/x-aiff",
		"asf" => "video/x-ms-asf",
		"asr" => "video/x-ms-asf",
		"asx" => "video/x-ms-asf",
		"au" => "audio/basic",
		"avi" => "video/x-msvideo",
		"bas" => "text/plain",
		"bin" => "application/octet-stream",
		"bmp" => "image/bmp",
		"css" => "text/css",
		"doc" => "application/msword",
		"dot" => "application/msword",
		"gif" => "image/gif",
		"ico" => "image/x-icon",
		"jpe" => "image/jpeg",
		"jpeg" => "image/jpeg",
		"jpg" => "image/jpeg",
		"js" => "application/x-javascript",
		"latex" => "application/x-latex",
		"m3u" => "audio/x-mpegurl",
		"mht" => "message/rfc822",
		"mhtml" => "message/rfc822",
		"mov" => "video/quicktime",
		"mp3" => "audio/mpeg",
		"mpeg" => "video/mpeg",
		"mpg" => "video/mpeg",
		"pdf" => "application/pdf",
		"png" => 'image/png',
		"pot" => "application/vnd.ms-powerpoint",
		"pps" => "application/vnd.ms-powerpoint",
		"ppt" => "application/vnd.ms-powerpoint",
		"ps" => "application/postscript",
		"pub" => "application/x-mspublisher",
		"qt" => "video/quicktime",
		"sh" => "application/x-sh",
		"svg" => "image/svg+xml",
		"tar" => "application/x-tar",
		"tgz" => "application/x-compressed",
		"tif" => "image/tiff",
		"tiff" => "image/tiff",
		"tsv" => "text/tab-separated-values",
		"txt" => "text/plain",
		"vcf" => "text/x-vcard",
		"wav" => "audio/x-wav",
		"xla" => "application/vnd.ms-excel",
		"xlc" => "application/vnd.ms-excel",
		"xlm" => "application/vnd.ms-excel",
		"xls" => "application/vnd.ms-excel",
		"xlt" => "application/vnd.ms-excel",
		"xlw" => "application/vnd.ms-excel",
		"zip" => "application/zip"
	);

	/**
	 * List of HTTP status codes.
	 *
	 * @var array
	 */
	protected $statuscodes = array(
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

	/**
	 * Constructor.
	 *
	 * @access  protected
	 * @param   string     $body  (optional) Response body
	 */
	public function __construct($config = array()) {
		$keys = array('compress', 'cache', 'charset', 'mimetypes','statuscodes');
		foreach ($keys as $k) {
			if (isset($config[$k])) {
				$this->$k = $config[$k];
			}
		}
	}

	/**
	 * Sets the response body.
	 *
	 * @access  public
	 * @param   string|array    $body  Response body
	 */
	public function body($body) {
		$this->body = (string) $body;
		return $this;
	}

	public function data($data) {
		$this->data = array_merge($this->data,$data);
		return $this;
	}
	
	public function addData($key,$data) {
		if(!isset($this->data[$key])) {
			$this->data[$key] = array();
		}
		$this->data[$key][] = $data;
		return $this;
	}
	
	public function clearData() {
		$this->data = array();
		return $this;
	}

	/**
	 * Serve a file
	 * 
	 * @param string $file
	 * @param string $name
	 * @param bool $force
	 */
	public function file($file, $name = null, $force = false) {
		$this->file = $file;
		$this->filename = $name;
		if($force) {
			$this->header('Content-type','application/octet-stream');
		}
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

	public function json($flag = true) {
		$this->json = $flag;
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

	/**
	 * Clear the response headers.
	 * 
	 * @access  public
	 */
	public function clearHeaders() {
		$this->headers = array();
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
	 * Sends response headers.
	 * 
	 * @access  protected
	 */
	protected function sendHeaders() {
		header($this->status);
		
		$contentType = $this->contentType;
		if (stripos($contentType, 'text/') === 0 || in_array($contentType, array('application/json', 'application/xml'))) {
			$contentType .= '; charset=' . $this->charset;
		}
		header('Content-Type: ' . $contentType);

		foreach ($this->headers as $name => $value) {
			header($name . ': ' . $value);
		}
	}

	/**
	 * Sends HTTP status header.
	 *
	 * @access  public
	 * @param   int     HTTP status code
	 */
	public function status($statusCode) {
		if (isset($this->statuscodes[$statusCode])) {
			if (isset($_SERVER['FCGI_SERVER_VERSION'])) {
				$protocol = 'Status:';
			} else {
				$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
			}

			$this->status = $protocol . ' ' . $statusCode . ' ' . $this->statuscodes[$statusCode];
		}
		return $this;
	}

	public function redirect($url, $code = 302, $html = false) {
		if($this->json) {
			$this->data['location'] = $url;
			return;
		}
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
		if($this->json) {
			//you can't go "back" with ajax
			return;
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
	 * @param type $etag
	 * @param type $mtime
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
	
	/**
	 * Send output to browser.
	 *
	 * @access  public
	 * @param   int     $statusCode  (optional) HTTP status code
	 */
	public function send($statusCode = null) {
		if ($statusCode !== null) {
			$this->status($statusCode);
		}

		// Send a file
		if ($this->file) {
			// Set a content type if none set
			if (!isset($this->headers['Content-type'])) {
				$ext = pathinfo($this->file, PATHINFO_EXTENSION);
				if (isset($this->mimetypes[$ext])) {
					$contentType = $this->mimetypes[$ext];
				}
				if (!$contentType && function_exists('finfo_open')) {
					$info = finfo_open(FILEINFO_MIME_TYPE);
					$contentType = finfo_file($info, $this->file);
					finfo_close($info);
				}
				$filename = $this->filename;
				if (!$filename) {
					$filename = urlencode(basename($this->file));
				}
				$this->header('Content-type', $contentType);
			}
			$this->header('Content-Length', filesize($this->file));
			$this->header('Content-disposition: attachment; filename="' . $filename . '"');
			if ($contentType === 'application/octet-stream') {
				$this->header('Content-Transfer-Encoding: binary');
			}

			if ($this->cache) {
				
			} else {
				$this->headers('Pragma', 'public');
				$this->headers('Expires', 0);
				$this->headers('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
			}

			$this->sendHeaders();
			readfile($this->file);
			exit();
		}

		if ($this->data && $this->json) {
			$this->header('Content-type','application/json');
			$this->body = json_encode($this->data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
		}
		
		$this->sendHeaders();
		
		if ($this->body !== '') {

			//etag
			if ($this->cache === true) {

				$etag = '"' . sha1($this->body) . '"';
				header('ETag: ' . $etag);

				if (!$this->isModified($etag)) {
					$this->status(304);
					return; // Don't send any output
				}
			}

			//compress
			if ($this->compress) {
//				ob_start('ob_gzhandler');
			}

			echo $this->body;
		}
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