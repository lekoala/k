<?php
namespace k\db;

use \PDOException as NativePdoException;

/**
 * Extend PDO exception with more helpful error messages
 * 
 * @author lekoala
 */
class PdoException extends NativePdoException {

	public function __construct($e,$pdo) {
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
					$q = $pdo->getLastQuery();
					if($q) {
						switch($this->code) {
							case '42000':
								$this->message = 'Syntax error ' . preg_replace("#^(.*)(near '.*')(.*)$#","$2",$this->message). ' in ' . $q;
								break;
							default:
								$this->message .= ' in query ' . $q;
								break;
						}
					}
				}
			}
		}
	}

}