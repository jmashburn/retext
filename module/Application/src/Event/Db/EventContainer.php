<?php

namespace Event\Db;

use Application\Container\AbstractContainer;

class EventContainer extends AbstractContainer {

	public function toJson(array $defaults = array(), $filter = false) {
		return parent::toJson($defaults = array(), $filter = false);
	}

	public function setEventName($event) {
		$this->data['event'] = $event;
	}

	public function setResult($result) {
		$this->data['result'] = $result;
	}
}