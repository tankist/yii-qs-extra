<?php
/**
 * QsQueue class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2008-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * QsQueue is a base class for the queues.
 *
 * @see IQsQueue
 *
 * @property string $name public alias of {@link _name}.
 * @property IQsQueueManger $manager public alias of {@link _manager}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qsextra.queues
 */
abstract class QsQueue extends CComponent implements IQsQueue {
	/**
	 * @var string queue name.
	 */
	protected $_name = '';
	/**
	 * @var IQsQueueManger queue manager, which owns the queue.
	 */
	protected $_manager = null;

	/**
	 * Logs a message.
	 * @see CLogRouter
	 * @param string $message message to be logged.
	 * @param string $level level of the message (e.g. 'trace', 'warning', 'error'). It is case-insensitive.
	 * @return boolean success.
	 */
	protected function log($message, $level=CLogger::LEVEL_INFO) {
		$category = 'qs_queues';
		$message = 'Queue "'.$this->getName().'": '.$message;
		Yii::log($message, $level, $category);
		return true;
	}

	/**
	 * Sets queue name.
	 * @param string $name - queue name.
	 * @return boolean success.
	 */
	public function setName($name) {
		if (!is_string($name)) {
			throw new CException('"'.get_class($this).'::name" should be a string!');
		}
		$this->_name = $name;
		return true;
	}

	/**
	 * Gets current queue name.
	 * @return string $name - queue name.
	 */
	public function getName() {
		return $this->_name;
	}

	/**
	 * Sets queue manager.
	 * @param IQsQueueManger $manager - queue manager.
	 * @return boolean success.
	 */
	public function setManager(IQsQueueManger $manager) {
		$this->_manager = $manager;
		return true;
	}

	/**
	 * Gets queue manager.
	 * @return IQsQueueManger $manager - queue manager.
	 */
	public function getManager() {
		return $this->_manager;
	}
}