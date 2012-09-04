<?php

namespace K\Data;

use \ReflectionClass;

/**
 * Description of Object
 *
 * @author tportelange
 */
class Object extends Type_Object {
	protected $data;
	
	public function __get($name) {
		if(isset($data[$name])) {
			return $data[$name];
		}
	}
	
	public function __set($name, $value) {
		if(isset($data[$name])) {
			$data[$name] = $value;
			return true;
		}
		return false;
	}
	
	public static function getPk() {
		
	}
	
	public static function getFk() {
		
	}
	
	public static function getFields() {
		$reflection = new ReflectionClass(get_called_class());
		$doc = $reflection->getDocComment();
		preg_match_all('/@property\s*(?P<type>[\w]*)?\s*\$(?P<field>[\w]*)/', $doc, $matchesarray);
		$fields = array_combine($matchesarray['field'], $matchesarray['type']);
		return $fields;
	}
	
	public static function validation() {
		
	}
	
	public static function query() {
		return new Query(self::getObject());
	}
	
	public static function all($where = null) {
		return static::query($where);
	}
	
	public static function one($where = null) {
		return static::query($where)->one();
	}
	
	public static function delete($where = null) {
		return Mapper::delete(get_called_class(), $where);
	}
	
	public static function update($data, $where = null) {
		return Mapper::update(get_called_class(), $data, $where);
	}
	
	public static function create() {
		return Connection::get()->create(get_called_class());
	}
}