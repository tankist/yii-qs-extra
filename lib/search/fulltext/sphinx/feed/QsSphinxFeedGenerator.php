<?php
/**
 * QsSphinxFeedGenerator class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2008-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * QsSphinxFeedGenerator is the base class for Sphinx feed generators.
 * This class should be extended in order to create a particular index feed generator.
 * QsSphinxFeedGenerator is using {@link CDataProvider} objects to get data for the feed.
 * This allows fetching data from different resource types and using of pagination.
 * 
 * Usage example:
 * <code>
 * class SphinxFeedGeneratorPost extends QsSphinxFeedGenerator {
 *     protected function fields() {
 *         return array(...);
 *     }
 * 
 *     protected function attributes() {
 *         return array(...);
 *     }
 * 
 *     protected function dataProviderKillList() {
 *         return new CActiveDataProvider('Post', array(
 *             'criteria'=>array(
 *                 'condition'=>'status_id='.Post::STATUS_DELETED, 
 *             ),
 *             'pagination'=>array(
 *                 'pageSize'=>20,
 *             ),
 *         ));
 *     }
 * 
 *     protected function dataProviderDocument() {
 *         return new CActiveDataProvider('Post', array(
 *             'criteria'=>array(
 *                 'condition'=>'status_id='.Post::STATUS_ACTUAL, 
 *             ),
 *             'pagination'=>array(
 *                 'pageSize'=>20,
 *             ),
 *         ));
 *     }
 * }
 * ... 
 * $feedGenerator = new SphinxFeedGeneratorPost();
 * $feedGenerator->makeFeed();
 * </code>
 * 
 * Note: feed generation may take much time, so ensure you are keeping database connection live.
 * To do this you may use behavior {@link QsSphinxFeedGeneratorBehaviorControlComponentActivity}.
 * 
 * @see http://sphinxsearch.com/docs/
 * @see QsSphinxFeedXml
 * @see QsSphinx
 *
 * @property QsSphinxFeedXml $feedCreator public alias of {@link _feedCreator}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qsextra.search.fulltext.sphinx.feed
 */
abstract class QsSphinxFeedGenerator extends CComponent {
	/**
	 * @var QsSphinxFeedXml feed creator instance.
	 */
	protected $_feedCreator = null;
	/**
	 * @var array internal cache data.
	 * This field is for the internal usage only.
	 */
	protected $_internalCache = array();
	/**
	 * @var boolean|null indicates if enforced garbage collection available.
	 * This field is for internal usage only.
	 */
	protected $_garbageCollectionAvailable = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init();
		$this->attachBehaviors($this->behaviors());
		$this->afterConstruct();
	}

	// Set / Get :

	public function setFeedCreator($feedCreator) {
		$this->_feedCreator = $feedCreator;
		return true;
	}

	public function getFeedCreator() {
		if (!is_object($this->_feedCreator)) {
			$this->initFeedCreator();
		}
		return $this->_feedCreator;
	}

	/**
	 * Clears internal cache data.
	 * @return boolean success.
	 */
	public function clearInternalCache() {
		$this->_internalCache = array();
		return true;
	}

	/**
	 * Logs a message.
	 * @see CLogRouter
	 * @param string $message message to be logged.
	 * @param string $level level of the message (e.g. 'trace', 'warning', 'error'). It is case-insensitive.
	 * @return boolean success.
	 */
	protected function log($message, $level=CLogger::LEVEL_INFO) {
		$category = 'qs.search.fulltext.sphinx.feed.'.get_class($this);
		Yii::log($message, $level, $category);
		return true;
	}

	/**
	 * Initializes this feed generator.
	 * This method is invoked in the constructor.
	 * You may override this method to provide code that is needed to initialize the feed generator (e.g. setting
	 * initial property values.)
	 */
	public function init() {}

	/**
	 * Initializes {@link feedCreator} instance.
	 * @return boolean success.
	 */
	protected function initFeedCreator() {
		if (is_array($this->_feedCreator)) {
			$feedCreatorConfig = $this->_feedCreator;
		} else {
			$feedCreatorConfig = array();
		}
		if (!array_key_exists('class',$feedCreatorConfig)) {
			$feedCreatorConfig['class'] = 'QsSphinxFeedXml';
		}
		$this->_feedCreator = Yii::createComponent($feedCreatorConfig);
		return true;
	}

	/**
	 * Returns a list of behaviors that this Feed generator should behave as.
	 * The return value should be an array of behavior configurations indexed by
	 * behavior names. Each behavior configuration can be either a string specifying
	 * the behavior class or an array of the following structure:
	 * <pre>
	 * 'behaviorName' => array(
	 *     'class' => 'path.to.BehaviorClass',
	 *     'property1' => 'value1',
	 *     'property2' => 'value2',
	 * )
	 * </pre>
	 *
	 * Note, the behavior classes must implement {@link IBehavior} or extend from
	 * {@link CBehavior}. Behaviors declared in this method will be attached
	 * to the model when it is instantiated.
	 *
	 * For more details about behaviors, see {@link CComponent}.
	 * @return array behaviors configuration.
	 */
	public function behaviors() {
		return array();
	}

	/**
	 * Returns the list of index feed fields.
	 * The result of this method will be set to {@link QsSphinxFeedXml::fields}.
	 * @return array list of index feed fields.
	 */
	protected function fields() {
		return array();
	}

	/**
	 * Returns the list of index feed attributes.
	 * The result of this method will be set to {@link QsSphinxFeedXml::attributes}.
	 * @return array list of index feed attributes.
	 */
	protected function attributes() {
		return array();
	}

	/**
	 * Returns the data provider for the "killlist".
	 * Override this method in order to return adequate data provider.
	 * @return CDataProvider data provider object.
	 */
	protected function dataProviderKillList() {
		return null;
	}

	/**
	 * Returns the data provider for the documents list.
	 * Override this method in order to return adequate data provider.
	 * @return CDataProvider data provider object.
	 */
	protected function dataProviderDocument() {
		return null;
	}

	/**
	 * Creates and outputs the Sphinx index feed.
	 * @return boolean success.
	 */
	public function makeFeed() {
		$this->clearInternalCache();
		$this->beforeMakeFeed();
		$this->log('Feed generation has begun');
		$feedCreator = $this->prepareFeedCreator();
		$feedCreator->begin();
		$this->makeFeedKillList();
		$this->makeFeedDocument();
		$result = $feedCreator->end();
		$this->log('Feed generation complete');
		$this->afterMakeFeed();
		return $result;
	}

	/**
	 * Prepares the {@link feedCreator} object for the feed creation begin.
	 * @return QsSphinxFeedXml feedCreator instance.
	 */
	protected function prepareFeedCreator() {
		$feedCreator = $this->getFeedCreator();
		$feedCreator->setFields($this->fields());
		$feedCreator->setAttributes($this->attributes());
		return $feedCreator;
	}

	/**
	 * Creates and outputs the "killlist" feed section.
	 * @return boolean success.
	 */
	protected function makeFeedKillList() {
		$dataProvider = $this->dataProviderKillList();
		if (!is_object($dataProvider)) {
			return true;
		}
		$getBackup = $_GET;
		$this->log('KillList feed has begun');
		
		$feedCreator = $this->getFeedCreator();
		$feedCreator->beginKillList();
		$pageNumber = 0;
		while (true) {
			$this->beforeMakeFeedKillListPage();
			
			$documents = $dataProvider->getData(true);
			$documentIds = $dataProvider->getKeys(true);
			if (empty($documentIds)) {
				break;
			}
			foreach ($documentIds as $documentId) {
				$feedCreator->writeKillListElement($documentId);
			}
			$this->enforceGarbageCollection();

			$this->afterMakeFeedKillListPage();
			
			$pageNumber++;
			$pageCount = $dataProvider->getPagination()->getPageCount();
			if ($pageNumber>=$pageCount) {
				break;
			} else {
				$dataProvider->getPagination()->setCurrentPage($pageNumber);
			}
		}
		$feedCreator->endKillList();
		
		$this->enforceGarbageCollection();
		$_GET = $getBackup;
		$this->log('KillList feed complete');
		return true;
	}

	/**
	 * Creates and outputs the set of feed documents.
	 * @return boolean success.
	 */
	protected function makeFeedDocument() {
		$dataProvider = $this->dataProviderDocument();
		if (!is_object($dataProvider)) {
			return true;
		}
		$getBackup = $_GET;
		$this->log('Document feed has begun');
		
		$feedCreator = $this->getFeedCreator();
		$pageNumber = 0;
		while (true) {
			$this->beforeMakeFeedDocumentPage();
			$sourceDocuments = $dataProvider->getData(true);
			if (empty($sourceDocuments)) {
				break;
			}
			foreach ($sourceDocuments as $sourceDocument) {
				$documentData = $this->extractDocumentData($sourceDocument);
				$feedCreator->writeDocument($documentData);
			}
			$this->enforceGarbageCollection();
			
			$this->afterMakeFeedDocumentPage();
			
			$pageNumber++;
			$pageCount = $dataProvider->getPagination()->getPageCount();
			if ($pageNumber>=$pageCount) {
				break;
			} else {
				$dataProvider->getPagination()->setCurrentPage($pageNumber);
			}
		}
		
		$this->enforceGarbageCollection();
		$_GET = $getBackup;
		$this->log('Document feed complete');
		return true;
	}

	/**
	 * Extracts the data for the document feed from the raw document data source,
	 * which can be rather an array or an object.
	 * @param mixed $documentDataSource - raw document data source, can be an array or an object.
	 * @return array document data set.
	 */
	protected function extractDocumentData($documentDataSource) {
		if (is_array($documentDataSource)) {
			return $documentDataSource;
		} elseif (is_object($documentDataSource)) {
			$feedCreator = $this->getFeedCreator();
			$allowedDocumentParamNames = $feedCreator->getAllowedDocumentParamNames();
			$documentData = array();
			foreach ($allowedDocumentParamNames as $allowedDocumentParamName) {
				$documentData[$allowedDocumentParamName] = $documentDataSource->$allowedDocumentParamName;
			}
		} else {
			throw new CException('Document feed has wrong type! Expected: array or object, given: '.gettype($documentDataSource).'.');
		}
	}

	/**
	 * Returns the the list of allowed document param names,
	 * uses internal cache.
	 * @return array list of allowed document param names.
	 */
	protected function getAllowedDocumentParamNames() {
		if (array_key_exists('allowedDocumentParamNames', $this->_internalCache)) {
			return $this->_internalCache['allowedDocumentParamNames'];
		}
		$feedCreator = $this->getFeedCreator();
		$allowedDocumentParamNames = $feedCreator->getAllowedDocumentParamNames();
		$this->_internalCache['allowedDocumentParamNames'] = $allowedDocumentParamNames;
		return $allowedDocumentParamNames;
	}

	/**
	 * Enforces the PHP garbage collector to be run.
	 * This method allows to collect cycle references.
	 * @see http://www.php.net/manual/en/features.gc.php
	 */
	protected function enforceGarbageCollection() {
		if ($this->_garbageCollectionAvailable===null) {
			if (function_exists('gc_enable')) {
				$this->_garbageCollectionAvailable = true;
			} else {
				$this->_garbageCollectionAvailable = false;
				$this->log('PHP garbage collector is not available, please update the PHP up to 5.3.0 or higher.', CLogger::LEVEL_WARNING);
			}
		}
		if ($this->_garbageCollectionAvailable) {
			if (gc_enabled()) {
				gc_collect_cycles();
			} else {
				gc_enable();
				gc_collect_cycles();
				gc_disable();
			}
		}
	}

	// Event methods: 

	/**
	 * This method is invoked after a feed generator instance is created by new operator.
	 * The default implementation raises the {@link onAfterConstruct} event.
	 * You may override this method to do postprocessing after feed generator creation.
	 * Make sure you call the parent implementation so that the event is raised properly.
	 */
	protected function afterConstruct() {
		if ($this->hasEventHandler('onAfterConstruct')) {
			$this->onAfterConstruct(new CEvent($this));
		}
	}

	/**
	 * This method is invoked before creating Sphinx feed.
	 * The default implementation raises the {@link onBeforeMakeFeed} event.
	 * You may override this method to do preprocessing before feed generation.
	 * Make sure you call the parent implementation so that the event is raised properly.
	 */
	protected function beforeMakeFeed() {
		if ($this->hasEventHandler('onBeforeMakeFeed')) {
			$this->onBeforeMakeFeed(new CEvent($this));
		}
	}

	/**
	 * This method is invoked after creating Sphinx feed is complete.
	 * The default implementation raises the {@link onAfterMakeFeed} event.
	 * You may override this method to do postprocessing after feed generation.
	 * Make sure you call the parent implementation so that the event is raised properly.
	 */
	protected function afterMakeFeed() {
		if ($this->hasEventHandler('onAfterMakeFeed')) {
			$this->onAfterMakeFeed(new CEvent($this));
		}
	}

	/**
	 * This method is invoked before creating "killlist" feed signle page.
	 * The default implementation raises the {@link onBeforeMakeFeedKillListPage} event.
	 * You may override this method to do preprocessing before feed page generation.
	 * Make sure you call the parent implementation so that the event is raised properly.
	 */
	protected function beforeMakeFeedKillListPage() {
		if ($this->hasEventHandler('onBeforeMakeFeedKillListPage')) {
			$this->onBeforeMakeFeedKillListPage(new CEvent($this));
		}
	}

	/**
	 * This method is invoked after creating "killlist" feed signle page.
	 * The default implementation raises the {@link onAfterMakeFeedKillListPage} event.
	 * You may override this method to do postprocessing after feed page generation.
	 * Make sure you call the parent implementation so that the event is raised properly.
	 */
	protected function afterMakeFeedKillListPage() {
		if ($this->hasEventHandler('onAfterMakeFeedKillListPage')) {
			$this->onAfterMakeFeedKillListPage(new CEvent($this));
		}
	}

	/**
	 * This method is invoked before creating "document" feed signle page.
	 * The default implementation raises the {@link onBeforeMakeFeedDocumentPage} event.
	 * You may override this method to do preprocessing before feed page generation.
	 * Make sure you call the parent implementation so that the event is raised properly.
	 */
	protected function beforeMakeFeedDocumentPage() {
		if ($this->hasEventHandler('onBeforeMakeFeedDocumentPage')) {
			$this->onBeforeMakeFeedDocumentPage(new CEvent($this));
		}
	}

	/**
	 * This method is invoked after creating "document" feed signle page.
	 * The default implementation raises the {@link onAfterMakeFeedDocumentPage} event.
	 * You may override this method to do postprocessing after feed page generation.
	 * Make sure you call the parent implementation so that the event is raised properly.
	 */
	protected function afterMakeFeedDocumentPage() {
		if ($this->hasEventHandler('onAfterMakeFeedDocumentPage')) {
			$this->onAfterMakeFeedDocumentPage(new CEvent($this));
		}
	}

	// Events:

	/**
	 * This event is raised after the feed generator instance is created by new operator.
	 * @param CEvent $event the event parameter
	 */
	public function onAfterConstruct($event) {
		$this->raiseEvent('onAfterConstruct', $event);
	}

	/**
	 * This event is raised before creating Sphinx feed.
	 * @param CEvent $event the event parameter
	 */
	public function onBeforeMakeFeed($event) {
		$this->raiseEvent('onBeforeMakeFeed', $event);
	}

	/**
	 * This event is raised after creating Sphinx feed is complete.
	 * @param CEvent $event the event parameter
	 */
	public function onAfterMakeFeed($event) {
		$this->raiseEvent('onAfterMakeFeed', $event);
	}

	/**
	 * This event is raised before creating "killlist" feed signle page.
	 * @param CEvent $event the event parameter
	 */
	public function onBeforeMakeFeedKillListPage($event) {
		$this->raiseEvent('onBeforeMakeFeedKillListPage', $event);
	}

	/**
	 * This event is raised after creating "killlist" feed signle page.
	 * @param CEvent $event the event parameter
	 */
	public function onAfterMakeFeedKillListPage($event) {
		$this->raiseEvent('onAfterMakeFeedKillListPage', $event);
	}

	/**
	 * This event is raised before creating "document" feed signle page.
	 * @param CEvent $event the event parameter
	 */
	public function onBeforeMakeFeedDocumentPage($event) {
		$this->raiseEvent('onBeforeMakeFeedDocumentPage', $event);
	}

	/**
	 * This event is raised after creating "document" feed signle page.
	 * @param CEvent $event the event parameter
	 */
	public function onAfterMakeFeedDocumentPage($event) {
		$this->raiseEvent('onAfterMakeFeedDocumentPage', $event);
	}
}