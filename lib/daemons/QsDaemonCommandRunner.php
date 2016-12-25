<?php
/**
 * QsDaemonCommandRunner class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2008-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * QsDaemonCommandRunner manages daemon commands and executes the requested command.
 * 
 * @see QsDaemonApplication
 * @see CConsoleCommandRunner
 * @see System_Daemon
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qsextra.daemons
 */
class QsDaemonCommandRunner extends CConsoleCommandRunner {
	/**
	 * Searches for daemon commands under the specified directory.
	 * Found commands will be merged with the {@link QsDaemonApplication::daemonMap}.
	 * @param string $path the directory containing the command class files.
	 * @return array list of commands (command name=>command class file)
	 */
	public function findCommands($path) {
		if (($dir=@opendir($path))===false) {
			return array();
		}
		
		$applicationPath = Yii::getPathOfAlias('application');
		$pathAlias = str_replace($applicationPath, 'application', $path);
		$pathAlias = str_replace('/', '.', $pathAlias);
		
		$commands = array();
		
		$commandFileTail = 'Daemon.php';
		$commandFileTailLength = strlen($commandFileTail);
		
		while (($name=readdir($dir))!==false) {
			$file = $path.DIRECTORY_SEPARATOR.$name;
			if (!strcasecmp(substr($name, -$commandFileTailLength), $commandFileTail) && is_file($file)) {
				$commands[strtolower(substr($name, 0, -$commandFileTailLength))] = array(
					'class' => $pathAlias.'.'.basename($file, '.php')
				);
			}
		}
		
		closedir($dir);
		
		$commands = CMap::mergeArray($commands, Yii::app()->daemonMap);
		return $commands;
	}
}