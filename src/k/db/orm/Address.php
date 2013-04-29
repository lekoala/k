<?php

namespace k\db\orm;

/**
 * Address
 */
trait Address {
	protected $street;
	protected $street_no;
	protected $zip;
	protected $city;
	protected $country_code;
	
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