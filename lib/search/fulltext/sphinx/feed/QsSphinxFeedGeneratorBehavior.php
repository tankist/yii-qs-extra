<?php
/**
 * QsSphinxFeedGeneratorBehavior class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2008-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * QsSphinxFeedGeneratorBehavior is the base class for behaviors that can be attached to {@link QsSphinxFeedGenerator}.
 *
 * @see QsSphinxFeedGenerator
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qsextra.search.fulltext.sphinx.feed
 */
class QsSphinxFeedGeneratorBehavior extends CBehavior {
	/**
	 * Declares events and the corresponding event handler methods.
	 * If you override this method, make sure you merge the parent result to the return value.
	 * @return array events (array keys) and the corresponding event handler methods (array values).
	 * @see CBehavior::events
	 */
	public function events() {
		return array(
			'onAfterConstruct' => 'afterConstruct',
			'onBeforeMakeFeed' => 'beforeMakeFeed',
			'onAfterMakeFeed' => 'afterMakeFeed',
			'onBeforeMakeFeedKillListPage' => 'beforeMakeFeedKillListPage',
			'onAfterMakeFeedKillListPage' => 'afterMakeFeedKillListPage',
			'onBeforeMakeFeedDocumentPage' => 'beforeMakeFeedDocumentPage',
			'onAfterMakeFeedDocumentPage' => 'afterMakeFeedDocumentPage',
		);
	}

	/**
	 * Responds to {@link QsSphinxFeedGenerator::onAfterConstruct} event.
	 * Overrides this method if you want to handle the corresponding event of the {@link CBehavior::owner owner}.
	 * @param CEvent $event event parameter
	 */
	public function afterConstruct($event) {}

	/**
	 * Responds to {@link QsSphinxFeedGenerator::onBeforeMakeFeed} event.
	 * Overrides this method if you want to handle the corresponding event of the {@link CBehavior::owner owner}.
	 * @param CEvent $event event parameter
	 */
	public function beforeMakeFeed($event) {}

	/**
	 * Responds to {@link QsSphinxFeedGenerator::onAfterMakeFeed} event.
	 * Overrides this method if you want to handle the corresponding event of the {@link CBehavior::owner owner}.
	 * @param CEvent $event event parameter
	 */
	public function afterMakeFeed($event) {}

	/**
	 * Responds to {@link QsSphinxFeedGenerator::onBeforeMakeFeedKillListPage} event.
	 * Overrides this method if you want to handle the corresponding event of the {@link CBehavior::owner owner}.
	 * @param CEvent $event event parameter
	 */
	public function beforeMakeFeedKillListPage($event) {}

	/**
	 * Responds to {@link QsSphinxFeedGenerator::onAfterMakeFeedKillListPage} event.
	 * Overrides this method if you want to handle the corresponding event of the {@link CBehavior::owner owner}.
	 * @param CEvent $event event parameter
	 */
	public function afterMakeFeedKillListPage($event) {}

	/**
	 * Responds to {@link QsSphinxFeedGenerator::onBeforeMakeFeedDocumentPage} event.
	 * Overrides this method if you want to handle the corresponding event of the {@link CBehavior::owner owner}.
	 * @param CEvent $event event parameter
	 */
	public function beforeMakeFeedDocumentPage($event) {}

	/**
	 * Responds to {@link QsSphinxFeedGenerator::onAfterMakeFeedDocumentPage} event.
	 * Overrides this method if you want to handle the corresponding event of the {@link CBehavior::owner owner}.
	 * @param CEvent $event event parameter
	 */
	public function afterMakeFeedDocumentPage($event) {}
}