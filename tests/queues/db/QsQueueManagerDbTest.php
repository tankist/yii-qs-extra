<?php

/**
 * Test case for the extension "qsextra.queues.db.QsQueueManagerDb".
 * @see QsQueueManagerDb
 */
class QsQueueManagerDbTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qsextra.queues.*');
		Yii::import('qsextra.queues.db.*');
	}

	// Tests:

	public function testCreate() {
		$componentConfig = array(
			'class' => 'QsQueueManagerDb'
		);
		$queueManager = Yii::createComponent($componentConfig);
		$this->assertTrue(is_object($queueManager), 'Unable to create "QsQueueManagerDb" component!');
	}

	/**
	 * @depends testCreate
	 */
	public function testGetDefaultQueueClassName() {
		$queueManager = Yii::createComponent('QsQueueManagerDb');
		
		$defaultQueueClassName = $queueManager->getQueueClassName();
		$this->assertEquals('QsQueueDb', $defaultQueueClassName, 'Wrong default queue class name!');
	}

	/**
	 * @depends testCreate
	 */
	public function testSetGet() {
		$queueManager = Yii::createComponent('QsQueueManagerDb');
		
		$testQueueTableNamePrefix = 'test_queue_table_name_prefix_';
		$this->assertTrue($queueManager->setQueueTableNamePrefix($testQueueTableNamePrefix), 'Unable to set queue table name prefix!');
		$this->assertEquals($queueManager->getQueueTableNamePrefix(), $testQueueTableNamePrefix, 'Unable to set queue table name prefix correctly!');
	}
}
