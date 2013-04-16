<?php
namespace db;

/**
 * @author tportelange
 */
class exception extends \PDOException {

	public function __construct($e) {
		if (is_string($e)) {
			$this->code = 0;
			$this->message = $e;
		} else {
			$this->message = $e->getMessage();
			$this->code = $e->getCode();
			//make the code/message more consistent
			if (strstr($e->getMessage(), 'SQLSTATE[')) {
				preg_match('/SQLSTATE\[(\w+)\]\: (.*)/', $e->getMessage(), $matches);
				if (!empty($matches)) {
					$this->code = $matches[1];
					$this->message = $matches[2];
				}
			}
		}
	}

}