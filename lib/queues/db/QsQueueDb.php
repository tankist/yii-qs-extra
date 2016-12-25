<?php
/**
 * QsQueueDb class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2008-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * QsQueueDb introduces the queues based on the relational database tables.
 *
 * @see QsQueueManagerDb
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qsextra.queues.db
 */
class QsQueueDb extends QsQueue {
	/**
	 * Returns actual name of the table, which stores the queue data.
	 * @return string name of the queue table.
	 */
	public function getTableName() {
		$tableName = $this->getManager()->getQueueTableNamePrefix().$this->getName();
		return $tableName;
	}

	/**
	 * Creates the array of data, which is suitable for the SQL insert command,
	 * from the {@link QsQueueItem} instance.
	 * @param QsQueueItem $item queue item instance.
	 * @return array row data.
	 */
	protected function convertQueueItemToDbRow(QsQueueItem $item) {
		$dbRow = array(
			'date' => date('Y-m-d H:i:s', strtotime('NOW')),
			'data' => serialize($item->data)
		);
		return $dbRow;
	}

	/**
	 * Creates an instance of {@link QsQueueItem} and fills it, which the 
	 * database row data.
	 * @param array $dbRow database row data.
	 * @return QsQueueItem queue item instance.
	 */
	protected function convertDbRowToQueueItem(array $dbRow) {
		$queueItem = new QsQueueItem();
		$queueItem->setId($dbRow['id']);
		$queueItem->setHandler($dbRow['id']);
		$queueItem->setData(unserialize($dbRow['data']));
		return $queueItem;
	}

	/**
	 * Returns the currently active database connection.
	 * By default, the 'db' application component will be returned and activated.
	 * @return CDbConnection the currently active database connection
	 */
	public function getDbConnection() {
		return Yii::app()->getComponent('db');
	}

	/**
	 * Shortcut method to retrieve database command builder.
	 * @return CDbCommandBuilder database command builder.
	 */
	protected function getDbCommandBuilder() {
		$dbConnection = $this->getDbConnection();
		return $dbConnection->getSchema()->getCommandBuilder();
	}

	/**
	 * Inserts the row data into the queue databse table.
	 * @param array $rowData database row data.
	 * @return mixed new record id.
	 */
	protected function insertRow(array $rowData) {
		$tableName = $this->getTableName();
		$commandBuilder = $this->getDbCommandBuilder();
		$insertCommand = $commandBuilder->createInsertCommand($tableName, $rowData);
		$insertCommand->execute();
		return Yii::app()->db->getLastInsertID();
	}

	/**
	 * Deletes the row from the queue table.
	 * @param mixed $rowId row id.
	 * @return boolean success.
	 */
	protected function deleteRow($rowId) {
		$tableName = $this->getTableName();
		$commandBuilder = $this->getDbCommandBuilder();
		$criteriaConfig = array(
			'condition' => 'id = :id',
			'params' => array(
				'id' => $rowId
			),
			'limit' => 1
		);
		$criteria = new CDbCriteria($criteriaConfig);
		$deleteCommand = $commandBuilder->createDeleteCommand($tableName, $criteria);
		return $deleteCommand->execute();
	}

	/**
	 * Finds the earliest record in the queue database table.
	 * @return array $rowData database row data.
	 */
	protected function findEarliestRow() {
		$tableName = $this->getTableName();
		$commandBuilder = $this->getDbCommandBuilder();
		$criteriaConfig = array(
			'order'=>'date ASC',
			'limit'=>1
		);
		$criteria = new CDbCriteria($criteriaConfig);
		$findCommand = $commandBuilder->createFindCommand($tableName, $criteria);
		$rowData = $findCommand->queryRow();
		return $rowData;
	}

	/**
	 * Creates this queue.
	 * @return boolean success.
	 */
	public function create() {
		$dbConnection = $this->getDbConnection();
		$tableName = $this->getTableName();
		$columns = array(
			'id' => 'pk',
			'date' => 'datetime',
			'data' => 'text',
		);
		$dbConnection->createCommand()->createTable($tableName, $columns);
		$dbConnection->createCommand()->createIndex("idx_{$tableName}_date", $tableName, 'date');
		$this->log('queue has been created with the table "'.$tableName.'"');
		return true;
	}

	/**
	 * Destroys this queue.
	 * @return boolean success.
	 */
	public function destroy() {
		$dbConnection = $this->getDbConnection();
		$tableName = $this->getTableName();
		$dbConnection->createCommand()->dropTable($tableName);
		$this->log('queue has been destroyed with the table "'.$tableName.'"');
		return true;
	}

	/**
	 * Checks is queue exists.
	 * @return boolean success.
	 */
	public function exists() {
		$tableName = $this->getTableName();
		$sql = "SHOW TABLES LIKE :table_name";
		$dbCommand = Yii::app()->db->createCommand($sql);
		$params = array(
			'table_name' => $tableName
		);
		$tableNames = $dbCommand->queryColumn($params);
		return (count($tableNames)>=1);
	}

	/**
	 * Adds item into the queue
	 * @param QsQueueItem $item - queue item
	 * @return boolean success.
	 */
	public function add(QsQueueItem $item) {
		if (!$this->exists()) {
			$this->create();
		}
		$rowData = $this->convertQueueItemToDbRow($item);
		$newId = $this->insertRow($rowData);
		if (!empty($newId)) {
			$item->setId($newId);
			$item->setHandler($newId);
			$this->log('new item added id="'.$newId.'"');
		} else {
			$this->log('unable to add new item!', CLogger::LEVEL_ERROR);
		}
		return $newId;
	}

	/**
	 * Gets item from the queue.
	 * @return QsQueueItem $item - queue item.
	 */
	public function get() {
		if (!$this->exists()) {
			$this->create();
		}
		$rowData = $this->findEarliestRow();
		if (empty($rowData)) {
			$this->log('unable to get item: queue is empty.');
			return null;
		}
		$queueItem = $this->convertDbRowToQueueItem($rowData);
		$this->log('get item id="'.$queueItem->getId().'"');
		return $queueItem;
	}

	/**
	 * Removes item from the queue.
	 * @param mixed $itemHandler - item queue handler value.
	 * @return boolean success.
	 */
	public function remove($itemHandler) {
		$result = $this->deleteRow($itemHandler);
		$result = (boolean)$result;
		if ($result) {
			$this->log('item id="'.$itemHandler.'" has been removed');
		} else {
			$this->log('unable to remove item id="'.$itemHandler.'"', CLogger::LEVEL_ERROR);
		}
		return $result;
	}
}