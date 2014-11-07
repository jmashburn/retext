<?php

namespace Retext\Message\Db;

use Application\Db\ApiPDO;

use Application\Mapper\AbstractMapper,
	Application\Api\ApiException as Exception;


class MessageMapper extends AbstractMapper {

	protected $setClass = 'Retext\Message\Db\MessageContainer';

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

	public function findMessageByKey($key, $owner = null) {
		
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

	public function findMessageByCode($username, $owner = null) {
		if ($owner) {
			$username = $owner;
		}
		$keys = $this->select("SELECT * FROM `retext_messages` where `username`=:username", array(':username' => $username));
	    return (!empty($keys)?$keys:array());
	}

	public function findAllMessages($owner = null, $params = array()) {
		$sql = "SELECT * FROM `retext_messages`";
		if ($owner) {
			$sql .= " WHERE `username`=:username";
		}
		if ($owner) {
			$params[':username'] = $owner;
		}
		if (!empty($params['sql'])) {
			$sql .= $params['sql'];
			unset($params['sql']);
		}
		$messages = $this->select($sql, $params);
		if (!empty($messages)) {
			return $messages;
		}
		return array();
	}

	public function addMessage($params, $username = "") {
		$hash = $this->generateHash();
		$keyFields = array(
			'key' => uniqid('rtm_'),
			'code' => $params['code'],
			'message_received' => $params['message_received'],
			'message_sent' => $params['message_sent'],
			'status'		=> 'pending',
			'creation_time' => date('Y-m-d H:i:s', time()),
		);

		try {
			$instance = ApiPDO::getInstance($this->context)->prepare("INSERT INTO `retext_messages` (`key`, `code`, `message_received`, `message_sent`, `status`, `creation_time`) VALUES (:key, :code, :message_received, :message_sent, :status, :creation_time)");
			$result = $instance->execute(array_combine(array(':key', ':code', ':message_received', ':message_sent', ':status', ':creation_time'), $keyFields));
		} catch(\PDOException $e) {
			throw new Exception($e->getMessage(), $e->getCode());
		}
		return new \Application\Set(array(array_merge(array('id' => $result), $keyFields)), $this->setClass);
	}

	public function deleteMessagesById(array $ids) {
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