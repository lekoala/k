<?php

namespace k\session;

/**
 * PdoSession
 *
 * @author lekoala
 */
class PdoSession extends SessionAbstract {

	/**
	 * @var PDO
	 */
	protected $pdo;
	protected $table = 'cache';
	protected $idField = 'id';
	protected $dataField = 'data';
	protected $accessField = 'lastaccess_ts';

	public function __construct($pdo) {
		$this->setPdo($pdo);
		$this->registerHandlers();
	}

	public function getPdo() {
		return $this->pdo;
	}

	public function setPdo(PDO $pdo) {
		$this->pdo = $pdo;
		return $this;
	}

	public function getTable() {
		return $this->table;
	}

	public function setTable($table) {
		$this->table = $table;
		return $this;
	}

	public function getIdField() {
		return $this->idField;
	}

	public function setIdField($idField) {
		$this->idField = $idField;
		return $this;
	}

	public function getDataField() {
		return $this->dataField;
	}

	public function setDataField($dataField) {
		$this->dataField = $dataField;
		return $this;
	}

	public function getAccessField() {
		return $this->accessField;
	}

	public function setAccessField($accessField) {
		$this->accessField = $accessField;
		return $this;
	}

	public function getSqlCreate() {
		return "CREATE TABLE {$this->table} (
			{$this->idField} VARCHAR(40) NOT NULL,
			{$this->dataField} TEXT NOT NULL,
			{$this->accessField} INT NOT NULL,
			PRIMARY KEY ({$this->idField})
);";
	}

	public function close() {
		//no need to do anything
	}

	public function destroy($id) {
		session_write_close(); //make sure writing and saving are done
		$stmt = $this->getPdo()->prepare("DELETE FROM {$this->table} 
			WHERE {$this->idField} = :id");
		$res = $stmt->execute(compact('id'));
		if ($res) {
			setcookie(session_name(), "", time() - 3600);
		}
		return $res;
	}

	public function gc($lifetime) {
		$stmt = $this->getPdo()->prepare("DELETE FROM {$this->table} 
			WHERE {$this->accessField} < :time)");
		return $stmt->execute(array('time' => time() - $lifetime));
	}

	public function open($path, $name) {
		//see write
	}

	public function read($id) {
		$stmt = $this->getPdo()->prepare("SELECT {$this->dataField} FROM {$this->table} 
			WHERE {$this->idField} = :id)");
		$stmt->execute(compact('id'));
		return $stmt->fetchColumn();
	}

	public function write($id, $data) {
		$time = time();
		$stmt = $this->getPdo()->prepare("UPDATE {$this->table} 
			SET {$this->dataField} = :data, {$this->accessField} = :time
			WHERE {$this->idField} = :id");
		$res = $stmt->execute(compact('id', 'data', 'time'));

		//no session? create!
		if ($stmt->rowCount() == 0) {
			$stmt = $this->getPdo()->prepare("INSERT INTO {$this->table} 
			SET {$this->idField} = :id, {$this->dataField} = :data, , {$this->accessField} = :time");
			$res = $stmt->execute(compact('id', 'data', 'time'));
		}

		return $res;
	}

}