<?php

namespace Application\Identity;

class GuiIdentity extends AbstractIdentity {
		
	private $role;
	
	private $groups = array();
	
	private $username;

	private $partner;

	private $email;
	
	public function getIdentity() {
		return $this->identity;
	}
	
	public function getRole() {
		return $this->role;
	}
	
	public function getGroups() {
		return $this->groups;
	}
	
	public function getUsername() {
		return $this->username;
	}
	
	public function setUsername($username) {
		$this->username = $username;
		return $this;
	}

	public function getEmail() {
		return $this->email;
	}

	public function setEmail($email) {
		$this->email = $email;
		return $this;
	}

	public function getPartner() {
		return $this->partner;
	}
	
	public function setPartner($partner) {
		$this->partner = $partner;
		return $this;
	}
	
	public function setGroups($groups) {
		$this->groups = $groups;
		return $this;
	}
	
	public function setRole($role) {
		$this->role = $role;
		return $this;
	}
	
	public function setIdentity($identity) {
		$this->identity = $identity;
		return $this;
	}
	
	public function __toString() {
		$string = $this->getIdentity();
		if ($this->getRole()) {
			$string .= ":" . $this->getRole();
		}
		return $string;
	}
}
?>