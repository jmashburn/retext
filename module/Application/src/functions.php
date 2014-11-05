<?php

function __($string, array $params = array()) {
	$string = vsprintf($string, $params);
	return $string;
}

function gmt($timestamp = null) {
	$time = time();
	if ($timestamp) {
		$time = $timestamp;
	}
	return gmmktime(
		intval(date('G', $time)),
		intval(date('i', $time)),
		intval(date('s', $time)),
		intval(date('n', $time)),
		intval(date('j', $time)),
		intval(date('Y', $time))
	);
}

function merge($arr1, $arr2 = null) {
	$args = func_get_args();

	$r = (array)current($args);
	while (($arg = next($args)) !== false) {
		foreach ((array)$arg as $key => $val) {
			if (!empty($r[$key]) && is_array($r[$key]) && is_array($val)) {
				$r[$key] = merge($r[$key], $val);
			} elseif (is_int($key)) {
				$r[] = $val;
			} else {
				$r[$key] = $val;
			}
		}
	}
	return $r;
}
