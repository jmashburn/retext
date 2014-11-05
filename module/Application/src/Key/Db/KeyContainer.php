<?php

namespace Key\Db;

use Application\Container\AbstractContainer;

class KeyContainer extends AbstractContainer {

	public function toJson(array $defaults = array(), $filter = false) {
		unset($this->id);
		unset($this->username);
		unset($this->creation_time);
		return parent::toJson($defaults, $filter);
	}

}