<?php

/**
 * Test case for the extension "qsextra.queues.QsQueueManagerAmazonSqs".
 * @see QsQueueManagerAmazonSqs
 */
class QsQueueManagerAmazonSqsTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qsextra.queues.*');
		Yii::import('qsextra.queues.amazon.*');
	}

	/**
	 * Returns the test AWS key.
	 * @return string AWS key.
	 */
	protected function getAwsKey() {
		$awsKey = defined('AWS_KEY') ? AWS_KEY : 'AKIAJKOLV5CNLYHH76MQ';
		return $awsKey;
	}

	/**
	 * Returns the test AWS secret key.
	 * @return string AWS secret key.
	 */
	protected function getAwsSecretKey() {
		$awsSecretKey = defined('AWS_SECRET_KEY') ? AWS_SECRET_KEY : 'h5PCmACxFqvuR2cn+ohKr5PdLQ5F3cmcya2uEk/4';
		return $awsSecretKey;
	}

	/**
	 * Creates test queue manager instance.
	 * @return QsQueueManagerAmazonSqs queue manager instance.
	 */
	protected function createQueueManager() {
		$componentConfig = array(
			'class' => 'QsQueueManagerAmazonSqs',
			'awsKey' => $this->getAwsKey(),
			'awsSecretKey' => $this->getAwsSecretKey(),
		);
		$queueManager = Yii::createComponent($componentConfig);
		return $queueManager;
	}

	// Tests:

	public function testCreate() {
		$componentConfig = array(
			'class' => 'QsQueueManagerAmazonSqs'
		);
		$queueManager = Yii::createComponent($componentConfig);
		$this->assertTrue(is_object($queueManager), 'Unable to create "QsQueueManagerAmazonSqs" component!');
	}

	/**
	 * @depends testCreate
	 */
	public function testSetGet() {
		$queueManager = Yii::createComponent('QsQueueManagerAmazonSqs');
		
		$testAwsSdkAutoloaderPath = '/test/amazon/sdk/library/path';
		$this->assertTrue($queueManager->setAwsSdkAutoloaderPath($testAwsSdkAutoloaderPath), 'Unable to set amazon sdk library path!');
		$this->assertEquals($testAwsSdkAutoloaderPath, $queueManager->getAwsSdkAutoloaderPath(), 'Unable to set amazon sdk autoloader path correctly!');
		
		$testAmazonSqs = new stdClass();
		$this->assertTrue($queueManager->setAmazonSqs($testAmazonSqs), 'Unable to set amazon sqs object!');
		$this->assertEquals($queueManager->getAmazonSqs(), $testAmazonSqs, 'Unable to set amazon sqs object correctly!');
		
		$testAwsKey = 'test_aws_key';
		$this->assertTrue($queueManager->setAwsKey($testAwsKey), 'Unable to set AWS key!');
		$this->assertEquals($queueManager->getAwsKey(), $testAwsKey, 'Unable to set AWS key correctly!');
		
		$testAwsSecretKey = 'test_aws_secret_key';
		$this->assertTrue($queueManager->setAwsSecretKey($testAwsSecretKey), 'Unable to set AWS secret key!');
		$this->assertEquals($queueManager->getAwsSecretKey(), $testAwsSecretKey, 'Unable to set AWS secret key correctly!');
		
		$testDefaultQueueAttributes = array(
			'test_param_1' => 'test_value_1',
			'test_param_2' => 'test_value_2',
		);
		$this->assertTrue($queueManager->setDefaultQueueAttributes($testDefaultQueueAttributes), 'Unable to set default queue attributes!');
		$this->assertEquals($queueManager->getDefaultQueueAttributes(), $testDefaultQueueAttributes, 'Unable to set default queue attributes correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultAwsSdkAutoloaderPath() {
		$queueManager = Yii::createComponent('QsQueueManagerAmazonSqs');
		
		$defaultAmazonSdkLibraryPath = $queueManager->getAwsSdkAutoloaderPath();
		$this->assertTrue(!empty($defaultAmazonSdkLibraryPath), 'Unable to get default amazon sdk library path!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultAmazonSqs() {
		$queueManager = $this->createQueueManager();
		
		$defaultAmazonSqs = $queueManager->getAmazonSqs();
		$this->assertTrue(is_object($defaultAmazonSqs), 'Unable to get default amazon sqs object!');
	}
}
