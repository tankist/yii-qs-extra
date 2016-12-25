<?php
/**
 * QsQueueManagerDb class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2008-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * QsQueueManagerDb manages queues based on the relational database tables.
 * 
 * @see QsQueueDb
 *
 * @property string $queueTableNamePrefix public alias of {@link _queueTableNamePrefix}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qsextra.queues.db
 */
class QsQueueManagerDb extends QsQueueManager {
	/**
	 * @var string name of the queue class.
	 */
	protected $_queueClassName = 'QsQueueDb';
	/**
	 * @var string prefix of the all queue table names. 
	 */
	protected $_queueTableNamePrefix = '_queue_';

	// Set / Get :

	public function setQueueTableNamePrefix($queueTableNamePrefix) {
		if (!is_string($queueTableNamePrefix)) {
			throw new CException('"'.get_class($this).'::queueTableNamePrefix" should be a string!');
		}
		$this->_queueTableNamePrefix = $queueTableNamePrefix;
		return true;
	}

	public function getQueueTableNamePrefix() {
		return $this->_queueTableNamePrefix;
	}
}