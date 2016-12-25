<?php
/**
 * QsQueueManager class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2008-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * QsQueueManager is a base class for the queue managers.
 * This class stores the queue instances and creates them based on
 * the configuration array.
 * Each particular queue manager is supposed to use a particular class for its queues.
 * Name of this class can be set through the {@link queueClassName}.
 *
 * @see IQsQueueManger
 *
 * @property IQsQueue[]|array[] $queues public alias of {@link _queues}.
 * @property string $queueClassName public alias of {@link _queueClassName}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qsextra.queues
 */
abstract class QsQueueManager extends CApplicationComponent implements IQsQueueManger {
	/**
	 * @var IQsQueue[]|array[] list of queues.
	 */
	protected $_queues = array();
	/**
	 * @var string name of the queue class.
	 */
	protected $_queueClassName = 'QsQueue';

	// Set / Get :

	public function setQueueClassName($queueClassName) {
		if (!is_string($queueClassName)) {
			throw new CException('"'.get_class($this).'::queueClassName" should be a string!');
		}
		$this->_queueClassName = $queueClassName;
		return true;
	}

	public function getQueueClassName() {
		return $this->_queueClassName;
	}

	/**
	 * Logs a message.
	 * @see CLogRouter
	 * @param string $message message to be logged.
	 * @param string $level level of the message (e.g. 'trace', 'warning', 'error'). It is case-insensitive.
	 * @return boolean success.
	 */
	protected function log($message, $level=CLogger::LEVEL_INFO) {
		$category = 'qs.queues';
		Yii::log($message, $level, $category);
		return true;
	}

	/**
	 * Creates queue instance based on the configuration array.
	 * @param array $queueConfig - configuration array for the queue.
	 * @return IQsQueue queue instance.
	 */
	protected function createQueueInstance(array $queueConfig) {
		if (!array_key_exists('class', $queueConfig)) {
			$queueClassName = $this->getQueueClassName();
			$queueConfig['class'] = $queueClassName;
		}
		$queueConfig['manager'] = $this;
		return Yii::createComponent($queueConfig);
	}

	/**
	 * Sets the list of available queues.
	 * @param array $queues - set of queue instances or queue configurations.
	 * @return boolean success.
	 */
	public function setQueues(array $queues) {
		foreach($queues as $queueKey => $queueValue) {
			if (is_numeric($queueKey) && is_string($queueValue)) {
				$queueName = $queueValue;
				$queueData = array();
			} else {
				$queueName = $queueKey;
				$queueData = $queueValue;
			}
			$this->addQueue($queueName, $queueData);
		}
		return true;
	}

	/**
	 * Gets the list of available queue instances.
	 * @return array set of queue instances.
	 */
	public function getQueues() {
		$result = array();
		foreach($this->_queues as $queueName => $queueData) {
			$result[$queueName] = $this->getQueue($queueName);
		}
		return $result;
	}

	/**
	 * Gets the queue intance by name.
	 * @param string $queueName - name of the queue
	 * @return array set of queue instances.
	 */
	public function getQueue($queueName) {
		if (!array_key_exists($queueName, $this->_queues)) {
			throw new CException("Queue named '{$queueName}' does not exists in the manager '".get_class($this)."'");
		}
		$queueData = $this->_queues[$queueName];
		if (is_object($queueData)) {
			$queueInstance = $queueData;
		} else {
			$queueData['name'] = $queueName;
			$queueInstance = $this->createQueueInstance($queueData);
			$this->_queues[$queueName] = $queueInstance;
		}
		return $queueInstance;
	}

	/**
	 * Adds the queue to the queues list.
	 * @param string $queueName - name of the queue.
	 * @param IQsQueue|array $queueData - queue instance or configuration array.
	 * @return boolean success.
	 */
	public function addQueue($queueName, $queueData=array()) {
		if (!is_string($queueName)) {
			throw new CException('Name of the queue should be a string!');
		}
		if (is_scalar($queueData)) {
			throw new CException('Data of the queue should be an queue object or configuration array!');
		}
		if (is_object($queueData)) {
			$queueData->setName($queueName);
		}
		$this->_queues[$queueName] = $queueData;
		return true;
	}
}