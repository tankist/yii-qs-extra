<?php
/**
 * QsSphinxActiveRecord class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2008-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * QsSphinxActiveRecord is the data model for the Sphinx search result.
 * Each instance of this class represents single Sphinx document, returned by the query.
 * 
 * @see QsSphinx
 * @see QsSphinxCriteria
 *
 * @property string $sphinxComponentName public alias of {@link _sphinxComponentName}.
 * @property string $index public alias of {@link _index}.
 * @property QsSphinxCriteria $sphinxCriteria public alias of {@link _sphinxCriteria}.
 * @property array $defaultSnippetOptions public alias of {@link _defaultSnippetOptions}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qsextra.search.fulltext.sphinx.ar
 */
class QsSphinxActiveRecord extends QsSphinxModel {
	/**
	 * @var array static Sphinx models in format: class name => model.
	 */
	protected static $_models = array();

	/**
	 * @var string name of the application component, which
	 * stores {@link QsSphinx} instance.
	 */
	protected $_sphinxComponentName = 'sphinx';
	/**
	 * @var string sphinx index locator.
	 * Default value - "*", meaning all indexes.
	 */
	protected $_index = '*';
	/**
	 * @var QsSphinxCriteria internal Sphinx search criteria instance.
	 */
	protected $_sphinxCriteria = null;
	/**
	 * @var array options for the query, which is used by default to acquire snippet.
	 * @see QsSphinx::buildSnippets()
	 */
	protected $_defaultSnippetOptions = array();

	/**
	 * Returns the static model of the specified Sphinx model class.
	 * The model returned is a static instance of the Sphinx model class.
	 *
	 * EVERY derived Sphinx AR model class must override this method as follows,
	 * <pre>
	 * public static function model($className=__CLASS__) {
	 *     return parent::model($className);
	 * }
	 * </pre>
	 *
	 * @param string $className active record class name.
	 * @return QsSphinxActiveRecord active record model instance.
	 */
	public static function model($className=__CLASS__) {
		if (isset(self::$_models[$className])) {
			return self::$_models[$className];
		} else {
			$model=self::$_models[$className] = new $className();
			return $model;
		}
	}

	/**
	 * Calls the named method which is not a class method.
	 * Do not call this method. This is a PHP magic method that we override
	 * to implement the named scope feature.
	 * @param string $name the method name
	 * @param array $parameters method parameters
	 * @return mixed the method return value
	 */
	public function __call($name, $parameters) {
		$scopes = $this->scopes();
		if (isset($scopes[$name])) {
			$this->getSphinxCriteria()->mergeWith($scopes[$name]);
			return $this;
		}
		return parent::__call($name, $parameters);
	}

	// Set / Get :

	public function setSphinxComponentName($sphinxComponentName) {
		if (!is_string($sphinxComponentName)) {
			throw new CException('"'.get_class($this).'::sphinxComponentName" should be a string!');
		}
		$this->_sphinxComponentName = $sphinxComponentName;
		return true;
	}

	public function getSphinxComponentName() {
		return $this->_sphinxComponentName;
	}

	public function setIndex($index) {
		if (!is_string($index)) {
			throw new CException('"'.get_class($this).'::index" should be a string!');
		}
		$this->_index = $index;
		return true;
	}

	public function getIndex() {
		return $this->_index;
	}

	public function setSphinxCriteria($sphinxCriteria) {
		$this->_sphinxCriteria = $sphinxCriteria;
		return true;
	}

	public function getSphinxCriteria() {
		if (!is_object($this->_sphinxCriteria)) {
			$this->initSphinxCriteria();
		}
		return $this->_sphinxCriteria;
	}

	public function setDefaultSnippetOptions(array $defaultSnippetOptions) {
		$this->_defaultSnippetOptions = $defaultSnippetOptions;
		return true;
	}

	public function getDefaultSnippetOptions() {
		return $this->_defaultSnippetOptions;
	}

	/**
	 * Returns the {@link QsSphinx} application component.
	 * @return QsSphinx Sphinx component.
	 */
	public function getSphinx() {
		return Yii::app()->getComponent($this->getSphinxComponentName());
	}

	/**
	 * Initializes the internal fields using the Sphinx query result data.
	 * Raises {@link afterFind} event.
	 * @param integer $documentId - Sphinx document id.
	 * @param array $sphinxDocumentData - Sphinx single document data.
	 * @param QsSphinxCriteria $lastSphinxCriteria - Sphinx criteria used to find this document.
	 * @return boolean success.
	 */
	public function populateModel($documentId, array $sphinxDocumentData, $lastSphinxCriteria=null) {
		$result = parent::populateModel($documentId, $sphinxDocumentData, $lastSphinxCriteria);
		if ($result) {
			$this->afterFind();
		}
		return $result;
	}

	/**
	 * Initializes internal Sphinx criteria,
	 * applies default scopes to it.
	 * @return boolean success.
	 */
	protected function initSphinxCriteria() {
		if (is_array($this->_sphinxCriteria)) {
			$sphinxCriteriaConfig = $this->_sphinxCriteria;
		} else {
			$sphinxCriteriaConfig = array();
		}
		$sphinxCriteria = new QsSphinxCriteria($sphinxCriteriaConfig);
		$scopes = $this->defaultScope();
		foreach ($scopes as $criteria) {
			$sphinxCriteria->mergeWith($criteria);
		}
		$this->_sphinxCriteria = $sphinxCriteria;
		return true;
	}

	/**
	 * Clears internal Sphinx criteria.
	 * @return boolean success.
	 */
	public function resetSphinxCriteria() {
		$this->_sphinxCriteria = null;
		return true;
	}

	/**
	 * Merges own Sphinx criteria with the given one.
	 * @param QsSphinxCriteria|array $sphinxCriteria criteria for the merging.
	 * @return QsSphinxActiveRecord self reference.
	 */
	public function mergeSphinxCriteria($sphinxCriteria) {
		$thisCriteria = $this->getSphinxCriteria();
		$thisCriteria->mergeWith($sphinxCriteria);
		return $this;
	}

	/**
	 * Applies {QsSphinxCriteria::scopes} to the internal Sphinx search criteria.
	 * This method translates scopes into the actual criteria params.
	 * @param QsSphinxCriteria $criteria Sphinx search criteria.
	 * @return boolean success.
	 */
	protected function applySphinxCriteriaScopes($criteria=null) {
		if (!is_object($criteria)) {
			$criteria = $this->getSphinxCriteria();
		}
		$criteriaScopes = $criteria->getScopes();
		if (!empty($criteriaScopes)) {
			foreach ($criteriaScopes as $key => $value) {
				if (is_numeric($key)) {
					$scopeName = $value;
					$scopeParams = array();
					$thisScopes = $this->scopes();
					if (isset($thisScopes[$scopeName])) {
						$this->mergeSphinxCriteria($thisScopes[$scopeName]);
					} else {
						call_user_func_array(array($this, $scopeName), $scopeParams);
					}
				} else {
					$scopeName = $key;
					$scopeParams = $value;
					call_user_func_array(array($this, $scopeName), $scopeParams);
				}
			}
			$criteria->setScopes(array());
		}
		return true;
	}

	/**
	 * Performs Sphinx fulltext search query according to specified search criteria.
	 * @param QsSphinxCriteria|array $criteria search criteria.
	 * @param boolean $all indicates if all documents should be queried, or the single one only.
	 * @return array list of models.
	 */
	protected function query($criteria, $all=false) {
		$sphinx = $this->getSphinx();
		$this->mergeSphinxCriteria($criteria);
		$this->applySphinxCriteriaScopes();
		$criteria = $this->getSphinxCriteria();
		$criteria->setIndex($this->getIndex());
		if (!$all) {
			$criteria->setLimit(1);
		}
		$this->beforeFind();
		$models = $sphinx->findModelAll($criteria, get_class($this));
		$this->resetSphinxCriteria();
		//$this->afterFind();
		return $models;
	}

	/**
	 * Performs fulltext search according to specified search criteria.
	 * @param QsSphinxCriteria|array $criteria search criteria.
	 * @return array list of models.
	 */
	public function findAll($criteria=array()) {
		return $this->query($criteria, true);
	}

	/**
	 * Performs fulltext search according to specified search criteria.
	 * Fetches only the firsts result model.
	 * @param QsSphinxCriteria|array $criteria search criteria.
	 * @return array list of models.
	 */
	public function find($criteria=array()) {
		$models = $this->query($criteria, false);
		if (is_array($models)) {
			$model = array_shift($models);
			return $model;
		}
		return null;
	}

	/**
	 * Finds the number of Sphinx documents satisfying the specified query condition.
	 * @param QsSphinxCriteria|array $criteria search criteria.
	 * @return integer number of documents satisfying the specified query condition.
	 */
	public function count($criteria=array()) {
		$sphinx = $this->getSphinx();
		$this->mergeSphinxCriteria($criteria);
		$this->applySphinxCriteriaScopes();
		$criteria = $this->getSphinxCriteria();
		$criteria->setIndex($this->getIndex());
		$count = $sphinx->count($criteria);
		$this->resetSphinxCriteria();
		return $count;
	}

	/**
	 * Returns the declaration of named scopes.
	 * A named scope represents a Sphinx query criteria that can be chained together with
	 * other named scopes and applied to a query. This method should be overridden
	 * by child classes to declare named scopes for the particular Sphinx AR classes.
	 * For example, the following code declares two named scopes: 'recently' and
	 * 'published'.
	 * <code>
	 * return array(
	 *     'published'=>array(
	 *         'filters'=>array(
	 *              'attribute' => 'status_id',
	 *              'values' => array(2),
	 *         ),
	 *     ),
	 *     'recently'=>array(
	 *         'order'=>array(
	 *             QsSphinx::SORT_ATTRIBUTE_DESC,
	 *             'date',
	 *         ),
	 *         'limit'=>5,
	 *     ),
	 * );
	 * </code>
	 * If the above scopes are declared in a 'Post' model, we can perform the following
	 * queries:
	 * <code>
	 * $posts = Post::model()->published()->findAll();
	 * $posts = Post::model()->published()->recently()->findAll();
	 * </code>
	 *
	 * @return array the scope definition. The array keys are scope names; the array
	 * values are the corresponding scope definitions. Each scope definition is represented
	 * as an array whose keys must be properties of {@link QsSphinxCriteria}.
	 */
	public function scopes() {
		return array();
	}

	/**
	 * Returns the default named scope that should be implicitly applied to all queries for this model.
	 * The default implementation simply returns an empty array. You may override this method
	 * if the model needs to be queried with some default criteria (e.g. only active records should be returned).
	 * @return array the Sphinx query criteria. This will be used as the parameter to the constructor
	 * of {@link QsSphinxCriteria}.
	 */
	public function defaultScope() {
		return array();
	}

	/**
	 * Saves model, which means performing update of own attributes in own indexes.
	 * 
	 * Warning: saving the model in this way has a temporary effect, which will be lost after
	 * the Sphinx index next update! Do not forget to make index feed for these changes too.
	 * 
	 * @param boolean $runValidation whether to perform validation before saving the document.
	 * @return integer amount of updated documents (0 or more) on success, or -1 on failure.
	 */
	public function save($runValidation=true) {
		if (!$runValidation || $this->validate()) {
			if ($this->beforeSave()) {
				$sphinx = Yii::app()->getComponent($this->getSphinxComponentName());
				$attributes = $this->getDocumentAttributeNames();
				$values = array(
					$this->getDocumentId() => $this->getDocumentAttributes()
				);
				$mva = false;
				$result = $sphinx->updateAttributes($this->getIndex(), $attributes, $values, $mva);
				$this->afterSave();
				return $result;
			}
		}
		return false;
	}

	/**
	 * Builds search snippet (exceprt) for this model.
	 * @param array $options - build snippet query options.
	 * @param string|boolean $searchString - searching string, if boolean 'false' passed will be fetched from {@link lastSphinxCriteria}
	 * @return string snippet content.
	 * @see QsSphinxModel::getDocumentContent()
	 * @see QsSphinx::buildSnippets()
	 */
	public function getSnippet(array $options=array(), $searchString=false) {
		$sphinx = $this->getSphinx();
		
		$options = array_merge($this->getDefaultSnippetOptions(), $options);
		
		if ($searchString===false) {
			$searchString = $this->determineCriteriaConditionString();
			$options['query_mode'] = true;
		}
		$documentContents = array(
			$this->getDocumentContent()
		);
		$index = $this->determineIndexName();
		
		$snippets = $sphinx->buildSnippets($documentContents, $index, $searchString, $options);
		return array_shift($snippets);
	}

	/**
	 * Determines search condition string.
	 * Method attempts to use {@link lastSphinxCriteria} to acquire search string.
	 * @return string Sphinx query search string.
	 */
	protected function determineCriteriaConditionString() {
		$criteria = $this->getLastSphinxCriteria();
		if (is_object($criteria)) {
			$conditionString = $criteria->getCondition();
			return $conditionString;
		} else {
			throw new CException('Unable to determine criteria condition string!');
		}
	}

	/**
	 * Extract index name from the {@link index}, which can be passed to
	 * {@link QsSphinx::buildSnippets()} method.
	 * @return string Sphinx local index name.
	 */
	protected function determineIndexName() {
		$index = $this->getIndex();
		if (strpos($index, ',')!==false) {
			$indexNames = explode(',', $index);
			if (is_array($indexNames)) {
				list($indexName) = $indexNames;
				return trim($indexName);
			}
		}
		return $index;
	}

	/**
	 * Generates keyword list for a model.
	 * @param string|boolean $searchString query to extract keywords from, if boolean 'false' passed will be fetched from {@link lastSphinxCriteria}.
	 * @param boolean $statisticRequired - flag that indicates whether keyword occurrence statistics (hits) are required.
	 * @return integer amount of updated documents (0 or more) on success, or -1 on failure.
	 */
	public function getKeywords($searchString=false, $statisticRequired=false) {
		$sphinx = $this->getSphinx();
		if ($searchString===false) {
			$searchString = $this->determineCriteriaConditionString();
		}
		$index = $this->determineIndexName();
		return $sphinx->buildKeywords($searchString, $index, $statisticRequired);
	}

	// Event methods: 

	/**
	 * This method is invoked before an AR finder executes a find call.
	 * The default implementation raises the {@link onBeforeFind} event.
	 * If you override this method, make sure you call the parent implementation
	 * so that the event is raised properly.
	 */
	protected function beforeFind() {
		if ($this->hasEventHandler('onBeforeFind')) {
			$event = new CModelEvent($this);
			$this->onBeforeFind($event);
		}
	}

	/**
	 * This method is invoked after each document is instantiated by a find method.
	 * The default implementation raises the {@link onAfterFind} event.
	 * You may override this method to do postprocessing after each newly found document is instantiated.
	 * Make sure you call the parent implementation so that the event is raised properly.
	 */
	protected function afterFind() {
		if ($this->hasEventHandler('onAfterFind')) {
			$this->onAfterFind(new CEvent($this));
		}
	}

	/**
	 * This method is invoked before saving a document (after validation, if any).
	 * The default implementation raises the {@link onBeforeSave} event.
	 * You may override this method to do any preparation work for record saving.
	 * @return boolean whether the saving should be executed. Defaults to true.
	 */
	protected function beforeSave() {
		if ($this->hasEventHandler('onBeforeSave')) {
			$event = new CModelEvent($this);
			$this->onBeforeSave($event);
			return $event->isValid;
		} else {
			return true;
		}
	}

	/**
	 * This method is invoked after saving a document successfully.
	 * The default implementation raises the {@link onAfterSave} event.
	 * You may override this method to do postprocessing after record saving.
	 * Make sure you call the parent implementation so that the event is raised properly.
	 */
	protected function afterSave() {
		if ($this->hasEventHandler('onAfterSave')) {
			$this->onAfterSave(new CEvent($this));
		}
	}

	// Events:

	/**
	 * This event is raised before an AR finder performs a find call.
	 * If you want to access the query criteria , please use {@link getSphinxCriteria()}.
	 * You can modify either criteria to customize them based on needs.
	 * @param CModelEvent $event the event parameter
	 * @see beforeFind
	 */
	public function onBeforeFind($event) {
		$this->raiseEvent('onBeforeFind', $event);
	}

	/**
	 * This event is raised after the record is instantiated by a find method.
	 * @param CEvent $event the event parameter
	 */
	public function onAfterFind($event) {
		$this->raiseEvent('onAfterFind', $event);
	}

	/**
	 * This event is raised before the document is saved.
	 * By setting {@link CModelEvent::isValid} to be false, the normal {@link save()} process will be stopped.
	 * @param CModelEvent $event the event parameter
	 */
	public function onBeforeSave($event) {
		$this->raiseEvent('onBeforeSave', $event);
	}

	/**
	 * This event is raised after the record is saved.
	 * @param CEvent $event the event parameter
	 */
	public function onAfterSave($event) {
		$this->raiseEvent('onAfterSave', $event);
	}
}