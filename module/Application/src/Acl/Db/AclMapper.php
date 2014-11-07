<?php

namespace Acl\Db;


use Application\Mapper\AbstractMapper,
	Application\Db\ApiPDO,
	Application\Api\ApiException as Exception;


class AclMapper extends AbstractMapper  {

	public $context = 'gui';

	public function addResource($resource) {
		try {
			$sql = "INSERT INTO `gui_acl_resources` ( `resource_name` ) VALUES (:resource_name)";
			$result = $this->select($sql, array(':resource_name' => $resource));
		} catch (\Application\Exception $e) {
			#throw new Exception($e->getMessage(), $e->getCode());
		} catch (\PDOException $e) {
			print_r($e);
			die();
		}
	}

	public function getRoles() {
		try {
			$roles = $this->select("SELECT * FROM `gui_acl_roles`")->toArray();
			if (is_array($roles)) {
				foreach ($roles as $key => $role) {
						$roles[$key]['parent_name'] = $this->getRoleParentName($roles, $role['role_parent']);
				}
				unset($role);
				return $roles;
			}
			return array();
		} catch (\Application\Exception $e) {
			throw new Exception($e);
		}
	}

	public function getResources() {
		$resources = $this->select("SELECT * FROM `gui_acl_resources`")->toArray();
	    if (is_array($resources)) {
	    	return $resources;
	    }
	    return array();
	}

	public function getPrivileges() {
		$sql = 'SELECT priv.*, role.role_name, resource.resource_name' . 
			' FROM `gui_acl_privileges` AS priv' .
			' INNER JOIN `gui_acl_roles` AS role ON role.role_id = priv.role_id' .
			' INNER JOIN `gui_acl_resources` as resource ON resource.resource_id = priv.resource_id';
		$privileges = $this->select($sql, array(), true, true, true)->toArray();
	    if (is_array($privileges)) {
	    	foreach ($privileges as $key => $privilege) {
	    		$privileges[$key]['allowed_actions'] = null;
	    		if ($privilege['allow']) {
	    			$privileges[$key]['allowed_actions'] = explode(',', $privilege['allow']);
	    		}
	    	}
	    	return $privileges;
	    }

	    return array();
	}

	protected function getRoleParentName($roles, $parentId) {
		foreach ($roles as $role) {
			if ($parentId == $role['role_id']) {
				return $role['role_name'];
			}
		}
		return null;
	}
}
