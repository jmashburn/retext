<?php

namespace Application\Identity;

abstract class AbstractIdentity {

	protected $identity = '';

	protected $role = '';

	protected $username;

	protected $valid = false;

	protected $message;

	private $groups = array();

	public function __construct($identity = null, $role = null) {
		$this->identity = $identity;
		$this->role = $role;
		$this->username = $identity;
	}

	public function getIdentity() {
		return $this->identity;
	}

	public function setIdentity($identity) {
		$this->identity = $identity;
		return $this;
	}

	public function getRole() {
		return $this->role;
	}

	public function setRole($role) {
		$this->role = $role;
		return $this;
	}

	public function getGroups() {
		return $this->groups;
	}

	public function setGroups($groups) {
		$this->groups = $groups;
		return $this;
	}

	public function getMessage() {
		return $this->message;
	}

	public function setMessage($message) {
		$this->message = $message;
	}

	public function getUsername() {
		return $this->username;
	}
	
	public function setUsername($username) {
		$this->username = $username;
		return $this;
	}

	public function getOwner() {
		return $this->owner;
	}
	
	public function setOwner($owner) {
		$this->owner = $owner;
		return $this;
	}

	public function isValid() {
		return $this->valid;
		if (!empty($this->identity)) {
			return true;
		}
		return false;
	}

	public function setValid($valid) {
		$this->valid = (boolean) $valid;
	}

	public function __toString() {
		$string = $this->getIdentity();
		if ($this->getRole()) {
			$string .= ":" . $this->getRole();
		}
		return $string;
	}


}