<?php

namespace k;

/**
 * Something more useful than an array
 */
class Collection extends ArrayObject {

	/**
	 * Alias of getArrayCopy()
	 * @return array
	 */
	function toArray() {
		return $this->getArrayCopy();
	}

	/**
	 * Shortcut for array_ methods
	 * 
	 * @method array_change_key_case() Changes all keys in an array
	 * @method array_chunk() Split an array into chunks
	 * @method array_combine() Creates an array by using one array for keys and another for its values
	 * @method array_count_values() Counts all the values of an array
	 * @method array_diff_assoc() Computes the difference of arrays with additional index check
	 * @method array_diff_key() Computes the difference of arrays using keys for comparison
	 * @method array_diff_uassoc() Computes the difference of arrays with additional index check which is performed by a user supplied callback function
	 * @method array_diff_ukey() Computes the difference of arrays using a callback function on the keys for comparison
	 * @method array_diff() Computes the difference of arrays
	 * @method array_fill_keys() Fill an array with values, specifying keys
	 * @method array_fill() Fill an array with values
	 * @method array_filter() Filters elements of an array using a callback function
	 * @method array_flip() Exchanges all keys with their associated values in an array
	 * @method array_intersect_assoc() Computes the intersection of arrays with additional index check
	 * @method array_intersect_key() Computes the intersection of arrays using keys for comparison
	 * @method array_intersect_uassoc() Computes the intersection of arrays with additional index check, compares indexes by a callback function
	 * @method array_intersect_ukey() Computes the intersection of arrays using a callback function on the keys for comparison
	 * @method array_intersect() Computes the intersection of arrays
	 * @method array_key_exists() Checks if the given key or index exists in the array
	 * @method array_keys() Return all the keys or a subset of the keys of an array
	 * @method array_map() Applies the callback to the elements of the given arrays
	 * @method array_merge_recursive() Merge two or more arrays recursively
	 * @method array_merge() Merge one or more arrays
	 * @method array_multisort() Sort multiple or multi-dimensional arrays
	 * @method array_pad() Pad array to the specified length with a value
	 * @method array_pop() Pop the element off the end of array
	 * @method array_product() Calculate the product of values in an array
	 * @method array_push() Push one or more elements onto the end of array
	 * @method array_rand() Pick one or more random entries out of an array
	 * @method array_reduce() Iteratively reduce the array to a single value using a callback function
	 * @method array_replace_recursive() Replaces elements from passed arrays into the first array recursively
	 * @method array_replace() Replaces elements from passed arrays into the first array
	 * @method array_reverse() Return an array with elements in reverse order
	 * @method array_search() Searches the array for a given value and returns the corresponding key if successful
	 * @method array_shift() Shift an element off the beginning of array
	 * @method array_slice() Extract a slice of the array
	 * @method array_splice() Remove a portion of the array and replace it with something else
	 * @method array_sum() Calculate the sum of values in an array
	 * @method array_udiff_assoc() Computes the difference of arrays with additional index check, compares data by a callback function
	 * @method array_udiff_uassoc() Computes the difference of arrays with additional index check, compares data and indexes by a callback function
	 * @method array_udiff() Computes the difference of arrays by using a callback function for data comparison
	 * @method array_uintersect_assoc() Computes the intersection of arrays with additional index check, compares data by a callback function
	 * @method array_uintersect_uassoc() Computes the intersection of arrays with additional index check, compares data and indexes by a callback functions
	 * @method array_uintersect() Computes the intersection of arrays, compares data by a callback function
	 * @method array_unique() Removes duplicate values from an array
	 * @method array_unshift() Prepend one or more elements to the beginning of an array
	 * @method array_values() Return all the values of an array
	 * @method array_walk_recursive() Apply a user function recursively to every member of an array
	 * @method array_walk() Apply a user function to every member of an array
	 * @param string $func
	 * @param string $argv
	 * @return mixed
	 */
	public function __call($func, $argv) {
		if (!is_callable($func) || substr($func, 0, 6) !== 'array_') {
			throw new BadMethodCallException(__CLASS__ . '->' . $func);
		}
		return call_user_func_array($func, array_merge(array($this->getArrayCopy()), $argv));
	}

	function apply($callback) {
		foreach ($this as $k => $v) {
			$this[$k] = $callback($v);
		}
	}

	function first() {
		return $this[0];
	}

	function last() {
		return $this[$this->count()];
	}

}