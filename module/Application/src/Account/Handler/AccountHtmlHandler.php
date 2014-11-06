<?php

namespace Account\Handler;

use ToroHook;

use Application\Handler\HtmlHandler;


class AccountHtmlHandler extends HtmlHandler {

	public function get() {
    	$this->setTitle('Accounts');
    	$params = $this->getParameters();
		return $this->display('/account/index.phtml', compact('data'));
	}

	public function post() {
		
	}

}