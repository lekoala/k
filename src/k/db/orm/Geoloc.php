<?php

namespace k\db\orm;

/**
 * Geoloc
 */
trait Geoloc {

	public $lat;
	public $lng;
	public $accuracy;
	public $mapped_address;

	public static function typesGeoloc() {
		return [
			'lat' => 'FLOAT(10,6)',
			'lng' => 'FLOAT(10,6)',
			'accuracy' => 'DECIMAL(10)',
			'mapped_address' => 'VARCHAR(255)'
		];
	}

	public function geocode($save = true) {
		$address = $this->mapped_address;
		//if you use the Address trait
		if (empty($address) && method_exists($this, 'get_address')) {
			$address = $this->get_address();
		}
		$url = sprintf('http://maps.google.com/maps?output=js&q=%s', rawurlencode($address));
		if ($result = file_get_contents($url)) {
			if (strpos($result, 'errortips') > 1 || strpos($result, 'Did you mean:') !== false) {
				return false;
			}
			preg_match('!center:\s*{lat:\s*(-?\d+\.\d+),lng:\s*(-?\d+\.\d+)}!U', $result, $match);
			$this->lat = $match[1];
			$this->lng = $match[2];
			if ($save) {
				$this->save;
			}
			return array(
				'lat' => $this->lat,
				'lng' => $this->lng
			);
		}
		return false;
	}

	public function reverseGeocode($save = true) {
		if (empty($this->lat)) {
			return false;
		}
		$url = "http://maps.googleapis.com/maps/api/geocode/json?latlng=" . $this->lat . "," . $this->lng . "&sensor=false";
		$data = file($url);
		foreach ($data as $num => $line) {
			if (false != strstr($line, "\"formatted_address\"")) {
				$this->mapped_address = substr(trim($line), 23, -2);
				if ($save) {
					$this->save();
				}
			}
		}
		return false;
	}

}