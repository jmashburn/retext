<?php

namespace Retext\Code\Handler;

use Db\Mapper\ApiMapper; 

use Application\Api\Handler\JsonHandler,
    Application\Api\ApiException as Exception;

class CodeHandler extends JsonHandler {

	protected $mapperClass = "Retext\Code\Db\CodeMapper";

	protected $authAdapter = 'Application\Authentication\Authentication';

	//  List All/Single Hooks(s)
	public function get($code = null) {
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

			if ($code == 'count') {
				return $this->count();
			}

			if ($code) {
				$codes = $this->getMapper()->findCodeByKey($code, $owner);
				if (empty($codes[0]['id'])) {
					throw new Exception(__('Code: "%s" does not exist. Please try again.', array($code)), Exception::WEBAPI_VALUE_ADD_FAILED);
				}
			} else {
				$codes = $this->getMapper()->findAllCodes($owner);
			}
			\Event::fire('retext.code.found', compact('codes'));
			return $this->display('retext/code/list.json.phtml', compact('codes'));
		} catch (Exception $e) {
			return $this->display('exception/exception.json.phtml', $e);
		}
	}

	// Add/Update Hook(s)
	public function post($code = null) {
		try {
			if (!$this->hasIdentity()) {
				throw new Exception(__('Authentication error. Please login.'), Exception::AUTH_ERROR);
			}

			$params = $this->getParameters();
			$this->validateMandatoryParameters($params, array('code', 'message'));
			$this->validateString($params['code'], 'code');
			$this->validateString($params['message'], 'message');


			$identity = $this->getIdentity();
			if (!$identity->isValid()) {
 				throw new Exception(__('Authentication error. Please login.'), Exception::AUTH_ERROR);
			}
			$params = $this->getParameters(array('username' => $identity->getUsername()));

			if ($identity->getUsername() !== $params['username']) {
				if (!$this->getAcl()->isAllowed($identity->getRole(), $this->resourceRoute, self::PERMISSION_CREATE_ALL)) {
					// Log/Audit
					throw new Exception(__('Unable to create Ccode: "%s" for "%s". Please check permissions.', array($params['end_point'])));
				}

				// Check Username is valid.
				$account = $this->getMapper('Account\Db\AccountMapper')->findAccountByName($params['username']);
				if (empty($account[0]['id'])) {
					throw new Exception(__('Account: "%s" does not exist. Please try again.', array($params['username'])), Exception::WEBAPI_VALUE_ADD_FAILED);
				}
			}

			$owner = null;
			$code = $this->getMapper()->findCodeByCode($params['code'], $params['username']);
			if (empty($code[0]['id'])) {
				$codes = $this->getMapper()->addCode($params, $params['username']);
				if (empty($codes[0]['id'])) {
					// Audit/Log
					throw new Exception(__('Could not write to database: codes table. Please check permissions'), Exception::PERMISSIONS);
				}
				\Event::fire('retext.code.created', compact('codes'));
				return $this->display('retext/code/list.json.phtml', compact('codes'));
			} else {
				throw new Exception(__('The code "%s" already exists.', array($params['code'])), Exception::WEBAPI_KEY_ADD_FAILED);
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
			$codes = array();
			if (!empty($key)) {
				$codes = $this->getMapper()->findCodeByKey($key, $owner);
				if (!empty($codes[0]['id'])) {
					$deletes['ids'][] = $codes[0]['id'];
				}
			} elseif (!empty($params['ids'])) {
				$codes = $this->getMapper()->findAllCodes($owner);
				foreach ($codes->toArray() as $key => $code) {
					if (!in_array($code['key'], $params['ids'])) {
						unset($codes[$key]);
					} else {
						$deletes['ids'][] = $code['id'];
					}
				}
			}

			if (!empty($deletes['ids'])) {
				$result = $this->getMapper()->deleteCodesById($deletes['ids']);
				\Event::fire('retext.code.deleted', compact('result'));
			} else {
				throw new Exception(__('No Retexts were deleted. Please check permissions'), Exception::AUTH_ERROR);
			}

			return $this->display('retext/code/delete.json.phtml', compact('codes'));
		} catch (Exception $e) {
			return $this->display('exception/exception.json.phtml', $e);
		}

	}

	private function count() {
		$this->layout = "views/layout/blank.phtml";
		$count = $this->getMapper()->count("SELECT COUNT(*) FROM retext_codes");
		$count = json_encode(array('count' => $count));
		return $this->display('retext/code/blank.json.phtml', $count);
	}
}
