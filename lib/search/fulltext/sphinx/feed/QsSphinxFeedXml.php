<?php
/**
 * QsSphinxFeedXml class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2008-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * QsSphinx is the XML feed generator for Sphinx's xmlpipe2 data adapter.
 * Usage example:
 * <code>
 * $sphinxFeed = new QsSphinxFeedXml();
 * $fields = array(
 *     'title',
 *     'content'
 * );
 * $sphinxFeed->setFields($fields);
 * $attributes = array(
 *     array(
 *         'name' => 'status_id',
 *         'type' => 'int',
 *         'bit' => '32',
 *     ), 
 * );
 * $sphinxFeed->setAttributes($attributes);
 * 
 * $sphinxFeed->begin();
 * 
 * $killList = array(
 *     12, 17, 41
 * );
 * $sphinxFeed->writeKillList($killList);
 * 
 * $document = array(
 *     'id' => 58,
 *     'title' => 'Some title',
 *     'content' => 'Some content',
 *     'status_id' => 2,
 * );
 * $sphinxFeed->writeDocument($document);
 * 
 * $sphinxFeed->end();
 * </code>
 * 
 * Note: watch for the PHP memory limits! Do not try to fill all documents at once!
 * 
 * @see http://sphinxsearch.com/docs/
 * @see QsSphinx
 *
 * @property XMLWriter $xmlWriter public alias of {@link _xmlWriter}.
 * @property string $encoding public alias of {@link _encoding}.
 * @property array $xmlWriterOptions public alias of {@link _xmlWriterOptions}.
 * @property string $outputMode public alias of {@link _outputMode}.
 * @property array $fields public alias of {@link _fields}.
 * @property array $attributes public alias of {@link _attributes}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qsextra.search.fulltext.sphinx.feed
 */
class QsSphinxFeedXml extends CComponent {
	const OUTPUT_MODE_PRINT = 'print'; // Output to stdout, using memory flushing
	const OUTPUT_MODE_FETCH = 'fetch'; // Output as return string, using memory flushing
	const OUTPUT_MODE_BUFFER = 'buffer'; // Use output buffer, return whole document at the end
	/**
	 * @var XMLWriter xml writer instance.
	 */
	protected $_xmlWriter = null;
	/**
	 * @var string XML document encoding.
	 */
	protected $_encoding = 'UTF-8';
	/**
	 * @var array options for the {@link xmlWriter}.
	 * Any option, which {@link xmlWriter} has a setter for can be specified here.
	 * For example:
	 * <code>
	 * array(
	 *     'indent' => true,
	 * );
	 * </code>
	 */
	protected $_xmlWriterOptions = array();
	/**
	 * @var string XML feed output mode.
	 */
	protected $_outputMode = self::OUTPUT_MODE_PRINT;
	/**
	 * @var array list of Sphinx index document fields.
	 * For example:
	 * <code>
	 * array(
	 *     'title',
	 *     'content',
	 * );
	 * </code>
	 */
	protected $_fields = array();
	/**
	 * @var array list of Sphinx index document attributes.
	 * Each element should be an array with following keys:
	 * - 'name' - attribute name,
	 * - 'type' - attribute type,
	 * - 'bits' - amount of bits allocated for this attribute,
	 * - 'default' - default attribute value,
	 * For example:
	 * <code>
	 * array(
	 *     array(
	 *         'name'=>'author_id',
	 *         'type'=>'int',
	 *         'bits'=>'32',
	 *     ),
	 *     array(
	 *         'name'=>'status_id',
	 *         'type'=>'int',
	 *         'bits'=>'32',
	 *         'default'=>'1',
	 *     ),
	 * );
	 * </code>
	 */
	protected $_attributes = array();

	// Set / Get :

	public function setXmlWriter($xmlWriter) {
		if ($xmlWriter!==null) {
			if (!is_object($xmlWriter)) {
				throw new CException('"'.get_class($this).'::xmlWriter" should be an XMLWriter instance. "'.gettype($xmlWriter).'" has been given!');
			}
		}
		$this->_xmlWriter = $xmlWriter;
		return true;
	}

	public function getXmlWriter() {
		if (!is_object($this->_xmlWriter)) {
			$this->initXmlWriter();
		}
		return $this->_xmlWriter;
	}

	public function setEncoding($encoding) {
		$this->_encoding = $encoding;
		return true;
	}

	public function getEncoding() {
		return $this->_encoding;
	}

	public function setXmlWriterOptions(array $xmlWriterOptions) {
		$this->_xmlWriterOptions = $xmlWriterOptions;
		return true;
	}

	public function getXmlWriterOptions() {
		return $this->_xmlWriterOptions;
	}

	public function setOutputMode($outputMode) {
		$this->_outputMode = $outputMode;
		return true;
	}

	public function getOutputMode() {
		return $this->_outputMode;
	}

	public function setFields(array $fields) {
		$this->_fields = $fields;
		return true;
	}

	public function getFields() {
		return $this->_fields;
	}

	public function setAttributes(array $attributes) {
		$this->_attributes = $attributes;
		return true;
	}

	public function getAttributes() {
		return $this->_attributes;
	}

	/**
	 * Initializes the {@link xmlWriter} field.
	 * @return boolean success.
	 */
	protected function initXmlWriter() {
		$xmlWriter = new XMLWriter();
		$this->setXmlWriter($xmlWriter);
		return true;
	}

	/**
	 * Applies {@link xmlWriterOptions} to the {@link xmlWriter} instance.
	 * @return boolean success.
	 */
	protected function applyXmlWriterOptions() {
		$xmlWriter = $this->getXmlWriter();
		$options = $this->getXmlWriterOptions();
		foreach ($options as $optionName => $optionValue) {
			$setter = 'set'.$optionName;
			$xmlWriter->$setter($optionValue);
		}
		return true;
	}

	/**
	 * Performs current XML output according to the {@link outputMode} setting.
	 * @param boolean $isFinal - determines if the ouput should be final for the feed.
	 * @return mixed string or boolean depending on {@link outputMode}.
	 */
	protected function output($isFinal=false) {
		$xmlWriter = $this->getXmlWriter();
		
		switch ($this->getOutputMode()) {
			case self::OUTPUT_MODE_PRINT: {
				$bufferContent = $xmlWriter->outputMemory();
				print $bufferContent;
				return true;
			}
			case self::OUTPUT_MODE_FETCH: {
				$bufferContent = $xmlWriter->outputMemory();
				return $bufferContent;
			}
			case self::OUTPUT_MODE_BUFFER: {
				if ($isFinal) {
					$bufferContent = $xmlWriter->outputMemory();
					return $bufferContent;
				}
				return true;
			}
			default: {
				throw new CException('Unknown output mode "'.$this->getOutputMode().'"!');
			}
		}
	}

	/**
	 * Writes XML start document tags.
	 * @return boolean success.
	 */
	protected function startDocument() {
		$xmlWriter = $this->getXmlWriter();
		$xmlWriter->startDocument('1.0', $this->getEncoding());
		$xmlWriter->startElement('sphinx:docset');
		return true;
	}

	/**
	 * Writes XML feed "schema" section.
	 * {@link fields} and {@link attributes} will are processed here.
	 * @return boolean success.
	 */
	protected function writeSchema() {
		$xmlWriter = $this->getXmlWriter();
		
		$xmlWriter->startElement('sphinx:schema');
 
		// add fields to the schema
		foreach ($this->getFields() as $field) {
			$xmlWriter->startElement('sphinx:field');
			$xmlWriter->writeAttribute('name', $field);
			$xmlWriter->endElement();
		}
	 
		// add attributes to the schema
		foreach ($this->getAttributes() as $attribute) {
			$xmlWriter->startElement('sphinx:attr');
			if (!is_array($attribute)) {
				throw new CException('Wrong attributes setup: each attribute should be an array!');
			}
			foreach ($attribute as $key => $value) {
				$xmlWriter->writeAttribute($key, $value);
			}
			$xmlWriter->endElement();
		}
	 
		// end sphinx:schema
		$xmlWriter->endElement();
		return true;
	}

	/**
	 * Returns the list of feed document allowed param names.
	 * Result set is created merging {@link fields} and {@link $attributes}.
	 * @return array list of allowed param names.
	 */
	public function getAllowedDocumentParamNames() {
		$allowedParamNames = $this->getFields();
		$attributes = $this->getAttributes();
		foreach ($attributes as $attribute) {
			if (!empty($attribute['name'])) {
				$allowedParamNames[] = $attribute['name'];
			}
		}
		return $allowedParamNames;
	}

	/**
	 * Begins the XML feed creation, outputs starting XML tags.
	 * @return mixed string or boolean depending on {@link outputMode}.
	 */
	public function begin() {
		$xmlWriter = $this->getXmlWriter();
		
		// Store the xml tree in memory
		$xmlWriter->openMemory();
		
		$this->applyXmlWriterOptions();
		
		$this->startDocument();
		$this->writeSchema();
		
		return $this->output();
	}

	/**
	 * Ends the XML feed creation, outputs ending XML tags.
	 * @return mixed string result, depending on {@link outputMode}.
	 */
	public function end() {
		$xmlWriter = $this->getXmlWriter();
		// end sphinx:docset
		$xmlWriter->endElement();
		return $this->output(true);
	}

	/**
	 * Opens "killlist" section.
	 * @return mixed string or boolean depending on {@link outputMode}.
	 */
	public function beginKillList() {
		$xmlWriter = $this->getXmlWriter();
		$xmlWriter->startElement('sphinx:killlist');
		return $this->output();
	}

	/**
	 * Write single element into "killlist" section.
	 * @param mixed $documentId document id.
	 * @return string|boolean depends on {@link outputMode}.
	 */
	public function writeKillListElement($documentId) {
		$xmlWriter = $this->getXmlWriter();
		$xmlWriter->startElement('id');
		$xmlWriter->text($documentId);
		$xmlWriter->endElement();
		return $this->output();
	}

	/**
	 * Closes "killlist" section.
	 * @return mixed string or boolean depending on {@link outputMode}.
	 */
	public function endKillList() {
		$xmlWriter = $this->getXmlWriter();
		$xmlWriter->endElement();
		return $this->output();
	}

	/**
	 * Writes XML feed "killlist" section at once.
	 * This method is equal to the sequence: {@link beginKillList()}, {@link writeKillListElement()}, {@link endKillList}.
	 * @param array $documentIds list of Sphinx document ids, which should be removed.
	 * @return mixed string or boolean depending on {@link outputMode}.
	 */
	public function writeKillList(array $documentIds) {
		$xmlWriter = $this->getXmlWriter();

		$xmlWriter->startElement('sphinx:killlist');

		foreach ($documentIds as $documentId) {
			$xmlWriter->startElement('id');
			$xmlWriter->text($documentId);
			$xmlWriter->endElement();
		}

		$xmlWriter->endElement();
		return $this->output();
	}

	/**
	 * Writes a single document into XML feed.
	 * @param array $document Sphinx document data.
	 * @return mixed string or boolean depending on {@link outputMode}.
	 */
	public function writeDocument(array $document) {
		$xmlWriter = $this->getXmlWriter();
		
		$xmlWriter->startElement('sphinx:document');
		$xmlWriter->writeAttribute('id', $document['id']);
	 
		$allowedElementNames = $this->getAllowedDocumentParamNames();
		foreach ($document as $elementName => $elementValue) {
			// Skip any not declared param:
			if (!in_array($elementName, $allowedElementNames, true)) {
				continue;
			}
			$xmlWriter->startElement($elementName);
			$elementValue = str_replace(']]>', ']]&gt;', $elementValue);
			$xmlWriter->writeCData($elementValue);
			$xmlWriter->endElement();
		}
	 
		$xmlWriter->endElement();
		return $this->output();
	}
}
