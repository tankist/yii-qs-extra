<?php
/**
 * QsSphinxDataProviderActiveRecord class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2008-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * QsSphinxDataProviderActiveRecord implements a data provider based on {@link QsSphinxActiveRecord}.
 * 
 * @see QsSphinxActiveRecord
 *
 * @property QsSphinxActiveRecord $model public alias of {@link _model}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qsextra.search.fulltext.sphinx.ar
 */
class QsSphinxDataProviderActiveRecord extends QsSphinxDataProviderModel {
	/**
	 * @var QsSphinxActiveRecord the AR finder instance (eg <code>Post::model()</code>).
	 * This property can be set by passing the finder instance as the first parameter
	 * to the constructor. For example, <code>Post::model()->published()</code>.
	 */
	protected $_model = null;

	/**
	 * Constructor.
	 * @param mixed $modelClass the model class (e.g. 'Post') or the model finder instance
	 * (e.g. <code>Post::model()</code>, <code>Post::model()->published()</code>).
	 * @param array $config configuration (name=>value) to be applied as the initial property values of this class.
	 */
	public function __construct($modelClass='QsSphinxActiveRecord', $config=array()) {
		if (is_object($modelClass)) {
			$this->setModel($modelClass);
		} else {
			$this->setModelClass($modelClass);
		}
		parent::__construct($config);
	}

	// Set / Get :

	public function setModel(QsSphinxActiveRecord $model) {
		$this->setModelClass(get_class($model));
		$this->_model = $model;
		return true;
	}

	public function getModel() {
		if (!is_object($this->_model)) {
			$this->initModel();
		}
		return $this->_model;
	}

	/**
	 * Initializes AR finder instance.
	 * @return boolean success.
	 */
	protected function initModel() {
		$this->_model = QsSphinxActiveRecord::model($this->getModelClass());
		return true;
	}

	/**
	 * Returns criteria, which is a merging result of internal criteria of {@link model} and
	 * own criteria.
	 * @return QsSphinxCriteria merged criteria.
	 */
	protected function getMergedCriteria() {
		$model = $this->getModel();
		$modelCriteria = $model->getSphinxCriteria();
		$criteria = clone $modelCriteria;
		$thisCriteria = clone $this->getCriteria();
		$criteria->mergeWith($thisCriteria);
		return $criteria;
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
		$model = $this->getModel();
		$data = $model->findAll($criteria);
		return $data;
	}

	/**
	 * Calculates the total number of data items.
	 * @return integer the total number of data items.
	 */
	protected function calculateTotalItemCount() {
		$criteria = clone $this->getCriteria();
		$model = clone $this->getModel();
		return $model->count($criteria);
	}
}