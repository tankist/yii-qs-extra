<?php
/**
 * QsSphinxDataProvider class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2008-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * QsSphinxDataProvider is a base data provider for the {@link QsSphinx}.
 * 
 * Derived classes mainly need to implement the following methods: {@link fetchData}. 
 * 
 * @see QsSphinx
 * @see QsSphinxCriteria
 *
 * @property string $sphinxComponentName public alias of {@link _sphinxComponentName}.
 * @property string $keyAttribute public alias of {@link _keyAttribute}.
 * @property QsSphinxCriteria|array $criteria public alias of {@link _criteria}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qsextra.search.fulltext.sphinx
 */
abstract class QsSphinxDataProvider extends CDataProvider {
	/**
	 * @var string the name of the Sphinx application component.
	 */
	protected $_sphinxComponentName = 'sphinx';
	/**
	 * @var string the name of key attribute for {@link modelClass}.
	 */
	protected $_keyAttribute = 'id';
	/**
	 * @var QsSphinxCriteria|array Sphinx search criteria.
	 */
	protected $_criteria = array();

	/**
	 * Constructor.
	 * @param array $config configuration (name => value) to be applied as the initial property values of this class.
	 */
	public function __construct($config=array()) {
		foreach ($config as $key => $value) {
			$this->$key = $value;
		}
	}

	// Set / Get :

	public function setSphinxComponentName($sphinxComponentName) {
		$this->_sphinxComponentName = $sphinxComponentName;
		return true;
	}

	public function getSphinxComponentName() {
		return $this->_sphinxComponentName;
	}

	public function setKeyAttribute($keyAttribute) {
		if (!is_string($keyAttribute)) {
			throw new CException('"'.get_class($this).'::keyAttribute" should be a string! "'.gettype($keyAttribute).'" has been given!');
		}
		$this->_keyAttribute = $keyAttribute;
		return true;
	}

	public function getKeyAttribute() {
		return $this->_keyAttribute;
	}

	public function setCriteria($criteria) {
		if (!is_object($criteria) && !is_array($criteria)) {
			throw new CException('"'.get_class($this).'::criteria" should be an instance of QsSphinxCriteria or an array config! "'.gettype($criteria).'" has been given!');
		}
		$this->_criteria = $criteria;
		return true;
	}

	public function getCriteria() {
		if (!is_object($this->_criteria)) {
			$this->_criteria = new QsSphinxCriteria($this->_criteria);
		}
		return $this->_criteria;
	}

	/**
	 * Returns the Sphinx application component.
	 * @return QsSphinx Sphinx component object.
	 */
	protected function getSphinx() {
		$sphinxComponentName = $this->getSphinxComponentName();
		return Yii::app()->getComponent($sphinxComponentName);
	}

	/**
	 * Fetches the data item keys from the persistent data storage.
	 * @return array list of data item keys.
	 */
	protected function fetchKeys() {
		$keys = array();
		$keyAttribute = $this->getKeyAttribute();
		foreach ($this->getData() as $i=>$data) {
			$key = is_object($data) ? $data->$keyAttribute : $data[$keyAttribute];
			$keys[$i] = is_array($key) ? implode(',', $key) : $key;
		}
		return $keys;
	}

	/**
	 * Calculates the total number of data items.
	 * @return integer the total number of data items.
	 */
	protected function calculateTotalItemCount() {
		$criteria = clone $this->getCriteria();
		$sphinx = $this->getSphinx();
		return $sphinx->count($criteria);
	}
}