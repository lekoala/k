<?php

namespace k;

use \RuntimeException;

/**
 * Cli
 *
 * @author lekoala
 */
class Cli {

	/**
	 * Text colors.
	 *
	 * @var array
	 */
	protected $foregroundColors = [
		'black' => '30',
		'red' => '31',
		'green' => '32',
		'yellow' => '33',
		'blue' => '34',
		'purple' => '35',
		'cyan' => '36',
		'white' => '37',
	];

	/**
	 * Background colors.
	 *
	 * @var array
	 */
	protected $backgroundColors = [
		'black' => '40',
		'red' => '41',
		'green' => '42',
		'yellow' => '43',
		'blue' => '44',
		'purple' => '45',
		'cyan' => '46',
		'white' => '47',
	];

	/**
	 * Text styles.
	 *
	 * @var array
	 */
	protected $textStyles = [
		'bold' => 1,
		'faded' => 2,
		'underlined' => 4,
		'blinking' => 5,
		'reversed' => 7,
		'hidden' => 8,
	];

	/**
	 * Windows
	 * 
	 * @var bool
	 */
	protected $isWindows = false;

	public function __construct() {
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$this->isWindows = true;
		}
	}

	/**
	 * Returns the screen size.
	 *
	 * @return  array
	 */
	public function screenSize() {
		$size = array('width' => 0, 'height' => 0);

		if (function_exists('ncurses_getmaxyx')) {
			ncurses_getmaxyx(STDSCR, $size['height'], $size['width']);
		} else {
			if (!$this->isWindows) {
				$size['width'] = (int) exec('tput cols');
				$size['height'] = (int) exec('tput lines');
			}
		}

		return $size;
	}

	/**
	 * Returns the screen width.
	 * 
	 * @return  int
	 */
	public function screenWidth() {
		$size = $this->screenSize();

		return $size['width'];
	}

	/**
	 * Returns the screen height.
	 * 
	 * @return  int
	 */
	public function screenHeight() {
		$size = $this->screenSize();

		return $size['height'];
	}

	/**
	 * Add text color and background color to a string.
	 *
	 * @param   string  $str         String to colorize
	 * @param   string  $foreground  (optional) Text color name
	 * @param   string  $background  (optional) Background color name
	 * @return  string
	 */
	public function color($str, $foreground = null, $background = null) {
		if ($this->isWindows) {
			return $str;
		}

		$ansiCodes = array();

		// Font color

		if ($foreground !== null) {
			if (!isset($this->foregroundColors[$foreground])) {
				throw new RuntimeException('Invalid text color');
			}

			$ansiCodes[] = $this->foregroundColors[$foreground];
		}

		// Background color

		if ($background !== null) {
			if (!isset($this->backgroundColors[$background])) {
				throw new RuntimeException('Invalid background color');
			}

			$ansiCodes[] = $this->backgroundColors[$background];
		}

		return sprintf("\033[%sm%s\033[0m", implode(';', $ansiCodes), $str);
	}

	/**
	 * Add styles to a string.
	 *
	 * @param   string  $str      String to style 
	 * @param   array   $styles  (optional) Text styles
	 * @return  string
	 */
	public function style($str, array $styles) {
		if ($this->isWindows) {
			return $str;
		}

		$ansiCodes = array();

		foreach ($styles as $style) {
			if (!isset($this->textStyles[$style])) {
				throw new RuntimeException('Invalid text options');
			}

			$ansiCodes[] = $this->textStyles[$style];
		}

		return sprintf("\033[%sm%s\033[0m", implode(';', $ansiCodes), $str);
	}

	/**
	 * Return value of named parameters (--<name>=<value>).
	 *
	 * @param  string  $name     Parameter name
	 * @param  string  $default  (optional) Default value
	 * @return string
	 */
	public function param($name, $default = null) {
		static $parameters;

		if ($parameters === null) {
			$parameters = array();

			foreach ($_SERVER['argv'] as $arg) {
				if (substr($arg, 0, 2) === '--') {
					$arg = explode('=', substr($arg, 2), 2);

					$parameters[$arg[0]] = isset($arg[1]) ? $arg[1] : true;
				}
			}
		}

		return isset($parameters[$name]) ? $parameters[$name] : $default;
	}

	/**
	 * Prompt user for input.
	 *
	 * @param   string  $question  Question for the user
	 * @return  string
	 */
	public function input($question) {
		fwrite(STDOUT, $question . ': ');

		return trim(fgets(STDIN));
	}

	/**
	 * Prompt user a confirmation.
	 *
	 * @param   string   $question  Question for the user
	 * @return  boolean
	 */
	public function confirm($question) {
		fwrite(STDOUT, $question . ' [Y/N]: ');

		$input = trim(fgets(STDIN));

		switch (strtoupper($input)) {
			case 'Y':
				return true;
			case 'N':
				return false;
			default:
				return $this->confirm($question);
		}
	}

	/**
	 * Print message to STDOUT.
	 *
	 * @param   string  $message     (optional) Message to print
	 * @param   string  $foreground  (optional) Text color
	 * @param   string  $background  (optional) Background color
	 * @param   array   $styles      (optional) Text styles
	 */
	public function stdout($message = '', $foreground = null, $background = null, array $styles = array()) {
		fwrite(STDOUT, $this->style($this->color($message, $foreground, $background), $styles) . PHP_EOL);
	}

	/**
	 * Print message to STDERR.
	 *
	 * @param   string  $message     Message to print
	 * @param   string  $foreground  (optional) Text color
	 * @param   string  $background  (optional) Background color
	 * @param   array   $styles      (optional) Text styles
	 */
	public function stderr($message, $foreground = 'red', $background = null, array $styles = array()) {
		fwrite(STDERR, $this->style($this->color($message, $foreground, $background), $styles) . PHP_EOL);
	}

	/**
	 * Outputs n empty lines.
	 * 
	 * @param   int     $lines  Number of empty lines
	 */
	public function newLine($lines = 1) {
		fwrite(STDOUT, str_repeat(PHP_EOL, $lines));
	}

	/**
	 * Clears the screen.
	 */
	public function clearScreen() {
		if ($this->isWindows) {
			$this->newLine(50);
		} else {
			fwrite(STDOUT, "\033[H\033[2J");
		}
	}

	/**
	 * Sytem Beep.
	 *
	 * @param   int     $beeps  (optional) Number of system beeps
	 */
	public function beep($beeps = 1) {
		fwrite(STDOUT, str_repeat("\x07", $beeps));
	}

	/**
	 * Display countdown for n seconds.
	 *
	 * @param   int      $seconds   Number of seconds to wait
	 * @param   boolean  $withBeep  (optional) Enable beep?
	 */
	public function wait($seconds = 5, $withBeep = false) {
		$length = strlen($seconds);

		while ($seconds > 0) {
			fwrite(STDOUT, "\r" . 'Please wait ... [ ' . $this->color(str_pad($seconds--, $length, 0, STR_PAD_LEFT), 'yellow') . ' ]');

			if ($withBeep === true) {
				$this->beep();
			}

			sleep(1);
		}

		fwrite(STDOUT, "\r\033[0K");
	}
}