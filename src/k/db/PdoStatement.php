<?php

namespace db;

use \PDOStatement as NativePdoStatement;
/**
 * @author tportelange
 */
class PdoStatement extends NativePdoStatement {
	
	private function __construct($pdo) {
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
			$this->getDb()->log($niceSql, $time);
		} catch (\PDOException $e) {
			$this->getDb()->log($niceSql);
			throw new exception($e);
		}

		return $result;
	}

}