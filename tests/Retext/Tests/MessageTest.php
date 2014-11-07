<?php

class Retext_Tests_MessageTest extends Retext_Tests_TestCase {
	
	public $handler;
	public $request;

	private $messageData = array();

	public function setUp() {
		parent::setUp();

		$this->messageData = array(
			array('code' => 'TEST_1EE2D2FF2'.rand(), 'message_received' => 'Message Received', 'message_sent' => 'Message Sent', 'status' => 'pending')
		);

		$this->handler = new Retext\Message\Handler\MessageHandler();
		$this->handler->getResponse()->setSendHeaders(false);
		$this->request = new \Http\Request();
		// Create root Identity
		$this->identity = new \Application\Identity\Identity('testuser', 'root');
		$this->identity->setValid(true);
		$this->handler->setIdentity($this->identity);

		$this->handler->setRequest($this->request);
		$this->handler->getAcl()->addRole('root')->addResource('Retext\Message\Handler\MessageHandler');
	}

	public function testCanCreateMessage() {
		$this->request->setMethod('POST');
		$this->request->setPost($this->messageData[0]);

		$result = $this->handler->post();
		$result = json_decode($result, true);
		$this->assertEquals($this->handler->getResponse()->getStatusCode(), 200);
		$this->assertEquals($result['APIResponse']['responseData']['messages'][0]['code'], $this->messageData[0]['code']);

		$this->messageData[0]['key'] = $result['APIResponse']['responseData']['messages'][0]['key'];
		return $this->messageData;
	}

   /**
    * @depends testCanCreateMessage
    */
	public function testCanGetMessage(array $messageData) {
		$result = $this->handler->get($messageData[0]['key']);
		$result = json_decode($result, true);
		$this->assertEquals($result['APIResponse']['responseData']['messages'][0]['code'], $messageData[0]['code']);
	}

   /**
    * @depends testCanCreateMessage
    */
	public function testCanDeleteMessage(array $messageData) {
		$result = $this->handler->delete($messageData[0]['key']);
		$result = json_decode($result->getContent(), true);
		$this->assertEquals($result['APIResponse']['responseData']['messages'][0]['deleted'], "true");
	}   

}
