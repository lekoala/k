<?php

namespace K;

use \Exception;

class App {

	protected $request;
	protected $response;
	protected $user;
	protected $router;
	protected $db;
	protected $debugBar;

	public function __construct($config = null) {
		if ($config) {
			$this->applyConfig($config);
		}
		
		$this->request = new Request();
		$this->response = new Response();
	}

	public function applyConfig($config) {
		foreach ($config->toArray() as $obj => $opts) {
			if (property_exists($this, $obj)) {
				if (is_object($this->$obj) && method_exists($this->$obj, 'configure')) {
					$this->$obj->configure($opts);
				} else {
					$class = 'K\\' . ucfirst($obj);
					$this->$obj = new $class($opts);
				}
			}
		}
	}

	public function __toString() {
		try {
			return (string) $this->response;
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}

}