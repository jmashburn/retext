<?php
namespace Retext\Code\Handler;

use ToroHook;

use Application\Handler\HtmlHandler;

use Services_Twilio as Twilio;

class CodeHtmlHandler extends HtmlHandler {

	protected $authAdapter = 'Application\Authentication\Authentication';

    public function get($action=null) {
    	$this->setTitle('Retext Codes');
    	$params = $this->getParameters();
		return $this->display('retext/code/index.phtml', compact('data'));
    }
    
}
