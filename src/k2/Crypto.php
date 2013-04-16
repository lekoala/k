<?php

namespace k;

/**
 * Description of Crypto
 *
 * @author tportelange
 */
class Crypto {

	use TConfigure;

	protected static $key;

	public static function safe_b64encode($string) {
		$data = base64_encode($string);
		$data = str_replace(array('+', '/', '='), array('-', '_', ''), $data);
		return $data;
	}

	public static function safe_b64decode($string) {
		$data = str_replace(array('-', '_'), array('+', '/'), $string);
		$mod4 = strlen($data) % 4;
		if ($mod4) {
			$data .= substr('====', $mod4);
		}
		return base64_decode($data);
	}

	public static function encode($value) {
		if (empty($value)) {
			return false;
		}
		if (!self::$key) {
			throw new Exception("You must set a key before using this class");
		}
		$text = $value;
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, self::$key, $text, MCRYPT_MODE_ECB, $iv);
		return trim(self::safe_b64encode($crypttext));
	}

	public static function decode($value) {
		if (!$value) {
			return false;
		}
		if (!self::$key) {
			throw new Exception("You must set a key before using this class");
		}
		$crypttext = self::safe_b64decode($value);
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$decrypttext = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, self::$key, $crypttext, MCRYPT_MODE_ECB, $iv);
		return trim($decrypttext);
	}

}