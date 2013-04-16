<?php

namespace k;

/**
 * String helper class
 * 
 * @author lekoala
 */
class Str {

	protected static $pluralRules = array(
		'([ml])ouse$' => '\1ice',
		'(media|info(rmation)?|news)$' => '\1',
		'(phot|log|vide)o$' => '\1os',
		'^(q)uiz$' => '\1uizzes',
		'(c)hild$' => '\1hildren',
		'(p)erson$' => '\1eople',
		'(m)an$' => '\1en',
		'([ieu]s|[ieuo]x)$' => '\1es',
		'([cs]h)$' => '\1es',
		'(ss)$' => '\1es',
		'([aeo]l)f$' => '\1ves',
		'([^d]ea)f$' => '\1ves',
		'(ar)f$' => '\1ves',
		'([nlw]i)fe$' => '\1ves',
		'([aeiou]y)$' => '\1s',
		'([^aeiou])y$' => '\1ies',
		'([^o])o$' => '\1oes',
		's$' => 'ses',
		'(.)$' => '\1s'
	);
	protected static $singularRules = array(
		'([ml])ice$' => '\1ouse',
		'(media|info(rmation)?|news)$' => '\1',
		'(q)uizzes$' => '\1uiz',
		'(c)hildren$' => '\1hild',
		'(p)eople$' => '\1erson',
		'(m)en$' => '\1an',
		'((?!sh).)oes$' => '\1o',
		'((?<!o)[ieu]s|[ieuo]x)es$' => '\1',
		'([cs]h)es$' => '\1',
		'(ss)es$' => '\1',
		'([aeo]l)ves$' => '\1f',
		'([^d]ea)ves$' => '\1f',
		'(ar)ves$' => '\1f',
		'([nlw]i)ves$' => '\1fe',
		'([aeiou]y)s$' => '\1',
		'([^aeiou])ies$' => '\1y',
		'(la)ses$' => '\1s',
		'(.)s$' => '\1'
	);

	private function __construct() {
		//cannot be instantiated
	}

	/**
	 * Convert a var to a string
	 * 
	 * @param mixed $var
	 * @param string $glue
	 * @return string
	 */
	public static function make($var, $glue = ',') {
		if (empty($var)) {
			return '';
		}
		if (is_bool($var)) {
			if ($var) {
				return 'true';
			}
			return 'false';
		}
		if (is_string($var) || is_int($var) || is_float($var)) {
			return (string) $var;
		}
		if (is_array($var)) {
			$string = implode($glue, $var);
			return $string;
		}
		if (is_object($var)) {
			if (method_exists($var, '__toString')) {
				return (string) $var;
			}
			throw new Exception('Object does not have a __toString method');
		}
		throw new Exception('Stringify does not support objects of type ' . gettype($var));
	}

	/**
	 * The opposite of nl2br
	 * 
	 * @param string $string
	 * @return string
	 */
	public static function br2nl($string) {
		return str_replace(array('<br>', '<br/>', '<br />'), "\n", $string);
	}

	/**
	 * Check if string starts with needle
	 * 
	 * @param string $haystack
	 * @param string $needle
	 * @param bool $case
	 * @return bool
	 */
	public static function startsWith($haystack, $needle, $case = true) {
		if ($case) {
			return strpos($haystack, $needle, 0) === 0;
		}

		return stripos($haystack, $needle, 0) === 0;
	}

	/**
	 * Check if string ends with needle
	 * 
	 * @param string $haystack
	 * @param string $needle
	 * @param bool $case
	 * @return bool
	 */
	public static function endsWith($haystack, $needle, $case = true) {
		$expectedPosition = strlen($haystack) - strlen($needle);

		if ($case) {
			return strrpos($haystack, $needle, 0) === $expectedPosition;
		}

		return strripos($haystack, $needle, 0) === $expectedPosition;
	}

	/**
	 * Pluralizes English nouns.
	 *
	 * @param    string    $word    English noun to pluralize
	 * @return string Plural noun
	 */
	public static function pluralize($word) {
		foreach (self::$pluralRules as $from => $to) {
			if (preg_match('#' . $from . '#iD', $word)) {
				$word = preg_replace('#' . $from . '#iD', $to, $word);
				break;
			}
		}
		return $word;
	}

	/**
	 * Singularizes English nouns.
	 *
	 * @param    string    $word    English noun to singularize
	 * @return string Singular noun.
	 */
	public static function singularize($word) {
		foreach (self::$singularRules as $from => $to) {
			if (preg_match('#' . $from . '#iD', $word)) {
				$word = preg_replace('#' . $from . '#iD', $to, $word);
				break;
			}
		}
		return $word;
	}

	/**
	 * Returns given word as CamelCased
	 *
	 * Converts a word like "send_email" to "SendEmail". It
	 * will remove non alphanumeric character from the word, so
	 * "who's online" will be converted to "WhoSOnline"
	 *
	 * @param    string    $word    Word to convert to camel case
	 * @param bool $upper
	 * @return string UpperCamelCasedWord
	 */
	public static function camelize($word, $upper = true) {
		$word = str_replace(' ', '', ucwords(preg_replace('/[^A-Z^a-z^0-9]+/', ' ', $word)));
		if ($upper) {
			return $word;
		}
		return lcfirst($word);
	}

	/**
	 * Converts a word "into_it_s_underscored_version"
	 *
	 * Convert any "CamelCased" or "ordinary Word" into an
	 * "underscored_word".
	 *
	 * This can be really useful for creating friendly URLs.
	 *
	 * @access public
	 * @param    string    $word    Word to underscore
	 * @return string Underscored word
	 */
	public static function underscorize($word) {
		return strtolower(preg_replace('/[^A-Z^a-z^0-9]+/', '_', preg_replace('/([a-zd])([A-Z])/', '1_2', preg_replace('/([A-Z]+)([A-Z][a-z])/', '1_2', $word))));
	}

	/**
	 * Returns a human-readable string from $word
	 *
	 * Returns a human-readable string from $word, by replacing
	 * underscores with a space, and by upper-casing the initial
	 * character by default.
	 *
	 * If you need to uppercase all the words you just have to
	 * pass 'all' as a second parameter.
	 *
	 * @param    string    $word    String to "humanize"
	 * @param    string    $uppercase    If set to 'all' it will uppercase all the words
	 * instead of just the first one.
	 * @return string Human-readable word
	 */
	public static function humanize($word, $uppercase = '') {
		$uppercase = $uppercase == 'all' ? 'ucwords' : 'ucfirst';
		return $uppercase(str_replace('_', ' ', preg_replace('/_id$/', '', $word)));
	}

	/**
	 * Converts number to its ordinal English form.
	 *
	 * This method converts 13 to 13th, 2 to 2nd ...
	 *
	 * @param    integer    $number    Number to get its ordinal value
	 * @return string Ordinal representation of given string.
	 */
	public static function ordinalize($number) {
		if (in_array(($number % 100), range(11, 13))) {
			return $number . 'th';
		} else {
			switch (($number % 10)) {
				case 1:
					return $number . 'st';
					break;
				case 2:
					return $number . 'nd';
					break;
				case 3:
					return $number . 'rd';
				default:
					return $number . 'th';
					break;
			}
		}
	}

	/**
	 * Strips all non-ASCII characters.
	 *
	 * @access  public
	 * @param   string  $string  The input string
	 * @return  string
	 */
	public static function ascii($string) {
		return preg_replace('/[^\x0-\x7F]/', '', $string);
	}

	/**
	 * Truncates text.
	 *
	 * Cuts a string to the length of $length and replaces the last characters
	 * with the ending if the text is longer than length.
	 *
	 * @param string  $text String to truncate.
	 * @param integer $length Length of returned string, including ellipsis.
	 * @param string $ending If string, will be used as Ending and appended to the trimmed string.
	 * @param boolean $exact If false, $text will not be cut mid-word
	 * @return string Trimmed string.
	 */
	public static function truncate($text, $length = 100, $ending = '...', $exact = true) {

		if (mb_strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
			return $text;
		}
		$totalLength = mb_strlen($ending);
		$openTags = array();
		$truncate = '';
		preg_match_all('/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER);
		foreach ($tags as $tag) {
			if (!preg_match('/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tag[2])) {
				if (preg_match('/<[\w]+[^>]*>/s', $tag[0])) {
					array_unshift($openTags, $tag[2]);
				} else if (preg_match('/<\/([\w]+)[^>]*>/s', $tag[0], $close_tag)) {
					$pos = array_search($close_tag[1], $openTags);
					if ($pos !== false) {
						array_splice($openTags, $pos, 1);
					}
				}
			}
			$truncate .= $tag[1];

			$contentLength = mb_strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $tag[3]));
			if ($contentLength + $totalLength > $length) {
				$left = $length - $totalLength;
				$entititesLength = 0;
				if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tag[3], $entities, PREG_OFFSET_CAPTURE)) {
					foreach ($entities[0] as $entity) {
						if ($entity[1] + 1 - $entititesLength <= $left) {
							$left--;
							$entititesLength += mb_strlen($entity[0]);
						} else {
							break;
						}
					}
				}

				$truncate .= mb_substr($tag[3], 0, $left + $entititesLength);
				break;
			} else {
				$truncate .= $tag[3];
				$totalLength += $contentLength;
			}
			if ($totalLength >= $length) {
				break;
			}
		}

		if (!$exact) {
			$spacepos = mb_strrpos($truncate, ' ');
			if (isset($spacepos)) {
				$bits = mb_substr($truncate, $spacepos);
				preg_match_all('/<\/([a-z]+)>/', $bits, $droppedTags, PREG_SET_ORDER);
				if (!empty($droppedTags)) {
					foreach ($droppedTags as $closingTag) {
						if (!in_array($closingTag[1], $openTags)) {
							array_unshift($openTags, $closingTag[1]);
						}
					}
				}
				$truncate = mb_substr($truncate, 0, $spacepos);
			}
		}

		$truncate .= $ending;

		foreach ($openTags as $tag) {
			$truncate .= '</' . $tag . '>';
		}

		return $truncate;
	}

	/**
	 * Returns a masked string where only the last n characters are visible.
	 *
	 * @access  public
	 * @param   string  $string   String to mask
	 * @param   int     $visible  (optional) Number of characters to show
	 * @param   string  $mask     (optional) Character used to replace remaining characters
	 * @return  string
	 */
	public static function mask($string, $visible = 3, $mask = '*') {
		$substr = mb_substr($string, -$visible);

		return str_pad($substr, (mb_strlen($string) + (strlen($substr) - mb_strlen($substr))), $mask, STR_PAD_LEFT);
	}

}