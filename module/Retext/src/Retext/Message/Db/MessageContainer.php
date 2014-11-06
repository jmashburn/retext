<?php

namespace Retext\Message\Db;

use Application\Container\AbstractContainer;

class MessageContainer extends AbstractContainer {

	public function toJson(array $defaults = array(), $filter = false) {
		$this->DT_RowId = $this->key;
		unset($this->creation_time);
		return parent::toJson($defaults, $filter);
	}

}