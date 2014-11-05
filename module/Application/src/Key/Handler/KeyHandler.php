<?php

namespace Key\Handler;

use Db\Mapper\ApiMapper; 

use Application\Api\Handler\JsonHandler,
    Application\Api\ApiException as Exception;

class KeyHandler extends JsonHandler {

	protected $mapperClass = "\Key\Db\KeyMapper";

	protected $authAdapter = 'Application\Authentication\Authentication';

	const ACTION_ENABLE 	= 'enable';
	const ACTION_DISABLE 	= 'disable';

	//  List All/Single Key(s)
	public function get($key = null) {
		try {
			if (is_string($key) && $key == self::ACTION_DISABLE) {
				$keys = $this->disableKey();
			} else {
				// Check if User is allowed to List all Keys
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
				if ($key) {
					$keys = $this->getMapper()->findKeyByKey($key, $owner);
					if (empty($keys[0])) {
						throw new Exception(__('Key: "%s" does not exist. Please try again.', array($key)), Exception::WEBAPI_VALUE_ADD_FAILED);
					}
				} else {
					$keys = $this->getMapper()->findAllKeys($owner);
				}
			}
			return $this->display('key/list.json.phtml', compact('keys'));
		} catch (Exception $e) {
			return $this->display('exception/exception.json.phtml', $e);
		}
	}

	// Add/Update/Enable Key(s)
	public function post($key = null) {
		try {
			if (!$this->hasIdentity()) {
				throw new Exception(__('Authentication error. Please login.'), Exception::AUTH_ERROR);
			}

			$identity = $this->getIdentity();
			if (!$identity->isValid()) {
 				throw new Exception(__('Authentication error. Please login.'), Exception::AUTH_ERROR);
			}
			$params = $this->getParameters();
			if (is_string($key) && $key == self::ACTION_ENABLE) {
				#$this->validateMandatoryParameters($params, array('password'));
				#$value = $this->validateString($params['password'], 'password');
				$keys = $this->enableKey();
				\Event::fire('key.enabled', compact('key'));
			} else {
				$this->validateMandatoryParameters($params, array('name'));
				$this->validateString($params['name'], 'name');
				$owner = null;
				try {
					$key = $this->enableKey();
					\Event::fire('key.created', compact('key'));
				} catch (Exception $e) {
					throw new Exception($e->getMessage(), $e->getCode());
				}
				if (!$this->getAcl()->isAllowed($identity->getRole(), $this->resourceRoute, self::PERMISSION_LIST_ALL)) {
					$owner = $identity->getUsername();
				}
				$keys = $this->getMapper()->findAllKeys($owner);
			}
			return $this->display('key/list.json.phtml', compact('keys'));
		} catch (Exception $e) {
			return $this->display('exception/exception.json.phtml', $e);
		}
	}

	// Remove the Key
	public function delete($key = null) {
		try {
			$params = $this->getParameters(array('name' => $key));
			if (!$params['name']) {
				$this->validateMandatoryParameters($params, array('ids'));
				$this->validateArray($params['ids'], 'ids');
			}

			try {
				$keys = $this->disableKey($key);
			} catch (Exception $e) {
				throw $e;
			}
			return $this->display('key/delete.json.phtml', compact('keys'));
		} catch (Exception $e) {
			return $this->display('exception/exception.json.phtml', $e);
		}
	}

	private function enableKey() {
		$params = $this->getParameters(array('username' => false, 'name' => false));
		if (!$this->hasIdentity()) {
			// Not Logged in.  Require a name and password
			if (!$params['username']) {
 				throw new Exception(__('No authentication data found. Please login.'), Exception::AUTH_ERROR);
			}
			$this->validateMandatoryParameters($params, array('name'));
			$parameters = array('username' => $params['username'], 'password' => $params['password']);
			$identity = $this->getAuthAdapter()->authenticateOnly(array_intersect($params, $parameters));
			if (! $identity->isValid()) {
				// New Audit/Log
				throw new Exception(__('The current password for user "%s" is incorrect', array($params['username'])), Exception::WRONG_PASSWORD);
			}
		} else {
			$identity = $this->getIdentity();
			if (!$identity->isValid()) {
 				throw new Exception(__('Authentication error. Please login.'), Exception::AUTH_ERROR);
			}
			$params = $this->getParameters(array('username' => $identity->getUsername(), 'name' => $identity->getUsername()));
			$this->validateMandatoryParameters($params, array('username'));
			$this->validateString($params['username'], 'username');
		}

		if ($identity->getUsername() !== $params['username']) {

			if (!$this->getAcl()->isAllowed($identity->getRole(), $this->resourceRoute, self::PERMISSION_CREATE_ALL)) {
				// Log/Audit
				throw new Exception(__('Unable to create key name: "%s" for "%s". Please check permissions.', array($params['name'], $params['username'])), Exception::ACL_PERMISSION_DENIED);
			}

			// Check Username is valid.
			$account = $this->getMapper('Account\Db\AccountMapper')->findAccountByName($params['username']);
			if (empty($account[0]['id'])) {
				throw new Exception(__('Account: "%s" does not exist. Please try again.', array($params['username'])), Exception::WEBAPI_VALUE_ADD_FAILED);
			}
		}

		$owner = null;
		$key = $this->getMapper()->findKeyByName($params['name']);

		if (!isset($key[0])) {
			if (!$this->getMapper()->addKey($params['name'], $params['username'])) {
				// Audit/Log
				throw new Exception(__('Could not write to database: keys table. Please check permissions'), Exception::PERMISSIONS);
			}
			if (!$this->getAcl()->isAllowed($identity->getRole(), $this->resourceRoute, self::PERMISSION_LIST_ALL)) {
				$owner = $identity->getUsername();
			}
			return $this->getMapper()->findAllKeys($owner);
		} else {
			throw new Exception(__('The key name "%s" already exists.', array($params['name'])), Exception::WEBAPI_KEY_ADD_FAILED);
		}
	}

	private function disableKey($key = null) {
		$params = $this->getParameters(array('name' => $key));
		if ($this->hasIdentity()) {
			$identity = $this->getIdentity();
			if (!$this->getIdentity()->isValid()) {
 				throw new Exception(__('Authentication error. Please login.'), Exception::AUTH_ERROR);
			}

			if (empty($params['ids'])) {
				$params = $this->getParameters(array('name' => $identity->getUsername()));
			}

			$owner = null;
			$deletes = array();
			$keys = array();

			if (!$this->getAcl()->isAllowed($this->getIdentity()->getRole(), $this->resourceRoute, self::PERMISSION_DELETE_ALL)) {
				$owner = $this->getIdentity()->getUsername();
			}

			if ($params['name'] && !is_null($key)) {
				// Logged in.. Check if you have perms to delete 
				$keys = $this->getMapper()->findKeyByKey($key, $owner);

				if (!isset($keys[0])) {
					throw new Exception(__('The key for user "%s" does not exist', array($params['name'])));
				}

				if ($keys[0]['name'] == 'apiuser') {
					throw new Exception(__('Failed to remove user: "%s"', array($keys[0]['name'])), Exception::WEBAPI_KEY_REMOVE_FAILED);
				}

				$deletes['ids'][] = $keys[0]['id'];
			} elseif (!empty($params['ids'])) {
				$keys = $this->getMapper()->findAllKeys($owner);
				foreach ($keys->toArray() as $_key => $key) {
					if (!in_array($key['key'], $params['ids'])) {
						unset($keys[$_key]);
					} else {
						if ($key['name'] == 'apiuser') {
							throw new Exception(__('Failed to remove user: "%s"', array($key['name'])), Exception::WEBAPI_KEY_REMOVE_FAILED);
						}
						$deletes['ids'][] = $key['id'];
					}
				}
			}

			if (!empty($deletes['ids'])) {
				$this->getMapper()->deleteKeysById($deletes['ids']);
				return $keys;
			} else {
				throw new Exception(__('No Keys were deleted. Please check permissions'), Exception::AUTH_ERROR);
			}
		} else {
			throw new Exception(__('No authentication data found. Please login.'), Exception::AUTH_ERROR);
		}
	}
}
