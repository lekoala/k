<?php

class Frank {
	public function __call($name, $arguments) {
		print_r($name);
		print_r($arguments);
	}
	
	public function __get($name) {
		print_r($name);
	}
}

$f = new Frank();
$f->{"SOME TEST"}('val','val');