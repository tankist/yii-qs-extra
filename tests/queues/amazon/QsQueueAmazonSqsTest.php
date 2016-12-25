<?php

/**
 * Test case for the extension "qsextra.queues.amazon.QsQueueAmazonSqs".
 * @see QsQueueAmazonSqs
 */
class QsQueueAmazonSqsTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qsextra.queues.*');
		Yii::import('qsextra.queues.amazon.*');
	}

	public function tearDown() {
		$queueManager = $this->createQueueManager();
		$amazonSqs = $queueManager->getAmazonSqs();
		$response = $amazonSqs->listQueues();
		$queueUrls = $response->get('QueueUrls');
		foreach ($queueUrls as $queueUrl) {
			if (strpos($queueUrl, 'test') !== false) {
				try {
				$amazonSqs->deleteQueue(array(
					'QueueUrl' => $queueUrl
				));
				} catch (Exception $exception) {
					// shutdown exception
				}
			}
		}
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

	/**
	 * Creates test queue instance.
	 * @return QsQueueAmazonSqs queue instance.
	 */
	protected function createQueue() {
		$componentConfig = array(
			'class' => 'QsQueueAmazonSqs'
		);
		$queue = Yii::createComponent($componentConfig);
		$queueManager = $this->createQueueManager();
		$queue->setManager($queueManager);
		return $queue;
	}

	// Tests:

	public function testCreate() {
		$componentConfig = array(
			'class' => 'QsQueueAmazonSqs'
		);
		$queue = Yii::createComponent($componentConfig);
		$this->assertTrue(is_object($queue), 'Unable to create "QsQueueAmazonSqs" component!');
	}

	/**
	 * @depends testCreate
	 */
	public function testSetGet() {
		$queue = Yii::createComponent('QsQueueAmazonSqs');
		
		$testUrl = '/test/url';
		$this->assertTrue($queue->setUrl($testUrl), 'Unable to set url!');
		$this->assertEquals($queue->getUrl(), $testUrl, 'Unable to set url correctly!');
		
		$testUrlName = 'test_url_name';
		$this->assertTrue($queue->setUrlName($testUrlName), 'Unable to set url name!');
		$this->assertEquals($queue->getUrlName(), $testUrlName, 'Unable to set url name correctly!');

		$testAttributes = array(
			'test_param_1' => 'test_value_1',
			'test_param_2' => 'test_value_2',
		);
		$this->assertTrue($queue->setAttributes($testAttributes), 'Unable to set attributes!');
		$this->assertEquals($queue->getAttributes(), $testAttributes, 'Unable to set attributes correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testComposeAttributes() {
		$queue = Yii::createComponent('QsQueueAmazonSqs');
		$queueManager = Yii::createComponent('QsQueueManagerAmazonSqs');
		$queue->setManager($queueManager);

		$testDefaultAttributes = array(
			'default_key_1' => 'default_value_1',
			'default_key_2' => 'default_value_2',
		);
		$queueManager->setDefaultQueueAttributes($testDefaultAttributes);

		$testAttributes = array(
			'key_1' => 'value_1',
			'key_2' => 'value_2',
		);
		$queue->setAttributes($testAttributes);

		$expectedComposedOptions = array_merge($testDefaultAttributes, $testAttributes);
		$composedAttributes = $queue->composeAttributes();
		$this->assertEquals($expectedComposedOptions, $composedAttributes, 'Unable to compose attributes correctly!');
	}

	/**
	 * @depends testComposeAttributes
	 */
	public function testCreateQueue() {
		$queue = $this->createQueue();
		
		$testQueueName = 'test_create_queue_name_' . time();
		$queue->setName($testQueueName);
		$this->assertTrue($queue->create(), 'Unable to create queue!');
		
		$queueUrl = $queue->getUrl();
		$this->assertTrue(!empty($queueUrl), 'Url is empty for the created queue!');
	}

	/**
	 * @depends testCreateQueue
	 */
	public function testDestroyQueue() {
		$queue = $this->createQueue();
		
		$testQueueName = 'test_destroy_queue_name_' . time();
		$queue->setName($testQueueName);
		$queue->create();
		
		$this->assertTrue($queue->destroy(), 'Unable to destroy queue!');
		
		$queueUrl = $queue->getUrl();
		$this->assertTrue(empty($queueUrl), 'Url is not empty for the destroyed queue!');
	}

	/**
	 * @depends testDestroyQueue
	 */
	public function testQueueExists() {
		$queue = $this->createQueue();
		$testQueueName = 'test_exists_queue_name_' . time();
		$queue->setName($testQueueName);

		$this->assertFalse($queue->exists(), 'Not created queue exists!');
		
		$queue->create();
		$this->assertTrue($queue->exists(), 'Created queue not exists!');
		
		$queue->destroy();
		$this->assertFalse($queue->exists(), 'Destroyed queue exists!');
	}

	/**
	 * @depends testQueueExists
	 */
	public function testAddItem() {
		$queue = $this->createQueue();
		$testQueueName = 'test_add_queue_name_' . time();
		$queue->setName($testQueueName);
		$queue->create();
		
		$testQueueItem = new QsQueueItem();
		$testQueueItemData = array(
			'data_key_1' => 'data_value_1',
			'data_key_2' => 'data_value_2',
		);
		$testQueueItem->data = $testQueueItemData;
		
		$this->assertTrue($queue->add($testQueueItem)>0, 'Unable to add item!');
		$this->assertTrue($testQueueItem->id !== null, 'Queue item id has not been updated!');
	}

	/**
	 * @depends testAddItem
	 */
	public function testGetItem() {
		$queue = $this->createQueue();
		$testQueueName = 'test_get_queue_name_' . time();
		$queue->setName($testQueueName);
		$queue->create();

		$testQueueItem = new QsQueueItem();
		$testQueueItemData = array(
			'data_key_1' => 'data_value_1',
			'data_key_2' => 'data_value_2',
		);
		$testQueueItem->data = $testQueueItemData;
		$this->assertTrue($queue->add($testQueueItem), 'Unable to add item!');
		
		unset($queue);
		sleep(15);
		
		$queue = $this->createQueue();
		$queue->setName($testQueueName);
		$queue->create();
		
		$returnedQueueItem = $queue->get();
		
		$this->assertTrue(is_object($returnedQueueItem), 'Unable to get item!');
		$this->assertEquals($testQueueItemData, $returnedQueueItem->getData(), 'Wrong item data returned!');
		
		unset($queue);
		sleep(15);
		
		$queue = $this->createQueue();
		$queue->setName($testQueueName);
		$queue->create();
		
		$returnedQueueItem = $queue->get();
		$this->assertTrue(empty($returnedQueueItem), 'Item present in the queue after it has been already get!');
	}

	/**
	 * @depends testGetItem
	 */
	public function testRemoveItem() {
		$queue = $this->createQueue();
		$testQueueName = 'test_remove_queue_name_' . time();
		$queue->setName($testQueueName);
		$queue->create();
		
		$testQueueItem = new QsQueueItem();
		$testQueueItemData = array(
			'data_key_1' => 'data_value_1',
			'data_key_2' => 'data_value_2',
		);
		$testQueueItem->data = $testQueueItemData;
		$queue->add($testQueueItem);
		
		unset($queue);
		sleep(10);
		
		$queue = $this->createQueue();
		$queue->setName($testQueueName);
		$queue->create();
		
		$returnedQueueItem = $queue->get();
		$this->assertTrue(is_object($returnedQueueItem), 'Unable to get queue item!');
		$this->assertTrue($queue->remove($returnedQueueItem->getHandler()), 'Unable to remove item!');
		
		unset($queue);
		sleep(10);
		
		$queue = $this->createQueue();
		$queue->setName($testQueueName);
		$queue->create();
		
		$returnedQueueItem = $queue->get();
		$this->assertTrue(empty($returnedQueueItem), 'Item present in the queue after it has been already get!');
	}
}
