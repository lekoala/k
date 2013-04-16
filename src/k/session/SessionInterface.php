<?php

namespace k\session;

/**
 * SessionInterface
 *
 * @link http://au1.php.net/manual/en/class.sessionhandlerinterface.php
 * @author lekoala
 */
class SessionInterface {

	public function open($path,$name);
	public function read($id);
	public function write($id,$data);
	public function close();
	public function destroy($id);
	public function gc($lifetime);

}