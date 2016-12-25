<?php
/**
 * QsDaemonCommand class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2008-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * QsDaemonCommand represents an executable daemon command.
 * 
 * The process will be daemonalized by {@link System_Daemon} library.
 * 
 * Such commands are the parts of the {@link QsDaemonApplication}.
 * The daemon command can be executed just like {@link CConsoleCommand}.
 * {@link daemonActions} specifies the list of actions, which will spawn the daemons.
 * 
 * While creating own daemon class place an author mark at the doc comments (&amp;author name <email>).
 * This will allow daemon to determine his author name automatically.
 * 
 * @see QsDaemonApplication
 * @see System_Daemon
 *
 * @property array $daemonOptions public alias of {@link _daemonOptions}.
 * @property array $daemonActions public alias of {@link _daemonActions}.
 * @property integer $iterationInterval public alias of {@link _iterationInterval}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qsextra.daemons
 */
class QsDaemonCommand extends CConsoleCommand {
	/**
	 * @var array list of daemon options, which should be applied to this daemon command.
	 * Values of this field will override {@link QsDaemonApplication::defaultDaemonOptions}.
	 * @see System_Daemon::setOptions()
	 */
	protected $_daemonOptions = array();
	/**
	 * @var array list of actions, which should be daemonalized.
	 */
	protected $_daemonActions = array(
		'index',
	);
	/**
	 * @var integer number of seconds between 2 daemon iterations.
	 */
	protected $_iterationInterval = 1;
	/**
	 * @var boolean determines if command should not be executed in daemon mode.
	 */
	public $nodaemon = false;
	/**
	 * @var boolean determines if, intead of daemon launch, auto run OS script should be created.
	 */
	public $writeautorun = false;
	/**
	 * @var boolean whether to execute daemon command in an interactive mode. Defaults to true.
	 * Set this to false when starting daemon in background process.
	 */
	public $interactive = true;
	/**
	 * @var boolean|null indicates if enforced garbage collection available.
	 * This field is for internal usage only.
	 */
	protected $_garbageCollectionAvailable = null;

	// Set / Get :

	public function setDaemonOptions(array $daemonOptions) {
		$this->_daemonOptions = $daemonOptions;
		return true;
	}

	public function getDaemonOptions() {
		return $this->_daemonOptions;
	}

	public function setDaemonActions(array $daemonActions) {
		$this->_daemonActions = $daemonActions;
		return true;
	}

	public function getDaemonActions() {
		return $this->_daemonActions;
	}

	public function setIterationInterval($iterationInterval) {
		if (!is_numeric($iterationInterval)) {
			throw new CException('"'.get_class($this).'::iterationInterval" should be an integer!');
		}
		$this->_iterationInterval = $iterationInterval;
		return true;
	}

	public function getIterationInterval() {
		return $this->_iterationInterval;
	}

	/**
	 * Initializes the command object.
	 * This method is invoked after a command object is created and initialized with configurations.
	 * You may override this method to further customize the command before it executes.
	 * If you override this method, make sure to call the parent implementation.
	 */
	public function init() {
		parent::init();
		$this->attachBehaviors($this->behaviors());
	}

	/**
	 * Returns a list of behaviors that this command should behave as.
	 * The return value should be an array of behavior configurations indexed by
	 * behavior names. Each behavior configuration can be either a string specifying
	 * the behavior class or an array of the following structure:
	 * <code>
	 * 'behaviorName' => array(
	 *     'class' => 'path.to.BehaviorClass',
	 *     'property1' => 'value1',
	 *     'property2' => 'value2',
	 * )
	 * </code>
	 * @return array list of behaviors.
	 */
	public function behaviors() {
		return array();
	}

	/**
	 * Asks user to confirm by typing y or n.
	 *
	 * @param string $message to echo out before waiting for user input
	 * @param boolean $default this value is returned if no selection is made. This parameter has been available since version 1.1.11.
	 * @return boolean whether user confirmed
	 */
	public function confirm($message, $default=false) {
		if (!$this->interactive) {
			return true;
		}
		return parent::confirm($message, $default);
	}

	/**
	 * Logs message.
	 * @param string $message the text message
	 * @param integer $level log message level.
	 * @return boolean success.
	 */
	protected function log($message, $level=null) {
		if ($level===null) {
			$level = CLogger::LEVEL_INFO;
		}
		return Yii::log($message, $level, 'qs.daemons');
	}

	/**
	 * Executes the command.
	 * The default implementation will parse the input parameters and
	 * dispatch the command request to an appropriate action with the corresponding
	 * option values
	 * @param array $args command line parameters for this command.
	 */
	public function run($args) {
		list($action, $options, $args) = $this->resolveRequest($args);

		$methodName = 'action'.$action;
		if (!preg_match('/^\w+$/',$action) || !method_exists($this,$methodName)) {
			$this->usageError("Unknown action: ".$action);
		}

		$method = new ReflectionMethod($this,$methodName);
		$params = array();
		// named and unnamed options
		foreach ($method->getParameters() as $i => $param) {
			$name = $param->getName();
			if (isset($options[$name])) {
				if ($param->isArray()) {
					$params[] = is_array($options[$name]) ? $options[$name] : array($options[$name]);
				} elseif (!is_array($options[$name])) {
					$params[] = $options[$name];
				} else {
					$this->usageError("Option --$name requires a scalar. Array is given.");
				}
			} elseif ($name==='args') {
				$params[] = $args;
			} elseif ($param->isDefaultValueAvailable()) {
				$params[] = $param->getDefaultValue();
			} else {
				$this->usageError("Missing required option --{$name}.");
			}
			unset($options[$name]);
		}

		// try global options
		if (!empty($options)) {
			$class = new ReflectionClass(get_class($this));
			foreach ($options as $name => $value) {
				if ($class->hasProperty($name)) {
					$property = $class->getProperty($name);
					if ($property->isPublic() && !$property->isStatic()) {
						$this->$name = $value;
						unset($options[$name]);
					}
				}
			}
		}

		if (!empty($options)) {
			$this->usageError('Unknown options: '.implode(', ',array_keys($options)));
		}
		
		if ($this->isDaemonAction($action)) {
			$this->runDaemonAction($method, $action, $params);
		} else {
			$this->runNormalAction($method, $action, $params);
		}
	}

	/**
	 * Checks if action should be run in daemon mode or not.
	 * @param string $action the action name
	 * @return boolean if action should be run in daemon mode.
	 */
	protected function isDaemonAction($action) {
		return (array_search($action, $this->_daemonActions, true) !== false);
	}

	/**
	 * Executes the command action in the normal (instant) way.
	 * @param ReflectionMethod $actionMethod the action method reflection
	 * @param string $actionName the action name
	 * @param array $actionParams call parameters for the action.
	 */
	protected function runNormalAction($actionMethod, $actionName, $actionParams) {
		if ($this->beforeAction($actionName, $actionParams)) {
			$actionMethod->invokeArgs($this, $actionParams);
			$this->afterAction($actionName, $actionParams);
		}
	}

	/**
	 * Executes the command action in the deamon mode.
	 * @param ReflectionMethod $actionMethod the action method reflection
	 * @param string $actionName the action name
	 * @param array $actionParams call parameters for the action.
	 * @return boolean success.
	 */
	protected function runDaemonAction($actionMethod, $actionName, $actionParams) {
		if ($this->writeautorun) {
			$this->writeAutoRun($actionName, $actionParams);
			return true;
		}
		
		System_Daemon::setOptions($this->composeDaemonOptions($actionName, $actionParams));
		
		if (!$this->nodaemon) {
			if (!$this->confirm("Are you sure you wish to start {$this->name} daemon?")) {
				return false;
			}
			System_Daemon::start();
		} else {
			if (!$this->confirm("Are you sure you wish to start {$this->name} in not daemon mode?")) {
				return false;
			}
		}
		
		while (!System_Daemon::isDying()) {
			if ($this->beforeIteration($actionName,$actionParams)) {
				$actionMethod->invokeArgs($this,$actionParams);
				$this->afterIteration($actionName,$actionParams);
				$this->enforceGarbageCollection();
			}
			System_Daemon::iterate($this->iterationInterval);
		}
		System_Daemon::stop();
		return true;
	}

	/**
	 * Write daemon autorun script in the file system, using
	 * {@link QsDaemonApplication::writeAutoRun()}.
	 * @param string $actionName the action name
	 * @param array $actionParams call parameters for the action.
	 * @param boolean $overwrite if autorun script should be overwritten if exists.
	 * @return boolean success.
	 */
	protected function writeAutoRun($actionName, $actionParams, $overwrite=false) {
		if (!$overwrite && !$this->confirm("Are you sure you wish to write autorun for the {$this->name} daemon?")) {
			return false;
		}

		$daemonOptions = $this->composeDaemonOptions($actionName,$actionParams);
		$daemonOptions['shellRunArgString'] = $this->composeShellRunArgString($actionName,$actionParams);
		System_Daemon::setOptions($daemonOptions);

		if (($initdLocation = System_Daemon::writeAutoRun($overwrite)) === false) {
			$this->log('Unable to write autorun script');
		} elseif ($initdLocation === true) {
			$this->log('Autorun script already exists');
			if (!$overwrite) {
				if ($this->confirm("{$this->name} daemon autorun script already exists do you want to overwrite it?") ) {
					return $this->writeAutoRun($actionName, $actionParams, true);
				}
			}
		} else {
			$this->log('Sucessfully written autorun script: '.$initdLocation);
		}
		return $initdLocation;
	}

	/**
	 * Composes daemon options.
	 * Created obvious options will be overridden by the values of
	 * {@link QsDaemonApplication::defaultDaemonOptions} and {@link self::daemonOptions}.
	 * @param string $actionName the action name
	 * @param array $actionParams call parameters for the action.
	 * @return array system daemon options.
	 */
	protected function composeDaemonOptions($actionName,$actionParams) {
		$defaultDaemonOptions = $this->createDefaultDaemonOptions($actionName,$actionParams);
		$daemonOptions = array_merge($defaultDaemonOptions, Yii::app()->getDefaultDaemonOptions(), $this->getDaemonOptions());
		if (empty($daemonOptions['logLocation'])) {
			$logPath =$this->resolveDefaultLogPath();
			$daemonOptions['logLocation'] = $logPath.DIRECTORY_SEPARATOR.get_class($this).'.log';
		}
		return $daemonOptions;
	}

	/**
	 * Returns default daemon log location path.
	 * Tries to create missing folders.
	 * @return string daemon log path.
	 */
	protected function resolveDefaultLogPath() {
		$logPath = Yii::app()->getDefaultDaemonLogPath();
		if (!is_dir($logPath)) {
			$oldUmask = umask(0);
			mkdir($logPath, 0777, true);
			umask($oldUmask);
		}
		return $logPath;
	}

	/**
	 * Determines self class author using doc comments.
	 * @return array author data array with 2 keys: 'name' and 'email'.
	 */
	protected function determineSelfAuthor() {
		$reflectionSelf = new ReflectionClass(get_class($this));
		$selfClassDocComment = $reflectionSelf->getDocComment();
		
		$author = array(
			'name' => 'unknown',
			'email' => 'unknown',
		);
		if (preg_match('/@author ([\w| ]+)([ <]([\S]+)[>])?/m', $selfClassDocComment, $matches)) {
			if (!empty($matches[1])) {
				$author['name'] = trim($matches[1]);
			}
			if (!empty($matches[3])) {
				$author['email'] = trim($matches[3]);
			}
		}
		return $author;
	}

	/**
	 * Composes string, which contains shell call params of the application root script,
	 * which allow to launch this particular daemon.
	 * @param string $actionName the action name.
	 * @param array $actionParams call parameters for the action.
	 * @return string shell command args string.
	 */
	protected function composeShellRunArgString($actionName, $actionParams) {
		$shellRunArguments = $this->name;
		if ($actionName != $this->defaultAction) {
			$shellRunArguments .= ' '.$actionName;
		}
		$shellRunArguments .= ' --interactive=0';
		foreach ($actionParams as $paramName => $paramValue) {
			$shellRunArguments .= " --{$paramName}={$paramValue}";
		}
		return $shellRunArguments;
	}

	/**
	 * Creates default daemon options.
	 * Method fills the options with data, which can be determined automatically.
	 * {@link QsDaemonApplication::defaultDaemonOptions} and {@link self::daemonOptions}.	 
	 * @param string $actionName the action name.
	 * @param array $actionParams call parameters for the action.
	 * @return array system daemon options.
	 */
	protected function createDefaultDaemonOptions($actionName, $actionParams) {
		$selfAuthor = $this->determineSelfAuthor();

		$defaultDaemonOptions = array(
			'appName' => Yii::app()->name.get_class($this),
			'appDir' => Yii::app()->getBasePath(),
			'appExecutable' => basename($_SERVER['argv'][0]),
			'appDescription' => $this->getDescription($actionName,$actionParams),
			'authorName' => $selfAuthor['name'],
			'authorEmail' => $selfAuthor['email'],
			'sysMaxExecutionTime' => '0',
			'sysMaxInputTime' => '0',
			'logFilePosition'=> 1,
			'logLinePosition' => 1,
			//'logLocation' => 'can be passed',
			//'appRunAsGID' => 'Must_be_passed',
			//'appRunAsUID' => 'Must_be_passed',
			'runTemplateLocation' => dirname(__FILE__).'/autorun_templates/red_hat.tpl',
		);
		return $defaultDaemonOptions;
	}

	/**
	 * This method is invoked to determine daemon description.
	 * You may override this method to specify explicit daemon description.
	 * @param string $actionName the action name.
	 * @param array $actionParams call parameters for the action.
	 * @return string daemon description
	 */
	protected function getDescription($actionName, $actionParams) {
		$defaultDescription = Yii::app()->name.get_class($this).' system daemon.';
		return $defaultDescription;
	}

	/**
	 * Provides the daemon command description.
	 * This method may be overridden to return the actual daemon command description.
	 * @return string the daemon command description. Defaults to 'Usage: php entry-script.php command-name'.
	 */
	public function getHelp() {
		$help = 'Usage: ';
		
		$commandShellString = $this->getCommandRunner()->getScriptName().' '.$this->getName();
		
		$options = $this->getOptionHelp();
		if (empty($options)) {
			$options = array(
				$this->defaultAction
			);
		}
		
		$usageOptions = array();
		$usageDescriptions = array();
		
		foreach ($options as $option) {
			$usageOptionString = $commandShellString;
			$usageDescriptionString = 'Description';
			
			if (strcmp($option, $this->defaultAction) != 0) {
				$usageOptionString .= ' '.$option;
				$usageDescriptionString .= " <{$option}>";
			}
			$usageOptions[] = $usageOptionString;
			$usageDescriptionString .= ":\n".$this->getDescription($option, array());
			$usageDescriptions[] = $usageDescriptionString;
		}
		
		// Options:
		$help .= implode("\n   or: ", $usageOptions);
		$help .= "\n\n";
		
		// Global Parameters:
		$help .= "Possible arguments: \n";
		
		$classReflection = new ReflectionClass(get_class($this));
		$publicProperties = $classReflection->getProperties(ReflectionProperty::IS_PUBLIC);
		if (is_array($publicProperties)) {
			$skippedPublicProperties = array(
				'defaultAction'
			);
			foreach ($publicProperties as $publicProperty) {
				if (in_array($publicProperty->getName(), $skippedPublicProperties, true)) {
					continue;
				}
				$propertyHelpString = '  --'.$publicProperty->getName();
				
				$searches = array(
					'*',
					'/',
					'@var ',
					"\n",
					"\r",
				);
				$propertyDescription = str_replace($searches, '', $publicProperty->getDocComment());
				
				$propertyDescriptionOffset = 25;
				$propertyHelpString .= str_pad(' ', $propertyDescriptionOffset-strlen($propertyHelpString),' ');				
				$propertyHelpString .= $propertyDescription."\n";
				
				$help .= $propertyHelpString;
			}
		}
		
		$help .= "\n";
		
		// Description:
		$help .= implode("\n\n", $usageDescriptions);
		$help .= "\n\n";
		
		return $help;
	}

	/**
	 * Enforces the PHP garbage collector to be run.
	 * This method allows to collect cycle references.
	 * @see http://www.php.net/manual/en/features.gc.php
	 */
	protected function enforceGarbageCollection() {
		if ($this->_garbageCollectionAvailable===null) {
			if (function_exists('gc_enable')) {
				$this->_garbageCollectionAvailable = true;
			} else {
				$this->_garbageCollectionAvailable = false;
				$this->log('PHP garbage collector is not available, please update the PHP up to 5.3.0 or higher.', CLogger::LEVEL_WARNING);
			}
		}
		if ($this->_garbageCollectionAvailable) {
			if (gc_enabled()) {
				gc_collect_cycles();
			} else {
				gc_enable();
				gc_collect_cycles();
				gc_disable();
			}
		}
	}

	/**
	 * This method is invoked right before a normal action is to be executed.
	 * You may override this method to do last-minute preparation for the action.
	 * The default implementation raises the {@link onBeforeAction} event.
	 * @param string $action the action name
	 * @param array $params the parameters to be passed to the action method.
	 * @return boolean whether the action should be executed.
	 */
	protected function beforeAction($action, $params) {
		if ($this->hasEventHandler('onBeforeAction')) {
			$eventParams = array(
				'action' => $action,
				'params' => $params,
			);
			$event = new QsDaemonCommandEvent($this, $eventParams);
			$this->onBeforeIteration($event);
			return $event->isValid;
		}
		return true;
	}

	/**
	 * This method is invoked right after a normal action finishes execution.
	 * You may override this method to do some postprocessing for the action.
	 * The default implementation raises the {@link onAfterAction} event.
	 * @param string $action the action name
	 * @param array $params the parameters to be passed to the action method.
	 */
	protected function afterAction($action, $params) {
		if ($this->hasEventHandler('onAfterAction')) {
			$eventParams = array(
				'action' => $action,
				'params' => $params,
			);
			$this->onAfterIteration(new CEvent($this, $eventParams));
		}
	}

	/**
	 * This method is invoked right before an daemon action iteration is to be executed.
	 * You may override this method to do last-minute preparation for the action iteration.
	 * The default implementation raises the {@link onBeforeIteration} event.
	 * @param string $action the action name
	 * @param array $params the parameters to be passed to the action method.
	 * @return boolean whether the action should be executed.
	 */
	protected function beforeIteration($action, $params) {
		if ($this->hasEventHandler('onBeforeIteration')) {
			$eventParams = array(
				'action' => $action,
				'params' => $params,
			);
			$event = new QsDaemonCommandEvent($this, $eventParams);
			$this->onBeforeIteration($event);
			return $event->isValid;
		}
		return true;
	}

	/**
	 * This method is invoked right after an daemon action iteration finishes execution.
	 * You may override this method to do some postprocessing for the action iteration.
	 * The default implementation raises the {@link onAfterIteration} event.
	 * @param string $action the action name
	 * @param array $params the parameters to be passed to the action method.
	 */
	protected function afterIteration($action, $params) {
		if ($this->hasEventHandler('onAfterIteration')) {
			$eventParams = array(
				'action' => $action,
				'params' => $params,
			);
			$this->onAfterIteration(new CEvent($this, $eventParams));
		}
	}

	/**
	 * This event is raised before the normal command execution begins.
	 * By setting {@link QsDaemonCommandEvent::isValid} to be false, the daemon execution will be stopped.
	 * @param QsDaemonCommandEvent $event the event parameter
	 */
	public function onBeforeAction($event) {
		$this->raiseEvent('onBeforeAction',$event);
	}

	/**
	 * This event is raised after the normal command execution ends.
	 * @param CEvent $event the event parameter
	 */
	public function onAfterAction($event) {
		$this->raiseEvent('onAfterAction',$event);
	}

	/**
	 * This event is raised before the daemon command iteration begins.
	 * By setting {@link QsDaemonCommandEvent::isValid} to be false, the daemon execution will be stopped.
	 * @param QsDaemonCommandEvent $event the event parameter
	 */
	public function onBeforeIteration($event) {
		$this->raiseEvent('onBeforeIteration',$event);
	}

	/**
	 * This event is raised after the daemon command iteration ends.
	 * @param CEvent $event the event parameter
	 */
	public function onAfterIteration($event) {
		$this->raiseEvent('onAfterIteration',$event);
	}
}