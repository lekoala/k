<?php

/* Bootstrap the framework with some sensible defaults */

// Constants
define('START_TIME', microtime(true));
define('START_MEMORY_USAGE', memory_get_usage(true));
define('DS', DIRECTORY_SEPARATOR);
define('PS', PATH_SEPARATOR);
define('NS', '\\');

// Environment
date_default_timezone_set(@date_default_timezone_get());
ini_set('variables_order', 'ECGPS');

// Error handling
error_reporting(-1);
ini_set('display_errors', true);

set_error_handler(function($code, $message, $file, $line) {
			if ((error_reporting() & $code) !== 0) {
				throw new ErrorException($message, $code, 0, $file, $line);
			}
			return true;
		});

// utf-8
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_http_input('UTF-8');
mb_language('uni');
mb_regex_encoding('UTF-8');