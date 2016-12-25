<?php
/**
 * QsQueueAmazonSqs class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2008-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * QsQueueAmazonSqs introduces queues based on Amazon Simple Queue Service (SQS).
 *
 * @see QsQueueManagerAmazonSqs
 * @see https://github.com/aws/aws-sdk-php
 * @see http://docs.aws.amazon.com/aws-sdk-php-2/guide/latest/service-sqs.html
 *
 * @property string $url public alias of {@link _url}.
 * @property string $urlName public alias of {@link _urlName}.
 * @property array $attributes public alias of {@link _attributes}.
 * @method QsQueueManagerAmazonSqs getManager()
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qsextra.queues.amazon
 */
class QsQueueAmazonSqs extends QsQueue {
	/**
	 * @var string Amazon SQS queue url.
	 */
	protected $_url = '';
	/**
	 * @var string queue URL name.
	 * This name will be used as actual queue name in Amazon SQS.
	 * If this field is left blank its value will be 
	 * generated using {@link name}.
	 */
	protected $_urlName = '';
	/**
	 * @var array Amazon SQS queue attributes.
	 * @see \Aws\Sqs\Enum\QueueAttribute
	 */
	protected $_attributes = array();

	// Set / Get :

	public function setUrl($url) {
		$this->_url = $url;
		return true;
	}

	public function getUrl() {
		return $this->_url;
	}

	public function setUrlName($urlName) {
		if (!is_string($urlName)) {
			throw new CException('"' . get_class($this) . '::urlName" should be a string!');
		}
		$this->_urlName = $urlName;
		return true;
	}

	public function getUrlName() {
		if (empty($this->_urlName)) {
			$this->initUrlName();
		}
		return $this->_urlName;
	}

	public function setAttributes(array $attributes) {
		$this->_attributes = $attributes;
		return true;
	}

	public function getAttributes() {
		return $this->_attributes;
	}

	/**
	 * Initializes URL name using {@link name}.
	 * @return boolean success.
	 */
	protected function initUrlName() {
		$urlName = $this->getName();
		$urlName = preg_replace('/([^A-Z|^0-9|^-])/is', '-', $urlName);
		$this->_urlName = $urlName;
		return true;
	}

	/**
	 * Composes the queue attributes, creating their actual value.
	 * During the composition {@link QsQueueManagerAmazonSqs::defaultQueueAttributes} and {@link attributes}
	 * will be merged.
	 * Any particular queue attribute will override the default one given by the manager.
	 * @return array actual queue attributes list.
	 */
	public function composeAttributes() {
		$defaultAttributes = $this->getManager()->getDefaultQueueAttributes();
		$attributes = CMap::mergeArray($defaultAttributes, $this->getAttributes());
		return $attributes;
	}

	/**
	 * Creates the string message from the {@link QsQueueItem} instance.
	 * @param QsQueueItem $item queue item instance.
	 * @return array row data.
	 */
	protected function convertQueueItemToSqsMessage(QsQueueItem $item) {
		$sqsMessage = serialize($item->getData());
		$sqsMessage = base64_encode($sqsMessage);
		return $sqsMessage;
	}

	/**
	 * Creates an instance of {@link QsQueueItem} and fills it, which the 
	 * data fetched from the string message.
	 * @param Guzzle\Service\Resource\Model $response - response xml object.
	 * @return QsQueueItem queue item instance.
	 */
	protected function convertResponseToQueueItem($response) {
		list($message) = $response->get('Messages');
		
		$messageBody = $message['Body'];
		$messageBodyDecoded = base64_decode($messageBody);
		$data = unserialize($messageBodyDecoded);
		
		if (is_array($data)) {
			$queueItem = new QsQueueItem();
			$queueItem->setId($message['MessageId']);
			$queueItem->setHandler($message['ReceiptHandle']);
			$queueItem->setData($data);
		} else {
			$queueItem = null;
		}
		return $queueItem;
	}

	/**
	 * Deletes the message from the SQS queue.
	 * @param string $receiptHandle - the receipt handle associated with the message.
	 * @return boolean success.
	 */
	protected function deleteSqsMessage($receiptHandle) {
		$amazonSqs = $this->getManager()->getAmazonSqs();
		$amazonSqs->deleteMessage(array(
			'QueueUrl' => $this->getUrl(),
			'ReceiptHandle' => $receiptHandle,
		));
		return true;
	}

	/**
	 * Creates this queue.
	 * @return boolean success.
	 */
	public function create() {
		if ($this->exists()) {
			return true;
		}
		$amazonSqs = $this->getManager()->getAmazonSqs();
		$response = $amazonSqs->createQueue(array(
			'QueueName' => $this->getUrlName(),
			'Attributes' => $this->composeAttributes()
		));
		$queueUrl = $response->get('QueueUrl');
		$this->setUrl($queueUrl);
		$this->log('queue has been created at the URL "' . $queueUrl . '"');
		return true;
	}

	/**
	 * Destroys this queue.
	 * @return boolean success.
	 */
	public function destroy() {
		if (!$this->exists()) {
			return true;
		}
		$amazonSqs = $this->getManager()->getAmazonSqs();
		$amazonSqs->deleteQueue(array(
			'QueueUrl' => $this->getUrl()
		));
		$this->log('queue has been destroyed at the URL "' . $this->getUrl() . '"');
		$this->setUrl(null);
		return true;
	}

	/**
	 * Checks is queue exists.
	 * @return boolean success.
	 */
	public function exists() {
		$queueUrl = $this->getUrl();
		if (!empty($queueUrl)) {
			return true;
		}
		$amazonSqs = $this->getManager()->getAmazonSqs();
		$response = $amazonSqs->listQueues();
		$queueUrls = $response->get('QueueUrls');
		foreach ($queueUrls as $queueUrl) {
			if (preg_match('/\/' . $this->getUrlName() . '$/s', $queueUrl)) {
				$this->setUrl($queueUrl);
				return true;
			}
		}
		return false;
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
		$sqsMessage = $this->convertQueueItemToSqsMessage($item);
		$amazonSqs = $this->getManager()->getAmazonSqs();
		try {
			$response = $amazonSqs->sendMessage(array(
				'QueueUrl' => $this->getUrl(),
				'MessageBody' => $sqsMessage,
			));
			$sqsMessageId = $response->get('MessageId');
			$item->setId($sqsMessageId);
			$this->log('new item added id="' . $sqsMessageId . '"');
			$result = true;
		} catch (Exception $exception) {
			$result = false;
			$this->log('unable to add new item: ' . $exception->getMessage() . '!', CLogger::LEVEL_ERROR);
		}
		return $result;
	}

	/**
	 * Gets item from the queue.
	 * @return QsQueueItem $item - queue item.
	 */
	public function get() {
		if (!$this->exists()) {
			$this->create();
		}
		$amazonSqs = $this->getManager()->getAmazonSqs();
		try {
			$response = $amazonSqs->receiveMessage(array(
				'QueueUrl' => $this->getUrl()
			));
			$queueItem = $this->convertResponseToQueueItem($response);
			if (!is_object($queueItem)) {
				$this->log('unable to get item: queue is empty.');
			} else {
				$this->log('get item id="' . $queueItem->getId() . '"');
			}
			return $queueItem;
		} catch (Exception $exception) {
			$this->log('unable to get item: ' . $exception->getMessage() . '!', CLogger::LEVEL_ERROR);
			return null;
		}
	}

	/**
	 * Removes item from the queue.
	 * @param mixed $itemHandler - item queue handler value.
	 * @return boolean success.
	 */
	public function remove($itemHandler) {
		try {
			$result = $this->deleteSqsMessage($itemHandler);
			$this->log('item handler="' . $itemHandler . '" has been removed');
		} catch (Exception $exception) {
			$result = false;
			$this->log('unable to remove item handler="' . $itemHandler . '": ' . $exception->getMessage(), CLogger::LEVEL_ERROR);
		}
		return $result;
	}
}