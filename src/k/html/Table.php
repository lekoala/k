<?php

namespace k\html;

/**
 * Dynamic table
 * 
 * Js integration expect to be using listjs
 * Css integration expect to be using twitter bootstrap compatible html and css
 * 
 * @link http://listjs.com/
 * @author lekoala
 */
class Table extends HtmlWriter {

	const MODE_APPEND = 'append';
	const MODE_QS = 'qs';
	const MODE_REPLACE = 'replace';

	protected static $instances = 0;
	protected static $scriptInserted = false;
	protected $identifier = 'id';
	protected $selectable;
	protected $selectableActions;
	protected $headers;
	protected $pagination;
	protected $data;
	protected $class = 'table table-striped';
	protected $id;
	protected $indent;
	protected $actions;
	protected $baseActionClass = 'btn';
	protected $actionsMode = 'append';
	protected $actionsClass = [
		'remove' => 'btn-danger confirm',
		'delete' => 'btn-danger confirm'
	];
	protected $actionConfirm = [];
	protected $confirmScript = "return confirm('Are you sure?');";
	protected $searchableHeaders;
	protected $sortableHeaders;
	protected $searchableKey = 'filters';
	protected $searchableInput = [
		'type' => 'submit',
		'value' => 'filter'
	];
	protected $formMethod = 'post';
	protected $tableSearch = true;
	protected $tableSearchInput = [
		'type' => 'text',
		'class' => 'search search-query',
		'placeholder' => 'search'
	];

	public function __construct() {
		self::$instances++;
	}

	public function getIdentifier() {
		return $this->identifier;
	}

	public function setIdentifier($id) {
		$this->identifier = $id;
		return $this;
	}
	
	public function getSelectable() {
		return $this->selectable;
	}

	public function setSelectable($v = true) {
		$this->selectable = $v;
		return $this;
	}

	public function getSelectableActions() {
		return $this->selectableActions;
	}

	public function setSelectableActions($v = array()) {
		$this->selectable = true;
		$this->selectableActions = $this->arrayify($v);
		return $this;
	}

	public function getPagination() {
		return $this->pagination;
	}

	public function setPagination($current, $total, $collapse = null) {
		$this->pagination = compact('current', 'total', 'collapse');
		return $this;
	}

	public function getHeaders() {
		return $this->headers;
	}

	public function setHeaders($headers = null) {
		$headers = $this->arrayify($headers);
		$this->headers = $headers;
		return $this;
	}

	public function getSearchableHeaders() {
		return $this->searchableHeaders;
	}

	public function setSearchableHeaders($headers = null) {
		$headers = $this->arrayify($headers);
		$this->searchableHeaders = $headers;
		return $this;
	}

	public function getSortableHeaders() {
		return $this->sortableHeaders;
	}

	public function setSortableHeaders($sortableHeaders) {
		$this->sortableHeaders = $this->arrayCollapse($sortableHeaders);
		return $this;
	}

	public function getData() {
		return $this->data;
	}

	public function setData($data) {
		$this->data = $data;
		return $this->data;
	}

	public function getClass() {
		return $this->class;
	}

	public function setClass($class) {
		$this->class = $this->stringify($class, ' ');
		return $this;
	}

	public function getId() {
		return $this->id;
	}

	public function setId($class) {
		$this->id = $class;
		return $this;
	}

	public function getActions() {
		return $this->actions;
	}

	public function setActions($actions, $mode = 'append') {
		$this->actions = $actions;
		$this->actionsMode = $mode;
		return $this;
	}

	public function getActionsMode() {
		return $this->actionsMode;
	}

	public function setActionsMode($mode) {
		$this->actionsMode = $mode;
		return $this;
	}
	
	public function getActionConfirm() {
		return $this->actionConfirm;
	}

	public function getConfirmScript() {
		return $this->confirmScript;
	}

	public function setActionConfirm($actionConfirm) {
		$this->actionConfirm = $actionConfirm;
		return $this;
	}

	public function setConfirmScript($confirmScript) {
		$this->confirmScript = $confirmScript;
		return $this;
	}

	public function getFormMethod() {
		return $this->formMethod;
	}

	public function setFormMethod($v) {
		$this->formMethod = $v;
		return $this;
	}

	public function getTableSearch() {
		return $this->tableSearch;
	}

	public function setTableSearch($tableSearch = true) {
		$this->tableSearch = $tableSearch;
		return $this;
	}

	public function getTableSearchInput() {
		return $this->tableSearchInput;
	}

	public function setTableSearchInput($tableSearchInput) {
		$this->tableSearchInput = $tableSearchInput;
		return $this;
	}

	public function getBaseActionClass() {
		return $this->baseActionClass;
	}

	public function setBaseActionClass($baseActionClass) {
		$this->baseActionClass = $baseActionClass;
		return $this;
	}

	public function getActionClass($k) {
		if (isset($this->actionsClass[$k])) {
			return $this->actionsClass[$k];
		}
	}

	public function setActionClass($k, $v) {
		$this->actionsClass[$k] = $v;
		return $this;
	}

	public function getActionsClass() {
		return $this->actionsClass;
	}

	public function setActionsClass($actionsClass) {
		$this->actionsClass = $actionsClass;
		return $this;
	}
	
	protected function getFormName() {
		return 'table_form_' . self::$instances;
	}

	public function renderHtml() {
		$html = '';
		if ($this->headers) {
			$headers = '';
			$headersCollapsed = $this->arrayCollapse($this->headers);

			if ($this->selectable) {
				//un-check all
				$headers .= $this->tag('th', '<input type="checkbox" onclick="toggleSelectable(this,document.' . $this->getFormName() . ');" />');
			}
			foreach ($this->headers as $header => $label) {
				if(is_int($header)) {
					$header = $label;
					$label = ucwords(str_replace(array('_','.','-'), ' ', $label));
				}
				$atts = [];
				if($this->sortableHeaders && in_array($header, $this->sortableHeaders)) {
					$atts['class'] = 'sort';
					$atts['data-sort'] = $header;
					$label .= '<span></span>';
				}
				$headers .= $this->tag('th', $label, $atts);
			}
			if ($this->actions) {
				$search = null;
				if ($this->tableSearch) {
					$search = $this->tag('input', $this->tableSearchInput);
				}
				$headers .= $this->tag('th', $search);
			}

			$headers = $this->tag('tr', $headers);
			if ($this->searchableHeaders) {
				$searchable_headers = $this->makeSearchableHeaders();
				$headers .= $this->tag('tr', $searchable_headers);
			}
			$html .= $this->tag('thead', $headers);
		}
		if ($this->data) {
			$html .= '<tbody class="list">';
			$i = 0;

			foreach ($this->data as $data) {
				$i++;
				$value = $i;
				if (isset($data[$this->identifier])) {
					$value = $data[$this->identifier];
				}
				//auto table id
				if (is_object($data)) {
					$this->id = 'table-' . strtolower(str_replace('\\', '-', get_class($data)));
				}
				$html .= '<tr>';
				if ($this->selectable) {
					//check item
					$html .= $this->tag('td', '<input type="checkbox" name="selectable[]" value="' . $value . '" />');
				}
				if ($this->headers) {
					$j = 0;
					//if we have headers, display only headers
					foreach ($headersCollapsed as $header) {
						$v = isset($data[$header]) ? $data[$header] : null;
						$atts = [];
						//add class to make it sortable
						if ($this->sortableHeaders && $this->headers) {
							$atts['class'] = $headersCollapsed[$j];
						}
						$html .= $this->tag('td',$v,$atts);
						$j++;
					}
				}
				//or display everything
				else {
					foreach ($data as $k => $v) {
						$html .= $this->tag('td', $v);
					}
				}
				if ($this->actions) {
					$actions = $this->makeActions($value);
					$actions = '<div class="btn-group">' . $actions . '</div>';
					$html .= $this->tag('td', $actions,['class' => 'actions']);
				}
				$html .= '</tr>';
			}
			$html .= '</tbody>';
		}

		//wrap table
		$class = $this->class;
		$id = $this->id;
		$table_attr = compact('class', 'id');
		$html = $this->tag('table', $html, $table_attr);

		if ($this->actions || $this->selectable) {
			//append selectable actions
			if ($this->selectable) {
				$selectable_actions = $this->makeSelectableActions();
				$html .= $selectable_actions;
			}
			$atts = [
				'name' => $this->getFormName(),
				'method' => $this->formMethod
			];
			$html = $this->tag('form', $html, $atts);
		}

		//pagination
		if ($this->pagination) {
			$pagination = $this->makePagination();
			$html = $pagination . $html . $pagination;
		}

		return $html;
	}

	protected function getScript() {
		if ($this->selectable) {
			if (!self::$scriptInserted) {
				$this->html .= <<<'SCRIPT'
<script type="text/javascript">
function toggleSelectable(el,fields) {
	for(var i=0; i < fields.length; i++) {
		if(fields[i].name === 'selectable[]') fields[i].checked = el.checked;
	}
}
</script>
SCRIPT;
				self::$scriptInserted = true;
			}
		}
	}

	protected function makeSearchableHeaders() {
		$headers_keys = $this->array_collapse($this->headers);
		$searchable_headers = '';
		if ($this->selectable) {
			$searchable_headers .= $this->tag('th');
		}
		foreach ($headers_keys as $header) {
			$input = '';
			if (in_array($header, $this->searchableHeaders)) {
				$value = isset($_GET[$this->searchableKey][$header]) ? $_GET[$this->searchableKey][$header] : null;
				$input = '<input name="' . $this->searchableKey . '[' . $header . ']" value="' . $value . '" style="width:auto" />';
			}
			$searchable_headers .= $this->tag('th', $input);
		}
		if ($this->actions) {
			$searchable_headers .= $this->tag('th', $this->tag('input',$this->searchableInput));
		}
		return $searchable_headers;
	}

	protected function makePagination() {
		$li = '';
		$current = $this->pagination['current'];
		$total = ceil($this->pagination['total']);
		if ($current > $total) {
			$current = $total;
		}
		$collapse = $this->pagination['collapse'];

		if ($current == 0) {
			$class = 'disabled';
		}
		$li .= $this->tag('li', $this->tag('a', '&laquo;', array('href' => _::querystring('p', $current - 1))), array('class' => $class));
		for ($i = 0; $i < $total; $i++) {
			if ($collapse) {
				if ($total > $collapse && ($i > $current + $collapse / 2 && $i > $collapse) || ($i < $current - $collapse / 2 && $i < $total - $collapse)) {
					continue;
				}
			}
			$class = '';
			if ($i == $current) {
				$class = 'active';
			}
			$li .= $this->tag('li', $this->tag('a', $i + 1, array('href' => _::querystring('p', $i))), array('class' => $class));
		}
		if ($current == $total) {
			$class = 'disabled';
		}
		$li .= $this->tag('li', $this->tag('a', '&raquo;', array('href' => _::querystring('p', $current + 1))), array('class' => $class));
		$pagination = $this->tag('div.pagination>ul', $li);
		return $pagination;
	}

	protected function makeSelectableActions() {
		if(empty($this->selectableActions)) {
			return '';
		}
		foreach ($this->selectableActions as $action => $value) {
			if (is_int($action)) {
				$action = $value;
				$value = ucwords(str_replace('_', ' ', $action));
			}
			$class = 'btn';
			$type = 'submit';
			$name = 'action[' . $action . ']';
			$btn = $this->tag('input', compact('type', 'class', 'name', 'value'));
			$actions[] = $btn;
		}
		$actions = implode('', $actions);
		return $actions;
	}

	protected function makeActions($value = null) {
		if (is_array($this->actions)) {
			$actions = array();
			foreach ($this->actions as $action => $label) {
				if (is_int($action)) {
					$action = $label;
					$label = ucwords(str_replace('_', ' ', $action));
				}

				$atts = [];
				$atts['class'] = $this->baseActionClass;
				if(isset($this->actionsClass[$action])) {
					$atts['class'] .= ' ' . $this->actionsClass[$action];
				}
				if(in_array($action,$this->actionConfirm)) {
					$atts['onclick'] = $this->confirmScript;
				}
				if (is_string($action)) {
					$href = preg_replace('#\?.*$#D', '', $_SERVER['REQUEST_URI']);
					if ($this->actionsMode == self::MODE_REPLACE) {
						$href = dirname($href);
					}
					if ($this->actionsMode == self::MODE_QS) {
						$href .= '?action=' . $action;
						if ($value) {
							$href .= '&id=' . urlencode($value);
						}
					} else {
						$href .= '/' . $action;
						if ($value) {
							$href .= '/' . urlencode($value);
						}
					}
					$atts['href'] = $href;
				}
				$btn = $this->tag('a', $label, $atts);
				$actions[] = $btn;
			}
			$actions = implode('', $actions);
		} else {
			$actions = $this->actions;
			preg_match_all('/{{(?P<var>.*)}}/', $actions, $matches);
			if (!empty($matches['var'])) {
				foreach ($matches['var'] as $var) {
					if (isset($data[$var])) {
						$actions = str_replace("{{" . $var . "}}", $data[$var], $actions);
					}
				}
			}
		}
		return $actions;
	}

}
