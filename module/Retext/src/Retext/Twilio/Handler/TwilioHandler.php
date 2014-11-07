<?php

namespace Retext\Twilio\Handler;

use Db\Mapper\ApiMapper; 

use Application\Handler\AbstractHandler,
    Application\Api\ApiException as Exception;

class TwilioHandler extends AbstractHandler {

	#protected $mapperClass = "Retext\Twillo\Db\KeyMapper";

	protected $authAdapter = 'Application\Authentication\Authentication';

	protected $layout = 'views/layout/blank.phtml';

	public function get() {
		return $this->processIncoming();
	}

	public function post() {
		return $this->processIncoming();
	}

	protected function processIncoming($type = "message") {
		try {	

			$params = $this->getParameters();
			$code = $this->getMapper('Retext\Code\Db\CodeMapper')->findCodeByCode(strtoupper(trim($params['Body'])));
			if (!empty($code[0]['id'])) {
				$code = $code->current();
				$data = array(
					'code' => $code->code,
					'message_sent' => $code->toJson(),
					'message_received' => json_encode($params)
				);
				$message = $this->getMapper('Retext\Message\Db\MessageMapper')->addMessage($data);
				\Application\Log::debug(print_r($message, true));
			} else {
				$code = array('message' => sprintf("Umm, yeah. \"%s\" is not really a thing.", $params['Body']));
			}
			return $this->display('twilio/message.twiml.phtml', compact('code'));
		} catch (Exception $e) {
			return $this->display('exception/exception.json.phtml', $e);
		}
	}
}
