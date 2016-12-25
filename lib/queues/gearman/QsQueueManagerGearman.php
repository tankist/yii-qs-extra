<?php
/**
 * QsQueueManagerGearman class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2008-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * QsQueueManagerGearman manages queues based on the 'Gearman' PHP Extension.
 *
 * @see http://www.php.net/manual/en/book.gearman.php
 * @see QsQueueGearman
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qsextra.queues.gearman
 */
class QsQueueManagerGearman extends QsQueueManager {
	/**
	 * @var string name of the queue class.
	 */
	protected $_queueClassName = 'QsQueueGearman';

	/**
	 * Initializes the application component.
	 * Checks if "Gearman" PHP extension is loaded.
	 */
	public function init() {
		parent::init();
		if (!extension_loaded('gearman')) {
			throw new CException('"Gearman" PHP extension is required!');
		}
	}
}
