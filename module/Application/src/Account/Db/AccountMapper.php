<?php

namespace Account\Db;

use Application\Db\ApiPDO; /// TODO Remove reference to this

use Application\Mapper\AbstractMapper,
	\Application\Api\ApiException as Exception;


class AccountMapper extends AbstractMapper  {

	public $context = 'gui';

	protected $setClass = '\Account\Db\AccountContainer';

	public function setAccount($username, $password, $role = null) {
		try {
			$updateFields = array('password' => \Application\Security::hash($password, 'sha256'));
			if ($role) {
				$updateFields['role'] = $role;
			}
			$tmp = array();
			foreach (array_keys($updateFields) as $value) {
				$tmp[] = $value."=?";
			}
			$updateFields['name'] = $username;

			$instance = ApiPDO::getInstance('gui')->prepare("UPDATE `gui_users` SET " . implode(", " , $tmp) . " WHERE `name`=?");
			$instance->execute(array_values($updateFields));
			if (!$instance->rowCount()) {
				$instance = ApiPDO::getInstance('gui')->prepare("INSERT INTO `gui_users` (`name`, `password`, `role`) VALUES (:name, :password, :role)");
				$instance->execute(array(':name' => $updateFields['name'], ':password' => $updateFields['password'], ':role' => $role));
				if (!$instance->rowCount()) {
					throw new Exception(__('Could not write to the database: users table. Please check permissions'));
				}
			}
			return true;
	    } catch (\Application\Exception $e) {
	    	throw new Exception($e->getMessage(), $e->getCode());
	    }
	}

	public function findAccountByKey($key, $owner = null) {
		if ($owner) {
			$sql .= " AND `name`=:name";
		}
		$params[':key'] = $key;
		if ($owner) {
			$params[':name'] = $owner;
		}
	    $hook = $this->selectOne('SELECT * FROM `gui_users` WHERE `key`=:key', $params);
	    return $hook;
	}

	public function findAccountByName($name) {
		try {
			$user = $this->selectOne("SELECT * FROM `gui_users` where `name`=:name", array(':name' => $name));
			return $user;
	    } catch (\Application\Exception $e) {
	    	throw new Exception($e->getMessage(), $e->getCode());
	    }
	}

	public function addAccount($params) {
		$userFields = array(
			'key' => uniqid('u_'),
			'name' => $params['name'],
			'password' => \Application\Security::hash($params['password'], 'sha256'),
			'email' => $params['email'],
			'role' => $params['role'],
		);

		try {
			$result = $this->insert("INSERT INTO `gui_users` (`key`, `name`, `password`, `email`, `role`) VALUES (:key, :name, :password, :email, :role)", 
				array_combine(array(':key', ':name', ':password', ':email', ':role'), $userFields));
				//return new \Application\Set(array_merge(array('id' => $result), $userFields));
				return new \Application\Set(array(array_merge(array('id' => $result), $userFields)), $this->setClass);
	    } catch (\Application\Exception $e) {
	    	throw new Exception($e->getMessage(), $e->getCode());
	    }
	}

	public function findAllAccounts($owner = null) {
		$sql = "SELECT * FROM `gui_users`";
		if ($owner) {
			$sql .= " WHERE `name`=:name";
		}
		$params = array();
		if ($owner) {
			$params[':name'] = $owner;
		}
		$users = $this->select($sql, $params);
		if (!empty($users)) {
			return $users;
		}
		return array();
	}

	public function deleteAccountsById(array $ids) {
		try {
			return $this->delete('DELETE FROM `gui_users` WHERE ' . $this->getSqlInStatement('id', $ids));
		} catch (\Application\Exception $e) {	
			throw new Exception ($e->getMessage(), $e->getCode());
		}
	}
}