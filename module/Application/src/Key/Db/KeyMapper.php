<?php

namespace Key\Db;

use Application\Db\ApiPDO;

use Application\Mapper\GuiMapper,
	Application\Api\ApiException as Exception;


class KeyMapper extends GuiMapper {

	protected $setClass = '\Key\Db\KeyContainer';

	public function findKeyByName($name, $owner = null) {
		
		$sql = "SELECT * FROM `gui_webapi_keys` WHERE `name`=:name";
		if ($owner) {
			$sql .= " AND `username`=:username";
		}
		$params[':name'] = $name;
		if ($owner) {
			$params[':username'] = $owner;
		}
		$key = $this->select($sql, $params);

		if ($key) {
			return $key;
		}
		return false;
	}

	public function findKeyByKey($key, $owner = null) {
		
		$sql = "SELECT * FROM `gui_webapi_keys` WHERE `key`=:key";
		if ($owner) {
			$sql .= " AND `username`=:username";
		}
		$params[':key'] = $key;
		if ($owner) {
			$params[':username'] = $owner;
		}
		$key = $this->selectOne($sql, $params);
		return $key;
	}

	public function findKeyByUsername($username, $owner = null) {
		if ($owner) {
			$username = $owner;
		}
		$keys = $this->select("SELECT * FROM `gui_webapi_keys` where `username`=:username", array(':username' => $username));
	    return (!empty($keys)?$keys:array());
	}

	public function findAllKeys($owner = null) {
		$sql = "SELECT * FROM `gui_webapi_keys`";
		if ($owner) {
			$sql .= " WHERE `username`=:username";
		}
		$params = array();
		if ($owner) {
			$params[':username'] = $owner;
		}
		$keys = $this->select($sql, $params);

		if (!empty($keys)) {
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
			$instance = ApiPDO::getInstance('gui')->prepare("INSERT INTO `gui_webapi_keys` (`key`, `name`, `username`, `hash`, `creation_time`) VALUES (:key, :name, :username, :hash, :creation_time)");
			$result = $instance->execute(array_combine(array(':key', ':name', ':username', ':hash', ':creation_time'), $keyFields));
		} catch(\PDOException $e) {
			die($e->getMessage());
			throw new Exception($e->getMessage(), $e->getCode());
		}
		return array_unshift($keyFields, ApiPDO::getInstance('gui')->lastInsertId());
	}

	public function deleteKeysById(array $ids) {
		return $this->delete('DELETE FROM `gui_webapi_keys` WHERE ' . $this->getSqlInStatement('id', $ids));
	}

	private function generateHash() {
		list($usec, $sec) = explode(' ', microtime());
		$seed = (float) $sec + ((float) $usec * 100000);
		mt_srand($seed);
		$min = 0;
		$max = mt_getrandmax();
		return \Application\Security::hash(mt_rand($min, $max), 'sha256');
	}
}