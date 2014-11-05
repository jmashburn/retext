<?php
namespace Retext\Handler;

use ToroHook;

use Application\Handler\HtmlHandler;

use Services_Twilio as Twilio;

class RetextHtmlHandler extends HtmlHandler {

	protected $authAdapter = 'Application\Authentication\Authentication';

    public function get($action=null) {

    	$config = \Config::getConfig('twilio');
    	$twilio = new Twilio($config['sid'], $config['token']);

    	$message = $twilio->account->messages->getIterator()

    	print_r($messages);
    	die();

    }

    public function post($action = null) {
    	die('asdfa');
    }
}
