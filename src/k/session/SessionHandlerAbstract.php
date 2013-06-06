<?php

namespace k\http;

use \SessionHandlerInterface;

/**
 * SessionAbstract
 *
 * @author lekoala
 */
abstract class SessionHandlerAbstract implements SessionHandlerInterface {

	protected function registerHandlers() {
		session_set_save_handler(
		array($this, 'open'), array($this, 'close'), array($this, 'read'), array($this, 'write'), array($this, 'destroy'), array($this, 'gc')
		);
	}

	abstract public function close();

	abstract public function destroy($session_id);

	abstract public function gc($maxlifetime);

	abstract public function open($save_path, $name);

	abstract public function read($session_id);

	abstract public function write($session_id, $session_data);

	public function start() {
		session_start();
	}

	public function __destruct() {
		session_regenerate_id(true);
		session_write_close();
	}

}