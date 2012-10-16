<?php

namespace K;

/**
 * Can a class be observed by DebugBar
 */
interface IDebuggable {
	public static function debugBarCallback();
}