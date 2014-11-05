<?php

namespace Retext\Code\Db;

use Application\Container\AbstractContainer;

class CodeContainer extends AbstractContainer {

	public function toJson(array $defaults = array(), $filter = false) {
		unset($this->id);
		unset($this->username);
		unset($this->creation_time);
		return parent::toJson($defaults, $filter);
	}

}