<?php

namespace k;

use \SplFileObject;
use \InvalidArgumentException;

/**
 * File
 *
 * @link https://github.com/symfony/symfony/blob/master/src/Symfony/Component/HttpFoundation/File/File.php
 * @link http://www.php.net/manual/en/class.splfileinfo.php
 * @link http://flourishlib.com/docs/fFile
 * @link https://github.com/onemightyroar/php-filemanager/blob/master/src/OneMightyRoar/PhpFileManager/FileObject.php
 * @link https://github.com/Kappa-app/FileSystem/blob/master/src/Kappa/FileSystem/File.php
 * 
 * @author lekoala
 */
class File extends SplFileObject {
	
	/**
	 * Constructs a file for a given path
	 * 
	 * @param string $file
	 * @param bool $check
	 * @throws InvalidArgumentException
	 */
	public function __construct($file, $check = true) {
		if ($check) {
			if (empty($file) || !is_file($file)) {
				throw new InvalidArgumentException($file);
			}
		}
		parent::__construct($file);
	}

	public static function createFromUpload($name, $destination) {
		if (isset($_FILES[$name])) {
			if (is_dir($destination)) {
				$destination .= DIRECTORY_SEPARATOR . '/' . $_FILES[$name]['name'];
			}
			move_uploaded_file($_FILES[$name]['tmp_name'], $destination);
			return new static($destination);
		}
		return false;
	}

	/**
	 * Return the mime type
	 * 
	 * @return bool|string
	 */
	public function getMimeType() {
		if (function_exists('finfo_open')) {
			$info = finfo_open(FILEINFO_MIME_TYPE);
			$mime = finfo_file($info, $this->getPathname());
			finfo_close($info);
			return $mime;
		}
		return false;
	}

	/**
	 * Returns the extension of the file
	 * 
	 * getExtension was not available before PHP 5.3.6
	 * 
	 * @return string
	 */
	public function getExtension() {
		return pathinfo($this->getBasename(), PATHINFO_EXTENSION);
	}

	/**
	 * Alias getMTime, allowing formatting
	 * 
	 * @param string $format
	 * @return string
	 */
	public function getMakeTime($format = null) {
		if ($format) {
			return date($format, $this->getMTime());
		}
		return $this->getMTime();
	}

	public function getDirectory() {
		return new Directory($this->getPath());
	}

	public function rename($name) {
		$dir = $this->getPath();
		if (!is_writable($dir)) {
			throw new RunTimeException($dir);
		}
		$ext = pathinfo($name, PATHINFO_EXTENSION);
		if (!$ext) {
			$name .= '.' . $this->getExtension();
		}
		$new = $dir . DIRECTORY_SEPARATOR . $name;
		rename($this->getPathname(), $new);
		return new static($new);
	}

	public function move($dir, $name = null) {
		if (!$name) {
			$name = $this->getBasename();
		}
		if (!is_writable($dir)) {
			throw new InvalidArgumentException($dir);
		}
		$new = $dir . DIRECTORY_SEPARATOR . $name;
		rename($this->getPathname(), $new);
		return new static($new);
	}

	public function duplicate($dir = null, $create = true) {
		if (!$dir) {
			$dir = $this->getPath();
		}
		$i = 0;
		if(!is_dir($dir)) {
			$filename = $dir;
			$name = basename($filename);
			$dir = dirname($dir);
		}
		else {
			$i = 1;
			$name = $this->getBasename('.' . $this->getExtension());
			$filename = $dir . DIRECTORY_SEPARATOR . $name . '_' . $i . '.' . $this->getExtension();
		}
		if($create && !is_dir($dir)) {
			mkdir($dir,0777,true);
		}
		if (!is_writable($dir)) {
			throw new InvalidArgumentException("Directory is not writable '$dir'");
		}
		while (is_file($filename)) {
			$i++;
			$filename = $dir . DIRECTORY_SEPARATOR . $name . '_' . $i . '.' . $this->getExtension();
		}

		copy($this->getPathname(), $filename);
		chmod($filename, $this->getPerms());

		return new static($filename);
	}

	public function write($data) {
		return file_put_contents($this->getPathname(), $data);
	}
 
	public function read() {
		return file_get_contents($this->getPathname());
	}

	/**
	 * Delete file
	 */
	public function remove() {
		unlink($this->getPathname());
	}

	public function getSize($format = false) {
		$size = parent::getSize();
		if ($format) {
			if ($size <= 0) {
				return '0B';
			}
			$base = log($size) / log(1024);
			$suffixes = array('B', 'k', 'M', 'G', 'T', 'P');

			return round(pow(1024, $base - floor($base)), 2) . $suffixes[floor($base)];
		}
		return $size;
	}

	public static function getMaxUploadSize() {
		$maxUpload = (int) (ini_get('upload_max_filesize'));
		$maxPost = (int) (ini_get('post_max_size'));
		$memoryLimit = (int) (ini_get('memory_limit'));
		return min($maxUpload, $maxPost, $memoryLimit);
	}

	public static function safeName($unsafeName) {
		// Removes accents
		$name = @iconv('UTF-8', 'ASCII//IGNORE//TRANSLIT', $unsafeName);

		// Removes all characters that are not separators, letters, numbers, dots or whitespaces
		$name = preg_replace("/[^a-zA-Z" . preg_quote('_') . "\d\.\s]/", '', strtolower($name));

		// Replaces all successive separators into a single one
		$name = preg_replace('![' . preg_quote('_') . '\s]+!u', '_', $name);

		// Trim beginning and ending seperators
		$name = trim($name, '_');

		return $name;
	}

}