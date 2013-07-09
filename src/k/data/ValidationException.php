<?php

namespace k\data;

use \RuntimeException;
use \Exception;

/**
 * Validation Class
 */
class ValidationException extends RuntimeException {

	protected $errors;

	public function __construct($message = "Data is not valid", $code = 0, Exception $previous = null, $errors = null) {
		$this->errors = $errors;
		parent::__construct($message, $code, $previous);
	}

	public function getErrors() {
		return $this->errors;
	}

}

