<?php

namespace k\cache;

use \InvalidArgumentException;
use \DirectoryIterator;

/**
 * File based cache
 *
 * @author lekoala
 */
class FileCache extends CacheAbstract {

	protected $dir;

	public function __construct($dir) {
		$this->setDir($dir);
	}

	public function getDir() {
		return $this->dir;
	}

	public function setDir($dir) {
		$this->dir = $dir;
		return $this;
	}
	
	protected function getFile($key) {
		return $this->getDir() . '/' . $key . '.cache';
	}
	
	public function clean() {
		$di = new DirectoryIterator($this->getDir());
		$t = time();
		foreach($di as $fi) {
			if($fi->isFile() && $fi->getExtension() == 'cache') {
				$file = $fi->getPathname();
				$handle = fopen($file,'r');
				$expire = trim(fgets($handle));
				fclose($handle);
				if($expire && $expire < $t) {
					unlink($file);
				}
			}
		}
	}
	
	protected function _clear($key = null) {
		if($key) {
			$file = $this->getFile($key);
			if($file) {
				return unlink($file);
			}
			return true;
		}
		$di = new DirectoryIterator($this->getDir());
		foreach($di as $fi) {
			if($fi->isFile() && $fi->getExtension() == 'cache') {
				unlink($fi->getPathname());
			}
		}
		return true;
	}

	protected function _get($key) {
		$file = $this->getFile($key);
		if (is_file($file)) {
			$handle = fopen($file,'r');
			$expire = fgets($handle);
			if ($expire != 0 && $expire < time()) {
				fclose($handle);
				unlink($file);
				return null;
			}
			$value = '';
			while(!feof($handle)) {
				$value .= fread($handle,8192);
			}
			fclose($handle);
			return $value;
		}
		return null;
	}

	protected function _set($key, $value, $ttl = 0) {
		return file_put_contents($this->getFile($key), $this->getExpire($ttl) . "\n" . $value, LOCK_EX);
	}
}