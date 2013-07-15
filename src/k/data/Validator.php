<?php

namespace k\data;

/**
 * Validation Class
 *
 * Api is mostly compatible with parsleyjs if you want to use the same validation
 * rules in frontend and backend
 * 
 * @author lekoala
 * @link http://parsleyjs.org/documentation.html
 * @link https://github.com/vlucas/valitron
 */
class Validator {
	//constraints
	const ACCEPTED = 'accepted';
	const DIFFERENT = 'different';
	const REQUIRED = 'required';
	const NOT_BLANK = 'notblank';
	const MIN_LENGTH = 'minlength';
	const MAX_LENGTH = 'maxlength';
	const RANGE_LENGTH = 'rangelength'; // array
	const MIN = 'min';
	const MAX = 'max';
	const RANGE = 'range'; //array
	const REGEXP = 'regexp'; //pattern
	const EQUAL_TO = 'equalto'; //another field
	const MIN_CHECK = 'mincheck'; //array(array('f1','f2','f3'),2);
	const MAX_CHECK = 'maxcheck';
	const REMOTE = 'remote'; //url
	const MIN_WORDS = 'minwords';
	const MAX_WORDS = 'maxwords';
	const RANGE_WORDS = 'rangewords'; //array
	const GREATER_THAN = 'greaterthan'; //field
	const LESS_THAN = 'lessthan'; //field
	const LOWER_THAN = 'lowerthan';
	const BEFORE_DATE = 'beforedate';
	const AFTER_DATE = 'afterdate';
	const IN_LIST = 'inlist'; //csv
	const CONTAINS = 'contains';
	const DATE_FORMAT = 'dateFormat';
	const URL_STRICT = 'urlstrict';
	const URL_ACTIVE = 'urlActive';
	//type
	const IP = 'ip';
	const EMAIL = 'email';
	const URL = 'url';
	const DIGITS = 'digits';
	const NUMBER = 'number';
	const NUMERIC = 'numeric';
	const INTEGER = 'integer';
	const SLUG = 'slug';
	const ALPHA = 'alpha';
	const ALPHANUM = 'alphanum';
	const DATE_ISO = 'dateIso';
	const DATE = 'date';
	const TIME = 'time';
	const PHONE = 'phone';
	const LUHN = 'luhn'; //bool
	
	protected $data = [];
	protected $errors = [];
	protected $rules = [];
	protected $currentKey;
	protected static $validators = [];
	protected $validUrlPrefixes = ['http://', 'https://', 'ftp://', 'git://'];

	/**
	 *  Setup validation
	 */
	public function __construct($data = null, $rules = null, $fields = []) {
		if ($data) {
			$this->setData($data, $fields);
		}
		if ($rules) {
			$this->setRules($rules);
		}
	}

	/**
	 * Get data
	 * 
	 * @return mixed
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * Set data
	 * 
	 * @param mixed $data
	 * @param array $fields Restrict data to values in the array
	 */
	public function setData($data, $fields = []) {
		if (is_string($data)) {
			$this->data = $data;
			return;
		}
		if (is_object($data)) {
			$data = (array) $data;
		}
		if (!empty($fields)) {
			foreach ($data as $key => $value) {
				if (in_array($key, $fields)) {
					$this->data[$key] = $value;
				}
			}
		} else {
			$this->data = $data;
		}
	}

	/**
	 * Helper to get a value from data 
	 * @param string $name
	 * @param string $default
	 * @return mixed
	 */
	public function getDataByName($name, $default = null) {
		$loc = &$this->data;;
		foreach (explode('[', $name) as $step) {
			$step = trim($step,']');
			if (isset($loc[$step])) {
				$loc = &$loc[$step];
			} else {
				return $default;
			}
		}
		return $loc;
	}

	/**
	 * Add a validator
	 * 
	 * @param string $name
	 * @param mixed $cb
	 */
	public static function addValidator($name, $cb) {
		self::$validators[$name] = $cb;
	}
	
	/**
	 * Has validator
	 * 
	 * @param string $name
	 * @return boolean
	 */
	public static function hasValidator($name) {
		if(isset(self::$validators[$name])) {
			return true;
		}
		$method = 'validate' . ucfirst($name);
		if(method_exists(get_called_class(), $method)) {
			return true;
		}
		return false;
	}

	/**
	 * Get rules
	 * @return array
	 */
	public function getRules() {
		return $this->rules;
	}
	
	/**
	 * Set rules
	 * 
	 * @param array $rules
	 */
	public function setRules($rules) {
		$r = [];

		//normalize rules definitions
		foreach ($rules as $key => $keyRules) {
			if (is_string($keyRules)) {
				$keyRules = [$keyRules];
			}
			$kr = [];
			foreach ($keyRules as $name => $rule) {
				if (is_int($name)) {
					$name = $rule;
					$rule = [];
				}
				if (!is_array($rule)) {
					$rule = [$rule];
				}
				$kr[$name] = $rule;
			}
			$r[$key] = $kr;
		}

		$this->rules = $r;
	}

	/**
	 * Run validations and return boolean result
	 *
	 * @return boolean
	 */
	public function validate($throwException = false) {
		foreach ($this->rules as $key => $rules) {
			$this->currentKey = $key;
			
			foreach ($rules as $rule => $params) {
				$v = $this->getDataByName($key);

				// don't validate not required empty fields
				if ($v == '' && !isset($rules['required'])) {
					continue;
				}

				// Callback is user-specified or assumed method on class
				if (isset(static::$validators[$rule])) {
					$callback = static::$validators[$rule];
				} else {
					$callback = array($this, 'validate' . ucfirst($rule));
				}

				$args = $params;
				array_unshift($args, $v);
				$result = call_user_func_array($callback, $args);
				if (!$result) {
					$this->error($key, $v, $rule, $params);
				}
			}
		}
		
		if(count($this->errors()) !== 0) {
			if($throwException) {
				throw new ValidationException(null,0,null,$this->errors);
			}
			return false;
		}
		return true;
	}

	/**
	 * Register an error
	 * 
	 * @param string $key
	 * @param string $rule
	 * @param array $params
	 */
	protected function error($name, $value, $rule, $params) {
		$this->errors[] = compact('name', 'value', 'rule', 'params');
	}

	/**
	 * Get list of errors
	 * 
	 * @return array
	 */
	public function errors() {
		return $this->errors;
	}

	/* -- validations -- */

	/**
	 * Required field validator
	 * 
	 * @param mixed $value
	 * @return boolean
	 */
	public function validateRequired($value) {
		if (is_null($value)) {
			return false;
		} elseif (is_string($value) and trim($value) === '') {
			return false;
		}
		return true;
	}

	/**
	 * Not blank
	 * 
	 * @param mixed $value
	 * @return boolean
	 */
	public function validateNotblank($value) {
		return $value != '';
	}

	/**
	 * Validate that two values match
	 *
	 * @param  mixed   $value
	 * @param  array   $value2
	 * @return boolean
	 */
	public function validateEqualto($value, $value2) {
		$value2 = $this->getDataByName($value2, $value2);
		return $value == $value2;
	}

	/**
	 * Validate that a field is different from another field
	 *
	 * @param  mixed   $value
	 * @param  array   $value2
	 * @return boolean
	 */
	public function validateDifferent($value, $value2) {
		$value2 = $this->getDataByName($value2, $value2);
		return $value != $value2;
	}
	
	/**
	 * Validate that a field is greater than another
	 * 
	 * @param mixed $value
	 * @param mixed $value2
	 * @return boolean
	 */
	public function validateGreaterthan($value,$value2) {
		$value2 = $this->getDataByName($value2, $value2);
		return $value > $value2;
	}
	
	/**
	 * Validate that a field is lower than another
	 * 
	 * @param mixed $value
	 * @param mixed $value2
	 * @return boolean
	 */
	public function validateLessthan($value,$value2) {
		$value2 = $this->getDataByName($value2, $value2);
		return $value < $value2;
	}
	
	/**
	 * Alias less than
	 * 
	 * @param mixed $value
	 * @param mixed $value2
	 * @return boolean
	 */
	public function validateLowerthan($value,$value2) {
		return $this->validateLessthan($value, $value2);
	}

	/**
	 * Validate that a field was "accepted" (based on PHP's string evaluation rules)
	 *
	 * This validation rule implies the field is "required"
	 *
	 * @param  mixed   $value
	 * @return boolean
	 */
	public function validateAccepted($value) {
		$acceptable = array('yes', 'on', 1, true);
		return $this->validateRequired($value) && in_array($value, $acceptable, true);
	}

	/**
	 * Validate that a field is numeric
	 *
	 * @param  mixed   $value
	 * @return boolean
	 */
	public function validateNumeric($value) {
		return is_numeric($value);
	}

	/**
	 * Validate that a field is numeric
	 *
	 * @param  mixed   $value
	 * @return boolean
	 */
	public function validateNumber($value) {
		return $this->validateNumeric($value);
	}

	/**
	 * Validate that a field is an integer
	 *
	 * @param  mixed   $value
	 * @return boolean
	 */
	public function validateInteger($value) {
		return filter_var($value, FILTER_VALIDATE_INT) !== false;
	}

	/**
	 * Validate that a field is an integer
	 *
	 * @param  mixed   $value
	 * @return boolean
	 */
	public function validateDigits($value) {
		return $this->validateInteger($value);
	}

	/**
	 * Validate the length of a string
	 *
	 * @param  mixed   $value
	 * @param  array   $length
	 * @return boolean
	 */
	public function validateRange($value, $length) {
		$l = $this->length($value);
		// Length between
		if (is_array($length)) {
			return $l >= $length[0] && $l <= $length[1];
		}
		// Length same
		return $length == $l;
	}

	/**
	 * Alias for Range
	 * 
	 * @param  mixed   $value
	 * @param  array   $length
	 * @return boolean
	 */
	public function validateRangelength($value, $length) {
		return $this->validateRange($value, $length);
	}

	/**
	 * Validate the min length of a string or array
	 * 
	 * @param string|array $value
	 * @param int $minlength
	 * @return boolean
	 */
	public function validateMinlength($value, $length) {
		$l = $this->length($value);
		if ($l < $length) {
			return false;
		}
		return true;
	}

	/**
	 * Alias for minlength
	 * 
	 * @param array $value
	 * @param int $length
	 * @return boolean
	 */
	public function validateMincheck($value, $length) {
		return $this->validateMinlength($value, $length);
	}

	/**
	 * Validate the min length of a string or array
	 * 
	 * @param type $value
	 * @param type $minlength
	 * @return boolean
	 */
	public function validateMaxlength($value, $length) {
		$l = $this->length($value);
		if ($l > $length) {
			return false;
		}
		return true;
	}

	/**
	 * Alias for maxlength
	 * 
	 * @param array $value
	 * @param int $length
	 * @return boolean
	 */
	public function validateMaxcheck($value, $length) {
		return $this->validateMaxlength($value, $length);
	}

	/**
	 * Validate from a remote url
	 * 
	 * @param string $value
	 * @param string $url
	 * @return boolean
	 */
	public function validateRemote($value, $url) {
		$res = file_get_contents($url . '?' . urlencode($this->currentKey) . '=' . urlencode($value));
		if (in_array($res, ['OK', 'ok', '1', 'true'])) {
			return true;
		}
		if (strpos($res, '{') === 0) {
			$res = json_decode($res);
			if (is_array($res)) {
				return isset($res['success']);
			}
			if (is_object($res)) {
				return property_exists($res, 'success');
			}
		}
		return false;
	}

	/**
	 * Validate range of words
	 * 
	 * @param string $value
	 * @param array $range
	 * @return boolean
	 */
	public function validateRangewords($value, $range) {
		$c = str_word_count($value);
		return $c > $range[0] && $c < $range[1];
	}

	/**
	 * Validate min numbers of words
	 * 
	 * @param string $value
	 * @param int $count
	 * @return boolean
	 */
	public function validateMinwords($value, $count) {
		return str_word_count($value) > $count;
	}

	/**
	 * Validate max numbers of words
	 * 
	 * @param string $value
	 * @param int $count
	 * @return boolean
	 */
	public function validateMaxwords($value, $count) {
		return str_word_count($value) < $count;
	}

	/**
	 * Get the length of a string or array
	 *
	 * @param  string  $value
	 * @return int
	 */
	protected function length($value) {
		if (is_array($value)) {
			return count($value);
		}
		if (function_exists('mb_strlen')) {
			return mb_strlen($value);
		}
		return strlen($value);
	}
	
	/**
	 * Validate a phone number
	 * 
	 * @link http://gskinner.com/RegExr/?2u98l
	 * @param type $value
	 * @return type
	 */
	public function validatePhone($value) {
		return preg_match('/^(?!(?:\d*-){5,})(?!(?:\d* ){5,})\+?[\d- ]+$/', $value);
	}

	/**
	 * Validate the size of a field is greater than a minimum value.
	 *
	 * @param  mixed   $value
	 * @param  array   $length
	 * @return boolean
	 */
	public function validateMin($value, $length) {
		return (int) $value >= $length;
	}

	/**
	 * Validate the size of a field is less than a maximum value
	 *
	 * @param  mixed   $value
	 * @param  array   $length
	 * @return boolean
	 */
	public function validateMax($value, $length) {
		return (int) $value <= $length;
	}

	/**
	 * Validate a field is contained within a list of values
	 *
	 * @param  mixed   $value
	 * @param  array   $values
	 * @return boolean
	 */
	public function validateInlist($value, $values) {
		return in_array($value, $values);
	}

	/**
	 * Validate a field is not contained within a list of values
	 *
	 * @param  mixed   $value
	 * @param  array   $values
	 * @return boolean
	 */
	public function validateNotInlist($value, $values) {
		return !$this->validateInlist($value, $values);
	}

	/**
	 * Validate a field contains a given string
	 *
	 * @param  mixed  $value
	 * @param  array  $str
	 * @return boolean
	 */
	public function validateContains($value, $str) {
		if (!is_string($str) || !is_string($value)) {
			return false;
		}
		return (strpos($value, $str) !== false);
	}

	/**
	 * Validate luhn algorithm for credits cards
	 * 
	 * @link http://rosettacode.org/wiki/Luhn_test_of_credit_card_numbers#PHP
	 * @param string $value
	 * @return boolean
	 */
	public function validateLuhn($value) {
		//keep numeric values
		$value = preg_replace("/[^0-9]+/","",$value);
		
		//make sum
		$len = strlen($value);
		for ($i = $len - 1; $i >= 0; $i--) {
			$ord = ord($value[$i]);
			if (($len - 1) & $i) {
				$sum += $ord;
			} else {
				$sum += $ord / 5 + (2 * $ord) % 10;
			}
		}
		
		return $sum % 10 == 0;
	}

	/**
	 * Validate that a field is a valid IP address
	 *
	 * @param  mixed   $value
	 * @return boolean
	 */
	public function validateIp($value) {
		return filter_var($value, FILTER_VALIDATE_IP) !== false;
	}

	/**
	 * Validate that a field is a valid e-mail address
	 *
	 * @param  mixed   $value
	 * @return boolean
	 */
	public function validateEmail($value) {
		return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
	}

	/**
	 * Validate that a field is a valid URL by syntax
	 *
	 * @param  mixed   $value
	 * @return boolean
	 */
	public function validateUrl($value) {
		return filter_var($value, FILTER_VALIDATE_URL) !== false;
	}

	/**
	 * Validate that a field is a valid URL by syntax
	 *
	 * @param  mixed   $value
	 * @return boolean
	 */
	public function validateUrlstrict($value) {
		foreach ($this->validUrlPrefixes as $prefix) {
			if (strpos($value, $prefix) !== false) {
				return filter_var($value, FILTER_VALIDATE_URL) !== false;
			}
		}
		return false;
	}

	/**
	 * Validate that a field is an active URL by verifying DNS record
	 *
	 * @param  mixed   $value
	 * @return boolean
	 */
	public function validateUrlActive($value) {
		foreach ($this->validUrlPrefixes as $prefix) {
			if (strpos($value, $prefix) !== false) {
				$url = str_replace($prefix, '', strtolower($value));

				return checkdnsrr($url);
			}
		}
		return false;
	}

	/**
	 * Validate that a field contains only alphabetic characters
	 *
	 * @param  mixed   $value
	 * @return boolean
	 */
	public function validateAlpha($value) {
		return preg_match('/^([a-z])+$/i', $value);
	}

	/**
	 * Validate that a field contains only alpha-numeric characters
	 *
	 * @param  mixed   $value
	 * @return boolean
	 */
	public function validateAlphanum($value) {
		return preg_match('/^([a-z0-9])+$/i', $value);
	}

	/**
	 * Validate that a field contains only alpha-numeric characters, dashes, and underscores
	 *
	 * @param  mixed   $value
	 * @return boolean
	 */
	public function validateSlug($value) {
		return preg_match('/^([-a-z0-9_-])+$/i', $value);
	}

	/**
	 * Validate that a field passes a regular expression check
	 *
	 * @param  mixed   $value
	 * @return boolean
	 */
	public function validateRegex($value, $regex) {
		return preg_match($regex, $value);
	}
	
	/**
	 * Alias validateRegex
	 */
	public function validateRegexp($value, $regex) {
		return $this->validateRegex($value, $regex);
	}

	/**
	 * Validate that a field is a valid date
	 *
	 * @param  mixed   $value
	 * @return boolean
	 */
	public function validateDate($value) {
		return strtotime($value) !== false;
	}
	
	/**
	 * Validate that a field is a valid time
	 *
	 * @param  mixed   $value
	 * @return boolean
	 */
	public function validateTime($value) {
		return preg_match('/^(?:(?:([01]?\d|2[0-3]):)?([0-5]?\d):)?([0-5]?\d)$/',$value) !== false;
	}

	/**
	 * Validate that a field matches a date format
	 *
	 * @param  mixed   $value
	 * @return boolean
	 */
	public function validateDateFormat($value, $format) {
		$parsed = date_parse_from_format($format, $value);

		return $parsed['error_count'] === 0;
	}

	/**
	 * Validate the date is before a given date
	 *
	 * @param  mixed   $value
	 * @param  array   $time
	 * @return boolean
	 */
	public function validateBeforedate($value, $time) {
		$vtime = ($value instanceof \DateTime) ? $value->getTimestamp() : strtotime($value);
		$ptime = ($time instanceof \DateTime) ? $time->getTimestamp() : strtotime($time);
		return $vtime < $ptime;
	}

	/**
	 * Validate the date is after a given date
	 *
	 * @param  mixed   $value
	 * @param  array   $time
	 * @return boolean
	 */
	public function validateAfterdate($value, $time) {
		$vtime = ($value instanceof \DateTime) ? $value->getTimestamp() : strtotime($value);
		$ptime = ($time instanceof \DateTime) ? $time->getTimestamp() : strtotime($time);
		return $vtime > $ptime;
	}

	/**
	 * Validate an iso date
	 * 
	 * @param string $value
	 * @return boolean
	 */
	public function validateDateIso($value) {
		return preg_match('/^([\+-]?\d{4}(?!\d{2}\b))((-?)((0[1-9]|1[0-2])(\3([12]\d|0[1-9]|3[01]))?|W([0-4]\d|5[0-2])(-?[1-7])?|(00[1-9]|0[1-9]\d|[12]\d{2}|3([0-5]\d|6[1-6])))([T\s]((([01]\d|2[0-3])((:?)[0-5]\d)?|24\:?00)([\.,]\d+(?!:))?)?(\17[0-5]\d([\.,]\d+)?)?([zZ]|([\+-])([01]\d|2[0-3]):?([0-5]\d)?)?)?)?$/', $value);
	}

}