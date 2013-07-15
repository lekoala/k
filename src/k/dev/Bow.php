<?php

namespace k\dev;

/**
 * Bow
 *
 * Handles dependancies with bower
 * 
 * @author lekoala
 */
class Bow {

	protected $baseDir;
	protected $config = [
		'use_compression' => true,
		'components_dir' => 'components',
		'public_dir' => 'public',
		'scripts_dir' => 'public/scripts',
		'deps' => 'deps.php',
		'loader_php' => 'loader.php',
		'loader_js' => 'loader.js'
	];

	public function __construct($dir, $config = null) {
		$this->baseDir = $dir;
		if ($config) {
			$this->setConfig($config);
		}
	}

	public function getSampleConfig() {
		return [
			'components_dir' => 'components',
			'packages_dir' => 'packages',
			'packages' => [
				'loader' => [
					'headjs/dist/head.min.js'
				],
				'app' => [
					'./init.js'
				]
			],
			'always_load' => [
				'app'
			]
		];
	}

	public function getConfig() {
		return $this->config;
	}

	public function setConfig($config) {
		$this->config = array_merge($this->config, $config);

		return $this;
	}

	protected function config($k) {
		if (!isset($this->config[$k])) {
			throw new \RuntimeException("$k does not exist in config");
		}
		return $this->config[$k];
	}

	protected function dir($k) {
		return $this->baseDir . '/' . $this->config($k . '_dir');
	}

	/**
	 * This is going to analyze dependancies and copy required files
	 * from the components dir
	 * Bow will also create packages and the loader
	 */
	public function shoot() {
		if (php_sapi_name() != 'cli') {
			echo '<pre>';
		}
		$deps = $this->dir('scripts') . '/' . $this->config('deps');
		if (!is_file($deps)) {
			throw new \RuntimeException("$deps does not exists");
		}
		$deps = require $deps;
		$scriptsDir = $this->dir('scripts');
		$packages = $scriptsDir . '/' . $deps['packages_dir'];
		$components = $scriptsDir . '/' . $deps['components_dir'];

		$this->deleteDir($packages);
		mkdir($packages);
		$this->deleteDir($components);
		mkdir($components);

		$count_files = $count_packages = 0;
		foreach ($deps['packages'] as $name => $files) {
			foreach ($files as $file) {
				//alias bower components
				if (strpos($file, '/') === 0 || strpos($file, './') === 0) {
					continue; //not a bower file
				}
				$source = $this->dir('components') . '/' . $file;
				if (is_file($source)) {
					$dest = $components . '/' . $file;
					if (!is_dir(dirname($dest))) {
						mkdir(dirname($dest), 0770, true);
					}
					$res = copy($source, $dest);
					if ($res) {
						$count_files++;
						echo "Copy file " . basename($source) . "\n";
						if (pathinfo($source, PATHINFO_EXTENSION) === 'css') {
							copy_images($source, dirname($dest), $packages);
						}
					} else {
						echo "Failed to copy $source \n";
					}
				} else {
					die('File ' . $source . ' is missing');
				}
			}

			//build packages for optimized loading
			$deps['packages'][$name] = array();
			$data = array();
			foreach ($files as $file) {
				$ext = pathinfo($file, PATHINFO_EXTENSION);
				if (strpos($file, './') === 0) {
					$filename = preg_replace('#^\./#', $this->dir('scripts') . '/', $file);
				} else if (strpos($file, '/') === 0) {
					$filename = $this->dir('public') . '/' . $file;
				} else {
					$filename = $components . '/' . $file;
				}
				if (!isset($data[$ext])) {
					$data[$ext] = '';
				}
				if (!is_file($filename)) {
					die('File ' . $filename . ' is missing');
				}
				$data[$ext] .= file_get_contents($filename);
			}

			foreach ($data as $ext => $content) {
				if ($ext === 'css') {
					$content = compress($content);
				}
				$packagefile = $packages . '/' . $name . '.' . $ext;
				$res = file_put_contents($packagefile, $content);
				if ($res) {
					$count_packages++;
					echo "Package created " . basename($packagefile) . "\n";
				} else {
					echo "Failed to create package $packagefile \n";
				}
			}
		}

		// cache loader
		$loaderPhp = $this->dir('scripts') . '/' . $this->config('loader_php');
		$loaderJs = $this->dir('scripts') . '/' . $this->config('loader_js');
		ob_start();
		require $loaderPhp;
		$loaderData = ob_get_clean();
		file_put_contents($loaderJs, $loaderData);
		echo "Loader created " . basename($loaderJs) . "\n";
		echo "---------------------\n";
		echo "$count_files files copied, $count_packages packages created\n";
	}

	protected function deleteDir($dir) {
		if (!file_exists($dir))
			return true;
		if (!is_dir($dir) || is_link($dir))
			return unlink($dir);
		foreach (scandir($dir) as $item) {
			if ($item == '.' || $item == '..')
				continue;
			if (!delete_dir($dir . "/" . $item)) {
				chmod($dir . "/" . $item, 0777);
				if (!$this->deleteDir($dir . "/" . $item))
					return false;
			};
		}
		return rmdir($dir);
	}

	protected function copyImages($src, $dest, $dest2) {
		$pattern = "/url\('([a-zA-Z0-9\.-_]*)'\)/";
		$content = file_get_contents($src);
		preg_match_all($pattern, $content, $matches);
		if (!empty($matches[0])) {
			$files = array_unique($matches[1]);
			foreach ($files as $file) {
				$org = realpath(dirname($src) . '/' . $file);
				//TODO: handle relative paths better
				copy($org, $dest . '/' . $file);
				copy($org, $dest2 . '/' . $file);
				echo "copying image $file\n";
			}
		}
	}

	protected function compress($buffer) {
		/* remove comments */
		$buffer = preg_replace("/((?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:\/\/.*))/", "", $buffer);
		/* remove tabs, spaces, newlines, etc. */
		$buffer = str_replace(array("\r\n", "\r", "\t", "\n", '  ', '    ', '     '), '', $buffer);
		/* remove other spaces before/after ) */
		$buffer = preg_replace(array('(( )+\))', '(\)( )+)'), ')', $buffer);
		return $buffer;
	}

}