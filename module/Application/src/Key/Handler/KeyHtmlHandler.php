<?php
namespace Key\Handler;

use ToroHook;

use Application\Handler\HtmlHandler;

class KeyHtmlHandler extends HtmlHandler {

	protected $authAdapter = 'Application\Authentication\Authentication';

    public function get($action=null) {
    	$this->setTitle('Api Keys');
    	$params = $this->getParameters();
		return $this->display('/key/index.phtml', compact('data'));
    }
    
}
