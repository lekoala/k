<?php

namespace k\db\orm;

/**
 * Address
 */
trait Address {

	public $building;
	public $street;
	public $street_no;
	public $zipcode;
	public $city;
	public $country_code;
	
	public static function typesAddress() {
		return [
			'building' => 'VARCHAR(255)',
			'street' => 'VARCHAR(255)',
			'street_no' => 'VARCHAR',
			'zipcode' => 'VARCHAR(20)',
			'city' => 'VARCHAR(255)',
			'country_code' => 'VARCHAR(2)'
		];
	}

	public function get_address() {
		if (empty($this->street) || ($this->location = '')) {
			return '';
		}
		return trim($this->street . ', ' . $this->street_no);
	}

	public function get_location() {
		if (empty($this->zipcode) || empty($this->city)) {
			return '';
		}
		return trim($this->zipcode . ' ' . $this->city);
	}
	
	public function get_address_location() {
		return trim($this->get_address() . ' ' . $this->get_location());
	}

}