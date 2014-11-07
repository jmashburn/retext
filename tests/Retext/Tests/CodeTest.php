<?php

class Retext_Tests_CodeTest extends Retext_Tests_TestCase {
	
	public $handler;
	public $request;

	private $codeData = array();

	public function setUp() {
		parent::setUp();

		$this->codeData = array(
			array('code' => 'TEST_1EE2D2FF2'.rand(), 'message' => 'Test Message 1')
		);

		$this->handler = new Retext\Code\Handler\CodeHandler();
		$this->handler->getResponse()->setSendHeaders(false);
		$this->request = new \Http\Request();
		// Create root Identity
		$this->identity = new \Application\Identity\Identity('testuser', 'root');
		$this->identity->setValid(true);
		$this->handler->setIdentity($this->identity);

		$this->handler->setRequest($this->request);
		$this->handler->getAcl()->addRole('root')->addResource('Retext\Code\Handler\CodeHandler');
	}

	public function testCanCreateCode() {
		$this->request->setMethod('POST');
		$this->request->setPost($this->codeData[0]);
		$result = $this->handler->post();
		$result = json_decode($result, true);
		$this->assertEquals($this->handler->getResponse()->getStatusCode(), 200);
		$this->assertEquals($result['APIResponse']['responseData']['codes'][0]['code'], $this->codeData[0]['code']);

		$this->codeData[0]['key'] = $result['APIResponse']['responseData']['codes'][0]['key'];
		return $this->codeData;
	}

   /**
    * @depends testCanCreateCode
    */
	public function testCanGetCode(array $codeData) {
		$result = $this->handler->get($codeData[0]['key']);
		$result = json_decode($result, true);
		$this->assertEquals($result['APIResponse']['responseData']['codes'][0]['code'], $codeData[0]['code']);
	}

   /**
    * @depends testCanCreateCode
    */
	public function testCanDeleteCode(array $codeData) {
		$result = $this->handler->delete($codeData[0]['key']);
		$result = json_decode($result->getContent(), true);
		$this->assertEquals($result['APIResponse']['responseData']['codes'][0]['deleted'], "true");
	}   

}
