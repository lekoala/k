<?php

namespace K;

/**
 * Debug bar that can be displayed at the bottom of a page or collect raw data
 * to be sent through ajax
 */
class DebugBar {
	
	use TConfigure;

	/**
	 * Store start time and memory usage
	 * @var array
	 */
	protected static $trackedStats = array();

	/**
	 * Store class name or instances to introspect
	 * @var array
	 */
	protected static $trackedObjects = array();

	/**
	 * Enable flag
	 * @var bool
	 */
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
	public static function init($registerOnShutdown = true) {
		self::$trackedStats['start_time'] = (defined('START_TIME')) ? START_TIME : microtime(true);
		self::$trackedStats['start_memory_usage'] = (defined('START_MEMORY_USAGE')) ? START_MEMORY_USAGE : memory_get_usage(true);

		if ($registerOnShutdown) {
			register_shutdown_function(array(__CLASS__, 'callback'));
		}

		if (is_array($registerOnShutdown)) {
			self::configure($registerOnShutdown);
		}
	}

	/**
	 * Echo
	 */
	public static function callback() {
		if (!self::$enabled) {
			return;
		}
		$html = self::getHtml();
		echo $html;
	}

	/**
	 * Get raw data as an array. Useful for adding to ajax requests for instance
	 * @return array
	 * @throws Exception
	 */
	public static function getRawData() {
		$stats = self::$trackedStats;
		$elements = array();

		// Time
		if (isset($stats['start_time'])) {
			$renderTime = sprintf('%0.6f', microtime(true) - $stats['start_time']);
			$elements['Rendering time'] = $renderTime . ' s';
		}

		// Memory
		if (isset($stats['start_memory_usage'])) {
			$memoryUsage = self::size(memory_get_usage(true) - $stats['start_memory_usage']);
			$memoryPeak = self::size(memory_get_peak_usage(true));
			$elements['Memory usage'] = $memoryUsage;
			$elements['Memory peak usage'] = $memoryPeak;
		}

		// Tracked objects
		foreach (self::$trackedObjects as $obj) {
			if (!method_exists($obj, 'debugBarCallback')) {
				throw new Exception('Callback debugBarCallback does not exist on ' . $obj);
			}
			$data = call_user_func(array($obj, 'debugBarCallback'));
			$class = $obj;
			if (is_object($obj)) {
				$class = get_class($obj);
			}
			$name = explode('\\', strtolower($class));
			$name = end($name);
			$elements[$name] = $data;
		}

		return $elements;
	}

	/**
	 * Generate html for the toolbar
	 * @return string
	 */
	public static function getHtml() {
		$colors = array('330000', '333300', '003300', '003333', '000033');
		$color = $colors[array_rand($colors)];

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

		$elements = self::getRawData();

		$htmlData = array();
		foreach ($elements as $k => $v) {
			if (is_array($v)) {
				$v = self::createPanel($k, $v);
			}
			$htmlData[] = $k . ' : ' . $v;
		}

		$html .= implode(' | ', $htmlData);

		$html .= '</div>';
		return $html;
	}

	/**
	 * Create a toggable html panel
	 * @param string $name
	 * @param array $data
	 * @return string
	 */
	public static function createPanel($name, $data) {
		$id = 'debug-bar-' . strtolower($name);
		$title = array_shift($data);
		if (is_array($data)) {
			$data = implode("<br/>", $data);
		}
		return '<a href="#' . $id . '" onclick="debugBarToggle(\'' . $id . '\');return false;" style="color:#fff;">' . $title . '</a>
			<div id="' . $id . '" style="
				display:none;
				position:fixed;
				background:#222;
				bottom:20px;
				right:0;
				height:400px;
				overflow:auto;
				width:400px;
				white-space:pre;
				padding:5px 20px 5px 5px;
				-webkit-box-shadow:  -2px -2px 5px 0px #ccc;
				box-shadow:  -2px -2px 5px 0px #ccc;
			">' . $data . '</div>';
	}

	/**
	 * Track an object
	 * @param string|object $o
	 */
	public static function track($o) {
		self::$trackedObjects[] = $o;
	}

	/**
	 * Enable or disable 
	 * @param type $flag
	 */
	public static function enable($flag = true) {
		self::$enabled = $flag;
	}

	/**
	 * Human readable size
	 * @param string $size
	 * @param init $precision
	 * @return string
	 */
	protected static function size($size, $precision = 2) {
		if ($size <= 0) {
			return '0B';
		}
		$base = log($size) / log(1024);
		$suffixes = array('B', 'k', 'M', 'G', 'T', 'P');

		return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
	}

}