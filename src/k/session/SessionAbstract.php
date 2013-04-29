<?php

namespace k\session;

/**
 * SessionAbstract
 *
 * @author lekoala
 */
class SessionAbstract extends SessionInterface {

	protected function registerHandlers() {
		session_set_save_handler(
				array($this, 'open'), array($this, 'close'), array($this, 'read'), array($this, 'write'), array($this, 'destroy'), array($this, 'gc')
		);
	}

	public function start() {
		session_start();
	}

	public function __destruct() {
		session_regenerate_id(true);
		session_write_close();
	}

}