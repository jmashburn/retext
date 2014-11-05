<?php

namespace Event\Db;

use Application\Mapper\AbstractMapper,
	Application\Api\ApiException as Exception;

use Application\Exception as AppException;


class EventMapper extends AbstractMapper  {

	public $context = 'gui';

	public $setClass  = 'Event\Db\EventContainer';

	public function findEventByKey($key, $owner = null) {
		
		$sql = "SELECT * FROM `server_events` WHERE `key`=:key";
		if ($owner) {
			$sql .= " AND `name`=:name";
		}
		$instance = ApiPDO::getInstance('gui')->prepare($sql);
		$instance->bindValue(':key', $key, \PDO::PARAM_STR);
		if ($owner) {
			$instance->bindValue(":username", $owner, \PDO::PARAM_STR);
		}
		$instance->execute();
	    $user = $instance->fetch(\PDO::FETCH_ASSOC);

		if (is_array($user)) {
			return $user;
		}
		return false;
	}

	public function findEventByEvent($type, $owner = null) {
		$sql = "SELECT * FROM `server_events` WHERE `event`=:event";
		if ($owner) {
			$sql .= " AND `name`=:name";
		}
		$instance = ApiPDO::getInstance('gui')->prepare($sql);
		$instance->bindValue(':event', $event, \PDO::PARAM_STR);
		if ($owner) {
			$instance->bindValue(":username", $owner, \PDO::PARAM_STR);
		}
		$instance->execute();
	    $user = $instance->fetch(\PDO::FETCH_ASSOC);

		if (is_array($user)) {
			return $user;
		}
		return false;
	}

	public function findAllEvents($owner = null) {
		$sql = "SELECT * FROM `server_event_actions`";
		if ($owner) {
			$sql .= " WHERE `name`=:name";
		}
		$instance = ApiPDO::getInstance('gui')->prepare($sql);
		if ($owner) {
			$instance->bindValue(':name', $owner, \PDO::PARAM_STR);
		}
		$instance->execute();
	    $events = $instance->fetchAll(\PDO::FETCH_ASSOC);

		if (is_array($events)) {
			return $events;
		}
		return array();
	}

	public function findEventActionByEvent($event = null, $owner = null) {
		$sql = "SELECT * FROM `server_event_actions` WHERE `event`=:event";
		if ($owner) {
			$sql .= " AND `name`=:name";
		}
		$instance = ApiPDO::getInstance('gui')->prepare($sql);
		$instance->bindValue(':event', $event, \PDO::PARAM_STR);
		if ($owner) {
			$instance->bindValue(":name", $owner, \PDO::PARAM_STR);
		}
		$instance->execute();
	    $user = $instance->fetch(\PDO::FETCH_ASSOC);

		if (is_array($user)) {
			return $user;
		}
		return false;
	}

	public function addEventAction($event, $name, $email, $custom_action) {
		$eventFields = array(
			'name' => $name,
			'event' => $event,
			'email' => $email,
			'custom_action' => $custom_action,
		);

		try {
			$instance = ApiPDO::getInstance('gui')->prepare("INSERT INTO `server_event_actions` (`name`, `event`, `email`, `custom_action`) 
				VALUES (:name, :event, :email, :custom_action)");
			$result = $instance->execute(array_combine(array(':name', ':event', ':email', ':custom_action'), $eventFields));
		} catch(\PDOException $e) {
			throw new Exception($e->getMessage());
		}
		return array_unshift($eventFields, ApiPDO::getInstance('gui')->lastInsertId());
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

	public function deleteEventAction($event, $owner) {
		$sql = "DELETE FROM `server_event_actions` WHERE `event`=:event AND `name`=:name";
		$instance = ApiPDO::getInstance('gui')->prepare($sql);
		$instance->bindValue(':event', $event, \PDO::PARAM_STR);
		$instance->bindValue(":name", $owner, \PDO::PARAM_STR);
		return $instance->execute();
	}

}