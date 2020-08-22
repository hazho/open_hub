<?php

class HubTest extends CTestCase
{
	public function testNow()
	{
		$now = time();
		$now2 = HUB::now();
		$this->assertEquals($now, $now2);
	}

	public function testEncrypt()
	{
		$string = 'Hello World';
		$mockSalt = Yii::app()->params['encryptionSalt'] = '123456';
		$mockResult = sha1($string . $mockSalt);

		$this->assertEquals($mockResult, HUB::encrypt($string));
	}

	public function testIsViewExists()
	{
		$viewPath = 'application.tests.views.helloWorld';
		$result = HUB::isViewExists($viewPath);
		$this->assertTrue($result);
	}

	public function testRenderPartial()
	{
		$viewPath = 'application.tests.views.helloWorld';
		$result = HUB::renderPartial($viewPath, array('var1' => 'Human'), true);
		$mockResult = 'Hello World, Human';
		$this->assertEquals($mockResult, $result);
	}
}
