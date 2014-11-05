<?php

namespace Application\Api\Identity;

use Application\Identity\AbstractIdentity;


class Identity extends AbstractIdentity {

	private $key;


	public function setKey($key) {
		$this->key = $key;
		return $this;
	}

	public function getKey() {
		return $this->key;
	}

}