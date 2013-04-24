<?php

namespace k;

/**
 * ViewRenderer
 *
 * @author lekoala
 */
class ViewRenderer {
	protected $baseDir;
	protected $defaultView = 'index';
	protected $defaultExtension = 'phtml';
	protected $layout;
	protected $viewDir = 'views';

	public function __construct() {
		
	}
	
	public function getBaseDir() {
		return $this->baseDir;
	}

	public function setBaseDir($baseDir) {
		$this->baseDir = $baseDir;
		return $this;
	}
	
	public function getDefaultView() {
		return $this->defaultView;
	}

	public function setDefaultView($defaultView) {
		$this->defaultView = $defaultView;
		return $this;
	}

	public function getDefaultExtension() {
		return $this->defaultExtension;
	}

	public function setDefaultExtension($defaultExtension) {
		$this->defaultExtension = $defaultExtension;
		return $this;
	}

	public function getLayout() {
		return $this->layout;
	}

	public function setLayout($layout) {
		$this->layout = $layout;
		return $this;
	}
	
	public function getViewDir() {
		return $this->viewDir;
	}

	public function setViewDir($viewDir) {
		$this->viewDir = $viewDir;
		return $this;
	}
	
	public function resolve($filename, $dir = null) {
		if(empty($filename)) {
			return false;
		}
		if(strpos($filename,'/') === 0 && is_file($filename)) {
			return $filename;
		}
		
		//append extension if needed
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		if (empty($ext)) {
			$filename .= '.' . $this->getDefaultExtension();
		}
		
		//if we don't have a dir, use the base one
		if($dir === null) {
			$dir = $this->baseDir;
		}
		//if the view dir is not appended, add it
		if(strpos($dir, '/' . $this->getViewDir()) === false) {
			$dir .= '/' . $this->getViewDir();
		}
		//if the file don't start with /, prepend dir
		if(strpos($filename,'/') !== 0) {
			$filename = $dir . '/' . $filename;
		}
		if(is_file($filename)) {
			return $filename;
		}
		return false;
	}
	
	public function render($filename, $vars = array()) {
		if (!is_array($vars)) {
			$vars = array('content' => $vars);
		}
		//recursive views
		if (is_array($filename)) {
			foreach ($filename as $file) {
				$content = $this->render($file, $vars);
				$vars['content'] = $content;
			}
			return $content;
		}
		
		$filename = $this->resolve($filename);
		if(!$filename) {
			return false;
		}
		
		// Extract variables as references
		extract(array_merge($vars), EXTR_REFS);

		//cleanup scope
		unset($vars);

		ob_start();
		include($filename);
		return ob_get_clean();
	}
	
	public function renderWithLayout($view) {
		return $this->render(array($view,$this->getLayout()));
	}
}