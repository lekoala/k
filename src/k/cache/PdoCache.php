<?php

namespace k\cache;

use \PDO;
use \InvalidArgumentException;

/**
 * Pdo based cache
 *
 * @author lekoala
 */
class PdoCache extends CacheAbstract {

	/**
	 * @var PDO
	 */
	protected $pdo;
	protected $table = 'cache';
	protected $valueField = 'value';
	protected $keyField = 'key';
	protected $expireField = 'expire_ts';

	public function __construct($pdo) {
		$this->setPdo($pdo);
	}
	
	/**
	 * @return PDO
	 */
	public function getPdo() {
		return $this->pdo;
	}

	public function setPdo(PDO $pdo) {
		if(!$pdo instanceof \PDO) {
			throw new InvalidArgumentException("You must pass an instance of PDO");
		}
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

	public function getValueField() {
		return $this->valueField;
	}

	public function setValueField($valueField) {
		$this->valueField = $valueField;
		return $this;
	}

	public function getKeyField() {
		return $this->keyField;
	}

	public function setKeyField($keyField) {
		$this->keyField = $keyField;
		return $this;
	}

	public function getExpireField() {
		return $this->expireField;
	}

	public function setExpireField($expireField) {
		$this->expireField = $expireField;
		return $this;
	}
	
	/**
	 * Return a sample sql statement to create the cache table
	 * 
	 * @return string
	 */
	public function getSqlCreate() {
		return "CREATE TABLE {$this->table} (
			{$this->keyField} VARCHAR(255) NOT NULL,
			{$this->valueField} TEXT NOT NULL,
			{$this->expireField} TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY ({$this->keyField})
);";
	}
	
	public function clean() {
		$time = time();
		$stmt = $this->getPdo()->prepare("DELETE FROM {$this->table} WHERE {$this->expireField} != 0 AND {$this->expireField} < :time");
		return $stmt->execute(compact('time'));
	}
	
	protected function _clear($key = null) {
		if($key) {
			$stmt = $this->getPdo()->prepare("DELETE FROM {$this->table} WHERE {$this->keyField} = :key");
			$args = compact('key');
		}
		else {
			$stmt = $this->getPdo()->prepare("DELETE FROM {$this->table}");
			$args = array();
		}
		return $stmt->execute($args);
	}

	protected function _get($key) {
		$stmt = $this->getPdo()->prepare("SELECT {$this->valueField} FROM {$this->table} WHERE {$this->keyField} = :key AND ({$this->expireField} = 0 OR {$this->expireField} >= :time)");
		$time = time();
		$stmt->execute(compact('key', 'time'));
		$res = $stmt->fetchColumn();
		if($res) {
			return $res;
		}
		return null;
	}

	protected function _set($key, $value, $ttl = 0) {
		$expire = $this->getExpire($ttl);
		$stmt = $this->getPdo()->prepare("SELECT {$this->valueField} FROM {$this->table} WHERE {$this->keyField} = :key");
		$stmt->execute(compact('key'));
		$res = $stmt->fetch();
		if (!$res) {
			$stmt = $this->getPdo()->prepare("INSERT INTO {$this->table}({$this->keyField}, {$this->valueField}, {$this->expireField}) VALUES (:key,:value,:expire)");
			return $stmt->execute(compact('key', 'value', 'expire'));
		} else {
			$stmt = $this->getPdo()->prepare("UPDATE {$this->table} SET {$this->keyField} = :key, {$this->valueField} = :value, {$this->expireField}  = :expire");
			return $stmt->execute(compact('key', 'value', 'expire'));
		}
	}

}