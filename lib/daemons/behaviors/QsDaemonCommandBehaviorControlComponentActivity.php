<?php
/**
 * QsDaemonCommandBehaviorControlComponentActivity class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2008-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * QsDaemonCommandBehaviorControlComponentActivity is the behavior for the {@link QsDaemonCommand},
 * which allows to activate and deactivate application components per each daemon iteration. 
 * Use {@link controlledComponentNames} to set up the list of components under control.
 * Note: each controlled component should provide the boolean property 'active', which handles its activity.
 * Usage example:
 * <code>
 * $behaviors = array(
 *     'controlComponentActivityBehavior' => array(
 *         'class' => 'qsdaemons.behaviors.QsDaemonCommandBehaviorControlComponentActivity',
 *         'controlledComponentNames' => array(
 *             'db'
 *         )
 *     )
 * )
 * </code>
 *
 * @see QsDaemonCommandBehavior
 *
 * @property array $controlledComponentNames public alias of {@link _controlledComponentNames}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qsextra.daemons.behaviors
 */
class QsDaemonCommandBehaviorControlComponentActivity extends QsDaemonCommandBehavior {
	/**
	 * @var array list of component names, which should be activated and deactivated.
	 */
	protected $_controlledComponentNames = array();

	// Set / Get :

	public function setControlledComponentNames(array $controlledComponentNames) {
		$this->_controlledComponentNames = $controlledComponentNames;
		return true;
	}

	public function getControlledComponentNames() {
		return $this->_controlledComponentNames;
	}

	/**
	 * Changes activity of the controlled components ot the given value.
	 * @param boolean $activity - new controlled components activity value.
	 * @return boolean success.
	 */
	protected function changeControlledComponentsActivity($activity) {
		foreach($this->_controlledComponentNames as $componentName) {
			Yii::app()->getComponent($componentName)->active = $activity;
		}
		return true;
	}

	/**
	 * Activates the controlled components.
	 * @return boolean success.
	 */
	public function activateControlledComponents() {
		return $this->changeControlledComponentsActivity(true);
	}

	/**
	 * Deactivates the controlled components.
	 * @return boolean success.
	 */
	public function deactivateControlledComponents() {
		return $this->changeControlledComponentsActivity(false);
	}

	// Events:

	/**
	 * Responds to {@link QsDaemonCommand::onBeforeIteration} event.
	 * Overrides this method if you want to handle the corresponding event of the {@link CBehavior::owner owner}.
	 * You may set {@link QsDaemonCommandEvent::isValid} to be false to stop the daemon execution.
	 * @param QsDaemonCommandEvent $event event parameter
	 */
	public function beforeIteration($event) {
		$this->activateControlledComponents();
	}

	/**
	 * Responds to {@link QsDaemonCommand::onAfterIteration} event.
	 * Overrides this method if you want to handle the corresponding event of the {@link CBehavior::owner owner}.	 
	 * @param CEvent $event event parameter
	 */
	public function afterIteration($event) {
		$this->deactivateControlledComponents();
	}
}