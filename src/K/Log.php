<?php

namespace K;

class Log {
	
	use TConfigure;
	
	const DEBUG = 100;
	const INFO = 200;
	const WARNING = 300;
	const ERROR = 400;
	const CRITICAL = 500;
	const ALERT = 550;

	static $enabled = true;
	static $file;
	static $thresold = 0;
	/**
	 *
	 * @var Pdo
	 */
	static $pdo;
	static $email;
	static $emailThresold = 500;
	protected static $levels = array(
		'debug' => 100,
		'info' => 200,
		'warning' => 300,
		'error' => 400,
		'critical' => 500,
		'alert' => 550,
	);
	protected static $logs = array();
	
	private function __construct() {
	}

	public static function add($message, $level = 'info', $context = array()) {
		if (!self::$enabled) {
			return false;
		}
		if (in_array($level, array_keys(self::$levels))) {
			$thresold = self::$levels[$level];
		} else {
			throw new Exception('Invalid log level ' . $level);
		}

		if (is_object($message)) {
			$message = get_class($message);
		}
		if (is_array($message)) {
			$message = json_encode($message);
		}
		$data = date('Y-m-d H:i:s') . "\t[" . $level . "]\t" . $message;
		self::$logs[] = $data;
		if (self::$file && $thresold >= self::$thresold) {
			$handle = fopen(self::$file, 'a+');
			fwrite($handle, $data . "\n");
		}
		if (self::$pdo && $thresold >= self::$thresold) {
			self::$pdo->insert('log', array(
				'created_at' => date('Y-m-d H:i:s'),
				'level' => $level,
				'message' => $message
			));
		}
		if (self::$email && $thresold >= self::$emailThresold) {
			$subject = '[' . $_SERVER['HTTP_HOST'] . ']' . '[' . $level . ']';
			mail(self::$email, $subject, $message);
		}
		return true;
	}

	public static function setFile($file, $level = null) {
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

	public static function setThresold($level) {
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
	
	public static function setEmail($email, $level = null) {
		if(filter_var($email, FILTER_VALIDATE_EMAIL)) {
			throw new Exception($email . ' is not a valid email');
		}
		if($level) {
			self::setEmailThresold($level);
		}
		self::$email = $email;
	}
	
	public static function setEmailThresold($level) {
		$thresold = $level;
		if (!is_numeric($level)) {
			if (!in_array($level, array_keys(self::$levels))) {
				$thresold = self::$levels[$level];
			} else {
				throw new Exception('Invalid log level ' . $level);
			}
		}
		self::$emailThresold = $thresold;
	}
	
	public static function setPdo($pdo) {
		if(!$pdo instanceof \PDO) {
			throw new Exception ($pdo . ' must be of type PDO');
		}
		self::$pdo = $pdo;
	}

	public static function debug($msg) {
		return self::add($msg, 'debug');
	}

	public static function info($msg) {
		return self::add($msg, 'info');
	}

	public static function warning($msg) {
		return self::add($msg, 'warning');
	}

	static function error($msg) {
		return self::add($msg, 'error');
	}

	public static function critical($msg) {
		return self::add($msg, 'critical');
	}

	public static function alert($msg) {
		return self::add($msg, 'alert');
	}

	public static function debugBarCallback() {
		$line = count(self::$logs) . ' logs';
		$logs = self::$logs;
		array_unshift($logs, $line);
		return $logs;
	}

}