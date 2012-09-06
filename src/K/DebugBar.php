<?php

namespace K;

use \Exception;

class DebugBar {

	protected static $trackedStats = array();
	protected static $trackedObjects = array();
	protected static $enabled = true;

	/**
	 * Init debug bar (rendering time and memory usage)
	 * 
	 * Define START_TIME and START_MEMORY_USAGE constants at the begining
	 * of your script for more precise stats (otherwise, it won't start until
	 * calling this init method)
	 * 
	 * Register objects with stats() method to trackedObjects array for 
	 * more information (eg : db object)
	 * 
	 * @param bool|array $registerOnShutdown Should register on shutdown or config array
	 */
	public function __construct($registerOnShutdown = true) {
		self::$trackedStats['start_time'] = (defined('START_TIME')) ? START_TIME : microtime(true);
		self::$trackedStats['start_memory_usage'] = (defined('START_MEMORY_USAGE')) ? START_MEMORY_USAGE : memory_get_usage(true);
		
		if ($registerOnShutdown) {
			register_shutdown_function(array(__CLASS__, 'callback'));
		}

		if (is_array($registerOnShutdown)) {
			$this->configure($registerOnShutdown);
		}
	}

	public function configure($options) {
		foreach ($options as $opt => $v) {
			self::$$opt = $v;
		}
	}

	/**
	 * Echo or return the performances
	 * 
	 * @param bool $return (optional)
	 */
	static function callback($return = false) {
		if (!self::$enabled) {
			return;
		}
		$colors = array('330000', '333300', '003300', '003333', '000033');
		$color = $colors[array_rand($colors)];

		$stats = self::$trackedStats;

		// Html
		$html = '<div id="debug-bar" style="
	opacity:0.7;
	padding:2px 20px 2px 5px;
	line-height:16px;
	font-size:10px;
	font-family:Verdana;
	position:fixed;
	bottom:0;
	right:0;
	white-space: normal;
	background:#' . $color . ';
	background-image: linear-gradient(bottom, #000000 0%, #' . $color . ' 50%);
	background-image: -o-linear-gradient(bottom, #000000 0%, #' . $color . ' 50%);
	background-image: -moz-linear-gradient(bottom, #000000 0%, #' . $color . ' 50%);
	background-image: -webkit-linear-gradient(bottom, #000000 0%, #' . $color . ' 50%);
	background-image: -ms-linear-gradient(bottom, #000000 0%, #' . $color . ' 50%);
	-webkit-box-shadow:  -2px -2px 5px 0px #ccc;
    box-shadow:  -2px -2px 5px 0px #ccc;
	color:#fff">';

		// Js
		$html .= '<script language="javascript">
var debugBarPanel ;
function debugBarToggle(target) {
	var el = document.getElementById(target);
	if(debugBarPanel && el != debugBarPanel) {
		debugBarPanel.style.display = "none";
	}
	debugBarPanel = el;
	if(debugBarPanel.style.display == "block") {
    	debugBarPanel.style.display = "none";
  	}
	else {
		debugBarPanel.style.display = "block";
	}
	return false;
}
</script>';
		$elements = array();

		// Time
		if (isset($stats['start_time'])) {
			$renderTime = sprintf('%0.6f', microtime(true) - $stats['start_time']);
			$elements[] = 'Rendering time : ' . $renderTime . ' s';
		}

		// Memory
		if (isset($stats['start_memory_usage'])) {
			$memoryUsage = self::size(memory_get_usage(true) - $stats['start_memory_usage']);
			$memoryPeak = self::size(memory_get_peak_usage(true));
			$elements[] = 'Memory usage : ' . $memoryUsage;
			$elements[] = 'Memory peak usage : ' . $memoryPeak;
		}

		// Tracked objects
		foreach (self::$trackedObjects as $obj) {
			if (!method_exists($obj, 'debugBarCallback')) {
				throw new Exception('Callback ' . $callback . ' does not exist on ' . $obj);
			}
			$data = call_user_func(array($obj, $callback));
			$class = $obj;
			if (is_object($obj)) {
				$class = get_class($obj);
			}
			$name = explode('\\', strtolower($class));
			$name = end($name);
			$elements[] = self::createPanel($name, $data);
		}

		$html .= implode(' | ', $elements);

		$html .= '</div>';

		if ($return) {
			return $html;
		}
		echo $html;
	}

	public static function createPanel($name, $data) {
		$id = 'debug-bar-' . strtolower($name);
		if (is_array($data)) {
			$data = implode('', $data);
		}
		return '<a href="#' . $id . '" onclick="debugBarToggle(\'' . $id . '\');return false;" style="color:#fff;">' . $name . '</a>
			<div id="' . $id . '" style="display:none;position:fixed;background:#222;bottom:16px;right:0;height:400px;overflow:auto;width:400px;white-space:pre;padding:5px 20px 5px 5px;">' . $data . '</div>';
	}

	public static function track($o) {
		self::$trackedObjects[] = $o;
	}

	public static function enable($flag = true) {
		self::$enabled = $flag;
	}

	protected static function size($size, $precision = 2) {
		if ($size <= 0) {
			return '0B';
		}
		$base = log($size) / log(1024);
		$suffixes = array('B', 'k', 'M', 'G', 'T', 'P');

		return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
	}

}