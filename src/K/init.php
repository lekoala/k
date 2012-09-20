<?php

// Define constants
define('START_TIME', microtime(true));
define('START_MEMORY_USAGE', memory_get_usage(true));

// Define paths
if (!defined('BASE_PATH'))
	define('BASE_PATH', dirname($_SERVER['SCRIPT_FILENAME']));
if (!defined('SRC_PATH'))
	define('SRC_PATH', BASE_PATH . '/src');

// Default autoloader - psr 0 in src path
set_include_path(SRC_PATH . PATH_SEPARATOR . get_include_path());
spl_autoload_extensions('.php');
spl_autoload_register(function($classname) {
			$classname = ltrim($classname, "\\");
			preg_match('/^(.+)?([^\\\\]+)$/U', $classname, $match);
			$classname = str_replace("\\", "/", $match[1])
					. str_replace(["\\", "_"], "/", $match[2])
					. spl_autoload_extensions();
			require_once $classname;
		});

// Some initialization
error_reporting(E_ALL);
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}
set_error_handler("exception_error_handler");
date_default_timezone_set(date_default_timezone_get());
ini_set('variables_order', 'ECGPS');

// Utf 8
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_http_input('UTF-8');
mb_language('uni');
mb_regex_encoding('UTF-8');

// Disable magic quotes
if (get_magic_quotes_gpc()) {
	$process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
	while (list($key, $val) = each($process)) {
		foreach ($val as $k => $v) {
			unset($process[$key][$k]);
			if (is_array($v)) {
				$process[$key][stripslashes($k)] = $v;
				$process[] = &$process[$key][stripslashes($k)];
			} else {
				$process[$key][stripslashes($k)] = stripslashes($v);
			}
		}
	}
	unset($process);
}