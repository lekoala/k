<?php

/* Provide a basic autoloader for the framework */
set_include_path(dirname(__DIR__) . '/src' . PATH_SEPARATOR . get_include_path());
spl_autoload_extensions('.php');
//psr-0 autoloader
spl_autoload_register(function($c) {
	@include preg_replace('#\\\|_(?!.+\\\)#', '/', $c) . '.php';
});
spl_autoload_register();