<?php

namespace Acl\Resource;


class Resource {

	protected $resourceId;

	public function __construct($resourceId) {
		$this->resourceId = (string) $resourceId;
	}

	public function getResourceId() {
		return $this->resourceId;
	}

	public function __toString() {
		return $this->getResourceId();
	}
}