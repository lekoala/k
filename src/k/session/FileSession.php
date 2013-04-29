<?php

namespace k\session;

/**
 * SessionAbstract
 *
 * @author lekoala
 */
class FileSession extends SessionAbstract {

	protected $path;
	
	public function __construct($path = null) {
		if($path === null) {
			$path = session_save_path();
		}
		$this->setPath($path);
		$this->registerHandlers();
	}
	
	public function getPath() {
		return $this->path;
	}

	public function setPath($path) {
		if (!is_dir($this->path)) {
			mkdir($this->path, 0777);
		}
		$this->path = $path;
		return $this;
	}

	public function open($path, $name) {
		//path is set on object instantiation
	}

	public function close() {
		return true;
	}

	public function read($id) {
		return (string) @file_get_contents("$this->path/sess_$id");
	}

	public function write($id, $data) {
		return file_put_contents("$this->path/sess_$id", $data) === false ? false : true;
	}

	public function destroy($id) {
		$file = "$this->path/sess_$id";
		if (file_exists($file)) {
			unlink($file);
		}

		return true;
	}

	public function gc($maxlifetime) {
		foreach (glob("$this->path/sess_*") as $file) {
			if (filemtime($file) + $maxlifetime < time() && file_exists($file)) {
				unlink($file);
			}
		}

		return true;
	}

}