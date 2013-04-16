<?php

define('SRC_PATH', realpath('../src'));
require SRC_PATH . '/K/init.php';

class Str1 {

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

}

class Str2 {

	protected static $rules = array(
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

	static function singularize($word) {


		foreach (self::$rules as $from => $to) {
			if (preg_match('#' . $from . '#iD', $word)) {
				$word = preg_replace('#' . $from . '#iD', $to, $word);
				break;
			}
		}

		return $word;
	}

}

$word = 'wives';
$class = 'Str1';
$count = 100000;

$start = microtime(true);

for($i = 0; $i < $count ; $i++) {
	$res = $class::singularize($word);
}

echo floor((microtime(true) - $start) * 1000) . '<br/>';
echo memory_get_peak_usage();

//same memory, inline method declaration slightly slower