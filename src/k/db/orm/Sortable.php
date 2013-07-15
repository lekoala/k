<?php

namespace k\db\orm;

/**
 * Sortable
 */
trait Sortable {
	
	public $sort_order;

	public static function getByOrder($order) {
		if ($order == 'last') {
			$order = static::max('sort_order');
		} elseif ($order == 'first') {
			$order = static::min('sort_order');
		}
		return static::query()->where('sort_order', $order)->fetchOne();
	}
	
	public static function updateSortOrder($array) {
		foreach($array as $id => $pos) {
			static::update(array('sort_order' => $pos), array('id' => $id));
		}
		return true;
	}

	public function moveFirst() {
		$count = static::count('sort_order = 1');
		if ($count) {
			//increment everthing by one
			static::getPdo()->query('UPDATE ' . static::getTable() . ' SET sort_order = sort_order + 1');
		}
		$this->sort_order = 1;
		return $this->save();
	}

	public function moveLast() {
		$last = static::max('sort_order');
		$this->sort_order = $last + 1;
		return $this->save();
	}

	public function moveUp() {
		$next = static::query()->whereGt('sort_order', $this->sort_order)->orderBy('sort_order ASC')->fetchOne();
		$order = $this->sort_order;
		$this->sort_order = $next->sort_order;
		$next->sort_order = $order;
		$this->save();
		return $next->save();
	}

	public function moveDown() {
		return static::getPdo()->query('UPDATE ' . static::getTable() . ' SET sort_order = sort_order + 1 WHERE sort_order < ' . $this->sort_order);
		$prev = static::query()->whereLt('sort_order', $this->sort_order)->orderBy('sort_order DESC')->fetchOne();
		$order = $this->sort_order;
		$this->sort_order = $prev->sort_order;
		$prev->sort_order = $order;
		$this->save();
		return $prev->save();
	}

}