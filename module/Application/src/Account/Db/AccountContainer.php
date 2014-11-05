<?php

namespace Account\Db;

use Application\Container\AbstractContainer;

class AccountContainer extends AbstractContainer {

	public function toJson() {
		unset($this->id);
		unset($this->password);
		return parent::toJson();
	}

}