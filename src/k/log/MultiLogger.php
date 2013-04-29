<?php

/**
 * MultiLogger
 *
 * @author lekoala
 */
class MultiLogger extends LoggerAbstract {

	protected $loggers = array();

	public function __construct() {
		
	}
	
	public function getLoggers() {
		return $this->loggers;
	}

	public function setLoggers($loggers) {
		$this->loggers = $loggers;
		return $this;
	}

	public function addLogger($logger) {
		$this->loggers[] = $logger;
		usort($this->loggers, function($a, $b) {
					if ($a->getThreshold() == $b->getThreshold()) {
						return 0;
					}
					return ($a->getThreshold() < $b->getThreshold()) ? 1 : -1;
				});
	}

	protected function _log($level, $message, $context = array()) {
		foreach ($this->loggers as $logger) {
			$logger->log($level, $message, $context);
		}
	}

}