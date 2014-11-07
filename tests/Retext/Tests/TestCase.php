<?php

class Retext_Tests_TestCase extends PHPUnit_Framework_TestCase
{
	public function setUp() {
		\Application\Db\WebPDO::setEnv('test');
	}
}