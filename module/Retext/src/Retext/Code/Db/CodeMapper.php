<?php

namespace Retext\Code\Db;

use Application\Db\ApiPDO;

use Application\Mapper\AbstractMapper,
	Application\Api\ApiException as Exception;


class CodeMapper extends AbstractMapper {

	protected $setClass = 'Retext\Code\Db\CodeContainer';

	public $context = 'retext';

	public function findRetextByName($name, $owner = null) {
		
		$sql = "SELECT * FROM `retext_codes` WHERE `name`=:name";
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

	public function findCodeByKey($key, $owner = null) {
		
		$sql = "SELECT * FROM `retext_codes` WHERE `key`=:key";
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

	public function findCodeByCode($code, $owner = null) {
		$sql = "SELECT * FROM `retext_codes` WHERE `code`=:code";
		if ($owner) {
			#$sql .= " AND `username`=:username";
		}
		$params[':code'] = $code;
		if ($owner) {
			#$params[':username'] = $owner;
		}
		try {
	    	$code = $this->selectOne($sql, $params);
	    } catch (\Application\Exception $e) {
	    	throw new Exception($e->getMessage(), $e->getCode());
	    }
	    return $code;
	}

	public function findAllCodes($owner = null) {
		$sql = "SELECT * FROM `retext_codes`";
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

	public function addCode($params, $username) {
		$hash = $this->generateHash();
		$keyFields = array(
			'key' => uniqid('rtc_'),
			'code' => strtoupper($params['code']),
			'message' => $params['message'],
			'mode' => 'LIVE',
			'creation_time' => time(),
		);

		try {
			$instance = ApiPDO::getInstance($this->context)->prepare("INSERT INTO `retext_codes` (`key`, `code`, `message`, `mode`, `creation_time`) VALUES (:key, :code, :message, :mode, :creation_time)");
			$result = $instance->execute(array_combine(array(':key', ':code', ':message', ':mode', ':creation_time'), $keyFields));
		} catch(\PDOException $e) {
			throw new Exception($e->getMessage(), $e->getCode());
		}
		return new \Application\Set(array(array_merge(array('id' => $result), $keyFields)), $this->setClass);
	}

	public function deleteCodesById(array $ids) {
		return $this->delete('DELETE FROM `retext_codes` WHERE ' . $this->getSqlInStatement('id', $ids));
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