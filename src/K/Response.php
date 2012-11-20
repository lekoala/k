<?php

namespace K;

use \Symfony\Component\HttpFoundation\Cookie;
use \Symfony\Component\HttpFoundation\RedirectResponse;
use \Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Response wrapper based on Symfony Response components. Make the class usage
 * less verbose.
 */
class Response extends \Symfony\Component\HttpFoundation\Response {

	public static function redirect($url) {
		return new RedirectResponse($url);
	}

	public function setContentDisposition($filename) {
		$d = $this->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
		return $this->headers->setProperty('Content-Disposition', $d);
	}

	public function setCookie($name, $value) {
		return $this->headers->setCookie(new Cookie($name, $value));
	}

	public function setNotFound() {
		return $this->setStatusCode(404);
	}

}
