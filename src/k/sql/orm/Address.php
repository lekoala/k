<?php

namespace k\sql\orm;

/**
 * Address
 */
trait Address {
	
	public static $addressFields = array(
		'building' => 'VARCHAR(255)',
		'street' => 'VARCHAR(255)',
		'street_no' => 'VARCHAR',
		'zip' => 'VARCHAR(20)',
		'city' => 'VARCHAR(255)',
		'country_code' => 'VARCHAR(2)'
	);
	
	function address() {
		if (empty($this->street) || ($this->location() == '')) {
			return '';
		}
		return $this->street . ' ' . $this->street_no . ' ' . $this->location();
	}

	function location() {
		if (empty($this->zipcode) || empty($this->city)) {
			return '';
		}
		return $this->zipcode . ' ' . $this->city;
	}
}