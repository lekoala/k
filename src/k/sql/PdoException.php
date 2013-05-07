<?php
namespace k\sql;

use \PDOException as NativePdoException;

/**
 * @author tportelange
 */
class PdoException extends NativePdoException {

	public function __construct($e,$pdo = null) {
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
					if($this->code == '42000' && $pdo->getLastQuery()) {
						$this->message = 'Syntax ' . preg_replace("#^(.*)(near '.*')(.*)$#","$2",$this->message). ' is not valid in ' . $pdo->getLastQuery();
					}
				}
			}
		}
	}

}