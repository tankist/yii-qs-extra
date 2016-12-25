<?php

/**
 * Test case for the extension "qsextra.queues.QsQueueItem".
 * @see QsQueueItem
 */
class QsQueueItemTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qsextra.queues.*');
	}

	// Tests:

	public function testSetGet() {
		$queueItem = new QsQueueItem();
		
		$testId = rand(1, 1000);
		$this->assertTrue( $queueItem->setId($testId), 'Unable to set id!' );
		$this->assertEquals( $queueItem->getId(), $testId, 'Unable to set id correctly!' );
		
		$testHandler = rand(1, 1000);
		$this->assertTrue( $queueItem->setHandler($testHandler), 'Unable to set handler!' );
		$this->assertEquals( $queueItem->getHandler(), $testHandler, 'Unable to set handler correctly!' );
		
		$testData = array(
			'key1' => 'value1',
			'key2' => 'value2',
		);
		$this->assertTrue( $queueItem->setData($testData), 'Unable to set data!' );
		$this->assertEquals( $queueItem->getData(), $testData, 'Unable to set data correctly!' );
	}

	/**
	 * @depends testSetGet
	 */
	public function testConstruct() {
		$testData = array(
			'test_key_1' => 'test_value_1',
			'test_key_2' => 'test_value_2',
		);
		$queueItem = new QsQueueItem($testData);
		
		$this->assertTrue(is_object($queueItem), 'Unable to create the "QsQueueItem" instance!');
		$this->assertEquals($testData, $queueItem->getData(), 'Unable to set data using constructor!');
	}
}
