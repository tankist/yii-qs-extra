<?php
/**
 * QsSphinxModel class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2008-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * QsSphinxModel is the data model for the Sphinx search result.
 * Each instance of this class represents single Sphinx document, returned by the query.
 *
 * @see QsSphinx
 *
 * @property string $documentIdAttributeName public alias of {@link _documentIdAttributeName}.
 * @property integer $weight public alias of {@link _weight}.
 * @property array $attributes public alias of {@link _attributes}.
 * @property QsSphinxCriteria $lastSphinxCriteria public alias of {@link _lastSphinxCriteria}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qsextra.search.fulltext.sphinx
 */
class QsSphinxModel extends CModel {    
	/**
	 * @var string name of the attribute, which stores Sphinx document id.
	 */
	protected $_documentIdAttributeName = 'id';
	/**
	 * @var integer Sphinx document weight.
	 */
	protected $_weight = null;
	/**
	 * @var array document attributes set in format: attribute name => attribute value.
	 */
	protected $_attributes = array();
	/**
	 * @var QsSphinxCriteria the Sphinx criteria, which was used to find this model.
	 */
	protected $_lastSphinxCriteria = null;

	/**
	 * Class constructor.
	 * @param string $scenario name of the scenario that this model is used in.
	 * See {@link CModel::scenario} on how scenario is used by models.
	 */
	public function __construct($scenario='') {
		$this->setScenario($scenario);
		$this->init();
		$this->attachBehaviors($this->behaviors());
		$this->afterConstruct();
	}

	// Magic function extension:

	public function __get($name) {
		if ($this->attributeExists($name)) {
			return $this->getAttribute($name);
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($this->attributeExists($name)) {
			return $this->setAttribute($name, $value);
		} else {
			return parent::__set($name, $value);
		}
	}

	public function __isset($name) {
		if ($this->attributeExists($name)) {
			return ($this->getAttribute($name)!==null);
		} else {
			return parent::__isset($name);
		}
	}

	public function __unset($name) {
		if ($this->attributeExists($name)) {
			return $this->setAttribute($name, null);
		} else {
			return parent::__unset($name);
		}
	}

	// Set / Get :

	public function setDocumentIdAttributeName($documentIdAttributeName) {
		$this->_documentIdAttributeName = $documentIdAttributeName;
		return true;
	}

	public function getDocumentIdAttributeName() {
		return $this->_documentIdAttributeName;
	}

	public function setWeight($weight) {
		$this->_weight = $weight;
		return true;
	}

	public function getWeight() {
		return $this->_weight;
	}

	public function setLastSphinxCriteria($lastSphinxCriteria) {
		$this->_lastSphinxCriteria = $lastSphinxCriteria;
		return true;
	}

	public function getLastSphinxCriteria() {
		if (is_array($this->_lastSphinxCriteria)) {
			$this->_lastSphinxCriteria = new QsSphinxCriteria($this->_lastSphinxCriteria);
		}
		return $this->_lastSphinxCriteria;
	}

	/**
	 * Sets the names of the document attributes.
	 * @param array $attributeNames list of attribute names.
	 * @return boolean success.
	 */
	public function setAttributeNames(array $attributeNames) {
		foreach ($attributeNames as $attributeName) {
			if (!array_key_exists($attributeName, $this->_attributes)) {
				$this->_attributes[$attributeName] = null;
			}
		}
		return true;
	}

	/**
	 * Returns the names of the document attributes.
	 * @return array list of attribute names.
	 */
	public function getAttributeNames() {
		$documentIdAttributeName = $this->getDocumentIdAttributeName();
		if (!array_key_exists($documentIdAttributeName, $this->_attributes)) {
			$this->_attributes[$documentIdAttributeName] = null;
		}
		return array_keys($this->_attributes);
	}

	/**
	 * Sets the value of the document id attribute directly.
	 * @param mixed $documentId new Sphinx document id.
	 * @return boolean success.
	 */
	public function setDocumentId($documentId) {
		$this->_attributes[$this->getDocumentIdAttributeName()] = $documentId;
		return true;
	}

	/**
	 * Returns the value of the document id attribute directly.	 
	 * @return mixed Sphinx document id value.
	 */
	public function getDocumentId() {
		return $this->_attributes[$this->getDocumentIdAttributeName()];
	}

	/**
	 * Checks if document attribute is specified in the model.
	 * @param string $attributeName Sphinx document attribute name.
	 * @return boolean attribute exists.
	 */
	public function attributeExists($attributeName) {
		return in_array($attributeName, $this->getAttributeNames(), true);
	}

	/**
	 * Sets the value of the Sphinx document attribute.
	 * @param string $attributeName Sphinx document attribute name.
	 * @param mixed $attributeValue Sphinx document attribute value.
	 * @return boolean success.
	 */
	public function setAttribute($attributeName, $attributeValue) {
		if (!$this->attributeExists($attributeName)) {
			throw new CException("Unable to resolve attribute '{$attributeName}'!");
		}
		$this->_attributes[$attributeName] = $attributeValue;
		return true;
	}

	/**
	 * Gets the value of the Sphinx document attribute.
	 * @param string $attributeName Sphinx document attribute name.
	 * @return mixed Sphinx document attribute value.
	 */
	public function getAttribute($attributeName) {
		if (!$this->attributeExists($attributeName)) {
			throw new CException("Unable to resolve attribute '{$attributeName}'!");
		}
		return $this->_attributes[$attributeName];
	}

	/**
	 * Initializes this model.
	 * This method is invoked when an model instance is newly created
	 * and before it is filled by the query data.
	 * You may override this method to provide code that is needed to initialize the model 
	 * (e.g. setting initial property values.)
	 */
	public function init() {
		// use this method to initialize default values.
	}

	/**
	 * Initializes the internal attributes set.
	 * @param array $attributes - set of attributes in format: attribute name => attribute value.
	 * @return boolean success.
	 */
	public function populateAttributes(array $attributes) {
		foreach ($attributes as $attributeName => $attributeValue) {
			$this->_attributes[$attributeName] = $attributeValue;
		}
		return true;
	}

	/**
	 * Initializes the internal fields using the Sphinx query result data.
	 * @param integer $documentId - Sphinx document id.
	 * @param array $sphinxDocumentData - Sphinx single document data.
	 * @param QsSphinxCriteria $lastSphinxCriteria - Sphinx criteria used to find this document.
	 * @return boolean success.
	 */
	public function populateModel($documentId, array $sphinxDocumentData, $lastSphinxCriteria=null) {
		$this->setDocumentId($documentId);
		$this->setLastSphinxCriteria($lastSphinxCriteria);
		
		if (array_key_exists('weight', $sphinxDocumentData)) {
			$this->setWeight($sphinxDocumentData['weight']);
		}
		if (array_key_exists('attrs', $sphinxDocumentData) && is_array($attributes=$sphinxDocumentData['attrs'])) {
			$this->populateAttributes($attributes);
		}
		return true;
	}

	/**
	 * Returns the list of attribute names of the model.
	 * @return array list of attribute names.
	 */
	public function attributeNames() {
		return $this->getAttributeNames();
	}

	/**
	 * This method supposed to return the model document content,
	 * which can be used to create snippets (exceprt).
	 * A particular Sphinx model class should override this method to provide
	 * adequate implementation, for example:
	 * <code>
	 * public function getDocumentContent() {
	 *     return file_get_contents($this->file_name);
	 * }
	 * </code>
	 * @return string model document content.
	 */
	public function getDocumentContent() {
		return '';
	}
}