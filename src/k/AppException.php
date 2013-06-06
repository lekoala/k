<?php

namespace k;

use \Exception;

/**
 * An exception that will be converted to a notification
 *
 * @author lekoala
 */
class AppException extends Exception {
	const GENERAL = 1;
	const DENIED = 2;
	const NOT_INSTALLED = 3;
}