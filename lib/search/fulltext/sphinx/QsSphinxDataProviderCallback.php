<?php
/**
 * QsSphinxDataProviderCallback class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2008-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * QsSphinxDataProviderCallback implements a data provider for the {@link QsSphinx}.
 * 
 * QsSphinxDataProviderCallback fetches actual data through the {@link fetchDataCallback}.
 * While obtaining the data, the array of Sphinx matches will be passed to this callback, 
 * which should return the actual set of data from some other resources, for example - database.
 * 
 * QsSphinxDataProviderModel may be used in the following way:
 * <code>
 * $dataProvider = new QsSphinxDataProviderModel('fetchActualData', array(
 *     'criteria' => array(
 *         'condition' => 'search example',
 *         'fieldWeights' => array(
 *             'title' => 10,
 *             'summary' => 2,
 *         ), 
 *     ),
 *     'pagination' => array(
 *         'pageSize' => 20,
 *     ),
 * ));
 * 
 * function fetchActualData($matches) {
 *     $ids = array_keys($matches);
 *     $modelFinder = Post::model();
 *     $models = $modelFinder->findAllByPk($ids);    
 *     return $models;
 * }
 * 
 * // $dataProvider->getData() will return a list of Post objects.
 * </code> 
 * 
 * @see QsSphinx
 *
 * @property callback $fetchDataCallback public alias of {@link _fetchDataCallback}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qsextra.search.fulltext.sphinx
 */
class QsSphinxDataProviderCallback extends QsSphinxDataProvider {
	/**
	 * @var callback PHP callback which should be called to fetch actual data.
	 */
	protected $_fetchDataCallback = null;

	/**
	 * Constructor.
	 * @param callback $callback - the callback, which should be used to fetch actual data.
	 * @param array $config configuration (name=>value) to be applied as the initial property values of this class.
	 */
	public function __construct($callback, $config=array()) {
		$this->setFetchDataCallback($callback);
		parent::__construct($config);
	}

	// Set / Get :

	public function setFetchDataCallback($fetchDataCallback) {
		if (!is_callable($fetchDataCallback, true)) {
			throw new CException('"'.get_class($this).'::fetchDataCallback" should be a valid callback! "'.gettype($fetchDataCallback).'" has been given!');
		}
		$this->_fetchDataCallback = $fetchDataCallback;
		return true;
	}
	
	public function getFetchDataCallback() {
		return $this->_fetchDataCallback;
	}
	
	/**
	 * Fetches the data from the persistent data storage.
	 * @return array list of data items
	 */
	protected function fetchData() {
		$criteria = clone $this->getCriteria();

		if (($pagination=$this->getPagination())!==false) {
			$pagination->setItemCount($this->getTotalItemCount());
			$pagination->applyLimit($criteria);
		}

		if (($sort=$this->getSort())!==false) {
			$sort->applyOrder($criteria);
		}

		$sphinx = $this->getSphinx();
		$matches = $sphinx->findMatchAll($criteria);
		
		$data = $this->fetchActualData($matches);
		return $data;
	}

	/**
	 * Fetches the actual data using {@link fetchDataCallback}.
	 * @param array $matches - Sphinx matches.
	 * @return array list of data items.
	 */
	protected function fetchActualData(array $matches) {
		if (empty($matches)) {
			return array();
		}
		$callback = $this->getFetchDataCallback();
		$data = call_user_func($callback, $matches);
		if (!is_array($data)) {
			throw new CException('"'.get_class($this).'::fetchDataCallback" has returned wrong result! Array is expected, while "'.gettype($data).'" given!');
		}
		if (count($data) != count($matches)) {
			throw new CException('"'.get_class($this).'::fetchDataCallback" has returned wrong amount of items! Expected: '.count($matches).', given: '.count($data).'!');
		}
		return $data;
	}
}