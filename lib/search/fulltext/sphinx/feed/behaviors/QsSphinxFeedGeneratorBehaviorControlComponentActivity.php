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
 * QsSphinxFeedGeneratorBehaviorControlComponentActivity is the behavior for the {@link QsSphinxFeedGenerator},
 * which allows to activate and deactivate application components per each feed page generation. 
 * Use {@link controlledComponentNames} to set up the list of components under control.
 * Note: each controlled component should provide the boolean property 'active', which handles its activity.
 * 
 * This behavior can be used to keep the database connection alive during the Sphinx feed generation.
 * 
 * Usage example:
 * <code>
 * public function behaviors() {
 *     return array(
 *         'controlComponentActivityBehavior' => array(
 *             'class' => 'qsextra.search.fulltext.sphinx.feed.behaviors.QsSphinxFeedGeneratorBehaviorControlComponentActivity',
 *             'controlledComponentNames' => array(
 *                 'db'
 *             )
 *         )
 *     );
 * }
 * </code>
 *
 * @property array $controlledComponentNames public alias of {@link _controlledComponentNames}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qsextra.search.fulltext.sphinx.feed.behaviors
 */
class QsSphinxFeedGeneratorBehaviorControlComponentActivity extends QsSphinxFeedGeneratorBehavior {
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
		foreach ($this->_controlledComponentNames as $componentName) {
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
	 * Responds to {@link QsSphinxFeedGenerator::onBeforeMakeFeedKillListPage} event.
	 * Overrides this method if you want to handle the corresponding event of the {@link CBehavior::owner owner}.
	 * @param CEvent $event event parameter
	 */
	public function beforeMakeFeedKillListPage($event) {
		$this->activateControlledComponents();
	}

	/**
	 * Responds to {@link QsSphinxFeedGenerator::onAfterMakeFeedKillListPage} event.
	 * Overrides this method if you want to handle the corresponding event of the {@link CBehavior::owner owner}.
	 * @param CEvent $event event parameter
	 */
	public function afterMakeFeedKillListPage($event) {
		$this->deactivateControlledComponents();
	}

	/**
	 * Responds to {@link QsSphinxFeedGenerator::onBeforeMakeFeedDocumentPage} event.
	 * Overrides this method if you want to handle the corresponding event of the {@link CBehavior::owner owner}.
	 * @param CEvent $event event parameter
	 */
	public function beforeMakeFeedDocumentPage($event) {
		$this->activateControlledComponents();
	}

	/**
	 * Responds to {@link QsSphinxFeedGenerator::onAfterMakeFeedDocumentPage} event.
	 * Overrides this method if you want to handle the corresponding event of the {@link CBehavior::owner owner}.
	 * @param CEvent $event event parameter
	 */
	public function afterMakeFeedDocumentPage($event) {
		$this->deactivateControlledComponents();
	}
}