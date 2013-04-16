<?php

namespace k\log;

/**
 * DirectoryLogger
 * 
 * Log items in a directory, structured like this dir/Y-m/Y-m-d
 *
 * @author lekoala
 */
class DirectoryLogger extends LoggerAbstract {

	protected $dir;

	public function __construct($dir) {
		$this->setDir($dir);
	}
	
	public function getDir() {
		return $this->dir;
	}

	public function setDir($dir) {
		$this->dir = $dir;
		return $this;
	}

	public function getFile($time = null, $create = true) {
		if(!$time) {
			$time = time();
		}
		$dir = $this->getDir();
		$dir .= '/' . date('Y-m',$time);
		if (!is_dir($dir) && $create) {
			mkdir($dir);
		}
		$filename = $dir . '/' . date('Y-m-d',$time);
		return $filename;
	}
	
	protected function _log($level, $message, $context = array()) {
		$filename = $this->getFile();
		$line = date('Y-m-d H:i:s') . "\t" . $level . "\t" . $message . "\n";
		file_put_contents($filename, $line, FILE_APPEND);
	}

	public function read($date, $lines = 5, $reverse = false) {
		if(!is_int($date)) {
			$date = strtotime($date);
		}
		$filename =  $this->getFile($date, false);
		$offset = -1;
		$c = '';
		$read = '';
		$i = 0;
		$fp = @fopen($filename, "r");
		while ($lines && fseek($fp, $offset, SEEK_END) >= 0) {
			$c = fgetc($fp);
			if ($c == "\n" || $c == "\r") {
				$lines--;
				if ($reverse) {
					$read[$i] = strrev($read[$i]);
					$i++;
				}
			}
			if ($reverse) {
				$read[$i] .= $c;
			} else {
				$read .= $c;
			}
			$offset--;
		}
		fclose($fp);
		if ($reverse) {
			if ($read[$i] == "\n" || $read[$i] == "\r") {
				array_pop($read);
			} else {
				$read[$i] = strrev($read[$i]);
			}
			return implode('', $read);
		}
		return strrev(rtrim($read, "\n\r"));
	}

}