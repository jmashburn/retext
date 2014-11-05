<?php

namespace Account\Handler;

use Application\Exception as Exception,
	Application\Handler\HtmlHandler;


class LoginHtmlHandler extends HtmlHandler {

	protected $layout = 'views/layout/login.phtml';

	public function get() {
        try {
            if ($this->hasIdentity()) {
                $this->getAuthAdapter()->clearIdentity();
            }
            $params = $this->getParameters(array('redirectUrl' => '/'));
        	return $this->display('account/login.phtml', compact('params'));
        } catch (Exception $e) {
            return $this->display('exception/exception.json.phtml', $e);
        }
    }
}