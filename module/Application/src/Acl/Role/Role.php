<?php

namespace Acl\Role;


class Role {

	protected $roleId;

	public function __construct($roleId) {
		$this->roleId = (string) $roleId;
	}

	public function getRoleId() {
		return $this->roleId;
	}

	public function __toString() {
		return $this->getRoleId();
	}
}