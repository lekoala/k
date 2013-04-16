<?php

namespace k\log;

use \InvalidArgumentException;

/**
 * EmailLogger
 *
 * @author lekoala
 */
class EmailLogger extends LoggerAbstract {

	protected $recipient;
	protected $sender;

	public function __construct($recipient) {
		$this->setRecipient($recipient);
		$domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'perdu.com';
		$this->sender = 'no-reply@' . $domain;
	}
	
	public function getRecipient() {
		return $this->recipient;
	}

	public function setRecipient($recipient) {
		if(!filter_var($recipient,FILTER_VALIDATE_EMAIL)) {
			throw new InvalidArgumentException($recipient);
		}
		$this->recipient = $recipient;
		return $this;
	}

	public function getSender() {
		return $this->sender;
	}

	public function setSender($sender) {
		if(!filter_var($sender,FILTER_VALIDATE_EMAIL)) {
			throw new InvalidArgumentException($sender);
		}
		$this->sender = $sender;
		return $this;
	}

	protected function _log($level, $message, $context = array()) {
		$subject = "[$level] " . substr($message, 0, 25);
		$to = $this->recipient;
		$headers = "From: {$this->sender}\r\n" .
				'X-Mailer: PHP/' . phpversion();
		$message .= str_repeat('-',80) . "\n" . print_r($context,true);
		mail($to, $subject, $message, $headers);
	}

}