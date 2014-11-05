<?php

namespace Event\Handler;

use Db\Mapper\ApiMapper; 

use Application\Api\ApiException as Exception;

use Event;

class EventHandler extends   {

	protected $mapperClass = "\Event\Db\EventMapper";

	protected $authAdapter = 'Application\Authentication\Authentication';

	const ACTION_SUBSCRIBE 		= 'subscribe';
	const ACTION_UNSUBSCRIBE 	= 'unsubscribe';

	//  List All/Single Events(s)
	public function get($event = null) {
		try {
			// Check if User is allowed to List all Events
			$owner = null;
			if ($this->getAcl()->hasResource($this->resourceRoute)) {
				$identity = $this->getIdentity();
	            if (!$identity->isValid()) {
            		throw new Exception(__('Authentication error. Please login.'), Exception::AUTH_ERROR);
        		}

				if (!$this->getAcl()->isAllowed($identity->getRole(), $this->resourceRoute, self::PERMISSION_LIST_ALL)) {
					$owner = $identity->getUsername();
				}
			}
			if ($event) {
				$events = $this->getMapper()->findEventByKey($event, $owner);
				$events = array($events);
			} else {
				$events = $this->getMapper()->findAllEvents($owner);
			}
			return $this->display('event/list.json.phtml', compact('events'));
		} catch (Exception $e) {
			return $this->display('exception/exception.json.phtml', $e);
		}
	}

	// Subscribe/Unsubscribe Events
	public function post($key = null) {
		try {
			$params = $this->getParameters();
			$this->validateMandatoryParameters($params, array('event'));
			$this->validateString($params['event'], 'event');

			if (is_string($key) && $key == self::ACTION_SUBSCRIBE) {
				$this->validateMandatoryParameters($params, array('email', 'custom_action'));
				$this->validateString($params['email'], 'email');
				$this->validateFilter($params['email'], 'email', FILTER_VALIDATE_EMAIL);

				$this->validateString($params['custom_action'], 'custom_action');
				$this->validateFilter($params['custom_action'], 'custom_action', FILTER_VALIDATE_URL);

				$this->subscribeEvent();
			} elseif (is_string($key) && $key == self::ACTION_UNSUBSCRIBE) {
				$event = $this->unsubscribeEvent();
			} else {
				throw new Exception(__('You must either subscribe or unsubscribe to an event'), Exception::WEBAPI_VALUE_ADD_FAILED);
			}
			$events = $this->getMapper()->findAllEvents();
			return $this->display('event/list.json.phtml', compact('events'));
		} catch (Exception $e) {
			return $this->display('exception/exception.json.phtml', $e);
		}
	}

	private function subscribeEvent() {
		$params = $this->getParameters(array('username' => false, 'event' => false));

		if (!$this->hasIdentity()) {
			// Not Logged in.  Require a name and password
			if (!$params['username']) {
 				throw new Exception(__('No authentication data found. Please login.'), Exception::AUTH_ERROR);
			}
			$this->validateMandatoryParameters($params, array('password'));
			$parameters = array('username' => $params['username'], 'password' => $params['password']);
			$identity = $this->getAuthAdapter()->authenticateOnly(array_intersect($params, $parameters));
			if (! $identity->isValid()) {
				// New Audit/Log
				throw new Exception(__('The current password for user "%s" is incorrect', array($params['username'])), Exception::WRONG_PASSWORD);
			}
		} else {
			// Logged In 
			$identity = $this->getIdentity();
			if (!$identity->isValid()) {
 				throw new Exception(__('Authentication error. Please login.'), Exception::AUTH_ERROR);
			}
			$params = $this->getParameters(array('username' => $identity->getUsername()));
			$this->validateMandatoryParameters($params, array('username'));
			$this->validateString($params['username'], 'username');
		}

		// Made it here
		if ($identity->getUsername() !== $params['username']) {
			if (!$this->getAcl()->isAllowed($identity->getRole(), $this->resourceRoute, self::PERMISSION_CREATE_ALL)) {
				// Log/Audit
				throw new Exception(__('Unable to create event name: "%s" for "%s". Please check permissions.', array($params['name'], $params['username'])));
			}

			// Check Username is valid.
			if (!$this->getMapper('User\Mapper\Mapper')->findUserByName($params['username'])) {
				throw new Exception(__('User: "%s" does not exist. Please try again.', array($params['username'])), Exception::WEBAPI_VALUE_ADD_FAILED);
			}
		}

		// Get Subscribed Event of username
		$event_action = $this->getMapper()->findEventActionByEvent($params['event'], $params['username']);
		if (empty($event_action)) {
			if (!$this->getMapper()->addEventAction($params['event'], $params['username'], $params['email'], $params['custom_action'])) {
				// Audit/Log
				throw new Exception(__('Could not write to database: event_action table. Please check permissions'), Exception::PERMISSIONS);
			}
			$event_action = $this->getMapper()->findEventActionByEvent($params['event'], $params['username']);
		} else {
			throw new Exception(__('The event name "%s" already exists.', array($params['event'])), Exception::WEBAPI_VALUE_ADD_FAILED);
		}
		return $event_action;
	}

	private function unsubscribeEvent($event = null) {
		$params = $this->getParameters(array('event' => $event, 'username' => false));

		if (!$this->hasIdentity()) {
			// Not Logged in.  Require a name and password
			if (!$params['username']) {
 				throw new Exception(__('No authentication data found. Please login.'), Exception::AUTH_ERROR);
			}
			$this->validateMandatoryParameters($params, array('password'));
			$parameters = array('username' => $params['username'], 'password' => $params['password']);
			$identity = $this->getAuthAdapter()->authenticateOnly(array_intersect($params, $parameters));
			if (! $identity->isValid()) {
				// New Audit/Log
				throw new Exception(__('The current password for user "%s" is incorrect', array($params['username'])), Exception::WRONG_PASSWORD);
			}
		} else {
			// Logged In 
			$identity = $this->getIdentity();
			if (!$identity->isValid()) {
 				throw new Exception(__('Authentication error. Please login.'), Exception::AUTH_ERROR);
			}
			$params = $this->getParameters(array('username' => $identity->getUsername()));
			$this->validateMandatoryParameters($params, array('username'));
			$this->validateString($params['username'], 'username');
		}

				// Made it here
		if ($identity->getUsername() !== $params['username']) {
			if (!$this->getAcl()->isAllowed($identity->getRole(), $this->resourceRoute, self::PERMISSION_DELETE_ALL)) {
				// Log/Audit
				throw new Exception(__('Unable to delete event name: "%s" for "%s". Please check permissions.', array($params['event'], $params['username'])), Exception::WEBAPI_VALUE_REMOVE_FAILED);
			}

			// Check Username is valid.
			if (!$this->getMapper('User\Mapper\Mapper')->findUserByName($params['username'])) {
				throw new Exception(__('User: "%s" does not exist. Please try again.', array($params['username'])), Exception::WEBAPI_VALUE_ADD_FAILED);
			}
		}

		// Get Subscribed Event of username
		$event_action = $this->getMapper()->findEventActionByEvent($params['event'], $params['username']);
		if (!empty($event_action)) {
			if (!$this->getMapper()->deleteEventAction($params['event'], $params['username'])) {
				// Audit/Log
				throw new Exception(__('Could not write to database: event_action table. Please check permissions'), Exception::PERMISSIONS);
			}
			$event_action = $this->getMapper()->findEventActionByEvent($params['event'], $params['username']);
		} else {
			throw new Exception(__('No Events were unsubscribed. Please check permissions'), Exception::WEBAPI_VALUE_REMOVE_FAILED);
		}
		return $event_action;
	}

	public function apiEventJson($event = array()) {
		$apiEventArray = array(
			'event'	=> $event['event'],
			//'data'	=> 	json_decode($event['data']),
		);
		return json_encode($apiEventArray);
	}
}