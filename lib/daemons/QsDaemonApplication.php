<?php
/**
 * QsDaemonApplication class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2008-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * Extension of the {@link CConsoleApplication}, which allows to create system daemons.
 * Daemon application can spawn several different daemons, each represented by the particular
 * {@link QsDaemonCommand} command.
 * 
 * The daemon command classes reside in the directory {@link getCommandPath commandPath}.
 * They also can be configured using (@link daemonMap).
 * 
 * Use {@link QsDaemonLogRoute} to route logs into the daemon log file.
 * 
 * Use {@link defaultDaemonOptions} to specify options, which should be shared 
 * between all daemons.
 * 
 * Example application configuration:
 * <code>
 * array(
 *     'basePath' => dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
 *     'name' => 'DaemonApplication',
 *     'preload' => array('log'),
 *     'components' => array(
 *         'log' => array(
 *             'class' => 'CLogRouter',
 *             'routes' => array(
 *                 array(
 *                     'class' => 'QsDaemonLogRoute',
 *                     'levels' => 'error, warning',
 *                 ),
 *                 array(
 *                     'class' => 'QsDaemonLogRoute',
 *                     'categories' => 'qs.daemons',
 *                 ), 
 *             ),                
 *         ),
 *     ),
 *     'defaultDaemonOptions' => array(
 *         'appRunAsGID' => '510',
 *         'appRunAsUID' => '511'
 *     ),
 *     'daemonMap' => array(
 *         'convert' => array(
 *             'iterationInterval' => 5,
 *         ),
 *     ),
 * )
 * </code>
 *
 * You should create another entry script for the daemon application (for example: "protected/daemon.php"),
 * which should be similar to "yiic.php" script.
 * For example:
 * <code>
 * $projectRoot = dirname(dirname(__FILE__));
 * require_once($projectRoot.'/framework/yii.php');
 *
 * $config = require_once(dirname(__FILE__).'/config/daemon.php');
 *
 * defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));
 *
 * require_once(dirname(__FILE__).'/extensions/qsextra/lib/daemons/QsDaemonApplication.php');
 * Yii::createApplication('QsDaemonApplication', $config)->run();
 * </code>
 *
 * @see System_Daemon
 *
 * @property array $daemonMap public alias of {@link _daemonMap}.
 * @property array $defaultDaemonOptions public alias of {@link _defaultDaemonOptions}.
 * @property string $defaultDaemonLogPath public alias of {@link _defaultDaemonLogPath}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qsextra.daemons
 */
class QsDaemonApplication extends CConsoleApplication {
	/**
	 * @var array mapping from daemon command name to daemon command configurations.
	 * Each command configuration should be an array.
	 * Each configuration array may contain a 'class' element which specifies
	 * the command class name or {@link YiiBase::getPathOfAlias} class path alias.
	 * If the 'class' parameter is not specified it will be automatically determined 
	 * from the files at {@link CConsoleApplication::commandPath}.
	 * The rest name-value pairs in the array are used to initialize
	 * the corresponding command properties.
	 * For example:
	 * <code>
	 * array(
	 *     'email' => array(
	 *         'class' => 'path.to.emailDaemon',
	 *         'iterationInterval' => 3600,
	 *     ),
	 *     'convert' => array(
	 *         'iterationInterval' => 2,
	 *     )
	 * )
	 * </code>
	 */
	protected $_daemonMap = array();
	/**
	 * @var array list of daemon options, which should be applied by default
	 * to any daemon command.
	 * @see System_Daemon::setOptions()
	 */
	protected $_defaultDaemonOptions = array();
	/**
	 * @var string path, which should be used to store daemon logs.
	 * Daemon options parameter 'logLocation' will override this option.
	 * @see System_Daemon::setOptions()
	 */
	protected $_defaultDaemonLogPath = '';

	// Set / Get :

	public function setDaemonMap(array $daemonMap) {
		$this->_daemonMap = $daemonMap;
		return true;
	}

	public function getDaemonMap() {
		return $this->_daemonMap;
	}

	public function setDefaultDaemonOptions(array $defaultDaemonOptions) {
		$this->_defaultDaemonOptions = $defaultDaemonOptions;
		return true;
	}
	
	public function getDefaultDaemonOptions() {
		return $this->_defaultDaemonOptions;
	}

	public function setDefaultDaemonLogPath($defaultDaemonLogPath) {
		if (!is_string($defaultDaemonLogPath)) {
			throw new CException('"'.get_class($this).'::defaultDaemonLogPath" should be a string!');
		}
		$this->_defaultDaemonLogPath = $defaultDaemonLogPath;
		return true;
	}

	public function getDefaultDaemonLogPath() {
		return $this->_defaultDaemonLogPath;
	}

	/**
	 * Constructor.
	 * @param mixed $config application configuration.
	 * If a string, it is treated as the path of the file that contains the configuration;
	 * If an array, it is the actual configuration information.
	 * Please make sure you specify the {@link getBasePath basePath} property in the configuration,
	 * which should point to the directory containing all application logic, template and data.
	 * If not, the directory will be defaulted to 'protected'.
	 */
	public function __construct($config=null) {
		$this->importResources();
		
		if (!is_array($config)) {
			$config = array(
				'basePath' => 'protected'
			);
		}
		if (!array_key_exists('commandPath', $config) ) {
			$config['commandPath'] = realpath($config['basePath']).DIRECTORY_SEPARATOR.'daemons';
		}
		if (!array_key_exists('defaultDaemonLogPath', $config) ) {
			$config['defaultDaemonLogPath'] = realpath($config['basePath']).DIRECTORY_SEPARATOR.'runtime'.DIRECTORY_SEPARATOR.'daemons';
		}

		parent::__construct($config);
	}

	/**
	 * Creates the command runner instance.
	 * @return CConsoleCommandRunner the command runner
	 */
	protected function createCommandRunner() {
		return new QsDaemonCommandRunner();
	}

	/**
	 * Imports related components and {@link System_Daemon} library.
	 */
	protected function importResources() {
		$selfDirName = dirname(__FILE__);
		Yii::setPathOfAlias('qsdaemons', $selfDirName);
		Yii::import('qsdaemons.*');
		require_once $selfDirName.'/vendors/system_daemon/Daemon.php';
		Yii::registerAutoloader(array('System_Daemon', 'autoload'));
	}

	/**
	 * Raised when an uncaught PHP exception occurs.
	 *
	 * An event handler can set the {@link CExceptionEvent::handled handled}
	 * property of the event parameter to be true to indicate no further error
	 * handling is needed. Otherwise, the {@link getErrorHandler errorHandler}
	 * application component will continue processing the error.
	 *
	 * @param CExceptionEvent $event event parameter
	 */
	public function onException($event) {
		parent::onException($event);
		if (class_exists('System_Daemon', false)) {
			if (System_Daemon::isRunning()) {
				System_Daemon::stop();
			}
		}
	}

	/**
	 * Raised when a PHP execution error occurs.
	 *
	 * An event handler can set the {@link CErrorEvent::handled handled}
	 * property of the event parameter to be true to indicate no further error
	 * handling is needed. Otherwise, the {@link getErrorHandler errorHandler}
	 * application component will continue processing the error.
	 *
	 * @param CErrorEvent $event event parameter
	 */
	public function onError($event) {
		parent::onError($event);
		if (class_exists('System_Daemon', false)) {
			if (System_Daemon::isRunning()) {
				System_Daemon::stop();
			}
		}
	}
}