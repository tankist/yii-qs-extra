<?php

/**
 * Test case for the extension "qsextra.queues.db.QsQueueDb".
 * @see QsQueueDb
 */
class QsQueueDbTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qsextra.queues.*');
		Yii::import('qsextra.queues.db.*');
	}

	public function tearDown() {
		$sql = "SHOW TABLES LIKE '%test%'";
		$dbCommand = Yii::app()->db->createCommand($sql);
		$tableNames = $dbCommand->queryColumn();
		if (is_array($tableNames)) {
			$dbSetUp = new QsTestDbMigration();
			foreach($tableNames as $tableName) {
				$dbSetUp->dropTable($tableName);
			}
		}
	}

	/**
	 * Asserts the table exists.
	 * @param string $tableName table name.
	 * @param string $message on failure message.
	 */
	protected function assertTableExists($tableName, $message=null) {
		$sql = "SHOW TABLES LIKE :table_name";
		$dbCommand = Yii::app()->db->createCommand($sql);
		$params = array(
			'table_name' => $tableName
		);
		$tableNames = $dbCommand->queryColumn($params);
		$this->assertTrue(count($tableNames)==1, $message);
	}

	/**
	 * Asserts the table does not exist.
	 * @param string $tableName table name.
	 * @param string $message on failure message.
	 */
	protected function assertTableNotExists($tableName, $message=null) {
		$sql = "SHOW TABLES LIKE :table_name";
		$dbCommand = Yii::app()->db->createCommand($sql);
		$params = array(
			'table_name' => $tableName
		);
		$tableNames = $dbCommand->queryColumn($params);
		$this->assertTrue(count($tableNames)<=0, $message);
	}

	/**
	 * Creates a test queue instance.
	 * @return QsQueueDb queue instance.
	 */
	protected function createQueue() {
		$componentConfig = array(
			'class' => 'QsQueueDb'
		);
		$queue = Yii::createComponent($componentConfig);
		
		$componentConfig = array(
			'class' => 'QsQueueManagerDb'
		);
		$queueManager = Yii::createComponent($componentConfig);
		
		$queue->setManager($queueManager);
		return $queue;
	}

	// Tests:

	public function testCreate() {
		$componentConfig = array(
			'class' => 'QsQueueDb'
		);
		$queueManager = Yii::createComponent($componentConfig);
		$this->assertTrue(is_object($queueManager), 'Unable to create "QsQueueDb" component!');
	}

	/**
	 * @depends testCreate
	 */
	public function testGetTableName() {
		$queue = $this->createQueue();
		
		$testTableNamePrefix = 'test_queue_table_name_prefix_';
		$queue->getManager()->setQueueTableNamePrefix($testTableNamePrefix);
		
		$testName = 'test_queue_name';
		$queue->setName($testName);
		$tableName = $queue->getTableName();
		
		$this->assertEquals($testTableNamePrefix.$testName, $tableName, 'Wrong queue table name!');
	}

	/**
	 * @depends testGetTableName
	 */
	public function testCreateQueue() {
		$queue = $this->createQueue();
		
		$testQueueName = 'test_queue_name';
		$queue->setName($testQueueName);
		$this->assertTrue( $queue->create(), 'Unable to create queue!' );
		
		$queueTableName = $queue->getTableName();
		$this->assertTableExists($queueTableName, 'Can not create queue table!');
	}

	/**
	 * @depends testCreateQueue
	 */
	public function testDestroyQueue() {
		$queue = $this->createQueue();
		
		$testQueueName = 'test_delete_queue_name';
		$queue->setName($testQueueName);
		$queue->create();

		$this->assertTrue($queue->destroy(), 'Unable to destroy queue!');
		
		$queueTableName = $queue->getTableName($testQueueName);
		$this->assertTableNotExists($queueTableName, 'Can not delete queue table!');
	}

	/**
	 * @depends testDestroyQueue
	 */
	public function testQueueExists() {
		$queue = $this->createQueue();
		$testQueueName = 'test_exists_queue_name';
		$queue->setName($testQueueName);

		$this->assertFalse($queue->exists(), 'Not created queue exists!');
		
		$queue->create();
		$this->assertTrue($queue->exists(), 'Created queue not exists!');
		
		$queue->destroy();
		$this->assertFalse($queue->exists(), 'Destroyed queue exists!');
	}

	/**
	 * @depends testQueueExists
	 */
	public function testAddItem() {
		$queue = $this->createQueue();
		$testQueueName = 'test_add_queue_name';
		$queue->setName($testQueueName);
		$queue->create();
		
		$testQueueItem = new QsQueueItem();
		$testQueueItemData = array(
			'data_key_1'=>'data_value_1',
			'data_key_2'=>'data_value_2',
		);
		$testQueueItem->data = $testQueueItemData;
		
		$this->assertTrue($queue->add($testQueueItem)>0, 'Unable to add item!');
		$this->assertTrue($testQueueItem->id !== null, 'Queue item id has not been updated!');
		
		$countCommand = Yii::app()->db->getSchema()->getCommandBuilder()->createCountCommand( $queue->getTableName(), array() );
		$queueTableRecordsCount = $countCommand->queryScalar();
		$this->assertTrue($queueTableRecordsCount>0, 'Unable to add record to the queue table!');
	}

	/**
	 * @depends testAddItem
	 */
	public function testGetItem() {
		$queue = $this->createQueue();
		$testQueueName = 'test_get_queue_name';
		$queue->setName($testQueueName);
		$queue->create();
		
		$testQueueItem = new QsQueueItem();
		$testQueueItemData = array(
			'data_key_1'=>'data_value_1',
			'data_key_2'=>'data_value_2',
		);
		$testQueueItem->data = $testQueueItemData;
		$queue->add($testQueueItem);
		
		$returnedQueueItem = $queue->get();
		
		$this->assertTrue(is_object($returnedQueueItem), 'Unable to get item!');
		$this->assertEquals($testQueueItemData, $returnedQueueItem->data, 'Wrong item data returned!');
	}

	/**
	 * @depends testGetItem
	 */
	public function testRemoveItem() {
		$queue = $this->createQueue();
		$testQueueName = 'test_get_queue_name';
		$queue->setName($testQueueName);
		$queue->create();

		$testQueueItem = new QsQueueItem();
		$testQueueItemData = array(
			'data_key_1' => 'data_value_1',
			'data_key_2' => 'data_value_2',
		);
		$testQueueItem->setData($testQueueItemData);
		$queue->add($testQueueItem);
		
		$returnedQueueItem = $queue->get();

		$this->assertTrue($queue->remove($returnedQueueItem->getHandler()), 'Unable to remove item!');
		
		$returnedQueueItem = $queue->get();
		$this->assertTrue(empty($returnedQueueItem), 'Item present in the queue after it has been removed!');
	}

	/**
	 * @depends testAddItem
	 */
	public function testNotExistingQueueAddItem() {
		$queue = $this->createQueue();
		$testQueueName = 'test_unexisting_queue';
		$queue->setName($testQueueName);
		
		$testQueueItem = new QsQueueItem();
		$testQueueItemData = array(
			'data_key_1' => 'data_value_1',
			'data_key_2' => 'data_value_2',
		);
		$testQueueItem->data = $testQueueItemData;
		
		$this->assertTrue($queue->add($testQueueItem)>0, 'Unable to add item!');
		$this->assertTrue($testQueueItem->id !== null, 'Queue item id has not been updated!');
		
		$countCommand = Yii::app()->db->getSchema()->getCommandBuilder()->createCountCommand($queue->getTableName(), array());
		$queueTableRecordsCount = $countCommand->queryScalar();
		$this->assertTrue($queueTableRecordsCount>0, 'Unable to add record to the queue table!');
	}
	
	/**
	 * @depends testGetItem
	 */
	public function testQueueSequence() {
		$queue = $this->createQueue();
		$testQueueName = 'test_get_queue_name';
		$queue->setName($testQueueName);
		$queue->create();
		
		$testQueueItem = new QsQueueItem();
		
		$indexKeyName = 'index';
		$itemsCount = 3;
		for ($i=1; $i<=$itemsCount; $i++) {
			$testQueueItemData = array(
				$indexKeyName => $i,
			);
			$testQueueItem->data = $testQueueItemData;
			$queue->add($testQueueItem);
		}
		
		for ($i=1; $i<=$itemsCount; $i++) {
			$testQueueItem = $queue->get();
			$this->assertEquals($i, $testQueueItem->data[$indexKeyName], 'Wrong item order!');
			$queue->remove($testQueueItem->getHandler());
		}
	}
}
