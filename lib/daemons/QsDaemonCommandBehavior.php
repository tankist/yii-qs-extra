<?php
/**
 * QsDaemonCommandBehavior class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2008-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * QsDaemonCommandBehavior is the base class for behaviors that can be attached to {@link QsDaemonCommand}.
 *
 * @see QsDaemonCommand
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qsextra.daemons
 */
class QsDaemonCommandBehavior extends CBehavior {
	/**
	 * Declares events and the corresponding event handler methods.
	 * If you override this method, make sure you merge the parent result to the return value.
	 * @return array events (array keys) and the corresponding event handler methods (array values).
	 * @see CBehavior::events
	 */
	public function events() {
		return array(
			'onBeforeAction' => 'beforeAction',
			'onAfterAction' => 'afterAction',
			'onBeforeIteration' => 'beforeIteration',
			'onAfterIteration' => 'afterIteration',
		);
	}

	/**
	 * Responds to {@link QsDaemonCommand::onBeforeAction} event.
	 * Overrides this method if you want to handle the corresponding event of the {@link CBehavior::owner owner}.
	 * You may set {@link QsDaemonCommandEvent::isValid} to be false to avoid the action execution.
	 * @param QsDaemonCommandEvent $event event parameter
	 */
	public function beforeAction($event) {}

	/**
	 * Responds to {@link QsDaemonCommand::onAfterAction} event.
	 * Overrides this method if you want to handle the corresponding event of the {@link CBehavior::owner owner}.	 
	 * @param CEvent $event event parameter
	 */
	public function afterAction($event) {}

	/**
	 * Responds to {@link QsDaemonCommand::onBeforeIteration} event.
	 * Overrides this method if you want to handle the corresponding event of the {@link CBehavior::owner owner}.
	 * You may set {@link QsDaemonCommandEvent::isValid} to be false to stop the daemon execution.
	 * @param QsDaemonCommandEvent $event event parameter
	 */
	public function beforeIteration($event) {}

	/**
	 * Responds to {@link QsDaemonCommand::onAfterIteration} event.
	 * Overrides this method if you want to handle the corresponding event of the {@link CBehavior::owner owner}.	 
	 * @param CEvent $event event parameter
	 */
	public function afterIteration($event) {}
}