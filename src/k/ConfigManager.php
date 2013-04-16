<?php

namespace k;

/**
 * Read and write to config file
 * Generate a form to edit the config file
 * 
 * Html form use twitter bootstrap conventions
 */
class ConfigManager {

	/**
	 * Config arrag
	 * @var array
	 */
	protected $data;

	/**
	 * Are we processing a fieldset
	 * @var bool
	 */
	protected $inFieldset = false;

	/**
	 * Are we processing a numeric array
	 * @var bool
	 */
	protected $inNumericArray = false;

	/**
	 * Indent level
	 * @var int
	 */
	protected $indent = 0;

	/**
	 * Track keys
	 * @var type 
	 */
	protected $keyStack = array();

	/**
	 * Config file to load/write
	 * @var string
	 */
	protected $file;

	/**
	 * Should we use some jquery and styles to make all this better?
	 * @var bool
	 */
	protected $improve = true;

	/**
	 * Pass a file to create a config manager for this config file
	 * @param string $file 
	 * @param bool $write_post Use available data in post to write config file
	 */
	function __construct($file, $write_post = false) {
		if($file instanceof Config) {
			$file = $file->getFile();
		}
		$this->file = $file;
		$this->data = require $file;

		if ($write_post) {
			if (!empty($_POST)) {
				$this->write($_POST);
				$this->data = require $file;
			}
		}
	}

	/**
	 * This function is similar to var_export, except that
	 * - it removes extra "," at the end of lines
	 * - doesn't add a numeric index for arrays
	 * - properly format code
	 * - All numerical entries are converted to ints
	 * 
	 * @param mixed $var
	 * @param int $indent
	 * @return string 
	 */
	function arrayWriter($var, $indent = 0) {
		if (is_array($var)) {
			$this->indent++;
			$code = "array(\n";
			$this->inNumericArray = false;
			foreach ($var as $key => $value) {
				$code .= str_repeat("\t", $this->indent);
				if ($this->inNumericArray || (is_int($key) && $key == 0)) {
					$this->inNumericArray = true;
					$code .= $this->arrayWriter($value, $this->indent) . ",\n";
				} else {
					if (is_string($key)) {
						$key = "'$key'";
					}
					$code .= "$key => " . $this->arrayWriter($value, $this->indent) . ",\n";
				}
			}
			$this->inNumericArray = false;
			$code = chop($code, ",\n");
			$code .= "\n";
			$this->indent--;
			$code .= str_repeat("\t", $this->indent);
			$code .= ")";
			return $code;
		} else {
			if (is_numeric($var)) {
				return $var;
			} elseif (is_string($var)) {
				return "'" . $var . "'";
			} elseif (is_bool($code)) {
				return ($code ? 'true' : 'false');
			} elseif (is_null($code)) {
				return 'null';
			} else {
				throw new Exception('Cannot use variables of type : ' . gettype($var));
			}
		}
	}

	/**
	 * Write given data to file
	 * 
	 * @param array $data
	 */
	function write($data) {
		$content = $this->arrayWriter($data);
		$content = '<?php 
return ' . $content . ';';

		file_put_contents($this->file, $content);
	}

	/**
	 * Render form item
	 * 
	 * @param mixed $val
	 * @param string $key
	 * @return string
	 */
	function renderItem($val, $key = null) {
		$html = '';

		//push key into stack to track input name value
		array_push($this->keyStack, $key);

		if (is_array($val)) {
			if (!$this->inFieldset) {
				$this->inFieldset = true;
				$html .= '<fieldset class="holder">';
				if ($key) {
					$html .= '<legend>' . $key . '</legend>';
				}
			} else {
				$this->indent++;
				if ($key) {
					$html .= '<p><strong>' . $key . '</strong></p>';
				}
				$margin = $this->indent * 10;
				$html .= '<div class="holder" style="padding-left:' . $margin . 'px">';
			}
			$this->inNumericArray = false;
			foreach ($val as $k => $v) {
				if (is_int($k) && $k == 0) {
					$this->inNumericArray = true;
				}
				$html .= $this->renderItem($v, $k);
			}
			if ($this->inNumericArray && $this->improve) {
				$html .= '<a href="#add" class="btn btn-add" style="display:none"><i class="icon-plus-sign"></i> add</a>';
			}
			$this->inNumericArray = false;
			if ($this->inFieldset && $this->indent == 0) {
				$html .= '</fieldset>';
				$this->inFieldset = false;
			}
			if ($this->indent > 0) {
				$html .= '</div>';
				$this->indent--;
			}
		} else {
			if (!$this->inFieldset) {
				//standalone values
				$html .= '<fieldset>';
				$html .= '<legend>' . $key . '</legend>';
				$html .= $this->renderField($key, $val);
				$html .= '</fieldset>';
			} else {
				$html .= $this->renderField($key, $val);
			}
		}

		array_pop($this->keyStack);

		return $html;
	}

	function renderField($key, $val) {
		//make name array to string
		$name = '';
		$count = count($this->keyStack);
		$curr = 0;
		foreach ($this->keyStack as $key_el) {
			$curr++;
			if (empty($name)) {
				$name .= $key_el;
			} else {
				if ($this->inNumericArray && $curr == $count) {
					$name .= '[]';
				} else {
					$name .= '[' . $key_el . ']';
				}
			}
		}

		//make html
		$html = '';
		$html .= '<div class="control-group">';
		if (!$this->inNumericArray) {
			$html .= '<label>' . $key . '</label>';
		}
		
		if (is_string($val)) {
			$html .= '<input type="text" name="' . $name . '" placeholder="' . $name . '" value="' . $val . '" />';
		} elseif (is_int($val)) {
			$html .= '<input type="numeric" name="' . $name . '" placeholder="' . $name . '" value="' . $val . '" />';
		} else {
			throw new Exception('Cannot use variables of type : ' . gettype($val));
		}
		
		if($this->inNumericArray && $this->improve) {
			$html .= ' <a href="" class="btn-minus"><i class="icon-minus-sign"></i></a>';
		}

		$html .= '</div>';
		return $html;
	}

	/**
	 * Render form to edit config file
	 * @return string 
	 */
	function render() {
		$html = '<form id="config-manager" method="post">';

		foreach ($this->data as $k => $v) {
			$html .= $this->renderItem($v, $k);
		}

		$html .= '<input type="submit" class="btn btn-primary" value="update config">';
		$html .= '</form>';

		if ($this->improve) {
			//define behaviour
			$html .= "<script type=\"text/javascript\">
if (jQuery) {
	jQuery('.btn-add').show().click(function(e) {
		e.preventDefault();
		var parent = jQuery(this).parents('.holder');
		var field = parent.find('.control-group:last').clone().val('');
		jQuery(this).before(field);
	});
	jQuery('.btn-minus').click(function(e) {
		e.preventDefault();
		jQuery(this).parents('.control-group').empty().remove();
	});
	jQuery('fieldset').hide();
	jQuery('legend').each(function() {
		btn = jQuery('<a class=\"btn btn-show\" style=\"margin:5px\" />').text(jQuery(this).text());
		btn.click(function(e) {
			e.preventDefault();
			jQuery('fieldset').hide();
			jQuery('.btn-show').show();
			jQuery(this).hide().next('fieldset').show();
		});
		jQuery(this).parents('fieldset').before(btn);
	});
}
</script>";
		}

		return $html;
	}

	function __toString() {
		return $this->render();
	}

}