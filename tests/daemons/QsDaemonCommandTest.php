<?php

/**
 * Test case for the extension "qsextra.daemons.QsDaemonCommand".
 * @see QsDaemonCommand
 */
class QsDaemonCommandTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qsextra.daemons.*');
	}

	public function testSetGet() {
		$daemonCommand = new QsDaemonCommand(null, null);
		
		$testDeamonActions = array(
			'testAction1',
			'testAction2'
		);
		$this->assertTrue($daemonCommand->setDaemonActions($testDeamonActions), 'Unable to set deamon actions!');
		$this->assertEquals($daemonCommand->getDaemonActions(), $testDeamonActions, 'Unable to set deamon actions correctly!');
		
		$testIterationInterval = rand(1, 100);
		$this->assertTrue($daemonCommand->setIterationInterval($testIterationInterval), 'Unable to set deamon iteration interval!');
		$this->assertEquals($daemonCommand->getIterationInterval(), $testIterationInterval, 'Unable to set deamon iteration interval correctly!');
		
		$testDaemonOptions = array(
			'test_name_1' => 'test_value_1',
			'test_name_2' => 'test_value_2'
		);
		$this->assertTrue($daemonCommand->setDaemonOptions($testDaemonOptions), 'Unable to set deamon options!');
		$this->assertEquals($daemonCommand->getDaemonOptions(), $testDaemonOptions, 'Unable to set deamon options correctly!');
	}
}
