<?php

/**
 * Test case for the extension "qsextra.queues.QsQueue".
 * @see QsQueue
 */
class QsQueueTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qsextra.queues.*');
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

	// Tests:

	public function testSetGet() {
		$queue = $this->createQueue();
		
		$testName = 'test_queue_name';
		$this->assertTrue($queue->setName($testName), 'Unable to set name!');
		$this->assertEquals($queue->getName(), $testName, 'Unable to set name! correctly');
		
		$testManager = $this->getMock('QsQueueManager');
		$this->assertTrue($queue->setManager($testManager), 'Unable to set manager!');
		$this->assertEquals($queue->getManager(), $testManager, 'Unable to set manager correctly!');
	}
}
