<?php

namespace Application\Identity;

class Identity extends AbstractIdentity {
		
	private $email;

	private $partner;

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
}
