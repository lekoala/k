<?php
namespace k;

use \Exception;

class module {
	protected $dir;
	protected $viewDir = 'views';
	
	function __construct() {
		$this->dir = __DIR__;
	}
	
	function setDir($dir) {
		if(!is_dir($dir)) {
			throw new Exception('Not a directory : ' . $dir);
		}
		$this->dir = $dir;
	}
	
	function dispatch($params) {
		$templ = '';
		$i = 0;
		foreach($params as $k => $v) {
			if(!empty($templ)) {
				$i++;
				if($i > 2) {
					break;
				}
				$templ .= '/';
			}
			$templ .= $v;
		}
		
		if(empty($templ)) {
			$templ = 'index';
		}
		
		$file = $this->dir . DIRECTORY_SEPARATOR . $this->viewDir . DIRECTORY_SEPARATOR . $templ;
		
		
		
		echo '<pre>' . __LINE__ . "\n";
		print_r($file);
		exit();
	}
}
