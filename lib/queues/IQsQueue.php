<?php
/**
 * IQsQueue interface file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2008-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * IQsQueue is an interface for the all queues.
 * All queues should be controlled by the instance of {@link IQsQueueManger}.
 *
 * @see IQsQueueManger
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qsextra.queues
 */
interface IQsQueue {
	/**
	 * Sets queue name.
	 * @param string $name - queue name.
	 * @return boolean success.
	 */
	public function setName($name);

	/**
	 * Gets current queue name.
	 * @return string $name - queue name.
	 */
	public function getName();

	/**
	 * Sets queue manager.
	 * @param IQsQueueManger $manager - queue manager.
	 * @return boolean success.
	 */
	public function setManager(IQsQueueManger $manager);

	/**
	 * Gets queue manager.
	 * @return IQsQueueManger $manager - queue manager.
	 */
	public function getManager();

	/**
	 * Creates this queue.
	 * @return boolean success.
	 */
	public function create();

	/**
	 * Destroys this queue.
	 * @return boolean success.
	 */
	public function destroy();

	/**
	 * Checks is queue exists.
	 * @return boolean success.
	 */
	public function exists();

	/**
	 * Adds item into the queue
	 * @param QsQueueItem $item - queue item
	 * @return boolean success.
	 */
	public function add(QsQueueItem $item);

	/**
	 * Gets item from the queue.
	 * @return QsQueueItem $item - queue item.
	 */
	public function get();

	/**
	 * Removes item from the queue.
	 * @param mixed $itemHandler - item queue handler value.
	 * @return boolean success.
	 */
	public function remove($itemHandler);
}