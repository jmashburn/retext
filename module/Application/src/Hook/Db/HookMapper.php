<?php

namespace Hook\Db;

use Application\Mapper\AbstractMapper,
	Application\Api\ApiException as Exception;

use Application\Exception as AppException;


class HookMapper extends AbstractMapper  {

	public $context = 'gui';

	public $setClass  = 'Hook\Db\HookContainer';

	public function findHookByKey($key, $owner = null) {
		
		$sql = "SELECT * FROM `gui_hooks` WHERE `key`=:key";
		if ($owner) {
			$sql .= " AND `username`=:username";
		}
		$params[':key'] = $key;
		if ($owner) {
			$params[':username'] = $owner;
		}
	    $hook = $this->selectOne('SELECT * FROM `gui_hooks` WHERE `key`=:key', $params);
	    return $hook;
	}

	public function findHookByEndPoint($end_point, $owner = null) {
		$sql = "SELECT * FROM `gui_hooks` WHERE `end_point`=:end_point";
		if ($owner) {
			$sql .= " AND `username`=:username";
		}
		$params[':end_point'] = $end_point;
		if ($owner) {
			$params[':username'] = $owner;
		}
		try {
	    	$hook = $this->selectOne($sql, $params);
	    } catch (\Application\Exception $e) {
	    	throw new Exception($e->getMessage(), $e->getCode());
	    }
	    return $hook;
	}

	public function findHookByUsername($username, $owner = null) {
		if ($owner) {
			$username = $owner;
		}
		try {
			$hook = $this->selectOne('SELECT * FROM `gui_hooks` where `username`=:username', array(':username' => $username));
		} catch (\Application\Exception $e) {
			throw new Exception($e->getMessage(), $e->getCode());
		}
		return $hook;
	}

	public function findAllHooks($owner = null) {
		$sql = "SELECT * FROM `gui_hooks`";
		if ($owner) {
			$sql .= " WHERE `username`=:username";
		}
		$params = array();
		if ($owner) {
			$params[':username'] = $owner;
		}
		try {
		    $hooks = $this->select($sql, $params);
		} catch (\Application\Exception $e) {
			throw new Exception($e->getMessage(), $e->getCode());
		}
		if (!empty($hooks)) {
			return $hooks;
		}
		return array();
	}

	public function addHook($params) {
		$keyFields = array(
			'key' => uniqid('wh_'),
			'username' => $params['username'],
			'end_point' => $params['end_point'],
			'mode' => $params['mode'],
			'creation_time' => date('Y-m-d H:i:s', time()),
		);
		try {
			$result = $this->insert("INSERT INTO `gui_hooks` (`key`, `username`, `end_point`, `mode`, `creation_time`) VALUES (:key, :username, :end_point, :mode, :creation_time)", 
				array_combine(array(':key', ':username', ':end_point', ':mode', ':creation_time'), $keyFields));
			return new \Application\Set(array(array_merge(array('id' => $result), $keyFields)), $this->setClass);
		} catch(\Application\Exception $e) {
			throw new Exception($e->getMessage(), $e->getCode());
		}
	}


	public function deleteHooksById(array $ids) {
		try {
			return $this->delete('DELETE FROM `gui_hooks` WHERE ' . $this->getSqlInStatement('id', $ids));
		} catch (\Application\Exception $e) {	
			throw new Exception ($e->getMessage(), $e->getCode());
		}
	}

}