<?php
/**
 * QsSphinxDataProviderModel class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2008-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * QsSphinxDataProviderModel implements a data provider for the {@link QsSphinx}.
 * 
 * QsSphinxDataProviderModel provides data in terms of Sphinx model objects which are
 * of class {@link modelClass}. By default {@link QsSphinxModel} class is used.
 * 
 * QsSphinxDataProviderModel may be used in the following way:
 * <code>
 * $dataProvider = new QsSphinxDataProviderModel(array(
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
 * // $dataProvider->getData() will return a list of {@link QsSphinxModel} objects
 * </code> 
 * 
 * @see QsSphinx
 * @see QsSphinxModel
 *
 * @property string $modelClass public alias of {@link _modelClass}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qsextra.search.fulltext.sphinx
 */
class QsSphinxDataProviderModel extends QsSphinxDataProvider {
	/**
	 * @var string the primary model class name. The {@link getData()} method
	 * will return a list of objects of this class.
	 */
	protected $_modelClass = 'QsSphinxModel';

	/**
	 * Constructor.
	 * @param array $config configuration (name=>value) to be applied as the initial property values of this class.
	 */
	public function __construct($config=array()) {
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
	 * Returns the sorting object.
	 * @return CSort the sorting object. If this is false, it means the sorting is disabled.
	 */
	public function getSort() {
		if (($sort=parent::getSort())!==false) {
			$sort->modelClass = $this->getModelClass();
		}
		return $sort;
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
		$data = $sphinx->findModelAll($criteria, $this->getModelClass());
		return $data;
	}
}