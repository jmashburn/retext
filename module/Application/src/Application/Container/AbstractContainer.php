<?php

namespace Application\Container;

use Application\Set;

class AbstractContainer implements \Countable, \Iterator, \ArrayAccess
{

	protected $count;

	protected $data = array();

	protected $skipNextIteration;

	public function __construct(array $array) {
		if (!empty($array)) {
			foreach ($array as $key => $value) {
				if (is_array($value)) {
					#$this->data[$key] = array();
					$this->data[$key] = new self($value);
				} else {
					$this->data[$key] = $value;
				}
				$this->count++;
			}
		}
	}

	public function get($name, $default = null) {
		if (array_key_exists($name, $this->data)) {
			return $this->data[$name];
		}
		return $default;
	}

	public function set($name, $value) {
		$this->data[$name] = $value;
	}

	public function __call($method, $args) {
		$prefix = substr($method, 0, 3);
		$option = substr($method, 3);
		$key = strtolower(preg_replace('#(?<=[a-z])([A-Z])#', '_\1', $option));

		if ($prefix === 'set') {
			$value = array_shift($args);
			return $this->set($key, $value);
		} elseif ($prefix === 'get') {
			return $this->get($key, '');
		} else {
			throw new \Application\Exception(__('Method "%s" does not exists in %s', array($method, get_called_class())));
		}
	}

	public function __get($name) {
		return $this->get($name);
	}

	public function __set($name, $value) {
		if (is_array($value)) {
			$value = new self($value, true);
		}
		if (null === $name) {
			$this->data[] = $value;
		} else {
			$this->data[$name] = $value;
		}
		$this->count++;
	}

	public function __clone() {
		$array = array();
		foreach ($this->data as $key => $value) {
			if ($value instanceof self) {
				$array[$key] == clone $value;
			} else {
				$array[$key] = $value;
			}
		}
		$this->data = $array;
	}

	private function processArray($data, $array = array()) {
		foreach ($data as $key => $value) {
			if (is_array($value)) {
				$this->processArray($value, $array);
			} elseif ($value instanceof self) {
				$array[$key][] = $value->toArray();
			} else {
				$array[$key] = $value;
			}
			// if (is_array($value)) {
			// 	#$array[$key][] = $this->processArray($value, $array);
			// } elseif ($value instanceof self) {
			// 	$array[$key] = $value->toArray();
			// } else {
			// 	$array[$key] = $value;
			// }
			// print_r($array);
			// die();
		}
		return $array;

	}

	public function toArray(array $defaults = array(), $filter = false) {
		$array = array();
		$data = $this->data;

		$data = array_merge($defaults, $data);

		foreach ($data as $key => $value) {
			if ($value instanceof self) {
				$array[$key] = $value->toArray($defaults);
			} elseif ($value instanceof \Application\Set) {
				$array[$key] = $value->toArray($defaults);
			} else {
				$array[$key] = $value;
			}
		}
		if ($filter && !empty($defaults)) {
			$array = array_intersect_key($array, $defaults);
		}
		return $array;
	}

	public function toJson(array $defaults = array(), $filter = false) {
		return json_encode($this->toArray($defaults, $filter));
	}

	public function __isset($name) {
		return isset($this->data[$name]);
	}

	public function __unset($name) {
		if (isset($this->data[$name])) {
			unset($this->data[$name]);
			$this->count--;
			$this->skipNextIteration = true;
		}
	}

	public function count() {
		return $this->count;
	}

	public function current() {
		$this->skipNextIteration = false;
		return current($this->data);
	}

	public function key() {
		return key($this->data);
	}

	public function next() {
		if ($this->skipNextIteration) {
			$this->skipNextIteration = false;
			return;
		}
		next($this->data);
	}

	public function rewind() {
		$this->skipNextIteration = false;
		reset($this->data);
	}

	public function valid() {
		return ($this->key() !== null);
	}

	public function offsetExists($offset) {
		return $this->__isset($offset);
	}

	public function offsetGet($offset) {
		return $this->__get($offset);
	}

	public function offsetSet($offset, $value) {
		$this->__set($offset, $value);
	}

	public function offsetUnset($offset) {
		$this->__unset($offset);
	}

	public function merge(self $merge) {
		foreach ($merge as $key => $value) {
			if (array_key_exists($key, $this->data)) {
				if (is_int($key)) {
					$this->data[] = $value;
				} elseif ($value instanceof self && $this->data[$key] instanceof self) {
					$this->data[$key]->merge($value);
				} else {
					if ($value instanceof self) {
						$this->data[$key] = new self($value->toArray());
					} else {
						$this->data[$key] = $value;
					}
				}
			} else {
				if ($value instanceof self) {
					$this->data[$key] = new self($value->toArray());
				} else {
					$this->data[$key] = $value;
				}
			}
		}

		return $this;
	}
}