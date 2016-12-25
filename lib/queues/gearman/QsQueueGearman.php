<?php
/**
 * QsQueueGearman class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2008-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * QsQueueGearman introduces the queues based on the 'Gearman' PHP Extension.
 *
 * @see http://www.php.net/manual/en/book.gearman.php
 * @see QsQueueManagerGearman
 *
 * @property string $gearmanServers public alias of {@link _gearmanServers}.
 * @property GearmanClient $gearmanClient public alias of {@link _gearmanClient}.
 * @property GearmanWorker $gearmanWorker public alias of {@link _gearmanWorker}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qsextra.queues.gearman
 */
class QsQueueGearman extends QsQueue {
	/**
	 * @var string a comma separated list of Gearman job servers in the format host:port. 
	 * If no port is specified, it defaults to 4730.
	 * Example value: '10.0.0.1,10.0.0.2:7003'.
	 */
	protected $_gearmanServers = '127.0.0.1:4730';
	/**
	 * @var GearmanClient Gearman client instance.
	 */
	protected $_gearmanClient = null;
	/**
	 * @var GearmanWorker Gearman worker instance.
	 */
	protected $_gearmanWorker = null;
	/**
	 * @var array internal cache data.
	 * This field is for the internal usage only.
	 */
	protected $_internalCache = array();

	public function setGearmanServers($gearmanServers) {
		$this->_gearmanServers = $gearmanServers;
		return true;
	}

	public function getGearmanServers() {
		return $this->_gearmanServers;
	}

	public function setGearmanClient($gearmanClient) {
		if (!is_object($gearmanClient) || !is_a($gearmanClient, 'GearmanClient')) {
			throw new CException('"'.get_class($this).'::gearmanClient" should be GearmanClient instance!');
		}
		$this->_gearmanClient = $gearmanClient;
		return true;
	}

	public function getGearmanClient() {
		if (!is_object($this->_gearmanClient)) {
			$this->initGearmanClient();
		}
		return $this->_gearmanClient;
	}

	public function setGearmanWorker($gearmanWorker) {
		if (!is_object($gearmanWorker) || !is_a($gearmanWorker, 'GearmanWorker')) {
			throw new CException('"'.get_class($this).'::gearmanClient" should be GearmanWorker instance!');
		}
		$callback = array(
			$this,
			'handleGearmanJob'
		);
		$gearmanWorker->addFunction($this->getGearmanFunctionName(), $callback);
		$gearmanWorker->addOptions(GEARMAN_WORKER_NON_BLOCKING);
		$this->_gearmanWorker = $gearmanWorker;
		return true;
	}

	public function getGearmanWorker() {
		if (!is_object($this->_gearmanWorker)) {
			$this->initGearmandWorker();
		}
		return $this->_gearmanWorker;
	}

	/**
	 * Initializes the {@link gearmanClient} instance.
	 * @return boolean success.
	 */
	protected function initGearmanClient() {
		$gearmanClient = new GearmanClient();
		$gearmanClient->addServers($this->getGearmanServers());
		return $this->setGearmanClient($gearmanClient);
	}
	
	/**
	 * Initializes the {@link gearmanWorker} instance.
	 * @return boolean success.
	 */
	protected function initGearmandWorker() {
		$gearmanWorker = new GearmanWorker();
		$gearmanWorker->addServers($this->getGearmanServers());
		return $this->setGearmanWorker($gearmanWorker);
	}
	
	/**
	 * Returns the name of the Gearman worker function.
	 * @return string Gearman function name.
	 */
	public function getGearmanFunctionName() {
		$functionName = 'handle'.ucfirst($this->getName());
		return $functionName;
	}
	
	/**
	 * Clears internal cache.
	 * @return boolean success.
	 */
	protected function clearInternalCache() {
		$this->_internalCache = array();
		return true;
	}
	
	/**
	 * Initializes the {@link gearmanClient} instance.
	 * @param GearmanJob $gearmanJob Gearman job instance.
	 * @return boolean success.
	 */
	public function handleGearmanJob($gearmanJob) {
		if (!is_object($gearmanJob)) {
			return false;
		}
		$queueItem = $this->convertGearmanJobToQueueItem($gearmanJob);
		$this->_internalCache['queueItem'] = $queueItem;
		return true;
	}
	
	/**
	 * Creates queue item instance based on the data of 
	 * Gearman job instance.
	 * @param GearmanJob $gearmanJob Gearman job instance.
	 * @return QsQueueItem queue item.
	 */
	protected function convertGearmanJobToQueueItem($gearmanJob) {
		$queueItem = new QsQueueItem();
		$queueItem->setId($gearmanJob->unique());
		$queueItem->setHandler( $gearmanJob );
		$data = CJSON::decode($gearmanJob->workload());
		if (is_array($data)) {
			$queueItem->setData($data);
		}
		return $queueItem;
	}

	/**
	 * Returns queue item instance based on Gearmon job
	 * if it is exists.
	 * @return QsQueueItem queue item.
	 */
	protected function fetchQueueItem() {
		return $this->_internalCache['queueItem'];
	}

	/**
	 * Creates this queue.
	 * @return boolean success.
	 */
	public function create() {
		return true;
	}

	/**
	 * Destroys this queue.
	 * @return boolean success.
	 */
	public function destroy() {
		$this->_gearmanClient = null;
		$this->_gearmanWorker = null;
		return true;
	}

	/**
	 * Checks is queue exists.
	 * @return boolean success.
	 */
	public function exists() {
		return true;
	}

	/**
	 * Adds item into the queue
	 * @param QsQueueItem $item - queue item
	 * @return boolean success.
	 */
	public function add(QsQueueItem $item) {
		$gearmanClient = $this->getGearmanClient();
		$data = CJSON::encode($item->getData());
		$gearmanTask = $gearmanClient->addTaskBackground($this->getGearmanFunctionName(), $data);
		//$gearmanTask = $gearmanClient->doBackground( $this->getGearmanFunctionName(), $data );
		$item->setId($gearmanTask->unique());
		$item->setHandler($gearmanTask->jobHandle());
		
		$result = $gearmanClient->runTasks();
		if ($result) {
			$this->log('new item added id="'.$item->getId().'"');
		} else {
			$this->log('unable to add new item: Gearman client error = "'.$gearmanClient->error().'" !', CLogger::LEVEL_ERROR);
		}
		return $result;
	}

	/**
	 * Gets item from the queue.
	 * @return QsQueueItem $item - queue item.
	 */
	public function get() {
		$gearmanWorker = $this->getGearmanWorker();
		$this->clearInternalCache();
		
		$totalWaitCount = 0;
		$maxWaitCount = 5;
		
		while( @$gearmanWorker->work() || $gearmanWorker->returnCode() == GEARMAN_IO_WAIT ) {
			switch($gearmanWorker->returnCode()) {
				case GEARMAN_SUCCESS: {
					$queueItem = $this->fetchQueueItem();
					$this->log('get item id="'.$queueItem->getId().'"');
					$this->clearInternalCache();
					return $queueItem;
				}
				case GEARMAN_IO_WAIT: {
					if ( $totalWaitCount >= $maxWaitCount ) {
						throw new CException('Wait too long!');
					}
					@$gearmanWorker->wait();
					$totalWaitCount++;
					continue;
				}
			}
		}
		switch($gearmanWorker->returnCode()) {
			case GEARMAN_NO_JOBS: {
				$this->log('unable to get item: queue is empty.');
				return null;
			}
			default: {
				$this->log('unable to get item: Gearman worker return code = "'.$gearmanWorker->returnCode().'" !', CLogger::LEVEL_ERROR);
				return null;
			}
		}
	}

	/**
	 * Removes item from the queue.
	 * @param mixed $itemHandler - item queue handler value.
	 * @return boolean success.
	 */
	public function remove($itemHandler) {
		$gearmanJob = $itemHandler;
		if (!is_object($gearmanJob)) {
			throw new CException('Wrong queue item handler type!');
		}
		$result = $gearmanJob->sendComplete('1');
		if ($result) {
			$this->log('item id="'.$gearmanJob->unique().'" has been removed');
		} else {
			$this->log('unable to remove item id="'.$gearmanJob->unique().'" Gearman return code = "'.$gearmanJob->returnCode().'"!', CLogger::LEVEL_ERROR);
		}
		return $result;
	}
}