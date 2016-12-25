<?php
/**
 * QsDaemonCommandEvent class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2008-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * QsDaemonCommandEvent represents the event parameters needed by events raised by a {@link QsDaemonCommand}.
 *
 * @see QsDaemonCommand
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qsextra.daemons
 */
class QsDaemonCommandEvent extends CEvent {
	/**
	 * @var boolean whether the sender is in valid status and should continue its normal method execution cycles. Defaults to true.
	 * If true, the normal execution cycles will continue.
	 * {@see QsDaemonCommand::beforeIteration}.
	 */
	public $isValid = true;
}
