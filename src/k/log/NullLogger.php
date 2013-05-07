<?php

namespace k\log;

/**
 * Write stuff to nowhere
 *
 * @author lekoala
 */
class NullLogger extends LoggerAbstract {

	protected function _log($level, $message, $context = array()) {
	}

}