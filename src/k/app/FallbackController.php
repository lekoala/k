<?php

namespace k\app;

/**
 * Display something
 *
 * @author lekoala
 */
class FallbackController extends Controller {

	public function __call($name, $arguments) {
		return $name;
	}

}