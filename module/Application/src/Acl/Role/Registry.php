<?php

namespace Acl\Role;

use Application\Exception;

class Registry {

	protected $roles = array();

	public function add($role, $parents = null) {
		$roleId = $role->getRoleId();

		if ($this->has($roleId)) {
			throw new Exception(sprintf('Role ID "%s" already exists in the registry', $roleId));
		}
		$roleParents = array();
		if ($parents && null !== $parents) {
			if (!is_array($parents) && !$parents instanceof Traversable) {
				$parents = array($parents);
			}
			foreach ($parents as $parent) {
				try {
					if ($parent instanceof Role) {
						$roleParentId = $parent->getRoleId();
					} else {
						$roleParentId = $parent;
					}
					$roleParent = $this->get($roleParentId);
				} catch (Exception $e) {
					throw new Exception(sprintf('Parent Role id "%s" does not exist', $roleParentId));
				}
				$roleParents[$roleParentId] = $roleParent;
				$this->roles[$roleParentId]['children'][$roleId] = $role;
			}
		}
		$this->roles[$roleId] = array(
			'instance' => $role,
			'parents' => $roleParents,
			'children' => array()
		);
		return $this;
	}

	public function get($role) {
		if ($role instanceof Role) {
			$roleId = $role->getRoleId();
		} else {
			if (is_array($role)) {
				var_dump(debug_backtrace());
			}
			$roleId = (string) $role;
		}

		if (!$this->has($role)) {
			throw new Exception(sprintf("Role '%s' not found", $roleId));
		}

		return $this->roles[$roleId]['instance'];
	}

	public function has($role) {
		if ($role instanceof Role\Role) {
			$roleId = $role->getRoleId();
		} else {
			$roleId = (string) $role;
		}

		return isset($this->roles[$roleId]);
	}

	public function getParents($role) {
		$roleId = $this->get($role)->getRoleId();
		return $this->roles[$roleId]['parents'];
	}

	public function inherits($role, $inherit, $onlyParents = false) {
		try {
			$roleId = $this->getRole($role)->getRoleId();
			$inheritId = $this->getRole($inherit)->getRoleId();
		} catch (Exception $e) {
			throw new Exception ($e->getMessage(), $e->getCode(), $e);
		}

		$inherits = isset($this->roles[$roleId]['parents'][$inheritId]);

		if ($inherits || $onlyParents) {
			return $inherits;
		}

		foreach ($this->roles[$roleId]['parents'] as $parentId => $parent) {
			if ($this->inherits($parentId, $inheritId)) {
				return true;
			}
		}
		return false;
	}

	public function remove($role) {
		try {
			$roleId = $this->get($role)->getRoleId();
		} catch (Exception $e) {
			throw new Exception ($e->getMessage(), $e->getCode(), $e);
		}

		foreach ($this->roles[$roleId]['children'] as $childId => $child) {
			unset($this->roles[$childId]['parents'][$roleId]);
		}
		foreach ($this->roles[$roleId]['parents'] as $parentId => $parent) {
			unset($this->roles[$parentId]['children'][$roleId]);
		}

		unset($this->roles[$roleId]);
		return $this;
	}

	public function removeAll() {
		$this->roles = array();
		return $this;
	}

	public function getRoles() {
		return $this->roles;
	}


}