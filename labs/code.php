<?php

class CodeGenerator {

	protected $code;
	protected $tokens;
	protected $file;
	protected $placeholders = [];

	public function __construct($code = null) {
		if ($code) {
			$this->setCode($code);
		}
	}

	public static function createFromFile($file) {
		$generator = new static(file_get_contents($file));
		$generator->setFile($file);
		return $generator;
	}
	
	public static function createFromArray($arr) {
		if(!is_array($arr) && is_file($arr)) {
			$arr = require $arr;
		}
		
	}
	
	public function getCode() {
		return $this->code;
	}

	public function setCode($code) {
		$this->code = $code;
		$this->tokens = token_get_all($code);
		return $this;
	}

	public function getFile() {
		return $this->file;
	}

	public function setFile($file) {
		$this->file = $file;
		return $this;
	}

	protected function extractCodeFromClosure($closure) {
		$ref = new ReflectionFunction($closure);

		// Open file and seek to the first line of the closure
		$file = new SplFileObject($ref->getFileName());
		$file->seek($ref->getStartLine() - 1);

		// Retrieve all of the lines that contain code for the closure
		$endLine = $ref->getEndLine();
		$code = '';
		while ($file->key() < $endLine) {
			$code .= $file->current();
			$file->next();
		}

		// Only keep the code defining that closure
		$begin = stripos($code, 'function');
		$end = strrpos($code, '}');
		$code = substr($code, $begin, $end - $begin + 1);
		
		return $code;
	}
	
	protected function makePlaceholder($v) {
		$this->placeholders[] = $v;
		return '__PLACEHOLDER_' . count($this->placeholders) . '__';
	}

	protected function parseArray($array) {
		if (!is_array($array)) {
			return $array;
		}
		$arr = [];
		if (is_array($array)) {
			foreach ($array as $k => $v) {
				if (is_array($v)) {
					$v = $this->parseArray($v);
				}
				if ($v instanceof Closure) {
					$v = $this->makePlaceholder($this->extractCodeFromClosure($v));
				}
				$arr[$k] = $v;
			}
		}
		$arr = var_export($arr, true);
		
		$arr = preg_replace_callback("/'__PLACEHOLDER_([0-9])*__'/", function($item) {
			$i = $item[1] - 1;
			return $this->placeholders[$i];
		}, $arr);
		
		return $arr;
	}

	public function saveArray($array, $file) {
		$data = "<?php\nreturn " . $this->parseArray($array) . ';';
		return file_put_contents($file, $data);
	}

	public function save($file = null) {
		if ($file === null) {
			if ($this->file === null) {
				throw new Exception('Must set a file');
			}
			$file = $this->file;
		}
		file_put_contents($file, $this->code);
	}

	public function show() {
		echo htmlentities($this->code);
	}

}


$file = 'src/SomeClass.php';
$generator = CodeGenerator::createFromFile($file);
$generator->save();

$arrGenerator = CodeGenerator::createFromArray('src/config.php');
$arrGenerator->show();
$arrGenerator->save();

//$arrGenerator = new CodeGenerator();
//$arrGenerator->saveArray(['test' => 'me', 'ddd' => function() {
//		return 'test';
//	}], 'src/config.php');