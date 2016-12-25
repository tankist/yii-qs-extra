<?php
/**
 * QsDaemonLogRoute class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2008-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsDaemonLogRoute records log messages using {@link System_Daemon::log()}.
 *
 * The log messages wil be passed to the {@link System_Daemon::log()}.
 * 
 * Warning: after be initialized, this log route switches {@link CLogger::autoFlush} to '1'
 * and {@link CLogger::autoDump} to 'true' in order to flush log messages immediately after their
 * appearance!
 *
 * You may set up several instances of such route in order to save different messages to the 
 * daemon log file.
 * Example:
 * <code>
 * array(
 *     ...
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
 *     ...
 * )
 * </code>
 * 
 * @see QsDaemonApplication
 * @see System_Daemon
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qsextra.daemons
 */
class QsDaemonLogRoute extends CLogRoute {
	/**
	 * Initializes the route.
	 * This method is invoked after the route is created by the route manager.
	 * This methods configures {@link CLogger} instance to flush log messages
	 * immediately one by one.
	 */
	public function init() {
		parent::init();
		Yii::getLogger()->autoFlush = 1;
		Yii::getLogger()->autoDump = true;
	}

	/**
	 * Saves log messages using {@link System_Daemon::log()}.
	 * @param array $logs list of log messages
	 */
	protected function processLogs($logs) {
		if (class_exists('System_Daemon', false)) {
			foreach($logs as $log) {
				$message = $log[0];
				$level = $this->convertLogLevel($log[1]);
				$category = $log[2];
				//$time = $log[3];
				System_Daemon::log($level, $message);
			}
		}
	}

	/**
	 * Converts Yii log level to the {@link System_Daemon} log level.
	 * @param string $logLevel {@link CLogger} log level.
	 * @return string {@link System_Daemon} log level.
	 */
	protected function convertLogLevel($logLevel) {
		switch ($logLevel) {
			case CLogger::LEVEL_ERROR : {
				return System_Daemon::LOG_ERR;
			}
			case CLogger::LEVEL_WARNING : {
				return System_Daemon::LOG_WARNING;
			}
			default: {
				return System_Daemon::LOG_INFO;
			}
		}
	}
}