<?php

namespace Acl;

use Application\Exception;


class Acl {

	const TYPE_ALLOW = 'TYPE_ALLOW';

	const TYPE_DENY = 'TYPE_DENY';

	const OP_ADD = 'OP_ADD';

	const OP_REMOVE = 'OP_REMOVE';

	protected $isAllowedRole;

	protected $isAllowedresource;

	protected $isAllowedPrivilege;

	protected $roleRegistery;

	protected $resources;

	protected $rules = array(
        'allResources' => array(
            'allRoles' => array(
                'allPrivileges' => array(
                    'type'   => self::TYPE_DENY,
                    'assert' => null,
                ),
                'byPrivilegeId' => array()
            ),
            'byRoleId' => array(
            	'root' => array(
            		'allPrivileges' => array(
            			'type' => self::TYPE_ALLOW,
            			'assert' => null
            		),
            		'byPrivilegeId' => array()
            	)
            )
        ),
        'byResourceId' => array()
    );

	public function addRole($role, $parents = null) {
		if (is_string($role)) {
			$role = new Role\Role($role);
		} elseif (!$role instanceof Role\Role) {
			throw new Exception ("addRole() expects $role to be of type Role\Role");
		}
		$this->getRoleRegistry()->add($role, $parents);
		return $this;
	}

	public function getRole($role) {
		return $this->getRoleRegistry()->get($role);
	}

	public function hasRole($role) {
		return $this->getRoleRegistry()->has($role);
	}

	public function inheritRole($role, $inherit, $onlyParents = false) {
		return $this->getRoleRegistry()->inherit($role, $inherit, $onlyParents);
	}

	public function removeRole($role) {
		$this->getRoleRegistry()->remove($role);

		if ($role instanceof Role\Role) {
			$roleId = $role->getRoleId();
		} else {
			$roleId = (string) $role;
		}

		return $this;
	}

	public function removeRoleAll($role) {
		$this->getRoleRegistry()->removeAll($role);
		return $this;
	}

	public function addResource($resource, $parent = null) {
		if (is_string($resource)) {
			$resource = new Resource\Resource($resource);
		} elseif (!$resource instanceof Resource\Resource) {
			throw new Exception ("addResource() expects  $resource to be of type Resource\Resource");
		}

		$resourceId = $resource->getResourceId();

		if ($this->hasResource($resourceId)) {
			throw new Exception("Resource id $resourceId already exists in the ACL");
		}

		$resourceParent = null;

		if (null !== $parent) {
			try {
				if ($parent instanceof Resource\Resource) {
					$resourceParentId = $parent->getResourceId();
				} else {
					$resourceParentId = $parent;
				}
				$resourceParent = $this->getResource($resourceParentId);
			} catch (Exception $e) {
				throw new Exception (sprintf("Parent Resource id %s does not exist", $resourceParentId));
			}
			$this->resourcs[$resourceParentId]['children'][$resourceId] = $resource;
		}

		$this->resources[$resourceId] = array(
			'instance' => $resource,
			'parent' => $resourceParent,
			'children' => array()
		);

		return $this;
	}

	public function getResource($resource) {
		if ($resource instanceof Resource\Resource) {
			$resourceId = $resource->getResourceId();
		} else {
			$resourceId = (string) $resource;
		}

		if (!$this->hasResource($resourceId)) {
			throw new Exception ("Resource $resourceId not found");
		}
		return $this->resources[$resourceId]['instance'];
	}

	public function hasResource($resource) {
		if ($resource instanceof Resource\Resource) {
			$resourceId = $resource->getresourceId();
		} else {
			$resourceId = (string) $resource;
		}
		return isset($this->resources[$resourceId]);
	}

	public function inheritResource($resource, $inherit, $onlyParents = false) {
		try {
			$resourceId = $this->getresource($resource)->getResourceId();
			$inheritId = $this->getresource($inherit)->getResourceId();
		} catch (Exception $e) {
			throw new Exception ($e->getMessage(), $e->getCode(), $e);
		}

		if (null !== $this->resources[$resourceId]['parent']) {
			$parentId = $this->resources[$resourceId]['parent']->getResourceId();
			if ($inheritId == $parentId) {
				return true;
			} elseif ($onlyParents) {
				return false;
			}
		} else {
			return false;
		}

		while (null !== $this->resources[$parentId]['parent']) {
			$parentId = $this->resources[$resourceId]['parent']->getResourceId();
			if ($inheritId == $parentId) {
				return true;
			}
		}
		return false;
	}

	public function removeResource($resource) {
		try {
			$resourceId = $this->getResource($resource)->getResourceId();
		} catch (Exception $e) {
			throw new Exception ($e->getMessage(), $e->getCode(), $e);
		}

		$resourcesRemoved = array($resourceId);
		if (null !== ($resourceParent = $this->resources[$resourceId]['parent'])){
			unset($this->resources[$resourceParent->getResourceId()]['children'][$resourceId]);
		}
		foreach ($this->resources[$resourceId]['children'] as $childId => $child) {
			$this->removeresource($childId);
			$resourcesRemoved[] = $childId;
		}

		// foreach ($resourcesRemoved as $resourceIdRemoved) {
		// 	foreach ()
		// }

		unset($this->resources[$resourceId]);
		return $this;
	}

	public function removeresourceAll() {
		$this->resources = array();
		return $this;
	}

	public function allow($roles = null, $resources = null, $privileges = null, $assert = null) {
		return $this->setRule(self::OP_ADD, self::TYPE_ALLOW, $roles, $resources, $privileges, $assert);

	}

	public function deny($roles = null, $resources = null, $privileges = null, $assert = null) {
		return $this->setRule(self::OP_ADD, self::TYPE_DENY, $roles, $resources, $privileges, $assert);
	}

	public function removeAllow($roles = null, $resources = null, $privileges = null, $assert = null) {
		return $this->setRule(self::OP_REMOVE, self::TYPE_ALLOW, $roles, $resources, $privileges, $assert);

	}

	public function removeDeny($roles = null, $resource = null, $privileges = null, $assert = null) {
		return $this->setRule(self::OP_REMOVE, self::TYPE_DENY, $roles, $resources, $privileges, $assert);
	}

	public function setRule($operation, $type, $roles = null, $resources = null, $privileges = null, $assert = null) {
		$type = strtoupper($type);
		if (self::TYPE_ALLOW !== $type && self::TYPE_DENY !== $type) {
			throw new Exception(sprintf("Unsupported rule type; must be  either %s or %s", self::TYPE_ALLOW, self::TYPE_DENY));
		}

		if (!is_array($roles)) {
			$roles = array($roles);
		} elseif (0 === count($roles)) {
			$roles = array(null);
		}
		$rolesTemp = $roles;
		$roles = array();
		foreach ($rolesTemp as $role) {
			if (null !== $role) {
				$roles[] = $this->getRoleRegistry()->get($role);
			} else {
				$roles[] = null;
			}
		}
		unset($rolesTemp);

		if (!is_array($resources)) {
			if (null === $resources && count($this->resources) > 0) {
				$resources = array_keys($this->resources);
				if (!in_array(null, $resources)) {
					array_unshift($resources, null);
				}
			} else {
				$resources = array($resources);
			}
		} elseif (0 === count($resources)) {
			$resources = array(null);
		}
		$resourcesTemp = $resources;
		$resources = array();
		foreach ($resourcesTemp as $resource) {
			if (null !== $resource) {
				$resourceObj = $this->getresource($resource);
				$resourceId = $resourceObj->getresourceId();
				$children = $this->getChildresources($resourceObj);
				$resources = array_merge($resources, $children);
				$resources[$resourceId] = $resourceObj;
			} else {
				$resources[] = null;
			}
		}
		unset($resourcesTemp);

		if (null === $privileges) {
			$privileges = array();
		} elseif (!is_array($privileges)) {
			$privileges = array($privileges);
		}

		switch ($operation) {
			case self::OP_ADD:
				foreach ($resources as $resource) {
					foreach ($roles as $role) {
						$rules =& $this->getRules($resource, $role, true);
						if (0 === count($privileges)) {
							$rules['allPrivileges']['type'] = $type;
							$rules['allPrivileges']['assert'] = $assert;
							if (!isset($rules['byPrivilegeId'])) {
								$rules['byPrivilegeId'] = array();
							}
						} else {
							foreach ($privileges as $privilege) {
								$rules['byPrivilegeId'][$privilege]['type'] = $type;
								$rules['byPrivilegeId'][$privilege]['assert'] = $assert;
							}
						}
					}
				}
				break;
			case self::OP_REMOVE:
				foreach ($resources as $resource) {
					foreach ($roles as $role) {
						$rules =& $this->getRules($resource, $role);
						if (null === $rules) {
							continue;
						}
						if (0 === count($privileges)) {
							if (null === $resource && null === $role) {
								if ($type === $rules['allPrivileges']['type']) {
									$rules = array(
										'allPrivileges' => array(
											'type' => self::TYPE_DENY,
											'assert' => null
										),
										'byPrivilegeId' => array()
									);
								}
								continue;
							}

							if (isset($rules['allPrivileges']['type']) &&
								$type === $rules['allPrivileges']['type']) {
								unset($rules['allPrivileges']);
							}
						} else {
							foreach ($privileges as $privilege) {
								if (isset($rules['byPrivilegeId'][$privilege]) && 
									$type === $rules['byPrivilegeId'][$privilege]['type']) {
									unset($rules['byPrivilegeId'][$privilege]);
								}
							}
						}
					}
				}
				break;

			default:
				throw new Exception(sprintf('Unsupported operation; must be either %s or %s', self::OP_ADD, self::OP_REMOVE));
		}
		return $this;

	}

	public function getChildresources(Resource\Resource $resource) {
		$return = array();
		$id = $resource->getresourceId();

		$children = $this->resources[$id]['children'];
		foreach ($children as $child) {
			$child_return = $this->getChildresources($child);
			$child_return[$child->getresourceId()] = $child;

			$return = array_merge($return, $child_return);
		}
		return $return;
	}

	public function isAllowed($role = null, $resource = null, $privilege = null) {
		$this->isAllowedRole = null;
		$this->isAllowedresource = null;
		$this->isAllowedPrivilege = null;

		if (null !== $role) {
			$this->isAllowedRole = $role;
			$role = $this->getRoleRegistry()->get($role);
			if (!$this->isAllowedRole instanceof Role\Role) {
				$this->isAllowedRole = $role;
			}
		}

		if (null !== $resource) {
			if (!$this->hasResource($resource)) {
				$resource = new Resource\Resource('');
			}
			$this->isAllowedresource = $resource;
			$resource = $this->getResource($resource);	
			if (!$this->isAllowedresource instanceof Resource\Resource) {
				$this->isAllowedresource = $resource;
			}
		}

		if (null === $privilege) {
			do {
				if (null !== $role && null !== ($result = $this->roleDFSAllPrivileges($role, $resource, $privilege))) {
					return $result;
				}

				if (null !== ($rules = $this->getRules($resource, null))) {
					foreach ($rules['byPrivilegeId'] as $privilege => $rule) {
						if (self::TYPE_DENY == ($ruleTypeOnePrivilege = $this->getRuleType($resource, null, $privilege))) {
							return false;
						}
					}
					if (null !== ($ruleTypeAllPrivileges = $this->getRuleType($resource, null, null))) {
						return self::TYPE_ALLOW == $ruleTypeAllPrivileges;
					}
				}

			} while (true);
		} else {
			$this->isAllowedPrivilege = $privilege;
			do {
				if (null !== $role && null !== ($result = $this->roleDFSOnePrivilege($role, $resource, $privilege))) {
					return $result;
				}

				if (null !== ($ruleType = $this->getRuleType($resource, null, $privilege))) {
					return self::TYPE_ALLOW == $ruleType;
				} elseif (null !== ($ruleTypeAllPrivileges = $this->getRuleType($resource, null, null))) {
					$result = self::TYPE_ALLOW == $ruleTypeAllPrivileges;
					if ($result || null === $resource) {
						return $result;
					}
				}
				$resource = $this->resources[$resource->getResourceId()]['parent'];
			} while (true);
		}
	}

	protected function &getRules(Resource\Resource $resource = null, Role\Role $role = null, $create = false) {
		$null = null;
		$nullRef =& $null;

		do {
			if (null === $resource) {
				$visitor =& $this->rules['allResources'];
				break;
			}
			$resourceId = $resource->getResourceId();
			if (!isset($this->rules['byResourceId'][$resourceId])) {
				if (!$create) {
					return $nullRef;
				}
				$this->rules['byResourceId'][$resourceId] = array();
			}
			$visitor =& $this->rules['byResourceId'][$resourceId];
		} while(false);

		if (null === $role) {
			if (!isset($visitor['allRoles'])) {
				if (!$create) {
					return $nullRef;
				}
				$visitor['allRoles']['byPrivilegeId'] = array();
			}
			return $visitor['allRoles'];
		}

		$roleId = $role->getRoleId();
		if (!isset($visitor['byRoleId'][$roleId])) {
			if (!$create) {
				return $nullRef;
			}
			$visitor['byRoleId'][$roleId]['byPrivilegeId'] = array();
		}
		return $visitor['byRoleId'][$roleId];
	}

	protected function getRuleType(Resource\Resource $resource = null, Role\Role $role = null, $privilege = null) {
		if (null === ($rules = $this->getRules($resource, $role))) {
			return null;
		}

		if (null === $privilege) {
			if (isset($rules['allPrivileges'])) {
				$rule = $rules['allPrivileges'];
			} else {
				return null;
			}
		} elseif (!isset($rules['byPrivilegeId'][$privilege])) {
			return null;
		} else {
			$rule = $rules['byPrivilegeId'][$privilege];
		}

		if (null === $rule['assert']) {
			return $rule['type'];
		} elseif (null !== $resource || null !== $role || null !== $privilege) {
			return null;
		} elseif (self::TYPE_ALLOW == $rule['type']) {
			return self::TYPE_DENY;
		}
		return self::TYPE_ALLOW;
	}

	protected function roleDFSAllPrivileges(Role\Role $role, Resource\Resource $resource = null) {
        $dfs = array(
            'visited' => array(),
            'stack'   => array()
        );

        if (null !== ($result = $this->roleDFSVisitAllPrivileges($role, $resource, $dfs))) {
            return $result;
        }

        // This comment is needed due to a strange php-cs-fixer bug
        while (null !== ($role = array_pop($dfs['stack']))) {
            if (!isset($dfs['visited'][$role->getRoleId()])) {
                if (null !== ($result = $this->roleDFSVisitAllPrivileges($role, $resource, $dfs))) {
                    return $result;
                }
            }
        }

        return null;
    }

	protected function roleDFSVisitAllPrivileges(Role\Role $role, Resource\Resource $resource = null, &$dfs = null) {
        if (null === $dfs) {
            throw new Exception('$dfs parameter may not be null');
        }

        if (null !== ($rules = $this->getRules($resource, $role))) {
            foreach ($rules['byPrivilegeId'] as $privilege => $rule) {
                if (self::TYPE_DENY === ($ruleTypeOnePrivilege = $this->getRuleType($resource, $role, $privilege))) {
                    return false;
                }
            }
            if (null !== ($ruleTypeAllPrivileges = $this->getRuleType($resource, $role, null))) {
                return self::TYPE_ALLOW === $ruleTypeAllPrivileges;
            }
        }

        $dfs['visited'][$role->getRoleId()] = true;
        foreach ($this->getRoleRegistry()->getParents($role) as $roleParent) {
            $dfs['stack'][] = $roleParent;
        }

        return null;
    }

	protected function roleDFSOnePrivilege(Role\Role $role, Resource\Resource $resource = null, $privilege = null) {
		if (null === $privilege) {
			throw new Exception("$privilege parameter must not be null");
		}

		$dfs = array(
			'visited' => array(),
			'stack' => array(),
		);

		if (null !== ($result = $this->roleDFSVisitOnePrivilege($role, $resource, $privilege, $dfs))) {
            return $result;
        }

		while (null !== ($role = array_pop($dfs['stack']))) {
			if (!isset($dfs['visited'][$role->getRoleId()])) {
				if (null !== ($result = $this->roleDFSVisitOnePrivilege($role, $resource, $privilege, $dfs))) {
					return $result;
				}
			}
		}
		return null;
	}

	protected function roleDFSVisitOnePrivilege(Role\Role $role, Resource\Resource $resource = null, $privilege = null, &$dfs = null) {
		if (null === $privilege) {
			throw new Exception('$privilege parameter must not be null');
		}

		if (null == $dfs) {
			throw new Exception('$dfs parameter must not be null');
		}

        if (null !== ($ruleTypeOnePrivilege = $this->getRuleType($resource, $role, $privilege))) {
            return self::TYPE_ALLOW === $ruleTypeOnePrivilege;
        } elseif (null !== ($ruleTypeAllPrivileges = $this->getRuleType($resource, $role, null))) {
            return self::TYPE_ALLOW === $ruleTypeAllPrivileges;
        }

        $dfs['visited'][$role->getRoleId()] = true;
        foreach ($this->getRoleRegistry()->getParents($role) as $roleParent) {
            $dfs['stack'][] = $roleParent;
        }

        return null;
	}

	protected function getRoleRegistry() {
		if (null == $this->roleRegistery) {
			$this->roleRegistery = new Role\Registry();
		}
		return $this->roleRegistery;
	}

	public function getRoles() {
		return array_keys($this->getRoleRegistry()->getRoles());
	}

	public function getResources() {
		return array_keys($this->resources);
	}
}