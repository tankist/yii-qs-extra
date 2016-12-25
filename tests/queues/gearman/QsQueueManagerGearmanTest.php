<?php

/**
 * Test case for the extension "qsextra.queues.gearman.QsQueueManagerGearman".
 * @see QsQueueManagerGearman
 */
class QsQueueManagerGearmanTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qsextra.queues.*');
		Yii::import('qsextra.queues.gearman.*');
	}

	// Tests:

	public function testCreate() {
		if (!extension_loaded('gearman')) {
			$this->markTestSkipped('PHP extension "gearman" is required!');
		}
		
		$componentConfig = array(
			'class' => 'QsQueueManagerGearman'
		);
		$queueManager = Yii::createComponent($componentConfig);
		$this->assertTrue(is_object($queueManager), 'Unable to create "QsQueueManagerGearman" component!');
	}

	public function testGetDefaultQueueClassName() {
		$queueManager = Yii::createComponent('QsQueueManagerGearman');
		
		$defaultQueueClassName = $queueManager->getQueueClassName();
		$this->assertEquals('QsQueueGearman', $defaultQueueClassName, 'Wrong default queue class name!');
	}
}
