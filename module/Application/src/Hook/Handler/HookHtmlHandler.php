<?php
namespace Hook\Handler;

use ToroHook;

use Application\Handler\HtmlHandler;

class HookHtmlHandler extends HtmlHandler {

	protected $authAdapter = 'Application\Authentication\Authentication';

    public function get($action=null) {
    	$this->setTitle('Web Hooks');
    	$params = $this->getParameters();
		return $this->display('/hook/index.phtml', compact('data'));
    }
    
}
