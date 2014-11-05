<?php

namespace Application\Api\Authentication;

class SignatureSimple extends SignatureAbstract {

	public $identityClass = 'Application\Api\Identity\Identity';

	public function collectGroups($identity) {
		try {
	        $username = $identity->getUsername();
	        $mapper = $this->getMapper('Account\Db\AccountMapper');
	        $user = $mapper->findAccountByName($username);
            if (!empty($user[0])) {
  				$identity->setRole($user[0]['role']);
            } else {
      			throw new \Exception('Could not collect groups. Setting role to guest.');
            }
        } catch (\Exception $e) {
                $identity->setRole('guest');
        }
        return $identity;
    }
}


?>

