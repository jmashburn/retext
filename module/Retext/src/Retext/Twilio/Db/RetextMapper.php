<?php

namespace Retext\Db;

use Application\Db\ApiPDO;

use Application\Mapper\AbstractMapper,
	Application\Api\ApiException as Exception;


class RetextMapper extends AbstractMapper {

	protected $setClass = '\Retext\Db\RetextContainer';

	public $context = 'retext';

	public function findRetextByName($name, $owner = null) {
		
		$sql = "SELECT * FROM `retext_messages` WHERE `name`=:name";
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

	public function findRetextByKey($key, $owner = null) {
		
		$sql = "SELECT * FROM `retext_messages` WHERE `key`=:key";
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

	public function findRetextByCode($username, $owner = null) {
		if ($owner) {
			$username = $owner;
		}
		$keys = $this->select("SELECT * FROM `retext_messages` where `username`=:username", array(':username' => $username));
	    return (!empty($keys)?$keys:array());
	}

	public function findAllRetexts($owner = null) {
		$sql = "SELECT * FROM `retext_messages`";
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

	public function addRetext($name, $username) {
		$hash = $this->generateHash();
		$keyFields = array(
			'key' => uniqid('wk_'),
			'name' => $name,
			'username' => $username,
			'hash' => $hash,
			'creation_time' => date('Y-m-d H:i:s', time()),
		);

		try {
			$instance = ApiPDO::getInstance('gui')->prepare("INSERT INTO `retext_messages` (`key`, `name`, `username`, `hash`, `creation_time`) VALUES (:key, :name, :username, :hash, :creation_time)");
			$result = $instance->execute(array_combine(array(':key', ':name', ':username', ':hash', ':creation_time'), $keyFields));
		} catch(\PDOException $e) {
			die($e->getMessage());
			throw new Exception($e->getMessage(), $e->getCode());
		}
		return array_unshift($keyFields, ApiPDO::getInstance('gui')->lastInsertId());
	}

	public function deleteRetextsById(array $ids) {
		return $this->delete('DELETE FROM `retext_messages` WHERE ' . $this->getSqlInStatement('id', $ids));
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