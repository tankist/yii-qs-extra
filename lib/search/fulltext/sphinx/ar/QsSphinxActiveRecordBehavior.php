<?php
/**
 * QsSphinxActiveRecordBehavior class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2008-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * QsSphinxActiveRecordBehavior is the base class for behaviors that can be attached to {@link QsSphinxActiveRecord}.
 * 
 * @see QsSphinxActiveRecord
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qsextra.search.fulltext.sphinx.ar
 */
class QsSphinxActiveRecordBehavior extends CModelBehavior {
	/**
	 * Declares events and the corresponding event handler methods.
	 * If you override this method, make sure you merge the parent result to the return value.
	 * @return array events (array keys) and the corresponding event handler methods (array values).
	 * @see CBehavior::events
	 */
	public function events() {
		return array_merge(parent::events(), array(
			'onBeforeFind' => 'beforeFind',
			'onAfterFind' => 'afterFind',
			'onBeforeSave' => 'beforeSave',
			'onAfterSave' => 'afterSave',
		));
	}

	/**
	 * Responds to {@link QsSphinxActiveRecord::onBeforeFind} event.
	 * Overrides this method if you want to handle the corresponding event of the {@link CBehavior::owner owner}.
	 * @param CEvent $event event parameter
	 */
	public function beforeFind($event) {}

	/**
	 * Responds to {@link QsSphinxActiveRecord::onAfterFind} event.
	 * Overrides this method if you want to handle the corresponding event of the {@link CBehavior::owner owner}.
	 * @param CEvent $event event parameter
	 */
	public function afterFind($event) {}

	/**
	 * Responds to {@link QsSphinxActiveRecord::onBeforeSave} event.
	 * Overrides this method if you want to handle the corresponding event of the {@link CBehavior::owner owner}.
	 * You may set {@link CModelEvent::isValid} to be false to quit the saving process.
	 * @param CModelEvent $event event parameter
	 */
	public function beforeSave($event) {}

	/**
	 * Responds to {@link QsSphinxActiveRecord::onAfterSave} event.
	 * Overrides this method if you want to handle the corresponding event of the {@link CBehavior::owner owner}.
	 * @param CModelEvent $event event parameter
	 */
	public function afterSave($event) {}
}