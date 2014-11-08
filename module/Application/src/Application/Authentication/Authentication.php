<?php

namespace Application\Authentication;

use Db\ApiPDO;
use PDO;

use Exception as Exception;
use Exception\ApiException as ApiException;

use Application\Identity\AbstractIdentity;


class Authentication extends AbstractAuthentication{

	public $identityClass = 'Application\Identity\Identity';

	public function authenticateOnly($authArray = array()) {
		try {
			$mapper = new \Account\Db\AccountMapper();
			$sql = "SELECT * FROM gui_users WHERE ";
			$sql .= filter_var($authArray['username'], FILTER_VALIDATE_EMAIL) ? "email=:name":"name=:name";
			$sql .= " AND password=:password";
			$result = $mapper->selectOne($sql, 
					array(':name' => $authArray['username'], ':password' => $this->setCredential($authArray['password'])));
		} catch (Exception $e) {
			throw new ApiException($e->getMessage(), $e->getCode());
		}

	    $identity = new $this->identityClass('unknown', 'guest');
	    if (!isset($result[0]['id'])) {
	    	$msg = __('GUI Authentication failed');
	    	$identity->setMessage($msg);
	    	return $identity;
	    }

	    $identity->setUsername($authArray['username']);
	    $identity->setIdentity($result[0]->name);
	    $identity->setEmail($result[0]->email);
	    $identity->setRole($result[0]->role);
	    $identity->setMessage(__('Logged In'));
	    $identity->setValid(true);
	    return $identity;
	}

	public function authenticate($authArray = array()) {
		$identity = $this->authenticateOnly($authArray);
	    $_SESSION['GUI']['identity'] = serialize($identity); 
		\Event::fire('gui.authenticate', compact('result', 'identity'));
	    return $identity;
	}

	public function getIdentity($identity = null) {
		if (!empty($_SESSION['GUI']['identity'])) {
			$identity = unserialize($_SESSION['GUI']['identity']);
			if ($identity instanceof AbstractIdentity) {
				return $identity;
			} 
		}
		$identity = new $this->identityClass('unknown', 'guest');
		return new $this->identityClass('unknown', 'guest');
    }

	public function isValid(AbstractIdentity $identity) {
		if ($identity->isValid()) {
			return true;
		}
		return false;
    }

   	public function clearIdentity() {
   		if (!empty($_SESSION['GUI']['identity'])) {
   			$identity = unserialize($_SESSION['GUI']['identity']);
			unset($_SESSION['GUI']['identity']);
			\Event::fire('gui.logout', array('identity' => $identity));
    		return true;
    	}
    	return false;
    }

	public function setCredential($credential) {
		return \Application\Security::hash($credential, "sha256");
	}

	
}