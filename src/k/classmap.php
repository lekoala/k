<?php
function glob_recursive($pattern, $flags = 0) {
	$files = glob($pattern, $flags);

	foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
		$files = array_merge($files, glob_recursive($dir . '/' . basename($pattern), $flags));
	}

	return $files;
}

$files = glob_recursive(__DIR__ . '/*.php');
$classmap = array();
foreach ($files as $file) {
	$content = file_get_contents($file);
	preg_match_all('/namespace\s*(\w+)\s*;/', $content, $namespace_matches);
	preg_match_all('/class[\s\n]+([a-zA-Z0-9_]+)[\s\na-zA-Z0-9_]+\{/', $content, $classes_matches);

	$namespace = '';
	if (!empty($namespace_matches[1])) {
		$namespace = $namespace_matches[1][0];
		$namespace = '\\' . $namespace . '\\';
	}
	if (!empty($classes_matches)) {
		foreach ($classes_matches[1] as $name) {
			$classmap[$file] = $namespace . $name;
		}
	}
}
return $classmap;