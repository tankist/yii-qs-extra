<?php

/**
 * Test case for the extension "qsextra.queues.gearman.QsQueueGearman".
 * @see QsQueueGearman
 */
class QsQueueGearmanTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qsextra.queues.*');
		Yii::import('qsextra.queues.gearman.*');
	}

	/**
	 * Creates a test queue instance.
	 * @return QsQueueGearman queue instance.
	 */
	protected function createQueue() {
		$componentConfig = array(
			'class'=>'QsQueueGearman'
		);
		$queue = Yii::createComponent($componentConfig);
		
		$componentConfig = array(
			'class'=>'QsQueueManagerGearman'
		);
		$queueManager = Yii::createComponent($componentConfig);
		
		$queue->setManager($queueManager);
		return $queue;
	}

	// Tests:

	public function testCreate() {
		$componentConfig = array(
			'class' => 'QsQueueGearman'
		);
		$queueManager = Yii::createComponent($componentConfig);
		$this->assertTrue(is_object($queueManager), 'Unable to create "QsQueueGearman" component!');
	}

	/**
	 * @depends testCreate
	 */
	public function testSetGet() {
		if (!extension_loaded('gearman')) {
			$this->markTestSkipped('PHP extension "gearman" is required!');
		}

		$queue = $this->createQueue();
		
		$testGearmanServerHost = rand(0,255).'.'.rand(0,255).'.'.rand(0,255).'.'.rand(0,255);
		$testGearmanServerPort = rand(1000, 5000);
		$testGearmanServers = $testGearmanServerHost.':'.$testGearmanServerPort;
		
		$this->assertTrue($queue->setGearmanServers($testGearmanServers), 'Unable to set Gearman servers!');
		$this->assertEquals($queue->getGearmanServers(), $testGearmanServers, 'Unable to set Gearman servers correctly!');

		$testGearmanClient = new GearmanClient();
		$this->assertTrue($queue->setGearmanClient($testGearmanClient), 'Unable to set Gearman client!');
		$this->assertEquals($queue->getGearmanClient(), $testGearmanClient, 'Unable to set Gearman client correctly!');
		
		$testGearmanWorker = new GearmanWorker();
		$this->assertTrue($queue->setGearmanWorker($testGearmanWorker), 'Unable to set Gearman worker!');
		$this->assertEquals($queue->getGearmanWorker(), $testGearmanWorker, 'Unable to set Gearman worker correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultGearmanClient() {
		$queue = $this->createQueue();
		
		$defaultGearmanClient = $queue->getGearmanClient();
		$this->assertTrue(is_object($defaultGearmanClient), 'Unable to get default Gearman client!');
		$this->assertTrue(is_a($defaultGearmanClient, 'GearmanClient'), 'Default Gearman client has wrong type!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultGearmanWorker() {
		$queue = $this->createQueue();
		
		$gearmanWorker = $queue->getGearmanWorker();
		$this->assertTrue(is_object($gearmanWorker), 'Unable to get default Gearman worker!');
		$this->assertTrue(is_a($gearmanWorker, 'GearmanWorker'), 'Unable to get default Gearman worker!');
	}

	/**
	 * @depends testGetDefaultGearmanClient
	 * @depends testGetDefaultGearmanWorker
	 */
	public function testCreateQueue() {
		$queue = $this->createQueue();
		
		$testQueueName = 'test_queue_name';
		$queue->setName($testQueueName);
		$this->assertTrue($queue->create(), 'Unable to create queue!');
	}

	/**
	 * @depends testCreateQueue
	 */
	public function testDestroyQueue() {
		$queue = $this->createQueue();
		
		$testQueueName = 'test_delete_queue_name';
		$queue->setName($testQueueName);
		$queue->create();
		
		$this->assertTrue($queue->destroy(), 'Unable to destroy queue!');
	}

	/**
	 * @depends testDestroyQueue
	 */
	public function testQueueExists() {
		$queue = $this->createQueue();
		$testQueueName = 'test_exists_queue_name';
		$queue->setName($testQueueName);

		//$this->assertFalse( $queue->exists(), 'Not created queue exists!' );
		
		$queue->create();
		$this->assertTrue( $queue->exists(), 'Created queue not exists!' );
		
		/*$queue->destroy();
		$this->assertFalse( $queue->exists(), 'Destroyed queue exists!' );*/
	}

	/**
	 * @depends testQueueExists
	 */
	public function testAddItem() {
		$queue = $this->createQueue();
		$testQueueName = 'test_add_queue_name';
		$queue->setName($testQueueName);
		$queue->create();
		
		$testQueueItem = new QsQueueItem();
		$testQueueItemData = array(
			'data_key_1' => 'data_value_1',
			'data_key_2' => 'data_value_2',
		);
		$testQueueItem->data = $testQueueItemData;

		$this->assertTrue($queue->add($testQueueItem), 'Unable to add item!');
		$this->assertTrue($testQueueItem->id !== null, 'Queue item id has not been updated!');
	}

	/**
	 * @depends testAddItem
	 */
	public function testGetItem() {
		$queue = $this->createQueue();
		$testQueueName = 'test_get_queue_name';
		$queue->setName($testQueueName);
		$queue->create();

		$testQueueItem = new QsQueueItem();
		$testQueueItemData = array(
			'data_key_1' => 'data_value_1',
			'data_key_2' => 'data_value_2',
		);
		$testQueueItem->data = $testQueueItemData;
		$queue->add($testQueueItem);
		
		//sleep(2);
		
		$returnedQueueItem = $queue->get();
		
		$this->assertTrue(is_object($returnedQueueItem), 'Unable to get item!');
		$this->assertEquals($testQueueItemData, $returnedQueueItem->data, 'Wrong item data returned!');
	}

	/**
	 * @depends testGetItem
	 */
	public function testRemoveItem() {
		$queue = $this->createQueue();
		$testQueueName = 'test_remove5_queue_name';
		$queue->setName($testQueueName);
		$queue->create();

		$testQueueItem = new QsQueueItem();
		$testQueueItemData = array(
			'data_key_1' => 'data_value_1',
			'data_key_2' => 'data_value_2',
		);
		$testQueueItem->setData($testQueueItemData);
		$queue->add($testQueueItem);
		
		$returnedQueueItem = $queue->get();
		
		$this->assertTrue($queue->remove($returnedQueueItem->getHandler()), 'Unable to remove item!');
		
		$returnedQueueItem = $queue->get();
		$this->assertTrue(empty($returnedQueueItem), 'Item present in the queue after it has been removed!');
	}
}
