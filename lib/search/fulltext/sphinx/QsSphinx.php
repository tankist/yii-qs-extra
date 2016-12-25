<?php
/**
 * QsSphinx class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2008-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

$qsSphinxPath = dirname(__FILE__);
Yii::setPathOfAlias('qssphinx', $qsSphinxPath);
Yii::import('qssphinx.*');
Yii::import('qssphinx.vendors.*');
 
/** 
 * QsSphinx is the wrapper for the Sphinx fulltext search engine.
 * 
 * Application config example:
 * <code>
 * 'import'=>array(
 *     'ext.qs.search.fulltext.sphinx.*',
 *     'ext.qs.search.fulltext.sphinx.ar.*', // optional
 *     'ext.qs.search.fulltext.sphinx.db.*', // optional
 *     'ext.qs.search.fulltext.sphinx.feed.*', // optional
 *     ...
 * ),
 * ...
 * 'components'=>array(
 *     ...
 *     'sphinx'=> array(
 *         'class'=>'QsSphinx',
 *         'host'=>'localhost',
 *         'port'=>6712, 
 *     ),
 *     ...
 * ),
 * </code>
 * 
 * Usage example:
 * <code>
 * $criteria = array(
 *     'condition' => 'some search string',
 *     'index' => '*',
 *     'filters' => array(
 *         'attribute' => status_id,
 *         'values' => array(2),
 *     ),
 * );
 * $sphinxQueryResult = Yii::app()->sphinx->findAll($criteria);
 * $matches = Yii::app()->sphinx->findMatchAll($criteria);
 * $documentIds = Yii::app()->sphinx->findDocumentIdAll($criteria); 
 * </code>
 * 
 * @see http://sphinxsearch.com/docs/
 * @see SphinxClient
 * @see QsSphinxCriteria
 * @see QsSphinxModel
 *
 * @property SphinxClient $sphinxClient public alias of {@link _sphinxClient}.
 * @property string $host public alias of {@link _host}.
 * @property integer $port public alias of {@link _port}.
 * @property integer $maxQueryTime public alias of {@link _maxQueryTime}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qsextra.search.fulltext.sphinx
 */
class QsSphinx extends CApplicationComponent {
	// known match modes:
	const MACTH_ALL = 'all';
	const MACTH_ANY = 'any';
	const MACTH_PHRASE = 'phrase';
	const MACTH_BOOLEAN = 'boolean';
	const MACTH_EXTENDED = 'extended';
	const MACTH_FULLSCAN = 'fullscan';
	// known sort modes:
	const SORT_RELEVANCE = 'relevance';
	const SORT_ATTRIBUTE_DESC = 'attribute_desc';
	const SORT_ATTRIBUTE_ASC = 'attribute_asc';
	const SORT_TIME_SEGMENTS = 'time_segments';
	const SORT_EXTENDED = 'extended';
	const SORT_EXPRESSION = 'expression';
	// known filter types:
	const FILTER_VALUES = 'values';
	const FILTER_RANGE = 'range';
	const FILTER_RANGE_FLOAT = 'range_float';
	// known grouping functions:
	const GROUP_DAY = 'day';
	const GROUP_WEEK = 'week';
	const GROUP_MONTH = 'month';
	const GROUP_YEAR = 'year';
	const GROUP_ATTRIBUTE = 'attribute';
	const GROUP_ATTRIBUTE_PAIR = 'attribute_pair';

	/**
	 * Finds real constant value for the {@link SphinxClient} instance.
	 * Used for the 'match' constants group.
	 * @param string $constantValue internal constant value.
	 * @return integer matching {@link SphinxClient} constant value.
	 */
	public static function realConstantValueMatch($constantValue) {
		switch ($constantValue) {
			case self::MACTH_ALL: {
				return 0;
			}
			case self::MACTH_ANY: {
				return 1;
			}
			case self::MACTH_PHRASE: {
				return 2;
			}
			case self::MACTH_BOOLEAN: {
				return 3;
			}
			case self::MACTH_EXTENDED: {
				return 6;
			}
			case self::MACTH_FULLSCAN: {
				return 5;
			}
			default: {
				return $constantValue;
			}
		}
	}

	/**
	 * Finds real constant value for the {@link SphinxClient} instance.
	 * Used for the 'sort' constants group.
	 * @param string $constantValue internal constant value.
	 * @return integer matching {@link SphinxClient} constant value.
	 */
	public static function realConstantValueSort($constantValue) {
		switch ($constantValue) {
			case self::SORT_RELEVANCE: {
				return 0;
			}
			case self::SORT_ATTRIBUTE_DESC: {
				return 1;
			}
			case self::SORT_ATTRIBUTE_ASC: {
				return 2;
			}
			case self::SORT_TIME_SEGMENTS: {
				return 3;
			}
			case self::SORT_EXTENDED: {
				return 4;
			}
			case self::SORT_EXPRESSION: {
				return 5;
			}
			default: {
				return $constantValue;
			}
		}
	}

	/**
	 * Finds real constant value for the {@link SphinxClient} instance.
	 * Used for the 'filter' constants group.
	 * @param string $constantValue internal constant value.
	 * @return integer matching {@link SphinxClient} constant value.
	 */
	public static function realConstantValueFilter($constantValue) {
		switch ($constantValue) {
			case self::FILTER_VALUES: {
				return 0;
			}
			case self::FILTER_RANGE: {
				return 1;
			}
			case self::FILTER_RANGE_FLOAT: {
				return 2;
			}
			default: {
				return $constantValue;
			}
		}
	}

	/**
	 * Finds real constant value for the {@link SphinxClient} instance.
	 * Used for the 'group' constants group.
	 * @param string $constantValue internal constant value.
	 * @return integer matching {@link SphinxClient} constant value.
	 */
	public static function realConstantValueGroup($constantValue) {
		switch ($constantValue) {
			case self::GROUP_DAY: {
				return 0;
			}
			case self::GROUP_WEEK: {
				return 1;
			}
			case self::GROUP_MONTH: {
				return 2;
			}
			case self::GROUP_YEAR: {
				return 3;
			}
			case self::GROUP_ATTRIBUTE: {
				return 4;
			}
			case self::GROUP_ATTRIBUTE_PAIR: {
				return 5;
			}
			default: {
				return $constantValue;
			}
		}
	}

	/**
	 * @var SphinxClient sphinx client instance.
	 */
	protected $_sphinxClient = null;
	/**
	 * @var string Sphinx server host name.
	 */
	protected $_host = 'localhost';
	/**
	 * @var integer Sphinx server port.
	 */
	protected $_port = 6712;
	/**
	 * @var integer maximum query time in milliseconds. 
	 * Default value is 0, meaning do not limit.
	 */
	protected $_maxQueryTime = 0;
	/**
	 * @var boolean whether to enable profiling the Sphinx queries being executed.
	 * Defaults to false. This should be mainly enabled and used during development.
	 */
	public $enableProfiling = false;

	public function __get($name) {
		try {
			return parent::__get($name);
		} catch (CException $exception) {
			$getter = 'get'.$name;
			$sphinxClient = $this->getSphinxClient();
			if (method_exists($sphinxClient, $getter)) {
				return $sphinxClient->$getter();
			} else {
				throw $exception;
			}
		}
	}

	public function __set($name, $value) {
		try {
			return parent::__set($name, $value);
		} catch (CException $exception) {
			$setter = 'set'.$name;
			$sphinxClient = $this->getSphinxClient();
			if (method_exists($sphinxClient, $setter)) {
				$sphinxClient->$setter($value);
			} else {
				throw $exception;
			}
		}
	}

	public function __call($name, $parameters) {
		try {
			return parent::__call($name, $parameters);
		} catch (CException $exception) {
			$sphinxClient = $this->getSphinxClient();
			if (method_exists($sphinxClient, $name)) {
				return call_user_func_array(array($sphinxClient, $name), $parameters);
			} else {
				throw $exception;
			}
		}
	}

	// Set / Get :

	public function setSphinxClient($sphinxClient) {
		if (!is_object($sphinxClient)) {
			throw new CException('"'.get_class($this).'::sphinxClient" should be an instance of SphinxClient class!');
		}
		$sphinxClient->setServer($this->getHost(), $this->getPort());
		$sphinxClient->setMaxQueryTime( $this->getMaxQueryTime() );
		$this->_sphinxClient = $sphinxClient;
		return true;
	}

	public function getSphinxClient() {
		if (!is_object($this->_sphinxClient)) {
			$this->initSphinxClient();
		}
		return $this->_sphinxClient;
	}

	public function setHost($host) {
		$this->_host = $host;
		return true;
	}

	public function getHost() {
		return $this->_host;
	}

	public function setPort($port) {
		if (!is_numeric($port)) {
			throw new CException('"'.get_class($this).'::port" should be an integer!');
		}
		$this->_port = (int)$port;
		return true;
	}

	public function getPort() {
		return $this->_port;
	}

	public function setMaxQueryTime($maxQueryTime) {
		if (!is_numeric($maxQueryTime)) {
			throw new CException('"'.get_class($this).'::maxQueryTime" should be an integer!');
		}
		$this->_maxQueryTime = $maxQueryTime;
		return true;
	}

	public function getMaxQueryTime() {
		return $this->_maxQueryTime;
	}

	/**
	 * Initializes the application component.
	 * If you override this method, make sure to call the parent implementation
	 * so that the application component can be marked as initialized.
	 */
	public function init() {
		parent::init();
	}

	/**
	 * Initializes the {@link sphinxClient} field.
	 * @return boolean success.
	 */
	protected function initSphinxClient() {
		if (!class_exists('SphinxClient')) {
			$vendorsPath = dirname(__FILE__).DIRECTORY_SEPARATOR.'vendors'.DIRECTORY_SEPARATOR;
			require_once($vendorsPath.'SphinxClient.php');
		}
		$sphinxClient = new SphinxClient();
		return $this->setSphinxClient($sphinxClient);
	}

	/**
	 * Logs a message.
	 * @see CLogRouter
	 * @param string $message message to be logged.
	 * @param string $level level of the message (e.g. 'trace', 'warning', 'error'). It is case-insensitive.
	 * @return boolean success.
	 */
	protected function log($message, $level=CLogger::LEVEL_INFO) {
		$category = 'qs.search.fulltext.sphinx';
		Yii::log($message, $level, $category);
		return true;
	}

	/**
	 * Checks if there are errors or warnings in Sphinx client.
	 * Logs found errors and warnings.
	 * Throws an exception on Sphinx fatal error.
	 * @param SphinxClient|null $sphinxClient - Sphinx client instance.
	 * @return array search result.
	 * @throws CException on Sphinx error.
	 */
	protected function trySphinxErrors($sphinxClient=null) {
		if (!is_object($sphinxClient)) {
			$sphinxClient = $this->getSphinxClient();
		}
		if ($queryWarning=$sphinxClient->GetLastWarning()) {
			$this->log('Sphinx warning: '.$queryWarning, CLogger::LEVEL_WARNING);
		}
		if ($queryError=$sphinxClient->GetLastError()) {
			$errorMessage = 'Sphinx error: '.$queryError;
			$this->log($errorMessage, CLogger::LEVEL_ERROR);
			throw new CException($errorMessage);
		}
		return true;
	}

	/**
	 * Performs fulltext search query according to specified search criteria.
	 * @param array|QsSphinxCriteria $criteria - search criteria.
	 * @param boolean $all indicates if all documents should be queried, or the single one only.
	 * @return array search result.
	 */
	protected function query($criteria=null, $all=true) {
		$criteria = $this->fetchCriteria($criteria);
		if (!$all) {
			$criteria->setLimit(1);
		}
		
		$sphinxClient = clone $this->getSphinxClient();
		
		$criteria->apply($sphinxClient);
		
		if ($this->enableProfiling) {
			$profileMethodName = 'qs.search.fulltext.sphinx.'.get_class($this).'.'.__FUNCTION__;
			$profileToken = $profileMethodName.'(condition='.$criteria->getCondition().
                ', filters='.$criteria->getFilters().',index='.$criteria->getIndex().')';
			Yii::beginProfile($profileToken, $profileMethodName);
		}
		
		$queryResult = $sphinxClient->query($criteria->getQuery(), $criteria->getIndex(), $criteria->getComment());
		$this->trySphinxErrors($sphinxClient);
		
		if ($this->enableProfiling) {
			Yii::endProfile($profileToken, $profileMethodName);
		}
		
		if (!array_key_exists('matches', $queryResult) || !is_array($queryResult['matches'])) {
			$queryResult['matches'] = array();
		}
		return $queryResult;
	}

	/**
	 * Ensures search criteria to be an object.
	 * @param array|QsSphinxCriteria $criteria search criteria
	 * @return QsSphinxCriteria search criteria instance.
	 * @throws CException on invalid criteria.
	 */
	public function fetchCriteria($criteria) {
		if ($criteria===null) {
			$criteria = array();
		}
		if (is_array($criteria)) {
			$criteria['class'] = 'QsSphinxCriteria';
			return Yii::createComponent($criteria);
		} elseif (is_object($criteria)) {
			return $criteria;
		} else {
			throw new CException('Search criteria should be an instance of the QsSphinxCriteria or its config!');
		}
	}

	/**
	 * Performs fulltext search according to specified search criteria.
	 * Returns entire query result.
	 * @param array|QsSphinxCriteria $criteria search criteria.
	 * @return array search result.
	 */
	public function findAll($criteria=null) {
		$queryResult = $this->query($criteria);
		return $queryResult;
	}

	/**
	 * Performs fulltext search according to specified search criteria.
	 * Returns only the set of matches.
	 * @param array|QsSphinxCriteria $criteria search criteria.
	 * @return array search matches list.
	 */
	public function findMatchAll($criteria=null) {
		$queryResult = $this->query($criteria);
		return $queryResult['matches'];
	}

	/**
	 * Performs fulltext search according to specified search criteria.
	 * Returns only the document ids.
	 * @param array|QsSphinxCriteria $criteria search criteria.
	 * @return array Sphinx document id list.
	 */
	public function findDocumentIdAll($criteria=null) {
		$queryResult = $this->query($criteria);
		return array_keys($queryResult['matches']);
	}

	/**
	 * Performs fulltext search according to specified search criteria.
	 * Wraps result into {@link QsSphinxModel} instances.
	 * @param array|QsSphinxCriteria $criteria search criteria.
	 * @param string $modelClassName model class name.
	 * @return array list of models.
	 * @throws CException on error.
	 */
	public function findModelAll($criteria=null, $modelClassName='QsSphinxModel') {
		if (!is_string($modelClassName)) {
			throw new CException('Model class name should be a string! "'.gettype($modelClassName).'" has been given!');
		}
		$models = array();
		$matches = $this->findMatchAll($criteria);
		foreach ($matches as $documentId => $match) {
			$model = new $modelClassName();
			$model->populateModel($documentId, $match, $criteria);
			$models[] = $model;
		}
		return $models;
	}

	/**
	 * Finds the number of Sphinx documents satisfying the specified query condition.
	 * @param array|QsSphinxCriteria $criteria search criteria.
	 * @return integer number of documents satisfying the specified query condition.
	 */
	public function count($criteria=null) {
		$queryResult = $this->query($criteria, false);
		return $queryResult['total'];
	}

	/**
	 * Performs batch update given attributes in given rows in given indexes.
	 * @param string $index Sphinx index locator.
	 * @param array $attributes list of attributes to be updated.
	 * @param array $values list of new attribute values.
	 * @param boolean $mva - are multi valued attributes used.
	 * @return integer amount of updated documents (0 or more) on success, or -1 on failure.
	 */
	public function updateAttributes($index, array $attributes, array $values, $mva=false) {
		$sphinxClient = $this->getSphinxClient();
		
		if ($this->enableProfiling) {
			$profileMethodName = 'qs.search.fulltext.sphinx.'.get_class($this).'.'.__FUNCTION__;
			$profileToken = $profileMethodName.'(index='.$index.')';
			Yii::beginProfile($profileToken, $profileMethodName);
		}
		
		$result = $sphinxClient->UpdateAttributes($index, $attributes, $values, $mva);
		$this->trySphinxErrors($sphinxClient);
		
		if ($this->enableProfiling) {
			Yii::endProfile($profileToken, $profileMethodName);
		}
		
		return $result;
	}

	/**
	 * Generates snippets (exceprts) of given documents for given query.
	 * The following options can be specified:
	 * - 'before_match' - string to insert before a keyword match. String, default is '<b>'.
	 * - 'after_match' - string to insert after a keyword match. String,default is '</b>'.
	 * - 'chunk_separator' - string to insert between snippet chunks (passages). String, default is '...'.
	 * - 'limit' - integer - maximum snippet size, in symbols (codepoints). Integer, default is '256'.
	 * - 'limit_passages' - limits the maximum number of passages that can be included into the snippet. Integer, default is '0'(no limit).
	 * - 'limit_words' - limits the maximum number of keywords that can be included into the snippet. Integer, default is '0'(no limit).
	 * - 'around' - how much words to pick around each matching keywords block. Integer, default is '5'.
	 * - 'exact_phrase' - whether to highlight exact query phrase matches only instead of individual keywords. Boolean, default is 'false'.
	 * - 'single_passage' - whether to extract single best passage only. Boolean, default is 'false'.
	 * - 'use_boundaries' - whether to additionally break passages by phrase boundary characters. Boolean, default is 'false'.
	 * - 'weight_order' - whether to sort the extracted passages in order of relevance (decreasing weight), or in order of appearance in the document (increasing position). Boolean default is 'false'.
	 * - 'query_mode' - whether to handle $searchString as a query in extended syntax, or as a bag of words (default behavior). Boolean, default is 'false'.
	 * - 'force_all_words' - ignores the snippet length limit until it includes all the keywords. Boolean, default is 'false'.
	 * - 'start_passage_id' - specifies the starting value of %PASSAGE_ID% macro (that gets detected and expanded in before_match, after_match strings). Integer default is '1'.
	 * - 'load_files' - whether to handle $docs as data to extract snippets from (default behavior), or to treat it as file names, and load data from specified files on the server side. Boolean, default is 'false'.
	 * - 'html_strip_mode' - HTML stripping mode setting. String default is 'index'(use index settings).
	 * - 'allow_empty' - allows empty string to be returned as highlighting result when a snippet could not be generated. Boolean, default is 'false'.	 
	 * - 'passage_boundary' - ensures that passages do not cross a sentence, paragraph, or zone boundary. String, allowed values are "sentence", "paragraph", "zone".
	 * - 'emit_zones' - emits an HTML tag with an enclosing zone name before each passage. Boolean, default is 'false'.	 
	 * 
	 * @param array $documentContents list of document contents, which is used to build snippets.
	 * @param string $index Sphinx local index name.
	 * @param string $searchString searching string.
	 * @param array $options snippet options.
	 * @return array list of snippets.
	 */
	public function buildSnippets(array $documentContents, $index, $searchString, array $options=array()) {
		$sphinxClient = clone $this->getSphinxClient();
		
		if ($this->enableProfiling) {
			$profileMethodName = 'qs.search.fulltext.sphinx.'.get_class($this).'.'.__FUNCTION__;
			$profileToken = $profileMethodName.'(searchString='.$searchString.',index='.$index.')';
			Yii::beginProfile($profileToken, $profileMethodName);
		}
		
		$snippets = $sphinxClient->BuildExcerpts($documentContents, $index, $searchString, $options);
		$this->trySphinxErrors($sphinxClient);
		
		if ($this->enableProfiling) {
			Yii::endProfile($profileToken, $profileMethodName);
		}
		
		if (!is_array($snippets)) {
			$snippets = array();
		}
		return $snippets;
	}

	/**
	 * Generates keyword list for a given query.
	 * Extracts keywords from query using tokenizer settings for given index, 
	 * optionally with per-keyword occurrence statistics.
	 * @param string $query query to extract keywords from.
	 * @param string $index name of the index to get tokenizing settings.
	 * @param boolean $statisticRequired - flag that indicates whether keyword occurrence statistics (hits) are required.
	 * @return integer amount of updated documents (0 or more) on success, or -1 on failure.
	 */
	public function buildKeywords($query, $index, $statisticRequired=false) {
		$sphinxClient = clone $this->getSphinxClient();
		
		if ($this->enableProfiling) {
			$profileMethodName = 'qs.search.fulltext.sphinx.'.get_class($this).'.'.__FUNCTION__;
			$profileToken = $profileMethodName.'(query='.$query.',index='.$index.',statisticRequired='.$statisticRequired.')';
			Yii::beginProfile($profileToken, $profileMethodName);
		}
		
		$keywords = $sphinxClient->BuildKeywords($query, $index, $statisticRequired);
		$this->trySphinxErrors($sphinxClient);
		
		if ($this->enableProfiling) {
			Yii::endProfile($profileToken, $profileMethodName);
		}
		
		if (!is_array($keywords)) {
			$keywords = array();
		}
		return $keywords;
	}
}