<?php

namespace K;

class str {

	/**
	 * Convert a var to a string
	 * 
	 * @param mixed $var
	 * @param string $glue
	 * @return string
	 */
	static function make($var, $glue = ',') {
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
	 * Pluralizes English nouns.
	 *
	 * @param    string    $word    English noun to pluralize
	 * @return string Plural noun
	 */
	static function pluralize($word) {
		$rules = array(
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

		foreach ($rules as $from => $to) {
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
	static function singularize($word) {
		$rules = array(
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

		foreach ($rules as $from => $to) {
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
	 * @param bool $upper_camel_case
	 * @return string UpperCamelCasedWord
	 */
	static function camelize($word, $upper_camel_case = true) {
		$word = str_replace(' ', '', ucwords(preg_replace('/[^A-Z^a-z^0-9]+/', ' ', $word)));
		if ($upper_camel_case) {
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
	static function underscorize($word) {
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
	static function humanize($word, $uppercase = '') {
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
	static function ordinalize($number) {
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
	static function truncate($text, $length = 100, $ending = '...', $exact = true) {

		if (mb_strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
			return $text;
		}
		$total_length = mb_strlen($ending);
		$open_tags = array();
		$truncate = '';
		preg_match_all('/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER);
		foreach ($tags as $tag) {
			if (!preg_match('/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tag[2])) {
				if (preg_match('/<[\w]+[^>]*>/s', $tag[0])) {
					array_unshift($open_tags, $tag[2]);
				} else if (preg_match('/<\/([\w]+)[^>]*>/s', $tag[0], $close_tag)) {
					$pos = array_search($close_tag[1], $open_tags);
					if ($pos !== false) {
						array_splice($open_tags, $pos, 1);
					}
				}
			}
			$truncate .= $tag[1];

			$content_length = mb_strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $tag[3]));
			if ($content_length + $total_length > $length) {
				$left = $length - $total_length;
				$entities_length = 0;
				if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tag[3], $entities, PREG_OFFSET_CAPTURE)) {
					foreach ($entities[0] as $entity) {
						if ($entity[1] + 1 - $entities_length <= $left) {
							$left--;
							$entities_length += mb_strlen($entity[0]);
						} else {
							break;
						}
					}
				}

				$truncate .= mb_substr($tag[3], 0, $left + $entities_length);
				break;
			} else {
				$truncate .= $tag[3];
				$total_length += $content_length;
			}
			if ($total_length >= $length) {
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
						if (!in_array($closingTag[1], $open_tags)) {
							array_unshift($open_tags, $closingTag[1]);
						}
					}
				}
				$truncate = mb_substr($truncate, 0, $spacepos);
			}
		}

		$truncate .= $ending;

		foreach ($open_tags as $tag) {
			$truncate .= '</' . $tag . '>';
		}

		return $truncate;
	}

}