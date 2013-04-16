<?php
set_include_path(realpath(__DIR__ . '/../') . PATH_SEPARATOR . get_include_path());
spl_autoload_extensions('.php');
//psr-0 autoloader
spl_autoload_register(function($c){@include preg_replace('#\\\|_(?!.+\\\)#','/',$c).'.php';});
spl_autoload_register();