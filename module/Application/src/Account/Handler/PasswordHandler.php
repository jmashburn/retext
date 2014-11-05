<?php
namespace Account\Handler;

use ToroHook;

use \Application\Authentication\Authentication as Authentication;

use Application\Api\Handler\JsonHandler,
    Application\Api\ApiException as Exception;

class PasswordHandler extends JsonHandler {

    protected $authAdapter = 'Application\Authentication\Authentication';

    protected $mapperClass = 'Account\Db\AccountMapper';

    function post($username = null) {
        try {
    		$params = $this->getParameters();
			$this->validateMandatoryParameters($params, array('password', 'newPassword', 'confirmNewPassword'));

			if (!$this->hasIdentity()) {
				throw new Exception(__('No authentication data found.'), Exception::AUTH_ERROR);
			}

			$identity = $this->getIdentity();
			if (empty($username)) {
				$username = $identity->getUsername();
			}

			$params['username'] = $username;
			$this->validateMandatoryParameters($params, array('username', 'password', 'newPassword', 'confirmNewPassword'));
			$username = $this->changePassword($params);
			return $this->display('account/password.json.phtml', compact('username'));
        } catch (Exception $e) {
            return $this->display('exception/exception.json.phtml', $e);
        } catch (\Application\Exception $e) {
            return $this->display('exception/exception.json.phtml', $e);
        }
    }

    protected function changePassword($params) {
    	if ($params['confirmNewPassword'] != $params['newPassword']) {
    		$msg = __('New password should be identical to the confirmation password');
    		throw new Exception($msg, Exception::WRONG_PASSWORD);
    	}

    	try {
    		$identity = $this->getIdentity();
    		$auth = $this->getAuthAdapter();
            $identity = $auth->authenticateOnly(array('username' => $identity->getUsername(), 'password' => $params['password']));
            if (!$identity->isValid()) {
    			throw new Exception(__('The current password for user "%s" is incorrect', array($params['username'])), Exception::WRONG_PASSWORD);
    		}
    		$this->getMapper()->setAccount($params['username'], $params['newPassword']);
    	} catch (Application\Exception $e) {
    		throw new Exception(__("%s failed: %s", array($this->getApiUrl(), $e->getMessage())));
    	}
    	return array('username' => $params['username']);
    }

}
