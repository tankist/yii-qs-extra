<?php

/**
 * Test case for the extension "qsextra.daemons.behaviors.QsDaemonCommandBehaviorControlComponentActivity".
 * @see QsDaemonCommandBehaviorControlComponentActivity
 */
class QsDaemonCommandBehaviorControlComponentActivityTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qsextra.daemons.*');
		Yii::import('qsextra.daemons.behaviors.*');
	}

	// Tests:

	public function testSetGet() {
		$daemonCommandBehavior = new QsDaemonCommandBehaviorControlComponentActivity();
		
		$testControlledComponentNames = array(
			'testComponent1',
			'testComponent2'
		);
		$this->assertTrue( $daemonCommandBehavior->setControlledComponentNames($testControlledComponentNames), 'Unable to set controlled component names!' );
		$this->assertEquals( $daemonCommandBehavior->getControlledComponentNames(), $testControlledComponentNames, 'Unable to set controlled component names correctly!' );
	}

	/**
	 * @depends testSetGet
	 */
	public function testDeactivateControlledComponents() {
		$daemonCommandBehavior = new QsDaemonCommandBehaviorControlComponentActivity();
		
		$testCompoentName = 'db';
		$testControlledComponentNames = array(
			$testCompoentName
		);
		$daemonCommandBehavior->setControlledComponentNames($testControlledComponentNames);

		$this->assertTrue( $daemonCommandBehavior->deactivateControlledComponents(), 'Unable to deactivate controlled components!' );
		$this->assertEquals(false, Yii::app()->getComponent($testCompoentName)->active , 'Component under control has not been deactivated!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testActivateControlledComponents() {
		$daemonCommandBehavior = new QsDaemonCommandBehaviorControlComponentActivity();
		
		$testComponentName = 'db';
		Yii::app()->getComponent($testComponentName)->active = false;
		
		$testControlledComponentNames = array(
			$testComponentName
		);
		$daemonCommandBehavior->setControlledComponentNames($testControlledComponentNames);
				
		$this->assertTrue( $daemonCommandBehavior->activateControlledComponents(), 'Unable to activate controlled components!' );
		$this->assertEquals(true, Yii::app()->getComponent($testComponentName)->active, 'Component under control has not been activated!');
	}
}
