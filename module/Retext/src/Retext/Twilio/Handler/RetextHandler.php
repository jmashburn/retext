<?php

namespace Retext\Handler;

use Db\Mapper\ApiMapper; 

use Application\Api\Handler\JsonHandler,
    Application\Api\ApiException as Exception;

class RetextHandler extends JsonHandler {

	protected $mapperClass = "\Retext\Db\RetextMapper";

	protected $authAdapter = 'Application\Authentication\Authentication';

	//  List All/Single Hooks(s)
	public function get($retext = null) {
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

			if ($retext) {
				$retext = $this->getMapper()->findRetextByKey($hook, $owner);
				if (empty($retexts[0]['id'])) {
					throw new Exception(__('Retext: "%s" does not exist. Please try again.', array($retext)), Exception::WEBAPI_VALUE_ADD_FAILED);
				}
			} else {
				$retexts = $this->getMapper()->findAllRetexts($owner);
			}
			\Event::fire('retext.found', compact('retexts'));
			return $this->display('retext/list.json.phtml', compact('retexts'));
		} catch (Exception $e) {
			return $this->display('exception/exception.json.phtml', $e);
		}
	}

	// Add/Update Hook(s)
	public function post($retext = null) {
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
			$retext = $this->getMapper()->findRetextByEndPoint($params['end_point'], $params['username']);
			if (empty($hook['id'])) {
				$retexts = $this->getMapper()->addRetext($params);
				if (empty($retexts[0]['id'])) {
					// Audit/Log
					throw new Exception(__('Could not write to database: retexts table. Please check permissions'), Exception::PERMISSIONS);
				}
				\Event::fire('retext.created', compact('retexts'));
				// if (!$this->getAcl()->isAllowed($identity->getRole(), $this->resourceRoute, self::PERMISSION_LIST_ALL)) {
				// 	$owner = $identity->getUsername();
				// }
				// $hooks = $this->getMapper()->findAllHooks($owner);

				#$hooks = $this->getMapper()->findHookByEndPoint($params['end_point'], $params['username']);
				return $this->display('retext/list.json.phtml', compact('retexts'));
			} else {
				throw new Exception(__('The retet end_point "%s" already exists.', array($params['end_point'])), Exception::WEBAPI_KEY_ADD_FAILED);
			}
		} catch (Exception $e) {
			return $this->display('exception/exception.json.phtml', $e);
		}
	}

	// Remove the Retext
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
			$retexts = array();
			if (!empty($key)) {
				$retexts = $this->getMapper()->findRetextByKey($key, $owner);
				if (!empty($retexts[0]['id'])) {
					$deletes['ids'][] = $retexts[0]['id'];
				}
			} elseif (!empty($params['ids'])) {
				$retexts = $this->getMapper()->findAllRetexts($owner);
				foreach ($retexts->toArray() as $key => $retext) {
					if (!in_array($retext['key'], $params['ids'])) {
						unset($retexts[$key]);
					} else {
						$deletes['ids'][] = $retext['id'];
					}
				}
			}

			if (!empty($deletes['ids'])) {
				$result = $this->getMapper()->deleteRetextssById($deletes['ids']);
				\Event::fire('retext.deleted', compact('result'));
			} else {
				throw new Exception(__('No Retexts were deleted. Please check permissions'), Exception::AUTH_ERROR);
			}

			return $this->display('retext/delete.json.phtml', compact('hooks'));
		} catch (Exception $e) {
			return $this->display('exception/exception.json.phtml', $e);
		}
	}
}
