<?php
/**
 * QsQueueItem class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2008-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * QsQueueItem used as data set for the queue manger.
 *
 * @see IQsQueue
 *
 * @property mixed $id public alias of {@link _id}.
 * @property mixed $handler public alias of {@link _handler}.
 * @property array $data public alias of {@link _data}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qsextra.queues
 */
class QsQueueItem extends CComponent {
	/**
	 * @var mixed queue item id.
	 */
	protected $_id = null;
	/**
	 * @var mixed queue item handler.
	 * This value will be used to remove the item from the queue.
	 */
	protected $_handler = null;
	/**
	 * @var array data related with the queue item.
	 */
	protected $_data = array();

	/**
	 * Constructor.
	 * @param array $data data related with the queue item.
	 */
	public function __construct(array $data=array()) {
		$this->setData($data);
	}

	// Set / Get :

	public function setId($id) {
		$this->_id = $id;
		return true;
	}

	public function getId() {
		return $this->_id;
	}

	public function setHandler($handler) {
		$this->_handler = $handler;
		return true;
	}

	public function getHandler() {
		return $this->_handler;
	}

	public function setData(array $data) {
		$this->_data = $data;
		return true;
	}

	public function getData() {
		return $this->_data;
	}
}