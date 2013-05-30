<?php

namespace k\sql;

use \PDOStatement as NativePdoStatement;

/**
 * Custom PDO statement to allow automatic logging of prepared statements
 * 
 * @author lekoala
 */
class PdoStatement extends NativePdoStatement {

	/**
	 * @var Pdo
	 */
	protected $pdo;

	private function __construct($pdo) {
		$this->pdo = $pdo;
		//need to declare construct as private
	}

	function execute($params = array()) {
		$sql = $this->queryString;
	
		//replace placeholders for the log
		$replSql = $sql;
		if (!empty($params)) {
			$keys = array();
			foreach($params as $k => $v) {
				$keys[] = $k;
				$values[] = $this->pdo->quote($v);
			}
			$replSql = str_replace($keys,$values,$sql);
		}

		try {
			$time = microtime(true);
			$result = parent::execute($params);
			$time = microtime(true) - $time;
			$this->pdo->log($replSql, $time);
		} catch (\PDOException $e) {
			$this->pdo->log($replSql);
			throw new PdoException($e, $this->pdo);
		}

		return $result;
	}

}