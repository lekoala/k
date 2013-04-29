<?php

namespace k\log;

use \InvalidArgumentException;

/**
 * FileLogger
 * 
 * Log items in a single file
 *
 * @author lekoala
 */
class FileLogger extends LoggerAbstract {

	protected $file;

	public function __construct($file) {
		$this->setFile($$file);
	}
	
	public function getFile() {
		return $this->file;
	}

	public function setFile($file) {
		if(!is_writable($file)) {
			throw new InvalidArgumentException($file);
		}
		$this->file = $file;
		return $this;
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