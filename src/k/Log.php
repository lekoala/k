<?php

namespace k;

class Log {

	static $enabled = true;
	static $file;
	static $thresold = 0;
	protected static $levels = array(
		'debug' => 100,
		'info' => 200,
		'warning' => 300,
		'error' => 400,
		'critical' => 500,
		'alert' => 550,
	);
	protected static $logs = array();

	static function add($message, $level = 'info', $context = array()) {
		if (!self::$enabled) {
			return false;
		}
		if (in_array($level, array_keys(self::$levels))) {
			$thresold = self::$levels[$level];
		} else {
			throw new Exception('Invalid log level ' . $level);
		}

		if (is_array($message)) {
			$message = json_encode($message);
		}
		$data = date('Y-m-d H:i:s') . "\t[" . $level . "]\t" . $message . "\n";
		self::$logs[] = $data;
		if (self::$file && $thresold >= self::$thresold) {
			$handle = fopen(self::$file, 'a+');
			return fwrite($handle, $data);
		}
		return true;
	}

	static function setFile($file, $level = null) {
		if (!is_file($file)) {
			throw new Exception('Invalid file : ' . $file);
		}
		if (!is_writable($file)) {
			throw new Exception('File is not writable : ' . $file);
		}
		if ($level) {
			self::setThresold($level);
		}
		self::$file = $file;
	}

	static function setThresold($level) {
		$thresold = $level;
		if (!is_numeric($level)) {
			if (!in_array($level, array_keys(self::$levels))) {
				$thresold = self::$levels[$level];
			} else {
				throw new Exception('Invalid log level ' . $level);
			}
		}
		self::$thresold = $thresold;
	}

	static function debug($msg) {
		return self::add($msg, 'debug');
	}

	static function info($msg) {
		return self::add($msg, 'info');
	}

	static function warning($msg) {
		return self::add($msg, 'warning');
	}

	static function error($msg) {
		return self::add($msg, 'error');
	}

	static function critical($msg) {
		return self::add($msg, 'critical');
	}

	static function alert($msg) {
		return self::add($msg, 'alert');
	}

	static function stats() {
		return array(
			'link' => count(self::$logs) . ' logs',
			'data' => self::$logs
		);
	}
	
	static function debugBarCallback() {
		
	}

}