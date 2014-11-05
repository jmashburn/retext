<?php

namespace Application;

class Set implements \ArrayAccess, \Iterator, \Countable {

	protected $items;

	protected $hydrateClass;

	public function __construct(array $items, $hydrateClass = 'ArrayObject') {
		$this->items = $items;
		$this->hydrateClass = $hydrateClass;
		$this->rewind($this->items);
	}

	public function count() {
		return count($this->items);
	}

	public function current() {
		$current = current($this->items);
		return $this->hydrate($current ? $current : array(), $this->key());
	}

	public function key() {
		return key($this->items);
	}

	public function next() {
		$item = next($this->items);
		if ($item === false) {
			return false;
		}
		return $this->hydrate($item, $this->key());
	}

	public function rewind() {
		return reset($this->items);
	}

	public function end() {
		return end($this->items);
	}

	public function valid() {
		$current = current($this->items);
		return !empty($current) !== false;
		#return !empty(current($this->items));
		return current($this->items) !== false;
	}

	public function offsetExists($offset) {
		return isset($this->items[$offset]);
	}	

	public function offsetGet($offset) {
		$info = isset($this->items[$offset]) ? $this->items[$offset] : array();
		return $this->hydrate($info, $offset);
	}

	public function offsetSet($offset, $value) {
		if (is_null($offset)) {
			$this->items[] = $value;
		} else {
			$this->items[$offset] = $value;
		}
	}

	public function offsetUnset($offset) {
		unset($this->items[$offset]);
	}

	public function toArray() {
		return $this->items;
	}

	public function setHydrateClass($hydrateClass) {
		$this->hydrateClass = $hydrateClass;
		return $this;
	}

	public function hydrate($items, $key = null) {
		if (is_null($this->hydrateClass)) {
			return $items;
		}
		return new $this->hydrateClass($items, $key);
	}
}