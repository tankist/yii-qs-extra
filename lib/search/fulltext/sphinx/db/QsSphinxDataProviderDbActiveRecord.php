<?php
/**
 * QsSphinxDataProviderDbActiveRecord class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2008-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsSphinxDataProviderDbActiveRecord is Sphinx data provider,
 * which uses the database to fetch actual data for the search result.
 *
 * @property string $modelClass public alias of {@link _modelClass}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qsextra.search.fulltext.sphinx.db
 */
class QsSphinxDataProviderDbActiveRecord extends QsSphinxDataProvider {
	/**
	 * @var string the primary model class name. The {@link getData()} method
	 * will return a list of objects of this class.
	 */
	protected $_modelClass = 'CActiveRecord';

	/**
	 * Constructor.
	 * @param string $modelClass the model class (e.g. 'Post').
	 * @param array $config configuration (name=>value) to be applied as the initial property values of this class.
	 */
	public function __construct($modelClass, $config=array()) {
		$this->setModelClass($modelClass);
		parent::__construct($config);
		$this->setId($this->getModelClass());
	}

	// Set / Get :

	public function setModelClass($modelClass) {
		$this->_modelClass = $modelClass;
		return true;
	}

	public function getModelClass() {
		return $this->_modelClass;
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
		$documentIds = $sphinx->findDocumentIdAll($criteria);
		$data = $this->fetchActualData($documentIds);
		return $data;
	}

	/**
	 * Fetches the actual data using {@link fetchDataCallback}.
	 * @param array $documentIds list of found model id.
	 * @return array list of data items.
	 * @throws CException if active record fetches wrong amount of records.
	 */
	protected function fetchActualData(array $documentIds) {
		if (empty($documentIds)) {
			return array();
		}
		$modelFinder = CActiveRecord::model($this->getModelClass());
		$data = $modelFinder->findAllByPk($documentIds);
		if (!is_array($data)) {
			throw new CException('"'.$this->getModelClass().'" has returned wrong result! Array is expected, while "'.gettype($data).'" given!');
		}
		if (count($data) != count($documentIds)) {
			throw new CException('"'.$this->getModelClass().'" has returned wrong amount of items! Expected: '.count($documentIds).', given: '.count($data).'!');
		}
		return $data;
	}
}