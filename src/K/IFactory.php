<?php

namespace K;

/**
 * Interface for object that support generic constructors
 */
interface IFactory {
	public static function create($args = null) ;
}