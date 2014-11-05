<?php

namespace Hook\Handler;

use Db\Mapper\ApiMapper; 

use Application\Api\Handler\JsonHandler,
    Application\Api\ApiException as Exception;

class HookHandler extends JsonHandler {

	protected $mapperClass = "\Hook\Db\HookMapper";

	protected $authAdapter = 'Application\Authentication\Authentication';

	//  List All/Single Hooks(s)
	public function get($hook = null) {
		try {
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

			if ($hook) {
				$hooks = $this->getMapper()->findHookByKey($hook, $owner);
				if (empty($hooks[0]['id'])) {
					throw new Exception(__('Hook: "%s" does not exist. Please try again.', array($hook)), Exception::WEBAPI_VALUE_ADD_FAILED);
				}
			} else {
				$hooks = $this->getMapper()->findAllHooks($owner);
			}
			\Event::fire('hook.found', compact('hooks'));
			return $this->display('hook/list.json.phtml', compact('hooks'));
		} catch (Exception $e) {
			return $this->display('exception/exception.json.phtml', $e);
		}
	}

	// Add/Update Hook(s)
	public function post($hook = null) {
		try {
			if (!$this->hasIdentity()) {
				throw new Exception(__('Authentication error. Please login.'), Exception::AUTH_ERROR);
			}

			$params = $this->getParameters();
			$this->validateMandatoryParameters($params, array('end_point', 'mode'));
			$this->validateString($params['end_point'], 'end_point');
			$this->validateString($params['mode'], 'mode');

			$this->validateString($params['end_point'], 'end_point');
			$this->validateUri($params['end_point'], 'end_point');
			//$this->validateFilter($params['end_point'], 'end_point', FILTER_VALIDATE_URL);

			$identity = $this->getIdentity();
			if (!$identity->isValid()) {
 				throw new Exception(__('Authentication error. Please login.'), Exception::AUTH_ERROR);
			}
			$params = $this->getParameters(array('username' => $identity->getUsername()));

			if ($identity->getUsername() !== $params['username']) {
				if (!$this->getAcl()->isAllowed($identity->getRole(), $this->resourceRoute, self::PERMISSION_CREATE_ALL)) {
					// Log/Audit
					throw new Exception(__('Unable to create hook: "%s" for "%s". Please check permissions.', array($params['end_point'])));
				}

				// Check Username is valid.
				$account = $this->getMapper('Account\Db\AccountMapper')->findAccountByName($params['username']);
				if (empty($account[0]['id'])) {
					throw new Exception(__('Account: "%s" does not exist. Please try again.', array($params['username'])), Exception::WEBAPI_VALUE_ADD_FAILED);
				}
			}

			$owner = null;
			$hook = $this->getMapper()->findHookByEndPoint($params['end_point'], $params['username']);
			if (empty($hook['id'])) {
				$hooks = $this->getMapper()->addHook($params);
				if (empty($hooks[0]['id'])) {
					// Audit/Log
					throw new Exception(__('Could not write to database: hooks table. Please check permissions'), Exception::PERMISSIONS);
				}
				\Event::fire('hook.created', compact('hooks'));
				// if (!$this->getAcl()->isAllowed($identity->getRole(), $this->resourceRoute, self::PERMISSION_LIST_ALL)) {
				// 	$owner = $identity->getUsername();
				// }
				// $hooks = $this->getMapper()->findAllHooks($owner);

				#$hooks = $this->getMapper()->findHookByEndPoint($params['end_point'], $params['username']);
				return $this->display('hook/list.json.phtml', compact('hooks'));
			} else {
				throw new Exception(__('The hook end_point "%s" already exists.', array($params['end_point'])), Exception::WEBAPI_KEY_ADD_FAILED);
			}
		} catch (Exception $e) {
			return $this->display('exception/exception.json.phtml', $e);
		}
	}

	// Remove the Hook
	public function delete($key = null) {
		try {
			$params = $this->getParameters(array('key' => $key));
			$owner = null;
			if (!$this->hasIdentity()) {
				throw new Exception(__('Authentication error. Please login.'), Exception::AUTH_ERROR);
			}
			
			$identity = $this->getIdentity();
			if (!$identity->isValid()) {
				throw new Exception(__('Authentication error. Please login.'), Exception::AUTH_ERROR);
			}

			// Either We have ids or disabling a key
			if (!$this->getAcl()->isAllowed($this->getIdentity()->getRole(), $this->resourceRoute, self::PERMISSION_DELETE_ALL)) {
				$owner = $this->getIdentity()->getUsername();
			}

			$deletes = array();
			$hooks = array();
			if (!empty($key)) {
				$hooks = $this->getMapper()->findHookByKey($key, $owner);
				if (!empty($hooks[0]['id'])) {
					$deletes['ids'][] = $hooks[0]['id'];
				}
			} elseif (!empty($params['ids'])) {
				$hooks = $this->getMapper()->findAllHooks($owner);
				foreach ($hooks->toArray() as $key => $hook) {
					if (!in_array($hook['key'], $params['ids'])) {
						unset($hooks[$key]);
					} else {
						$deletes['ids'][] = $hook['id'];
					}
				}
			}

			if (!empty($deletes['ids'])) {
				$result = $this->getMapper()->deleteHooksById($deletes['ids']);
				\Event::fire('hook.deleted', compact('result'));
			} else {
				throw new Exception(__('No Keys were deleted. Please check permissions'), Exception::AUTH_ERROR);
			}

			return $this->display('hook/delete.json.phtml', compact('hooks'));
		} catch (Exception $e) {
			return $this->display('exception/exception.json.phtml', $e);
		}
	}
}
