<?php
namespace Account\Handler;

use ToroHook;

use Application\Api\Handler\JsonHandler,
    Application\Api\ApiException as Exception;

class LoginHandler extends JsonHandler {

    protected $authAdapter = 'Application\Authentication\Authentication';

    // Logout 
    function get() {
        try {
            if ($this->hasIdentity()) {
                $this->getAuthAdapter()->clearIdentity();
                $logoutResult = array('status' => 'OK');
            } else {
                throw new Exception(__('No Authentication data found.'), Exception::AUTH_ERROR);
            }
            return $this->display('account/logout.json.phtml', compact('logoutResult'));
        } catch (Exception $e) {
            return $this->display('exception/exception.json.phtml', $e);
        }
    }

    // Login
    function post() {
        try {
            $params = $this->getParameters(array('username' => '', 'password' => ''));
            $this->validateMandatoryParameters($params, array('username', 'password'));
            $identity = $this->getAuthAdapter()->authenticate($params); 


            if (!$identity->isValid()) {
                $this->getAuthAdapter()->clearIdentity();
                throw new Exception($identity->getMessage(), Exception::AUTH_ERROR);
            }
            $loginResult = array(
                'status' => 'OK',
                'name' => $identity->getUsername(),
                'role' => $identity->getRole(),
            );
            \Event::fire('account.login', compact('loginResult'));
            $hash = md5(serialize($loginResult));   
            return $this->display('account/login.json.phtml', compact('loginResult'));
        } catch (Exception $e) {
            return $this->display('exception/exception.json.phtml', $e);
        }
    }
}
