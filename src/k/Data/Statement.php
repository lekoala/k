<?php

namespace K\Db;

use \PDOStatement;

/**
 * PDOStatement wrapper
 */
class Statement extends PDOStatement {

	private function __construct($pdo) {
		//need to declare construct as private
	}

	function execute($input_parameters = array()) {
		$sql = $this->queryString;

		//nicer looking logs
		$sql_rpl = $sql;
		if (!empty($input_parameters)) {
			foreach ($input_parameters as $k => $v) {
				$sql_rpl = preg_replace('/' . $k . '/', $v, $sql_rpl);
			}
		}

		try {
			$time = microtime(true);
			$result = parent::execute($input_parameters);
			$time = microtime(true) - $time;
			Pdo::logQuery($sql_rpl, $time);
		} catch (PDOException $e) {
			throw new DbException($e);
		}

		return $result;
	}

}