<?php
namespace Retext\Message\Handler;

use ToroHook;

use Application\Handler\HtmlHandler;

class MessageHtmlHandler extends HtmlHandler {

	protected $authAdapter = 'Application\Authentication\Authentication';

    public function get($action=null) {
    	$this->setTitle('Retext Messages');
    	$params = $this->getParameters();
		return $this->display('retext/message/index.phtml', compact('data'));
    }
    
}
