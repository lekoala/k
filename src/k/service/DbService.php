<?php

namespace k\service;

use \InvalidArgumentException;

/**
 * Service
 *
 * @author lekoala
 */
class DbService extends Service {

	protected $table;
	protected $model;

	/**
	 * @return \k\db\Pdo
	 */
	public function getDb() {
		return $this->getApp()->getDb();
	}

	public function getTable() {
		if($this->table === null) {
			$this->table = str_replace($this->getApp()->getServicePrefix(),'',get_called_class());
			$this->table = strtolower(preg_replace('/(?<=\\w)(?=[A-Z])/',"_$1", $this->table));
		}
		return $this->table;
	}

	public function setTable($table) {
		$this->table = $table;
		return $this;
	}
	
	public function getModel() {
		return $this->model;
	}

	public function setModel($model) {
		$this->model = $model;
	}

	/**
	 * Query
	 * 
	 * @param string $table
	 * @param string $class
	 * @return \k\db\Query
	 */
	public function q($table = null, $class = null) {
		if ($table === null) {
			$table = $this->getTable();
		}
		$q = $this->getDb()->q($this->table);
		$model = $this->getModel();
		if($class) {
			$model = $class;
		}
		if($model) {
			$q->fetchAs($model);
		}
		return $q;
	}
	
	public function get($id) {
		$q = $this->q();
		$q->where('id',$id);
		return $q->fetchOnlyOne();
	}

	/**
	 * Insert records
	 * 
	 * @param array $data
	 * @param string $data
	 * @return int The id of the record
	 */
	public function insert(array $data, $table = null) {
		if ($table === null) {
			$table = $this->getTable();
		}
		return $this->getDb()->insert($table, $data);
	}

	/**
	 * Update records
	 * 
	 * @param array $data
	 * @param array|string $where
	 * @param array $params
	 * @param string $table
	 * @return bool
	 */
	public function update(array $data, $where = null, $params = array(), $table = null) {
		if ($table === null) {
			$table = $this->getTable();
		}
		return $this->getDb()->update($table, $data, $where, $params);
	}

	/**
	 * Delete records
	 * 
	 * @param array|string $where
	 * @param array $params
	 * @param string $table
	 * @return bool
	 */
	public function delete($where = null, $params = array(), $table = null) {
		return $this->getDb()->delete($table, $where, $params);
	}

}