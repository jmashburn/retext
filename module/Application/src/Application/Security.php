<?php

namespace Application;

use Config;

class Security {

	public static $hashType = null;


	public static function generateAuthKey() {
		return Security::hash(String::uuid());
	}

	public static function hash($string, $type = null, $salt = false) {
		if ($salt) {
			if (is_string($salt)) {
				$string = $salt . $string;
			} else {
				$string = \Config::getConfig('salt', 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi');
			}
		}

		if (empty($type)) {
			$type = self::$hashType;
		}
		$type = strtolower($type);
		if ($type == 'sha1' || $type == null) {
			if (function_exists('sha1')) {
				$return = sha1($string);
				return $return;
			}
			$type = 'sha256';
		}

		if ($type == 'sha256' && function_exists('hash')) {
			return hash($type, $string);
		}
		return md5($string);
	}

	public static function setHash($hash) {
		self::$hashType = $hash;
	}

	public static function cipher($text, $key) {
		if (empty($key)) {
			throw new Exception('Key cannot be empty for Security::cipher()');
		}
		srand(\Config::getConfig('cipherSeed', '76859309657453542496749683645'));
		$out = '';
		$keyLength = strlen($key);
		for ($i = 0, $textLength = strlen($text); $i < $textLength; $i++) {
			$j = ord(substr($key, $i % $keyLength, 1));
			while ($j--) {
				rand(0, 255);
			}
			$mask= rand(0, 255);
			$out .= chr(ord(substr($text, $i, 1)) ^ $mask);
		}
		srand();
		return $out;
	}
}