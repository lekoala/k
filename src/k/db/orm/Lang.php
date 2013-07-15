<?php

namespace k\db\orm;

/**
 * Allows translation of a record
 * The record must specify a static property called langField with the fields to be translated 
 * Translated fields are added to the records and prefixed with the lang code
 * 
 * You must create a $_lang property on the model
 */
trait Lang {
	protected $_readingLang;
	protected $_translations = [];
	protected static $_langTable = 'lang';
	protected static $_langKey = 'code';

	public static function getTableLang() {
		return static::getTableName() . static::$_langTable;
	}

	public static function getLangForeignKey() {
		return static::$_langTable . '_' . static::$_langKey;
	}

	public static function createTableLang($execute = true) {
		$fields = static::getFields();
		if (!in_array('id', $fields)) {
			throw new \Exception('To use this trait, the table must have a field id');
		}
		if (!property_exists(get_called_class(), '_lang')) {
			throw new Exception('You must defined a static property $_lang to use this trait');
		}
		$ttable = static::getTableLang();
		$fields = static::$_lang;
		$pk = [];
		$pk[] = static::getForForeignKey();
		$pk[] = static::getLangForeignKey();
		$fields = array_merge($pk, $fields);
		$fields = static::buildFieldsArray($fields);
		return static::getPdo()->createTable($ttable, $fields, $pk, array(), $execute);
	}

	public static function dropTableLang() {
		return static::getPdo()->dropTable(static::getTableLang());
	}

	/**
	 * Get a translation
	 * If lang is not specified, the getRadingLang method is called
	 *  
	 * @param string $field
	 * @param string $lang
	 * @return string
	 */
	public function getTranslation($field, $lang = null) {
		if($lang === null) {
			$lang = $this->getReadingLang();
		}
		if ($this->_translations === null) {
			$this->_translations = static::getPdo()->select(array(static::getForForeignKey => $this->getId()));
		}
		$fk = static::getLangForeignKey();
		foreach ($this->_translations as $t) {
			if ($t[$fk] == $lang) {
				return isset($t[$field]) ? $t[$field] : null;
			}
		}
	}

	/**
	 * Set a translation
	 * 
	 * @param string $field
	 * @param string $v
	 * @param string $lang
	 * @return boolean
	 */
	public function setTranslation($field, $v, $lang = null) {
		if($lang === null) {
			$lang = $this->getReadingLang();
		}
		if ($this->_translations === null) {
			$this->_translations = static::getPdo()->select(static::getTableLang(), array(static::getForForeignKey() => $this->getId()));
		}
		$fk = static::getLangForeignKey();
		foreach ($this->_translations as $t) {
			if ($t[$fk] == $lang) {
				$t[$field] = $v;
				return true;
			}
		}
		//push a new translation
		$lk = static::getLangForeignKey();
		$this->_translations[] = [
			$fk => $this->getId(),
			$lk => $lang,
			$field => $v
		];
	}

	protected function onPostSaveLang() {
		$lk = static::getLangForeignKey();
		$fk = static::getForForeignKey();
		$exists = $this->exists();
		foreach ($this->_translations as $translation) {
			if (!$exists) {
				static::getPdo()->insert(static::getTableLang(), $translation);
			} else {
				static::getPdo()->update(static::getTableLang(), $translation, array(
					$fk => $this->getId(),
					$lk => $translation[$lk]
						)
				);
			}
		}
	}
	
	public static function getDefaultLang() {
		return 'en';
	}
	
	public function setReadingLang($lang) {
		$this->_readingLang = $lang;
	}
	
	public function getReadingLang() {
		if($this->_readingLang === null) {
			$this->_readingLang = static::getDefaultLang();
		}
		return $this->_readingLang;
	}

}