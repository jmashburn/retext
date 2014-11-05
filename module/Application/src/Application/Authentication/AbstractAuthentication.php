<?php

namespace Application\Authentication;

use \Application\Identity\AbstractIdentity;


abstract class AbstractAuthentication {

	protected $mapper;

    public $identityClass;

	abstract public function getIdentity($identity = null);

    public function isValid(AbstractIdentity $identity) {
        if ($identity->isValid()) {
            return true;
        }
        return false;
    }


    public function setMapper($mapper) {
        $this->mapper = $mapper;
        return $this;
    }

    public function getMapper($mapper = null) {
        if ($mapper && class_exists(($mapper))) {
            $this->mapper = new $mapper;
        }
        return $this->mapper;
    }

    public function __toString() {
        return get_class($this);
    }

}