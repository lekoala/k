<?php
define('SRC_PATH',realpath('../src'));
require SRC_PATH . '/K/init.php';

echo "<pre>Routing '/' \n";
print_r(K\Router::route('/'));
echo "<pre>Routing '/test/action' \n";
print_r(K\Router::route('/test/action'));
echo "<pre>Routing '/test/action/id?qs=val' \n";
print_r(K\Router::route('/test/action/id?qs=val'));
echo "<pre>Routing '/test/action/9?qs=val' \n";
print_r(K\Router::route('/test/action/9?qs=val'));
echo "<pre>Routing with prefix '/en/test/action/9?qs=val' \n";
K\Router::setPrefix('lang','!lang');
print_r(K\Router::route('/en/test/action/9?qs=val'));
echo "<pre>Routing with prefix '/test/action/9?qs=val' \n";
print_r(K\Router::route('/test/action/9?qs=val'));

