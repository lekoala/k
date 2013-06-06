<?php

namespace k;

use \DateTime;
use \DateTimeZone;

/**
 * Extend datetime to allow to use it as a string
 * 
 * @link http://flourishlib.com/docs/fDate
 * @link https://github.com/fightbulc/moment.php
 */
class Date extends DateTime {

	const WEEK = 604800;
	const DAY = 86400;
	const HOUR = 3600;
	const MINUTE = 60;

	protected static $defaultFormat = 'Y-m-d H:i:s';

	/**
	 * Return Date in ISO8601 format
	 *
	 * @return String
	 */
	public function __toString() {
		return $this->format(static::$defaultFormat);
	}

	public static function create($date) {
		return new static($date);
	}
	static function createFromFormat($f, $t, $tz = null) {
		if (!$tz) {
			$tz = new DateTimeZone(date_default_timezone_get());
		}
		$dt = parent::createFromFormat($f, $t, $tz);
		if (!$dt) {
			return null;
		}
		return new static($dt->format('Y-m-d H:i:s e'));
	}

	static function createFromDatetime($time = null) {
		if (empty($time) || $time == '0000-00-00 00:00:00') {
			return null;
		}
		return static::createFromFormat('Y-m-d H:i:s', $time);
	}

	static function createFromDate($time = null) {
		if (empty($time) || $time == '0000-00-00') {
			return null;
		}
		$dt = self::createFromFormat('Y-m-d', $time . ' 00:00:00');
		if (!$dt) {
			return null;
		}
		$dt->type = 'date';
		return $dt;
	}

	static function createFromTime($time = null) {
		if (empty($time) || $time == '00:00:00') {
			return null;
		}
		$dt = self::createFromFormat('H:i:s', $time);
		if (!$dt) {
			return null;
		}
		$dt->type = 'time';
		return $dt;
	}
	/* new class methods */

	/**
	 * Return difference between $this and $now
	 *
	 * @param Datetime|String $date
	 * @return DateInterval
	 */
	public function diff($date = 'now', $absolute = null) {
		if (!($date instanceOf DateTime)) {
			$date = new DateTime($date);
		}
		return parent::diff($date);
	}

	/**
	 * Return Age in Years
	 *
	 * @param Datetime|String $date
	 * @return Integer
	 */
	public function age($date = 'now') {
		return $this->diff($date)->format('%y');
	}

	/**
	 * Returns the approximate difference in time, discarding any unit of measure but the least specific.
	 * 
	 * The output will read like:
	 * 
	 *  - "This date is `{return value}` the provided one" when a date it passed
	 *  - "This date is `{return value}`" when no date is passed and comparing with today
	 * 
	 * Examples of output for a date passed might be:
	 * 
	 *  - `'2 days after'`
	 *  - `'1 year before'`
	 *  - `'same day'`
	 * 
	 * Examples of output for no date passed might be:
	 * 
	 *  - `'2 days from now'`
	 *  - `'1 year ago'`
	 *  - `'today'`
	 * 
	 * You would never get the following output since it includes more than one unit of time measurement:
	 * 
	 *  - `'3 weeks and 1 day'`
	 *  - `'1 year and 2 months'`
	 * 
	 * Values that are close to the next largest unit of measure will be rounded up:
	 * 
	 *  - `6 days` would be represented as `1 week`, however `5 days` would not
	 *  - `29 days` would be represented as `1 month`, but `21 days` would be shown as `3 weeks`
	 * 
	 * @param  object|string|integer $date  The date to create the difference with, now by default
	 * @param  boolean                     $simple      When `true`, the returned value will only include the difference in the two dates, but not `from now`, `ago`, `after` or `before`
	 * @return string  The fuzzy difference in time between the this date and the one provided
	 */
	public function fuzzyDiff($date = 'now', $simple = false) {
		$relative_to_now = false;
		if ($date == 'now') {
			$relative_to_now = true;
		}
		if (!($date instanceOf DateTime)) {
			$date = new DateTime($date);
		}

		$diff = $this->diff($date)->format('U');
		$result = '';

		if (abs($diff) < 86400) {
			if ($relative_to_now) {
				return 'today';
			}
			return 'same day';
		}

		$break_points = array(
			/* 5 days      */
			432000 => array(86400, 'day', 'days'),
			/* 3 weeks     */
			1814400 => array(604800, 'week', 'weeks'),
			/* 9 months    */
			23328000 => array(2592000, 'month', 'months'),
			/* largest int */
			2147483647 => array(31536000, 'year', 'years')
		);

		foreach ($break_points as $break_point => $unit_info) {
			if (abs($diff) > $break_point) {
				continue;
			}

			$unit_diff = round(abs($diff) / $unit_info[0]);
			$units = ($unit_diff == 1) ? $unit_info[1] : $unit_info[2];
			break;
		}

		if ($simple) {
			return vsprintf('%1$s %2$s', $unit_diff, $units);
		}

		if ($relative_to_now) {
			if ($diff > 0) {
				return vsprintf('%1$s %2$s from now', $unit_diff, $units);
			}

			return vsprintf('%1$s %2$s ago', $unit_diff, $units);
		}

		if ($diff > 0) {
			return vsprintf('%1$s %2$s after', $unit_diff, $units);
		}

		return vsprintf('%1$s %2$s before', $unit_diff, $units);
	}

	/* static helpers */

	/**
	 * Returns the number of days in the requested month
	 *
	 * @param   int  month as a number (1-12)
	 * @param   int  the year, leave empty for current
	 * @return  int  the number of days in the month
	 */
	public static function daysInMonth($month, $year = null) {
		if (!$year) {
			$year = date('Y');
		}

		if ($month < 1 or $month > 12) {
			throw new \UnexpectedValueException('Invalid input for month given.');
		} elseif ($month == 2) {
			if ($year % 400 == 0 or ($year % 4 == 0 and $year % 100 != 0)) {
				return 29;
			}
		}

		$days = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
		return $days[$month - 1];
	}

	/**
	 *  Get all weeks of a year as follows
	 * 
	 * array(
	 * 	array('num' => x, 'start' => x, 'end' => x);
	 * )
	 * @param type $year
	 * @return type
	 */
	public static function weeksInYear($year = null) {
		if (!$year) {
			$year = date('Y');
		}

		$first_day_of_year = mktime(0, 0, 0, 1, 1, $year);
		$first_thursday = strtotime('thursday', $first_day_of_year); //needed to get first week as ISO format
		$next_monday = strtotime(date("Y-m-d", $first_thursday) . " - 3 days");
		$next_sunday = strtotime('sunday', $next_monday);
		$i = 1; //weeks counter
		$weeks = array();
		while (date('Y', $next_monday) == $year) {
			$weeks[] = array('num' => $i, 'start' => date('Y-m-d', $next_monday), 'end' => date('Y-m-d', $next_sunday));
			$i++;
			$next_monday = strtotime('+1 week', $next_monday);
			$next_sunday = strtotime('+1 week', $next_sunday);
		}
		return $weeks;
	}

	/**
	 * Format for database
	 * 
	 * @return string 
	 */
	public function formatForDb($type = 'datetime') {
		switch ($type) {
			case 'datetime' :
				$format = 'Y-m-d H:i:s';
				break;
			case 'date' :
				$format = 'Y-m-d';
				break;
			case 'time' :
				$format = 'H:i:s';
				break;
		}
		return $this->format($format);
	}

	/**
	 * Give the last day of month 
	 * 
	 * @param int $month
	 * @param int $year
	 * @return string 
	 */
	public static function lastDayOfMonth($month = null, $year = null) {
		if (!$year) {
			$year = date('Y');
		}
		if (!$month) {
			$month = date('m');
		}
		$day = date('d');
		$date = $year . '-' . $month . '-' . $day;
		//t returns the number of days in the month of a given date
		return date("Y-m-t", strtotime($date));
	}

	/**
	 * 
	 * @param type $format
	 * @param type $date
	 * @return string
	 */
	public static function convert($format, $date) {
		if (empty($date)) {
			return '';
		}
		return date($format, strtotime($date));
	}

	/**
	 * 
	 * @param type $secs
	 * @return string
	 */
	public static function secondsToTime($secs) {
		$times = array(3600, 60, 1);
		$time = '';
		$tmp = '';
		for ($i = 0; $i < 3; $i++) {
			$tmp = floor($secs / $times[$i]);
			if ($tmp < 1) {
				$tmp = '00';
			} elseif ($tmp < 10) {
				$tmp = '0' . $tmp;
			}
			$time .= $tmp;
			if ($i < 2) {
				$time .= ':';
			}
			$secs = $secs % $times[$i];
		}
		return $time;
	}

	/**
	 * Return an array of days of the week like 1 => monday.... 
	 * 
	 * @param bool $abbrev use abbreviation (mon) instead of number (1)
	 * @return array
	 */
	static function weekDays($abbrev = true) {
		if ($abbrev) {
			return array(
				'mon' => 'monday',
				'tue' => 'tuesday',
				'wed' => 'wednesday',
				'thu' => 'thursday',
				'fri' => 'friday',
				'sat' => 'saturday',
				'sun' => 'sunday'
			);
		}
		return array(
			'1' => 'monday',
			'2' => 'tuesday',
			'3' => 'wednesday',
			'4' => 'thursday',
			'5' => 'friday',
			'6' => 'saturday',
			'7' => 'sunday'
		);
	}

	/* static factory */



}