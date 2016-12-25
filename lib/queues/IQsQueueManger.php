<?php
/**
 * IQsQueueManger interface file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2008-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * IQsQueueManger is an interface for the all queue managers.
 * Queue manager should be a hub for the {@link IQsQueue} instances.
 *
 * @see IQsQueue
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qsextra.queues
 */
interface IQsQueueManger {
	/**
	 * Sets the list of available queues.
	 * @param array $queues - set of queue instances or queue configurations.
	 * @return boolean success.
	 */
	public function setQueues(array $queues);

	/**
	 * Gets the list of available queue instances.
	 * @return array set of queue instances.
	 */
	public function getQueues();

	/**
	 * Gets the queue instance by name.
	 * @param string $queueName - name of the queue
	 * @return array set of queue instances.
	 */
	public function getQueue($queueName);

	/**
	 * Adds the queue to the queues list.
	 * @param string $queueName - name of the queue.
	 * @param mixed $queueData - queue instance or configuration array.
	 * @return boolean success.
	 */
	public function addQueue($queueName, $queueData=array());
}
