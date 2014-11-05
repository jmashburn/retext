<?php

namespace Hook\Db;

use Application\Container\AbstractContainer;

class HookContainer extends AbstractContainer {

	public function toJson() {
		unset($this->id);
		unset($this->username);
		unset($this->creation_time);
		return parent::toJson();
	}
}