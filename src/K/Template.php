<?php

namespace K;

class template {
	static protected $global_vars = array();
	protected $filename;
	protected $vars;
	
	function __construct($filename, $vars) {
		if (!is_file($filename)) {
			throw new exception($filename . ' does not exist');
		}
		if (!is_array($vars)) {
			$vars = array('content' => $vars);
		}
		
		$this->filename = $filename;
		$this->vars = $vars;
	}
	
	/**
	 * Render a view file or multiple view file (eg : inner_view, layout)
	 * When rendering multiple view file, the embedded view is always placed
	 * in $content variable
	 * 
	 * @param string|array $filename
	 * @param array $vars (optional)
	 * @return string
	 */
	function render() {
		extract(array_merge($this->vars, self::$global_vars), EXTR_REFS);

		ob_start();
		include($this->filename);
		$output = ob_get_clean();

		return $output;
	}
	
	public function __toString() {
		return $this->render();
	}
}