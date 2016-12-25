<?php

/**
 * Test case for the extension "qsextra.queues.QsQueueManager".
 * @see QsQueueManager
 */
class QsQueueManagerTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qsextra.queues.*');
	}

	/**
	 * Creates test queue manager.
	 * @return QsQueueManager queue manager instance.
	 */
	protected function createQueueManager() {
		$methodsList = array(
			'init',
		);
		$queueManager = $this->getMock('QsQueueManager',$methodsList);
		return $queueManager;
	}

	/**
	 * Creates test queue.
	 * @return QsQueue queue instance.
	 */
	protected function createQueue() {
		$methodsList = array(
			'create',
			'destroy',
			'exists',
			'add',
			'get',
			'remove',
		);
		$queue = $this->getMock('QsQueue',$methodsList);
		return $queue;
	}

	public function testSetGet() {
		$queueManager = $this->createQueueManager();
		
		$testQueueClassName = 'TestQueueClassName';
		$this->assertTrue($queueManager->setQueueClassName($testQueueClassName), 'Unable to set queue class name!');
		$this->assertEquals($queueManager->getQueueClassName(), $testQueueClassName, 'Unable to set queue class name correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testAddQueue() {
		$queueManager = $this->createQueueManager();
		
		$testQueueName = 'testQueueName';
		$testQueue = $this->createQueue();

		$this->assertTrue($queueManager->addQueue($testQueueName, $testQueue), 'Unable to add queue object!');
		
		$returnedQueue = $queueManager->getQueue($testQueueName);
		$this->assertEquals($testQueueName, $returnedQueue->getName(), 'Added queue has wrong name!');
	}

	/**
	 * @depends testAddQueue
	 */
	public function testAddQueueAsConfig() {
		$queueManager = $this->createQueueManager();
		
		$testQueue = $this->createQueue();
		$testQueueClassName = get_class($testQueue);
		
		$testQueueName = 'test_queue_name';
		$testQueueConfig = array(
			'class' => $testQueueClassName
		);
		$this->assertTrue($queueManager->addQueue($testQueueName, $testQueueConfig), 'Unable to add queue as config!');
		
		$returnedQueue = $queueManager->getQueue($testQueueName);
		$this->assertTrue(is_object($returnedQueue), 'Unable to get queue added by config!');
		$this->assertEquals($testQueueClassName, get_class($returnedQueue), 'Added by config queue has wrong class name!');
	}

	/**
	 * @depends testAddQueueAsConfig
	 */
	public function testAddQueueOnlyByName() {
		$queueManager = $this->createQueueManager();
		
		$testQueue = $this->createQueue();
		$testQueueClassName = get_class($testQueue);
		$queueManager->setQueueClassName($testQueueClassName);
		
		$testQueueName = 'test_queue_name';
		$this->assertTrue($queueManager->addQueue($testQueueName), 'Unable to add queue only by name!');
		
		$returnedQueue = $queueManager->getQueue($testQueueName);
		$this->assertTrue(is_object($returnedQueue), 'Unable to get queue added only by name!');
		$this->assertEquals($testQueueClassName, get_class($returnedQueue), 'Added only by name queue has wrong class name!');
	}

	/**
	 * @depends testAddQueue
	 */
	public function testSetQueues() {
		$queueManager = $this->createQueueManager();
		
		$queuesCount = 5;
		$testQueues = array();
		for ($i=1; $i<=$queuesCount; $i++) {
			$testQueueName = 'testQueueName'.$i;
			$testQueue = $this->createQueue();
			$testQueues[$testQueueName] = $testQueue;
		}

		$this->assertTrue($queueManager->setQueues($testQueues), 'Unable to set queues list!');
		$returnedQueues = $queueManager->getQueues();
		$this->assertEquals(count($returnedQueues), count($testQueues), 'Wrong count of the set queues!');
	}

	/**
	 * @depends testSetQueues
	 * @depends testAddQueueOnlyByName
	 */
	public function testSetQueuesOnlyByName() {
		$queueManager = $this->createQueueManager();
		
		$testQueue = $this->createQueue();
		$testQueueClassName = get_class($testQueue);
		$queueManager->setQueueClassName($testQueueClassName);
		
		$queuesCount = 5;
		$testQueues = array();
		for ($i=1; $i<=$queuesCount; $i++) {
			$testQueueName = 'testQueueName'.$i;
			$testQueues[] = $testQueueName;
		}
		
		$this->assertTrue($queueManager->setQueues($testQueues), 'Unable to set queue names list!');
		$returnedQueues = $queueManager->getQueues();
		$this->assertEquals(count($returnedQueues), count($testQueues), 'Wrong count of the set queues!');
		
		for ($i=1; $i<=$queuesCount; $i++) {
			$testQueueName = 'testQueueName'.$i;
			$this->assertTrue(is_object($returnedQueues[$testQueueName]), 'Returned queue is not an object!');
		}
	}
}
