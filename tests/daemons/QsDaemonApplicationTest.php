<?php

/**
 * Test case for the extension "qsextra.daemons.QsDaemonApplication".
 * @see QsDaemonApplication
 */
class QsDaemonApplicationTest extends CTestCase {
	/**
	 * @var CApplication application instance backup.
	 */
	protected $_applicationBackup = null;

	public static function setUpBeforeClass() {
		Yii::import('qsextra.daemons.*');
	}

	public function setUp() {
		$this->_applicationBackup = Yii::app();
		Yii::setApplication(null);
		
		$this->clearTestDirectories();
		
		$dirname = $this->_applicationBackup->basePath.'/runtime/test_daemons';
		mkdir($dirname, 0777);
	}

	public function tearDown() {
		Yii::setApplication(null);
		Yii::setApplication($this->_applicationBackup);
		
		$this->killDaemons();
		$this->clearTestDirectories();
	}

	/**
	 * Clears the test directories.
	 * @return boolean success.
	 */
	protected function clearTestDirectories() {
		$testFileSourcePath = Yii::getPathOfAlias('application.runtime');
		
		$directory = $testFileSourcePath.'/test_daemons';
		if (file_exists($directory)) {
			$command = "rm -r {$directory}";
			exec($command);
		}
		$directory = $testFileSourcePath.'/test_daemon_logs';
		if (file_exists($directory)) {
			$command = "rm -r {$directory}";
			exec($command);
		}
		return true;
	}

	/**
	 * Kills all running daemons.
	 */
	protected function killDaemons() {
		//@todo;
	}

	/**
	 * Creates test daemon application configuration.
	 * @return array application configuration.
	 */
	protected function createTestApplicationConfig() {
		$config = array(
			'basePath' => $this->_applicationBackup->basePath,
			'name' => 'TestDaemonApplication',
			'commandPath' => $this->_applicationBackup->basePath.'/runtime/test_daemons',
			'defaultDaemonLogPath' => $this->_applicationBackup->basePath.'/runtime/test_daemon_logs',
			'defaultDaemonOptions' => array(
				'appRunAsGID' => '510',
				'appRunAsUID' => '511'
			),
			'preload' => array('log'),
			'components' => array(
				'log' => array(
					'class' => 'CLogRouter',
					'routes' => array(
						array(
							'class' => 'QsDaemonLogRoute',
							'levels' => 'error, warning',
						),
						array(
							'class' => 'QsDaemonLogRoute',
							'categories' => 'qs.daemons',
						),
					),
				),
			),
		);
		return $config;
	}

	/**
	 * Creates test daemon application.
	 * @return QsDaemonApplication application instance.
	 */
	protected function createTestApplication() {
		$config = $this->createTestApplicationConfig();
		$application = Yii::createApplication('QsDaemonApplication', $config);
		return $application;
	}

	/**
	 * Composes a test daemon command source code.
	 * @param array $params daemon command parameters.
	 * @return string daemon command source code.
	 */
	protected function renderTestDaemonCode(array $params) {
		$sourceCode = '
		<?php
/** 
 * Test Daemon, which serves test purposes.
 * @author '.$params['authorName'].' <'.$params['authorEmail'].'> 
 */
class '.$params['className'].' extends QsDaemonCommand {
	public function actionIndex() {
		$message = "'.$params['logMessageText'].' at ".date("Y-m-d H:i:s", strtotime("NOW"));
		$this->log($message);
		throw new Exception("'.$params['exceptionMessageText'].'");
	}
}
		';
		return $sourceCode;
	}

	/**
	 * Create a PHP file, containing source code of the daemon command.
	 * @param array $params daemon command parameters.
	 * @return boolean success.
	 */
	protected function prepareTestDaemon(array $params=array()) {
		$defaultParams = array(
			'className' => 'TestDaemon',
			'authorName' => 'Test Author',
			'authorEmail' => 'testauthor@quart-soft.com',
			'logMessageText' => 'Test message text',
			'exceptionMessageText' => 'Test terminate exception',
		);
		
		$params = array_merge($defaultParams, $params);
		$daemonSourceCode = $this->renderTestDaemonCode($params);
		
		$dirname = $this->_applicationBackup->basePath.'/runtime/test_daemons';
		$daemonClassName = $params['className'];
		$daemonFileName = $dirname.'/'.$daemonClassName.'.php';
		return file_put_contents($daemonFileName, $daemonSourceCode)>0;
	}

	/**
	 * Update the given application instance to throw a Exception at "onEndRequest" event.
	 * This prevents the program to be ended at {@link CApplication::end()}.
	 * @param CApplication $application initial application instance.
	 * @return boolean success.
	 */
	protected function convertApplicationEndToException(CApplication $application) {
		$application->onEndRequest = function() {
			throw new Exception('End');
		};
		return true;
	}

	// Tests:

	public function testSetGet() {
		$application = $this->createTestApplication();
		
		$testDefaultDaemonOptions = array(
			'test_option_1' => 'test_value_1',
			'test_option_2' => 'test_value_2',
		);
		$this->assertTrue( $application->setDefaultDaemonOptions($testDefaultDaemonOptions), 'Unable to set default daemon options!');
		$this->assertEquals( $application->getDefaultDaemonOptions(), $testDefaultDaemonOptions, 'Unable to set default daemon options correctly!');		
		
		$testDaemonMap = array(
			'test_daemon_1' => array(
				'test_param' => 'test_value'
			),
			'test_daemon_2' => array(
				'test_param' =>'test_value'
			),
		);
		$this->assertTrue($application->setDaemonMap($testDaemonMap), 'Unable to set daemon map!');
		$this->assertEquals($application->getDaemonMap(), $testDaemonMap, 'Unable to set daemon map correctly!');
		
		$testDefaultDaemonLogPath = 'test/default/daemon/log/path';
		$this->assertTrue($application->setDefaultDaemonLogPath($testDefaultDaemonLogPath), 'Unable to set default daemon log path!');
		$this->assertEquals($application->getDefaultDaemonLogPath(), $testDefaultDaemonLogPath, 'Unable to set default daemon log path correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDaemonHelp() {
		$this->prepareTestDaemon();
		$application = $this->createTestApplication();
		$this->convertApplicationEndToException($application);
		
		$testConsoleArguments = array(
			'test_daemon.php',
			'help',
			'test',
		);
		$_SERVER['argv'] = $testConsoleArguments;
		
		ob_start();
		ob_implicit_flush(false);
		try {
			$application->run();
		} catch (Exception $exception) {
			// Catch "onEndRequest" exception.
		}
		$output = ob_get_clean();
		
		$this->assertTrue(!empty($output));
	}

	/**
	 * @depends testGetDaemonHelp
	 */
	public function testAskConfirm() {
		$this->prepareTestDaemon();
		$application = $this->createTestApplication();
		
		$testConsoleArguments = array(
			'test_daemon.php',
			'test',
		);
		$_SERVER['argv'] = $testConsoleArguments;
		
		ob_start();
		ob_implicit_flush(false);
		$application->run();
		$output = ob_get_clean();

		$this->assertTrue(strpos($output, '(yes|no)')!==false, 'No confirmation asked!');
	}

	/**
	 * @depends testGetDaemonHelp
	 */
	public function testAskConfirmNoDaemon() {
		$this->prepareTestDaemon();
		$application = $this->createTestApplication();
		
		$testConsoleArguments = array(
			'test_daemon.php',
			'test',
			'--nodaemon',
		);
		$_SERVER['argv'] = $testConsoleArguments;
		
		ob_start();
		ob_implicit_flush(false);
		$application->run();
		$output = ob_get_clean();
		
		$this->assertTrue(strpos($output, '(yes|no)')!==false, 'No confirmation asked!');
	}

	/**
	 * @depends testAskConfirmNoDaemon
	 */
	public function testLaunchNoDaemon() {
		if (!extension_loaded('posix')) {
			$this->markTestSkipped('Posix PHP extension required');
		}
		
		$terminateExceptionMessageText = 'terminate_test_message';
		$testDaemonParams = array(
			'className'=>'LaunchDaemon',
			'exceptionMessageText'=>$terminateExceptionMessageText,
		);
		$this->prepareTestDaemon($testDaemonParams);
		$application = $this->createTestApplication();
		
		$testConsoleArguments = array(
			'test_daemon.php',
			'launch',
			'--nodaemon',
			'--interactive=0',
		);
		$_SERVER['argv'] = $testConsoleArguments;
		
		$exceptionCaught = false;
		try {
			$application->run();
		} catch(Exception $exception) {
			$this->assertEquals($terminateExceptionMessageText, $exception->getMessage(), 'Wrong exception terminates the daemon!');
			$exceptionCaught = true;
		}
		$this->assertTrue($exceptionCaught, 'Unable to start application in no daemon mode!');
	}

	/**
	 * @depends testLaunchNoDaemon
	 */
	public function testDaemonLog() {
		if (!extension_loaded('posix')) {
			$this->markTestSkipped('Posix PHP extension required');
		}
		
		$testLogMessageText = 'Test message text';
		$testDaemonParams = array(
			'logMessageText' => $testLogMessageText,
		);
		$this->prepareTestDaemon($testDaemonParams);
		$application = $this->createTestApplication();
		
		$testConsoleArguments = array(
			'test_daemon.php',
			'test',
			'--nodaemon',
			'--interactive=0',
		);
		$_SERVER['argv'] = $testConsoleArguments;
		
		try {
			$application->run();
		} catch(Exception $exception) {
			// Catch terminate exception.
		}
		
		$logFileName = System_Daemon::getOption('logLocation');
		$this->assertTrue(file_exists($logFileName), 'Unable to write daemon log!');
		
		$logFileContent = file_get_contents($logFileName);
		$this->assertTrue(strpos($logFileContent, $testLogMessageText) !== false, 'Unable to write message into the daemon log!');
	}

	/**
	 * @depends testLaunchNoDaemon
	 */
	public function testDaemonOptions() {
		if (!extension_loaded('posix')) {
			$this->markTestSkipped('Posix PHP extension required');
		}
		
		$testDaemonParams = array(
			'className'=>'TestDaemon',
			'authorName'=>'Test Author',
			'authorEmail'=>'testauthor@quart-soft.com',
		);
		$this->prepareTestDaemon($testDaemonParams);
		$application = $this->createTestApplication();
		
		$testConsoleArguments = array(
			'test_daemon.php',
			'test',
			'--nodaemon',
			'--interactive=0',
		);
		$_SERVER['argv'] = $testConsoleArguments;
		
		try {
			$application->run();
		} catch (Exception $exception) {
			// Catch terminate exception.
		}
		
		$daemonOptions = System_Daemon::getOptions();
		
		$this->assertEquals($testDaemonParams['authorName'], $daemonOptions['authorName'], 'Unable to set author name!');
		$this->assertEquals($testDaemonParams['authorEmail'], $daemonOptions['authorEmail'], 'Unable to set author email!');
		
		$expectedDaemonApplicationName = Yii::app()->name.$testDaemonParams['className'];
		$this->assertEquals($expectedDaemonApplicationName, $daemonOptions['appName'], 'Unable to set daemon application name!');
	}
}
