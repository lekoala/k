<?php

namespace k\db;

use \PDOStatement as NativePdoStatement;
/**
 * @author tportelange
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

		//nicer looking logs
		$niceSql = $sql;
		if (!empty($params)) {
			foreach ($params as $k => $v) {
				$k = ltrim($k, ':');
				if (!is_numeric($v)) {
					$v = "'$v'";
				}
				//TODO : find a single regex that matches both cases
				$niceSql = preg_replace('/:' . $k . '([,|\)|\s|$|\t|\n])/', $v . "$1", $niceSql);
				$niceSql = preg_replace('/= :' . $k . '/', "= " . $v, $niceSql);
			}
		}

		try {
			$time = microtime(true);
			$result = parent::execute($params);
			$time = microtime(true) - $time;
			$this->pdo->log($niceSql, $time);
		} catch (\PDOException $e) {
			$this->pdo->log($niceSql);
			throw new PdoException($e);
		}

		return $result;
	}

}