<?php

namespace K\Data;

use \PDO;

/**
 * Description of Driver
 *
 * @author tportelange
 */
class Driver_Pdo extends Driver_Abstract {
	public function __construct($dsn = array(), array $options = array()) {
		//always throw exception
//		$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		//use custom pdo statement class
//		$this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('K\Db\Statement', array($this)));
	}
}
