<?php

namespace Application\Api\Db;

use Application\Db\ApiPDO;

use Application\Mapper\AbstractMapper,
	ApiException as Exception;


class Mapper extends AbstractMapper  {

	public $context = 'gui';

	public function findKeyByName($name) {

		$instance = ApiPDO::getInstance('gui')->prepare("SELECT * FROM `gui_webapi_keys` where `name`=:name");
		$instance->bindValue(':name', $name, \PDO::PARAM_STR);
		$instance->execute();
	    $user = $instance->fetch(\PDO::FETCH_ASSOC);

		if (is_array($user)) {
			return $user;
		}
		return false;
	}

	public function findKeyByUsername($username) {

		$user = $this->select("SELECT * FROM `gui_webapi_keys` where `username`=:username", array(':username' => $username));
		// $instance = ApiPDO::getInstance('gui')->prepare("SELECT * FROM `gui_webapi_keys` where username=:username");
		// $instance->bindValue(':username', $username, \PDO::PARAM_STR);
		// $instance->execute();
	 //    $user = $instance->fetch(\PDO::FETCH_ASSOC);

		print_r($user);
		die();

		if (is_array($user)) {
			return $user;
		}
		return false;
	}

	public function findAllKeys() {
		$keys = $this->select("SELECT * FROM `gui_webapi_keys`");
		print_r($keys);
		die();

		// $instance = ApiPDO::getInstance('gui')->prepare("SELECT * FROM `gui_webapi_keys`");
		// $instance->execute();
	 //    $keys = $instance->fetchAll(\PDO::FETCH_ASSOC);

		if (is_array($keys)) {
			return $keys;
		}
		return array();
	}

	public function addKey($name, $username) {
		$hash = $this->generateHash();
		$keyFields = array(
			'key' => uniqid('wk_'),
			'name' => $name,
			'username' => $username,
			'hash' => $hash,
			'creation_time' => date('Y-m-d H:i:s', time()),
		);

		try {
			$instance = ApiPDO::getInstance('gui')->prepare("INSERT INTO `gui_webapi_keys` (`key`, `name`, `username`, `hash`, `creation_time`) VALUES (:name, :username, :hash, :creation_time)");
			$result = $instance->execute(array_combine(array(':key', ':name', ':username', ':hash', ':creation_time'), $keyFields));
		} catch(\PDOException $e) {
			throw new Exception($e->getMessage(), $e->getCode());
		}
		return array_unshift($keyFields, ApiPDO::getInstance('gui')->lastInsertId());
	}

	// This shouldn't be here
	public function findUserByUsername($username) {

		$instance = WebPDO::getInstance('gui')->prepare("SELECT * FROM `subscribers` where `username`=:username");
		$instance->bindValue(':username', $username, \PDO::PARAM_STR);
		$instance->execute();
	    $user = $instance->fetch(\PDO::FETCH_ASSOC);

		if (is_array($user)) {
			return $user;
		}
		return false;

		#
	}

	public function deleteKeysById($keys) {
		$searchKeysArray = implode(',', $keys);
		$instance = ApiPDO::getInstance('gui')->prepare('DELETE FROM `gui_webapi_keys` WHERE `key` IN ( ' . $searchKeysArray . ' ) ');
		return $instance->execute();
	}

	private function generateHash() {
		list($usec, $sec) = explode(' ', microtime());
		$seed = (float) $sec + ((float) $usec * 100000);
		mt_srand($seed);
		$min = 0;
		$max = mt_getrandmax();
		return \Security::hash(mt_rand($min, $max),'sha256');
	}
}