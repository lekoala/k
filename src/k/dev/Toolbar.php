<?php

namespace k\dev;

use \Exception;
use \RuntimeException;

/**
 * Debug bar that can be displayed at the bottom of a page or collect raw data
 * to be sent through ajax
 * @link http://phpdebugbar.com/ 
 */
class DevToolbar {

	/**
	 * Store start time and memory usage
	 * @var array
	 */
	protected $trackedStats = array();

	/**
	 * Store class name or instances to introspect
	 * @var array
	 */
	protected $trackedObjects = array();

	/**
	 * Enable flag
	 * @var bool
	 */
	protected $enabled = true;

	/**
	 * Init debug bar (rendering time and memory usage)
	 * 
	 * Define START_TIME and START_MEMORY_USAGE constants at the begining
	 * of your script for more precise stats (otherwise, it won't start until
	 * calling this constructor method)
	 * 
	 * Register objects with stats() method to trackedObjects array for 
	 * more information (eg : db object)
	 */
	public function __construct() {
		$this->trackedStats['start_time'] = (defined('START_TIME')) ? START_TIME : microtime(true);
		$this->trackedStats['start_memory_usage'] = (defined('START_MEMORY_USAGE')) ? START_MEMORY_USAGE : memory_get_usage(true);
	}

	/**
	 * Get raw data as an array. Useful for adding to ajax requests for instance
	 * @return array
	 * @throws Exception
	 */
	public function getRawData() {
		$stats = $this->trackedStats;
		$elements = array();

		// Time
		if (isset($stats['start_time'])) {
			$elements['Rendered in'] = $this->formatTime(microtime(true) - $stats['start_time']);
		}

		// Memory
		if (isset($stats['start_memory_usage'])) {
			$elements['Memory usage'] = $this->formatSize(memory_get_usage(true) - $stats['start_memory_usage']);
			$elements['Memory usage'] .= ' / ' . $this->formatSize(memory_get_peak_usage(true));
		}

		// Tracked objects
		foreach ($this->trackedObjects as $arr) {
			$obj = $arr['object'];
			$cb = $arr['callback'];
			if (is_callable($cb)) {
				$data = $cb($obj, $this);
			} elseif (method_exists($obj, 'devToolbarCallback')) {
				if (is_object($obj)) {
					$data = $obj->devToolbarCallback($this);
				} else {
					$data = $obj::devToolbarCallback($this);
				}
			} else {
				continue;
			}
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
	public function getHtml() {
		// Html
		$html = '<div id="dev-toolbar" style="
	padding:2px 20px 2px 10px;
	line-height:16px;
	font-size:10px;
	font-family:Verdana;
	position:fixed;
	bottom:0;
	right:0;
	white-space: normal;
	-webkit-border-top-left-radius: 5px;
	-moz-border-radius-topleft: 5px;
	border-top-left-radius: 5px;
	background-color:rgba(255,255,255,0.8);
    box-shadow:  -2px -2px 5px 0px #ccc;
	color:#000">';

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

		$elements = $this->getRawData();

		$htmlData = array();
		foreach ($elements as $k => $v) {
			if (is_array($v)) {
				$v = $this->createPanel($k, $v);
			}
			$htmlData[] = $k . ' : ' . $v;
		}

		$html .= implode(' <span style="color:#aaa">|</span> ', $htmlData);

		$html .= '</div>';
		return $html;
	}

	/**
	 * Create a toggable html panel
	 * @param string $name
	 * @param array $data
	 * @return string
	 */
	public function createPanel($name, $data) {
		$id = 'dev-toolbar-' . strtolower($name);
		$title = array_shift($data);
		if (is_array($data)) {
			$data = implode("<br/>", $data);
		}
		return '<a href="#' . $id . '" onclick="debugBarToggle(\'' . $id . '\');return false;" style="color:#666;">' . $title . '</a>
			<div id="' . $id . '" style="
				display:none;
				position:fixed;
				background:rgba(0,0,0,0.8);
				bottom:20px;
				right:0;
				color:#eee;
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
	public function track($o, $cb = null) {
		if (!$o) {
			return false;
		}
		$this->trackedObjects[] = array(
			'object' => $o,
			'callback' => $cb
		);
	}

	/**
	 * Enable or disable 
	 * @param type $flag
	 */
	public function setEnabled($flag = true) {
		$this->enabled = $flag;
	}

	/**
	 * Format time
	 * 
	 * @param int $time
	 * @return string
	 */
	public function formatTime($time, $ending = ' s') {
		return sprintf('%0.6f', $time) . $ending;
	}

	/**
	 * Human readable size
	 * @param string $size
	 * @param init $precision
	 * @return string
	 */
	public function formatSize($size, $precision = 2) {
		if ($size <= 0) {
			return '0B';
		}
		$base = log($size) / log(1024);
		$suffixes = array('B', 'k', 'M', 'G', 'T', 'P');

		return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
	}

	public function __toString() {
		try {
			return $this->getHtml();
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}

}