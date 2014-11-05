<?php
namespace Account\Handler;

use ToroHook;

use Db\MySQL;

use Application\Api\Handler\JsonHandler,
    Application\Api\ApiException as Exception;

class AccountHandler extends JsonHandler {

	protected $mapperClass = 'Account\Db\AccountMapper';

	protected $authAdapter = 'Application\Authentication\Authentication';

	// Get User Information
    function get($username = NULL) {
    	try {
    		if (!$this->hasIdentity()) {
				throw new Exception(__('No authentication data found.'), Exception::AUTH_ERROR);
			}
			// Check if User is allowed to List all Keys
			$owner = null;
			if ($this->getAcl()->hasResource($this->resourceRoute)) {
				$identity = $this->getIdentity();
	            if (!$identity->isValid()) {
            		throw new Exception(__('Authentication error. Please login.'), Exception::AUTH_ERROR);
        		}

				if (!$this->getAcl()->isAllowed($identity->getRole(), $this->resourceRoute, self::PERMISSION_LIST_ALL)) {
					if (!$this->getAcl()->isAllowed($identity->getRole(), $this->resourceRoute, self::PERMISSION_LIST)) {
						if (empty($username) || $username !== $identity->getUsername()) {
							throw new Exception(__('Unable access resource. Please check permissions.'), Exception::ACL_PERMISSION_DENIED);
						}
					}
					$owner = $identity->getUsername();
				}
			}
			
			if ($username) {
				$accounts = $this->getMapper()->findAccountByName($username, $owner);
				if (empty($accounts)) {
					throw new Exception(__('Key: "%s" does not exist. Please try again.', array($username)), Exception::WEBAPI_VALUE_ADD_FAILED);
				}
				$accounts = array($accounts[0]);
			} else {
				$accounts = $this->getMapper()->findAllAccounts($owner);
			}
			\Event::fire('account.found', compact('accounts'));
			return $this->display('account/list.json.phtml', compact('accounts'));
		} catch (Exception $e) {
			return $this->display('exception/exception.json.phtml', $e);
		}
    }

    // Add/Update User
    function post($username = null) {
		try {
			$params = $this->getParameters(array('role' => 'guest'));
			$this->validateMandatoryParameters($params, array('name', 'email', 'password', 'confirmPassword'));
			$this->validateFilter($params['email'], 'email', FILTER_VALIDATE_EMAIL);

			$identity = $this->getIdentity();
			if (!$this->getAcl()->isAllowed($identity->getRole(), $this->resourceRoute, self::PERMISSION_CREATE_ALL)) {
				// Log/Audit
				throw new Exception(__('Unable to create key name: "%s". Please check permissions.', array($params['name'])), Exception::ACL_PERMISSION_DENIED);
			}

			// Check Username is valid.
			$account = $this->getMapper()->findAccountByName($params['name']);
			if (!empty($account[0]['id'])) {
				throw new Exception(__('Account: "%s" already exist. Please try again.', array($params['name'])), Exception::WEBAPI_VALUE_ADD_FAILED);
			}

			if ($params['confirmPassword'] != $params['password']) {
	    		$msg = __('New password should be identical to the confirmation password');
	    		throw new Exception($msg, Exception::WRONG_PASSWORD);
    		}

    		$username = $this->getMapper()->findAccountByName($params['name']);
			if (empty($username['id'])) {
				$accounts = $this->getMapper()->addAccount($params);
				if (empty($accounts[0]['id'])) {
					// Audit/Log
					throw new Exception(__('Could not write to database: user table. Please check permissions'), Exception::PERMISSIONS);
				}
				return $this->display('account/list.json.phtml', compact('accounts'));
			} else {
				throw new Exception(__('The user: "%s" already exists.', array($params['name'])), Exception::WEBAPI_KEY_ADD_FAILED);
			}
		} catch (Exception $e) {
			return $this->display('exception/exception.json.phtml', $e);
		}
	}

    // Delete User
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
			$accounts = array();
			if (!empty($key)) {
				$accounts = $this->getMapper()->findAccountByKey($key, $owner);
				if (!empty($accounts[0]['id'])) {
					$deletes['ids'][] = $accounts[0]['id'];
				}
			} elseif (!empty($params['ids'])) {
				$accounts = $this->getMapper()->findAllAccounts($owner);
				foreach ($accounts->toArray() as $key => $account) {
					if (!in_array($account['key'], $params['ids'])) {
						unset($accounts[$key]);
					} else {
						$deletes['ids'][] = $account['id'];
					}
				}
			}

			if (!empty($deletes['ids'])) {
				$result = $this->getMapper()->deleteAccountsById($deletes['ids']);
				\Event::fire('account.deleted', compact('result'));
			} else {
				throw new Exception(__('No Keys were deleted. Please check permissions'), Exception::AUTH_ERROR);
			}

			return $this->display('account/delete.json.phtml', compact('accounts'));
		} catch (Exception $e) {
			return $this->display('exception/exception.json.phtml', $e);
		}
	}
}
