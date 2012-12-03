<?php

namespace K;

/**
 * Simple file wrapper
 *
 * @author tportelange
 */
class File {

	protected $path;
	protected $deleted = false;
	protected static $mimeTypes = array(
		"323" => "text/h323",
		"acx" => "application/internet-property-stream",
		"ai" => "application/postscript",
		"aif" => "audio/x-aiff",
		"aifc" => "audio/x-aiff",
		"aiff" => "audio/x-aiff",
		"asf" => "video/x-ms-asf",
		"asr" => "video/x-ms-asf",
		"asx" => "video/x-ms-asf",
		"au" => "audio/basic",
		"avi" => "video/x-msvideo",
		"axs" => "application/olescript",
		"bas" => "text/plain",
		"bcpio" => "application/x-bcpio",
		"bin" => "application/octet-stream",
		"bmp" => "image/bmp",
		"c" => "text/plain",
		"cat" => "application/vnd.ms-pkiseccat",
		"cdf" => "application/x-cdf",
		"cer" => "application/x-x509-ca-cert",
		"class" => "application/octet-stream",
		"clp" => "application/x-msclip",
		"cmx" => "image/x-cmx",
		"cod" => "image/cis-cod",
		"cpio" => "application/x-cpio",
		"crd" => "application/x-mscardfile",
		"crl" => "application/pkix-crl",
		"crt" => "application/x-x509-ca-cert",
		"csh" => "application/x-csh",
		"css" => "text/css",
		"dcr" => "application/x-director",
		"der" => "application/x-x509-ca-cert",
		"dir" => "application/x-director",
		"dll" => "application/x-msdownload",
		"dms" => "application/octet-stream",
		"doc" => "application/msword",
		"dot" => "application/msword",
		"dvi" => "application/x-dvi",
		"dxr" => "application/x-director",
		"eps" => "application/postscript",
		"etx" => "text/x-setext",
		"evy" => "application/envoy",
		"exe" => "application/octet-stream",
		"fif" => "application/fractals",
		"flr" => "x-world/x-vrml",
		"gif" => "image/gif",
		"gtar" => "application/x-gtar",
		"gz" => "application/x-gzip",
		"h" => "text/plain",
		"hdf" => "application/x-hdf",
		"hlp" => "application/winhlp",
		"hqx" => "application/mac-binhex40",
		"hta" => "application/hta",
		"htc" => "text/x-component",
		"htm" => "text/html",
		"html" => "text/html",
		"htt" => "text/webviewhtml",
		"ico" => "image/x-icon",
		"ief" => "image/ief",
		"iii" => "application/x-iphone",
		"ins" => "application/x-internet-signup",
		"isp" => "application/x-internet-signup",
		"jfif" => "image/pipeg",
		"jpe" => "image/jpeg",
		"jpeg" => "image/jpeg",
		"jpg" => "image/jpeg",
		"js" => "application/x-javascript",
		"latex" => "application/x-latex",
		"lha" => "application/octet-stream",
		"lsf" => "video/x-la-asf",
		"lsx" => "video/x-la-asf",
		"lzh" => "application/octet-stream",
		"m13" => "application/x-msmediaview",
		"m14" => "application/x-msmediaview",
		"m3u" => "audio/x-mpegurl",
		"man" => "application/x-troff-man",
		"mdb" => "application/x-msaccess",
		"me" => "application/x-troff-me",
		"mht" => "message/rfc822",
		"mhtml" => "message/rfc822",
		"mid" => "audio/mid",
		"mny" => "application/x-msmoney",
		"mov" => "video/quicktime",
		"movie" => "video/x-sgi-movie",
		"mp2" => "video/mpeg",
		"mp3" => "audio/mpeg",
		"mpa" => "video/mpeg",
		"mpe" => "video/mpeg",
		"mpeg" => "video/mpeg",
		"mpg" => "video/mpeg",
		"mpp" => "application/vnd.ms-project",
		"mpv2" => "video/mpeg",
		"ms" => "application/x-troff-ms",
		"mvb" => "application/x-msmediaview",
		"nws" => "message/rfc822",
		"oda" => "application/oda",
		"p10" => "application/pkcs10",
		"p12" => "application/x-pkcs12",
		"p7b" => "application/x-pkcs7-certificates",
		"p7c" => "application/x-pkcs7-mime",
		"p7m" => "application/x-pkcs7-mime",
		"p7r" => "application/x-pkcs7-certreqresp",
		"p7s" => "application/x-pkcs7-signature",
		"pbm" => "image/x-portable-bitmap",
		"pdf" => "application/pdf",
		"pfx" => "application/x-pkcs12",
		"pgm" => "image/x-portable-graymap",
		"pko" => "application/ynd.ms-pkipko",
		"pma" => "application/x-perfmon",
		"pmc" => "application/x-perfmon",
		"pml" => "application/x-perfmon",
		"pmr" => "application/x-perfmon",
		"pmw" => "application/x-perfmon",
		"png" => 'image/png',
		"pnm" => "image/x-portable-anymap",
		"pot" => "application/vnd.ms-powerpoint",
		"ppm" => "image/x-portable-pixmap",
		"pps" => "application/vnd.ms-powerpoint",
		"ppt" => "application/vnd.ms-powerpoint",
		"prf" => "application/pics-rules",
		"ps" => "application/postscript",
		"pub" => "application/x-mspublisher",
		"qt" => "video/quicktime",
		"ra" => "audio/x-pn-realaudio",
		"ram" => "audio/x-pn-realaudio",
		"ras" => "image/x-cmu-raster",
		"rgb" => "image/x-rgb",
		"rmi" => "audio/mid",
		"roff" => "application/x-troff",
		"rtf" => "application/rtf",
		"rtx" => "text/richtext",
		"scd" => "application/x-msschedule",
		"sct" => "text/scriptlet",
		"setpay" => "application/set-payment-initiation",
		"setreg" => "application/set-registration-initiation",
		"sh" => "application/x-sh",
		"shar" => "application/x-shar",
		"sit" => "application/x-stuffit",
		"snd" => "audio/basic",
		"spc" => "application/x-pkcs7-certificates",
		"spl" => "application/futuresplash",
		"src" => "application/x-wais-source",
		"sst" => "application/vnd.ms-pkicertstore",
		"stl" => "application/vnd.ms-pkistl",
		"stm" => "text/html",
		"svg" => "image/svg+xml",
		"sv4cpio" => "application/x-sv4cpio",
		"sv4crc" => "application/x-sv4crc",
		"t" => "application/x-troff",
		"tar" => "application/x-tar",
		"tcl" => "application/x-tcl",
		"tex" => "application/x-tex",
		"texi" => "application/x-texinfo",
		"texinfo" => "application/x-texinfo",
		"tgz" => "application/x-compressed",
		"tif" => "image/tiff",
		"tiff" => "image/tiff",
		"tr" => "application/x-troff",
		"trm" => "application/x-msterminal",
		"tsv" => "text/tab-separated-values",
		"txt" => "text/plain",
		"uls" => "text/iuls",
		"ustar" => "application/x-ustar",
		"vcf" => "text/x-vcard",
		"vrml" => "x-world/x-vrml",
		"wav" => "audio/x-wav",
		"wcm" => "application/vnd.ms-works",
		"wdb" => "application/vnd.ms-works",
		"wks" => "application/vnd.ms-works",
		"wmf" => "application/x-msmetafile",
		"wps" => "application/vnd.ms-works",
		"wri" => "application/x-mswrite",
		"wrl" => "x-world/x-vrml",
		"wrz" => "x-world/x-vrml",
		"xaf" => "x-world/x-vrml",
		"xbm" => "image/x-xbitmap",
		"xla" => "application/vnd.ms-excel",
		"xlc" => "application/vnd.ms-excel",
		"xlm" => "application/vnd.ms-excel",
		"xls" => "application/vnd.ms-excel",
		"xlt" => "application/vnd.ms-excel",
		"xlw" => "application/vnd.ms-excel",
		"xof" => "x-world/x-vrml",
		"xpm" => "image/x-xpixmap",
		"xwd" => "image/x-xwindowdump",
		"z" => "application/x-compress",
		"zip" => "application/zip");

	public function __construct($path, $noChecks = false) {
		if (!$noChecks) {
			if (empty($path)) {
				throw new Exception('You must specify a path');
			}
			if (!is_file($path)) {
				throw new Exception($path . ' is not a file');
			}
		}
		$this->path = $path;
	}

	public static function create($path) {
		return new static($path);
	}

	public static function createFromUpload($name, $destination) {
		if (isset($_FILES[$name])) {
			if (is_dir($destination)) {
				$destination .= DIRECTORY_SEPARATOR . '/' . $_FILES[$name]['name'];
			}
			move_uploaded_file($_FILES[$name]['tmp_name'], $destination);
			return new static($destination);
		}
	}

	/**
	 * Return full path, for instance /www/htdocs/inc/lib.inc.php
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * Return file extension, for instance php
	 * @return string
	 */
	public function getExtension() {
		return pathinfo($this->getPath(), PATHINFO_EXTENSION);
	}

	/**
	 * Return filename with extension, for instance lib.inc.php
	 * @return string
	 */
	public function getBasename() {
		return pathinfo($this->getPath(), PATHINFO_BASENAME);
	}

	/**
	 * Return filename with extension, for instance lib.inc
	 * @return string
	 */
	public function getFilename() {
		return pathinfo($this->getPath(), PATHINFO_FILENAME);
	}

	/**
	 * Directory without / at the end, for instance /www/htdocs/inc
	 * @return string
	 */
	public function getDirectory() {
		return pathinfo($this->getPath(), PATHINFO_DIRNAME);
	}

	public function getMimetype() {
		$extension = $this->getExtension();

		//use hardcoded table (faster and more reliable for css files for instance)
		$mime = isset(self::$mimeTypes[$extension]) ? self::$mimeTypes[$extension] : false;

		//if we don't find it, try to discover
		if (!$mime && function_exists('finfo_open')) {
			$this->checkIfDeleted();
			// Get mime using the file information functions
			$info = finfo_open(FILEINFO_MIME_TYPE);
			$mime = finfo_file($info, $this->getPath());
			finfo_close($info);
		}
		return $mime;
	}

	public function remove() {
		$this->checkIfDeleted();
		unlink($this->getPath());
		$this->deleted = true;
		return true;
	}

	public function duplicate($dir = null) {
		$this->checkIfDeleted();
		if (!$dir) {
			$dir = $this->getDirectory();
		}
		if (!is_writable($dir)) {
			throw new Exception($dir . ' is not writable');
		}
		$i = 1;
		$filename = $dir . DIRECTORY_SEPARATOR . $this->getFilename() . '_' . $i . '.' . $this->getExtension();
		while (is_file($filename)) {
			$i++;
			$filename = $dir . DIRECTORY_SEPARATOR . $this->getFilename() . '_' . $i . '.' . $this->getExtension();
		}

		copy($this->getPath(), $filename);
		chmod($filename, fileperms($this->getPath()));

		return new static($filename);
	}

	public function getSize($format = true) {
		$this->checkIfDeleted();
		$size = filesize($this->getPath());
		if (!$format) {
			return $size;
		}
		if ($size <= 0) {
			return '0B';
		}
		$base = log($size) / log(1024);
		$suffixes = array('B', 'k', 'M', 'G', 'T', 'P');

		return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
	}

	public function getMakeTime($format = null) {
		$this->checkIfDeleted();
		$time = filemtime($this->getPath());
		if ($format) {
			$time = date($format, $time);
		}
		return $time;
	}

	public function isWritable() {
		$this->checkIfDeleted();
		return is_writable($this->getPath());
	}

	public function move($dir) {
		$this->checkIfDeleted();
		$newPath = $dir . DIRECTORY_SEPARATOR . $this->getBasename();
		rename($this->getPath(), $newPath);
		$this->path = $newPath;
		return true;
	}

	public function rename($name) {
		$this->checkIfDeleted();
		$dir = $this->getDirectory();
		if (strpos($name, DIRECTORY_SEPARATOR) === 0) {
			$dir = pathinfo($name, PATHINFO_DIRNAME);
			$name = pathinfo($name, PATHINFO_BASENAME);
		}
		$ext = pathinfo($name, PATHINFO_EXTENSION);
		if (!$ext) {
			$name .= '.' . $this->getExtension();
		}
		$newPath = $dir . DIRECTORY_SEPARATOR . $name;
		rename($this->getPath(), $newPath);
		$this->path = $newPath;
		return true;
	}

	public function download($force = false, $name = null) {
		$this->checkIfDeleted();
		if ($force) {
			$type = 'application/force-download';
		} else {
			$type = $this->getMimetype();
		}
		if (!$name) {
			$name = $this->getBasename();
		}

		header('Content-type: ' . $type);
		header('Content-length: ' . $this->getSize(false));
		header('Last-Modified: ' . $this->getMakeTime('D, d M Y H:i:s'));
		header('Content-Disposition: attachment; filename="' . $name . '"');
		header('Expires: 0');
		ob_clean();
		flush();
		echo readfile($this->getPath());
		exit();
	}

	public function read() {
		$this->checkIfDeleted();
		return file_get_contents($this->getPath());
	}

	public function write($data) {
		$parent = $this->getDirectory();
		if (!is_dir($parent)) {
			mkdir($parent, 0777, true);
		}
		file_put_contents($this->getPath(), $data);
	}

	protected function checkIfDeleted($fs = false) {
		$deleted = $this->deleted;
		if ($fs) {
			$deleted = is_file($this->getPath());
		}
		if ($deleted) {
			throw new Exception($this->getPath() . ' has been deleted');
		}
	}

	public function __clone() {
		return $this->duplicate();
	}

	public function __toString() {
		try {
			return $this->read();
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}

	public static function emptyDir($dir, $selfDelete = false) {
		if (!is_dir($dir)) {
			throw new Exception($dir . ' is not a directory');
		}
		if (is_dir($dir))
			$handle = opendir($dir);
		if (!$handle)
			return false;
		while ($file = readdir($handle)) {
			if ($file != "." && $file != "..") {
				if (!is_dir($dir . "/" . $file))
					@unlink($dir . "/" . $file);
				else
					static::emptyDir($dir . '/' . $file, true);
			}
		}
		closedir($handle);
		if ($selfDelete) {
			@rmdir($dir);
		}
		return true;
	}

	public static function getMaxUploadSize() {
		$maxUpload = (int) (ini_get('upload_max_filesize'));
		$maxPost = (int) (ini_get('post_max_size'));
		$memoryLimit = (int) (ini_get('memory_limit'));
		return min($maxUpload, $maxPost, $memoryLimit);
	}

}