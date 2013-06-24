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
	
	const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';

	protected static $instances = 0;
	protected static $scriptInserted = false;
	protected $identifier;
	protected $selectable;
	protected $selectableActions;
	protected $headers;
	protected $pagination;
	protected $paginationKey = 'p';
	protected $data;
	protected $class = 'table table-striped';
	protected $id;
	protected $indent;
	protected $actions;
	protected $detailRow;
	protected $baseHref;
	protected $baseActionClass = 'btn';
	protected $actionsMode = 'append';
	protected $actionsClass = [
		'remove' => 'btn-danger confirm',
		'delete' => 'btn-danger confirm'
	];
	protected $actionConfirm = [];
	protected $confirmScript = "return confirm('Are you sure?');";
	protected $sortableHeaders;
	protected $sortData = false;
	protected $searchableHeaders;
	protected $searchableHeadersSize = [
		'id' => 4,
		'day' => 2,
		'week' => 2
	];
	protected $searchableKey = 'filters';
	protected $searchableInput = '<input type="submit" value="filter" class="btn">';
	protected $formMethod;
	protected $tableSearch = true;
	protected $tableSearchKey = 'search';
	protected $tableSearchInput = '<input type="text" class="search search-query" placeholder="search">';

	public function __construct() {
		self::$instances++;
	}

	public function getIdentifier() {
		return $this->identifier;
	}

	public function setIdentifier($id = 'id') {
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

	public function getDetailRow() {
		return $this->detailRow;
	}

	public function setDetailRow($detailRow) {
		$this->detailRow = $detailRow;
	}

	public function getPagination() {
		return $this->pagination;
	}

	public function setPagination($total, $collapse = null) {
		$this->pagination = compact('total', 'collapse');
		return $this;
	}
	
	public function getPaginationKey() {
		return $this->paginationKey;
	}

	public function setPaginationKey($paginationKey) {
		$this->paginationKey = $paginationKey;
		return $this;
	}

	public function getHeaders() {
		return $this->headers;
	}

	public function setHeaders($headers = null) {
		if (!is_array($headers)) {
			throw new Exception('Headers must be an array');
		}
		$this->headers = $headers;
		return $this;
	}

	public function getSearchableHeaders() {
		return $this->searchableHeaders;
	}

	public function setSearchableHeaders($headers = null) {
		$this->searchableHeaders = $headers;
		return $this;
	}

	public function getSearchableHeadersSize() {
		return $this->searchableHeadersSize;
	}

	public function setSearchableHeadersSize($searchableHeadersSize) {
		$this->searchableHeadersSize = $searchableHeadersSize;
		return $this;
	}

	public function getSearchableKey() {
		return $this->searchableKey;
	}

	public function getSearchableInput() {
		return $this->searchableInput;
	}

	public function setSearchableKey($searchableKey) {
		$this->searchableKey = $searchableKey;
		return $this;
	}

	public function setSearchableInput($searchableInput) {
		$this->searchableInput = $searchableInput;
		return $this;
	}

	public function getSortableHeaders() {
		return $this->sortableHeaders;
	}

	public function setSortableHeaders($sortableHeaders = true) {
		$this->sortableHeaders = $sortableHeaders;
		return $this;
	}
	
	public function getSortData() {
		return $this->sortData;
	}

	public function setSortData($sortData = true) {
		$this->sortData = $sortData;
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

	public function addClass($class) {
		$this->class .= ' ' . $class;
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
		if ($this->formMethod === null) {
			if ($this->searchableHeaders) {
				$this->formMethod = self::METHOD_GET;
			}
			if ($this->selectable) {
				$this->formMethod = self::METHOD_POST;
			}
		}
		return $this->formMethod;
	}

	public function setFormMethod($v) {
		$this->formMethod = strtoupper($v);
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
		
		//fetch data
		if($this->data instanceof \k\db\Query) {
			$this->data = $this->data->fetchAll();
		}

		//normalize data
		if ($this->actions) {
			$actions = [];
			foreach ($this->actions as $action => $label) {
				if (is_int($action)) {
					$action = $label;
					$label = ucwords(str_replace(array('_', '.', '-'), ' ', $label));
				}
				$actions[$action] = $label;
			}
			$this->actions = $actions;
			$baseActions = $this->makeActions();
		}
		
		if($this->sortableHeaders) {
			$sort = $this->getFromMethod('sort');
			$dir = $this->getFromMethod('dir','asc');
			if($this->sortData && $sort) {
				usort($this->data,function($a,$b) use($sort,$dir) {
					if($a[$sort] == $b[$sort]) {
						return 0;
					}
					if($dir == 'asc') {
						return $a[$sort] < $b[$sort] ? -1 : 1;
					}
					return $a[$sort] > $b[$sort] ? -1 : 1;
				});
			}
		}
			
		//build headers
		if ($this->headers) {
			$headers = [];
			foreach ($this->headers as $header => $label) {
				if (is_int($header)) {
					$header = $label;
					$label = ucwords(str_replace(array('_', '.', '-'), ' ', $label));
				}
				$headers[$header] = $label;
			}
			$this->headers = $headers;
			$headersKeys = array_keys($this->headers);
			if ($this->sortableHeaders === true) {
				$this->sortableHeaders = $headersKeys;
			}
			if ($this->searchableHeaders === true) {
				$this->searchableHeaders = $headersKeys;
			}
			$headers = '';

			if ($this->selectable) {
				//un-check all
				$headers .= '<th><input type="checkbox" onclick="toggleSelectable(this,document.' . $this->getFormName() . ');" /></th>';
			}
			
			foreach ($this->headers as $header => $label) {
				$tag = '<th';
				if ($this->sortableHeaders && in_array($header, $this->sortableHeaders)) {
					$class = 'sort';
					if($dir && $sort && $sort == $header) {
						$class .= ' ' . $dir;
					}
					$tag .=  ' class="'.$class.'" data-sort="' . $header . '"';
					$label .= '<span></span>';
					if($sort && $sort == $header) {
						if($dir == 'asc') {
							$dir = 'desc';
						}
						else {
							$dir = 'asc';
						}
					}
					$label = '<a href="'.$this->getQueryStringUrl(['sort' => $header,'dir' => $dir]).'">'.$label.'</a>';
				}
				$tag .= '>' . $label . '</th>';
				$headers .= $tag;
			}
			if ($this->actions) {
				$search = null;
				if ($this->tableSearch) {
					$value = $this->getFromMethod($this->tableSearchKey);
					$search = trim($this->tableSearchInput,'>');
					$search .= ' name="'.$this->tableSearchKey.'" value="'.$value.'">';
				}
				$headers .= '<th>' . $search . '</th>';
			}

			$headers = '<tr>' . $headers . '</tr>';
			if ($this->searchableHeaders) {
				$searchableHeaders = $this->makeSearchableHeaders();
				$headers .= '<tr class="filter">' . $searchableHeaders . '</tr>';
			}

			$html .= $this->tag('thead', $headers);
		}
		if ($this->data) {
			$html .= '<tbody class="list">';
			$i = 0;
			$useIdentifier = false;
			
			//first row analysis
			if(!empty($this->data)) {
				$row = $this->data[0];
				//auto id
				if(is_object($row)) {
					$this->id = 'table-' . strtolower(str_replace('\\', '-', get_class($row)));
				}
				//identifier
				if($this->identifier === null && isset($row['id'])) {
					$this->identifier = 'id';
				}
				if(isset($row[$this->identifier])) {
					$useIdentifier = true;
				}
			}
			foreach ($this->data as $data) {
				$i++;
				$value = $i;
				if ($useIdentifier) {
					$value = $data[$this->identifier];
				}
				$html .= '<tr';
				if ($this->detailRow) {
					$html .= ' onclick="toggleDetailRow(this)"';
				}
				$html .= '>';
				if ($this->selectable) {
					//check item
					$html .= '<td><input type="checkbox" name="selectable[]" value="' . $value . '" /></td>';
				}
				if ($this->headers) {
					//if we have headers, display only headers
					foreach ($headersKeys as $header) {
						$v = isset($data[$header]) ? $data[$header] : null;
						$tag = '<td';
						//add class to make it sortable
						if ($this->sortableHeaders && $this->headers) {
							$tag .= ' class="' . $header . '"';
						}
						$tag .= '>' . $v . '</td>';
						$html .= $tag;
					}
				}
				//or display everything
				else {
					foreach ($data as $k => $v) {
						$html .= '<td>' . $v . '</td>';
					}
				}
				if ($this->actions) {
					$actions = $baseActions;
					//replace vars
					preg_match_all('/{{(?P<var>[a-zA-Z0-9_]*)}}/', $actions, $matches);
					if (!empty($matches['var'])) {
						foreach ($matches['var'] as $var) {
							if (isset($data[$var])) {
								$actions = str_replace('{{' . $var . '}}', urlencode($data[$var]), $actions);
							}
						}
					}
					//wrap in group
					$actions = '<div class="btn-group">' . $actions . '</div>';
					$html .= '<td class="actions">' . $actions . '</td>';
				}
				$html .= '</tr>';

				if ($this->detailRow) {
					$html .= '<tr class="detail" style="display:none">';
					$colspan = count($headersKeys);
					if ($this->actions) {
						$colspan++;
					}
					$v = $this->detailRow;
					if (is_callable($this->detailRow)) {
						$v = $v($data, $this);
					}
					$html .= '<td colspan="' . $colspan . '">' . $v . '</td>';
					$html .= '</tr>';
				}
			}
			$html .= '</tbody>';
		}

		//wrap table
		$html = '<table class="' . $this->class . '" id="' . $this->id . '">' . $html . '</table>';

		if ($this->actions || $this->selectable) {
			//append selectable actions
			if ($this->selectable) {
				$selectable_actions = $this->makeSelectableActions();
				$html .= $selectable_actions;
			}
			$html = '<form name="' . $this->getFormName() . '" method="' . $this->getFormMethod() . '">' . $html . '</form>';
		}

		//pagination
		if ($this->pagination) {
			$pagination = $this->makePagination();
			$html = $html . $pagination;
		}

		return $html;
	}

	protected function getScript() {
		if ($this->selectable || $this->detailRow) {
			if (!self::$scriptInserted) {
				$this->html .= <<<'SCRIPT'
<script type="text/javascript">
function toggleSelectable(el,fields) {
	for(var i=0; i < fields.length; i++) {
		if(fields[i].name === 'selectable[]') fields[i].checked = el.checked;
	}
}
function toggleDetailRow(el) {
	var sibling = el.nextSibling;
	if(sibling.style.display == 'table-row') {
		sibling.style.display = 'none';
	}
	else {
		sibling.style.display = 'table-row';
	}
	
}
</script>
SCRIPT;
				self::$scriptInserted = true;
			}
		}
	}
	
	protected function getFromMethod($key,$default = null) {
		if ($this->getFormMethod() == self::METHOD_POST) {
			return isset($_POST[$key]) ? $_POST[$key] : $default;
		}
		return isset($_GET[$key]) ? $_GET[$key] : $default;
	}

	protected function makeSearchableHeaders() {
		$headersKey = array_keys($this->headers);
		$searchableHeaders = '';
		if ($this->selectable) {
			$searchableHeaders .= '<td></td>';
		}

		$data = $_GET;
		if ($this->getFormMethod() == self::METHOD_POST) {
			$data = $_POST;
		}
		if ($this->searchableKey && isset($data[$this->searchableKey])) {
			$data = $data[$this->searchableKey];
		}

		foreach ($headersKey as $header) {
			$input = '';
			if (in_array($header, $this->searchableHeaders)) {
				$size = 10;
				if (isset($this->searchableHeadersSize[$header])) {
					$size = $this->searchableHeadersSize[$header];
				}
				$value = isset($data[$header]) ? $data[$header] : null;
				$name = $header;
				if ($this->searchableKey) {
					$name = $this->searchableKey . '[' . $name . ']';
				}
				$input = '<input type="text" name="' . $name . '" value="' . $value . '" style="width:auto" data-filter="' . $header . '" size="' . $size . '" />';
			}
			$searchableHeaders .= '<td>' . $input . '</td>';
		}
		if ($this->actions) {
			$searchableHeaders .= '<td>' . $this->searchableInput . '</td>';
		}
		return $searchableHeaders;
	}

	protected function makePagination() {
		$html = '';
		$current = isset($_GET[$this->paginationKey]) ? (int) $_GET[$this->paginationKey] : 0;
		$total = ceil($this->pagination['total']);
		if ($current > $total) {
			$current = $total;
		}
		$collapse = $this->pagination['collapse'];
		$class = '';
		if ($current == 0) {
			$class = 'disabled';
		}
		$html .= '<li class="'.$class.'"><a href="'.$this->getQueryStringUrl($this->paginationKey, $current - 1).'">&laquo;</a></li>';
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
			$html .= '<li class="'.$class.'"><a href="'.$this->getQueryStringUrl($this->paginationKey, $i).'">'.($i+1).'</a></li>';
		}
		if ($current == $total) {
			$class = 'disabled';
		}
		$html .= '<li class="'.$class.'"><a href="'.$this->getQueryStringUrl($this->paginationKey, $current + 1).'">&raquo;</a></li>';
		$pagination = '<div data-table="'.$this->getId().'" class="pagination pagination-centered"><ul>'.$html.'</ul></div>';
		return $pagination;
	}

	protected function makeSelectableActions() {
		if (empty($this->selectableActions)) {
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

	protected function getBaseUrl() {
		if ($this->baseHref === null) {
			$this->baseHref = strtok($_SERVER["REQUEST_URI"], '?');
		}
		return $this->baseHref;
	}
	
	protected function getQueryStringUrl($key, $value = null) {
		$url = $this->getBaseUrl();
		$sep = ini_get('arg_separator.output');
		$qs = $_GET;
		if (is_array($key)) {
			$qs = array_merge($qs, $key);
		} else {
			if ($value === null) {
				unset($qs[$key]);
			} else {
				$qs[$key] = $value;
			}
		}
		$str = '';
		foreach ($qs as $k => $v) {
			if (is_array($v)) {
				$str .= http_build_query(array($k => $v)) . $sep;
			} else {
				$str .= "$k=" . urlencode($v) . $sep;
			}
		}
		return $url . '?' . substr($str, 0, -1); /* trim off trailing $sep */
	}

	protected function makeActions() {
		if(is_string($this->actions)) {
			return $this->actions;
		}
		$actions = '';
		if (is_array($this->actions)) {
			$actions = array();
			foreach ($this->actions as $action => $label) {
				$tag = '<a class="' . $this->baseActionClass;
				if (isset($this->actionsClass[$action])) {
					$tag .= ' ' . $this->actionsClass[$action];
				}
				$tag .= '"';
				if (in_array($action, $this->actionConfirm)) {
					$tag .= ' onclick="' . $this->confirmScript . '"';
				}
				if (is_string($action)) {
					$href = $this->getBaseUrl();
					if ($this->actionsMode == self::MODE_REPLACE) {
						$href = dirname($href);
					}
					if ($this->actionsMode == self::MODE_QS) {
						$href .= '?action=' . $action;
						if($this->getIdentifier()) {
							$href .= '&id={{'.$this->getIdentifier().'}}';
						}
						
					} else {
						$href .= '/' . $action;
						if($this->getIdentifier()) {
							$href .= '/{{'.$this->getIdentifier().'}}';
						}
					}
					$tag .= ' href="' . $href . '"';
				}
				$tag .= '>' . $label . '</a>';
				$actions[] = $tag;
			}
			$actions = implode('', $actions);
		}
		return $actions;
	}

}
