<?php
/**
 * QsSphinxCriteria class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2008-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * QsSphinxCriteria used to specify Sphinx search criteria.
 *
 * @property string $index public alias of {@link _index}.
 * @property integer|string $matchMode public alias of {@link _matchMode}.
 * @property string $select public alias of {@link _select}.
 * @property string $condition public alias of {@link _condition}.
 * @property array $filters public alias of {@link _filters}.
 * @property string $fieldWeights public alias of {@link _fieldWeights}.
 * @property string $order public alias of {@link _order}.
 * @property string $group public alias of {@link _group}.
 * @property integer $limit public alias of {@link _limit}.
 * @property integer $offset public alias of {@link _offset}.
 * @property integer $cutoff public alias of {@link _cutoff}.
 * @property integer $maxMatches public alias of {@link _maxMatches}.
 * @property array $geoAnchor public alias of {@link _geoAnchor}.
 * @property array $scopes public alias of {@link _scopes}.
 * @property string $comment public alias of {@link _comment}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qsextra.search.fulltext.sphinx
 */
class QsSphinxCriteria extends CComponent {
	/**
	 * @var string sphinx index locator.
	 * Default value - "*", meaning all indexes.
	 */
	protected $_index = '*';
	/**
	 * @var integer|string Sphinx query match mode.
	 * @see SphinxClient::_mode
	 */
	protected $_matchMode = null;
	/**
	 * @var string select-list (attributes or expressions), SQL-like syntax.
	 */
	protected $_select = '';
	/**
	 * @var string query condition. 
	 * For example: '@(title,body) hello world'.
	 */
	protected $_condition = '';
	/**
	 * @var array query filters. 
	 * Each filter should be an array, with the following keys:
	 * - 'type' - filter type,
	 * - 'attribute' - filtering attribute name,
	 * - 'exclude' - determines if filter should work in exclude mode,
	 * - 'values' - matching attribute values,
	 * - 'min' - minimal attribute value,
	 * - 'max' - maximal attribute value.
	 */
	protected $_filters = array();
	/**
	 * @var array query field weights.
	 * For example:
	 * <code>
	 * array(
	 *     'name'=>10,
	 *     'title'=>2,
	 * );
	 * </code>
	 */
	protected $_fieldWeights = array();
	/**
	 * @var mixed sort parameters the query results.
	 * The value can be 
	 * - a string, which contains sort specification, for example "name ASC"
	 * - an array of 2 elements: the first one is the sort mode, the second - sort string,
	 * for example:
	 * <code>
	 * array(
	 *     QsSphinx::SORT_ATTRIBUTE_DESC,
	 *     'name'
	 * );
	 * </code>
	 * @see SphinxClient::SetSortMode()
	 */
	protected $_order = '';
	/**
	 * @var array group parameters for the query results.
	 * The array value of this field should specify the following group parameters:
	 * - 'attribute' - name of the group attribute.
	 * - 'function' - name of the group function.
	 * - 'groupsort' - the sort for the grouping.
	 * Example value:
	 * <code>
	 * array(
	 *     'name',
	 *     QsSphinx::GROUP_ATTRIBUTE,
	 *     '@group desc'
	 * );
	 * </code>
	 * @see SphinxClient::SetGroupBy()
	 */
	protected $_group = array();
	/**
	 * @var integer maximum number of matches to be returned. If less than 0, it means no limit.
	 */
	protected $_limit = -1;
	/**
	 * @var integer zero-based offset from where the matches are to be returned. If less than 0, it means starting from the beginning.
	 */
	protected $_offset = -1;
	/**
	 * @var integer cutoff limit of matches to be returned. 
	 * Default is 0, which means no limit.
	 */
	protected $_cutoff = 0;
	/**
	 * @var integer limit of maximum number of matches to be returned. 
	 * Default is 0, which means no limit.
	 */
	protected $_maxMatches = 0;
	/**
	 * @var array geographical anchor point.
	 * The array value of this field should specify the following parameters:
	 * - 'attributeLatitude' - name of the latitude attribute.
	 * - 'attributeLongitude' - name of the longitude attribute.
	 * - 'latitude' - anchor latitude value.
	 * - 'longitude' - anchor longitude value.
	 * @see SphinxClient::SetGeoAnchor()
	 */
	protected $_geoAnchor = array();
	/**
	 * @var array list of {@link QsSphinxActiveRecord} scopes.
	 * This property is effective only when passing criteria to the one of
	 * {@link QsSphinxActiveRecord} find methods.
	 * 
	 * Can be set to one of the following:
	 * <ul>
	 * <li>One scope: $criteria->scopes='scopeName';</li>
	 * <li>Multiple scopes: $criteria->scopes=array('scopeName1','scopeName1');</li>
	 * <li>Scope with parameters: $criteria->scopes=array('scopeName'=>array($paramters));</li>
	 * <li>Multiple scopes with the same name: array(array('scopeName'=>array($paramters1)),array('scopeName'=>array($paramters2)));</li>
	 * </ul>
	 */
	protected $_scopes = array();
	/**
	 * @var string search query comment.
	 */
	protected $_comment = '';

	/**
	 * Constructor.
	 * @param array $data criteria initial property values (indexed by property name)
	 */
	public function __construct(array $data=array()) {
		foreach($data as $name=>$value) {
			$this->$name=$value;
		}
	}

	// Set / Get :

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

	public function setMatchMode($matchMode) {
		$this->_matchMode = $matchMode;
		return true;
	}

	public function getMatchMode() {
		return $this->_matchMode;
	}

	public function setSelect($select) {
		if (!is_string($select)) {
			throw new CException('"'.get_class($this).'::select" should be a string!');
		}
		$this->_select = $select;
		return true;
	}

	public function getSelect() {
		return $this->_select;
	}

	public function setCondition($condition) {
		if (!is_string($condition)) {
			throw new CException('"'.get_class($this).'::condition" should be a string!');
		}
		$this->_condition = $condition;
		return true;
	}

	public function getCondition() {
		return $this->_condition;
	}

	public function setFilters(array $filters) {
		$this->_filters = $filters;
		return true;
	}

	public function getFilters() {
		return $this->_filters;
	}

	public function setFieldWeights(array $fieldWeights) {
		$this->_fieldWeights = $fieldWeights;
		return true;
	}

	public function getFieldWeights() {
		return $this->_fieldWeights;
	}

	public function setOrder($order) {
		$this->_order = $order;
		return true;
	}

	public function getOrder() {
		return $this->_order;
	}

	public function setGroup(array $group) {
		$this->_group = $group;
		return true;
	}

	public function getGroup() {
		return $this->_group;
	}

	public function setLimit($limit) {
		if (!is_numeric($limit)) {
			throw new CException('"'.get_class($this).'::limit" should be an integer!');
		}
		$this->_limit = (int)$limit;
		return true;
	}

	public function getLimit() {
		return $this->_limit;
	}

	public function setOffset($offset) {
		if (!is_numeric($offset)) {
			throw new CException('"'.get_class($this).'::offset" should be an integer!');
		}
		$this->_offset = (int)$offset;
		return true;
	}

	public function getOffset() {
		return $this->_offset;
	}

	public function setCutoff($cutoff) {
		if (!is_numeric($cutoff)) {
			throw new CException('"'.get_class($this).'::cutoff" should be an integer!');
		}
		$this->_cutoff = (int)$cutoff;
		return true;
	}

	public function getCutoff() {
		return $this->_cutoff;
	}

	public function setMaxMatches($maxMatches) {
		if (!is_numeric($maxMatches)) {
			throw new CException('"'.get_class($this).'::maxMatches" should be an integer!');
		}
		$this->_maxMatches = $maxMatches;
		return true;
	}

	public function getMaxMatches() {
		return $this->_maxMatches;
	}

	public function setGeoAnchor(array $geoAnchor) {
		$this->_geoAnchor = $geoAnchor;
		return true;
	}

	public function getGeoAnchor() {
		return $this->_geoAnchor;
	}

	public function setComment($comment) {
		if (!is_string($comment)) {
			throw new CException('"'.get_class($this).'::comment" should be a string!');
		}
		$this->_comment = $comment;
		return true;
	}

	public function getComment() {
		return $this->_comment;
	}

	public function setScopes(array $scopes) {
		$this->_scopes = $scopes;
		return true;
	}

	public function getScopes() {
		return $this->_scopes;
	}

	/**
	 * Builds up Sphinx query string based on the internal parameters.
	 * @return string Sphinx query string.
	 */
	public function getQuery() {
		return $this->getCondition();
	}

	/**
	 * Applies criteria parameters to the Sphinx client instance, preparing it for the query.
	 * @param SphinxClient $sphinxClient Sphinx client instance.
	 * @return SphinxClient Sphinx client instance.
	 */
	public function apply($sphinxClient) {
		if (!is_object($sphinxClient)) {
			throw new CException('"sphinxClient" should be an instance of SphinxClient class!"'.gettype($sphinxClient).'" is given!');
		}
		$this->applyMatchMode($sphinxClient);
		$this->applyFilters($sphinxClient);
		$this->applyFieldWeights($sphinxClient);
		$this->applySelect($sphinxClient);
		$this->applyOrder($sphinxClient);
		$this->applyGroup($sphinxClient);
		$this->applyLimits($sphinxClient);
		$this->applyGeoAnchor($sphinxClient);
		return $sphinxClient;
	}

	/**
	 * Applies match mode paramter to the Sphinx client instance.
	 * @param SphinxClient $sphinxClient Sphinx client instance.
	 * @return SphinxClient Sphinx client instance.
	 */
	protected function applyMatchMode($sphinxClient) {
		$matchMode = $this->getMatchMode();
		if ($matchMode!==null) {
			$matchMode = QsSphinx::realConstantValueMatch($matchMode);
			$sphinxClient->SetMatchMode($matchMode);
		}
		return $sphinxClient;
	}

	/**
	 * Applies select parameter to the Sphinx client instance.
	 * @param SphinxClient $sphinxClient Sphinx client instance.
	 * @return SphinxClient Sphinx client instance.
	 */
	protected function applySelect($sphinxClient) {
		$select = $this->getSelect();
		if (!empty($select)) {
			$sphinxClient->SetSelect($select);
		}
		return $sphinxClient;
	}

	/**
	 * Applies filters to the Sphinx client instance.
	 * @param SphinxClient $sphinxClient Sphinx client instance.
	 * @return SphinxClient Sphinx client instance.
	 * @throws CException on failure.
	 */
	protected function applyFilters($sphinxClient) {
		$filters = $this->getFilters();
		if (!empty($filters)) {
			foreach ($filters as $filter) {
				if (!is_array($filter)) {
					continue;
				}
				$sphinxClientFilter = array();
				if (array_key_exists('attribute', $filter)) {
					$sphinxClientFilter['attr'] = $filter['attribute'];
				} elseif (array_key_exists('attr', $filter)) {
					$sphinxClientFilter['attr'] = $filter['attr'];
				} else {
					throw new CException('Missing filter attribute.');
				}
				if (array_key_exists('exclude', $filter)) {
					$sphinxClientFilter['exclude'] = $filter['exclude'];
				}

				if (array_key_exists('values', $filter)) {
					$sphinxClientFilter['type'] = QsSphinx::FILTER_VALUES;
					$sphinxClientFilter['values'] = $filter['values'];
				} elseif (array_key_exists('min', $filter) || array_key_exists('max', $filter)) {
					$minValue = $filter['min'];
					$maxValue = $filter['max'];
					if (!array_key_exists('type', $filter)) {
						if ( ( is_int($minValue)||ctype_digit($minValue) ) && ( is_int($maxValue)||ctype_digit($maxValue) ) ) {
							$sphinxClientFilter['type'] = QsSphinx::FILTER_RANGE;
						} else {
							$sphinxClientFilter['type'] = QsSphinx::FILTER_RANGE_FLOAT;
						}
					}
					$sphinxClientFilter['min'] = $minValue;
					$sphinxClientFilter['max'] = $maxValue;
				}

				if (!array_key_exists('type', $sphinxClientFilter)) {
					if (array_key_exists('type', $filter)) {
						$sphinxClientFilter['type'] = $filter['type'];
					} else {
						throw new CException('Unable to determine filter type.');
					}
				}
				$sphinxClientFilter['type'] = QsSphinx::realConstantValueFilter($sphinxClientFilter['type']);
				$sphinxClient->_filters[] = $sphinxClientFilter;
			}
		}
		return $sphinxClient;
	}

	/**
	 * Applies field weights to the Sphinx client instance.
	 * @param SphinxClient $sphinxClient Sphinx client instance.
	 * @return SphinxClient Sphinx client instance.
	 */
	protected function applyFieldWeights($sphinxClient) {
		$weights = $this->getFieldWeights();
		if (!empty($weights)) {
			$sphinxClient->SetFieldWeights($weights);
		}
		return $sphinxClient;
	}

	/**
	 * Applies group parameters to the Sphinx client instance.
	 * @param SphinxClient $sphinxClient Sphinx client instance.
	 * @return SphinxClient Sphinx client instance.
	 */
	protected function applyOrder($sphinxClient) {
		$order = $this->getOrder();
		if (!empty($order)) {
			if (is_array($order)) {
				$mode = array_shift($order);
				$sortBy = array_shift($order);
			} else {
				$mode = QsSphinx::SORT_EXTENDED;
				$sortBy = $order;
			}
			$mode = QsSphinx::realConstantValueSort($mode);
			$sphinxClient->SetSortMode($mode, $sortBy);
		}
		return $sphinxClient;
	}

	/**
	 * Applies group parameters to the Sphinx client instance.
	 * @param SphinxClient $sphinxClient Sphinx client instance.
	 * @return SphinxClient Sphinx client instance.
	 */
	protected function applyGroup($sphinxClient) {
		$group = $this->getGroup();
		$groupAttribute = array_shift($group);
		$groupFunction = array_shift($group);
		if (!empty($groupAttribute) && !empty($groupFunction)) {
			$groupSort = array_shift($group);
			if (!is_string($groupSort)) {
				$groupSort = '@group desc';
			}
			$groupFunction = QsSphinx::realConstantValueGroup($groupFunction);
			$sphinxClient->SetGroupBy($groupAttribute, $groupFunction, $groupSort);
		}
		return $sphinxClient;
	}

	/**
	 * Applies limit parameters to the Sphinx client instance.
	 * @param SphinxClient $sphinxClient Sphinx client instance.
	 * @return SphinxClient Sphinx client instance.
	 */
	protected function applyLimits($sphinxClient) {
		$offset = $this->getOffset();
		$limit = $this->getLimit();
		if ($limit>0) {
			if ($offset<0) {
				$offset = 0;
			}
			$cutoff = $this->getCutoff();
			$maxMatches = $this->getMaxMatches();
			$sphinxClient->SetLimits($offset, $limit, $maxMatches, $cutoff);
		}
		return $sphinxClient;
	}

	/**
	 * Applies geographical anchor parameters to the Sphinx client instance.
	 * @param SphinxClient $sphinxClient Sphinx client instance.
	 * @return SphinxClient Sphinx client instance.
	 */
	protected function applyGeoAnchor($sphinxClient) {
		$geoAnchor = $this->getGeoAnchor();
		if (!empty($geoAnchor)) {
			$geoAnchor = array_change_key_case($geoAnchor, CASE_LOWER);
			$latitudeAttributeName = 'latitude';
			if (array_key_exists('attributelatitude', $geoAnchor)) {
				$latitudeAttributeName = $geoAnchor['attributelatitude'];
			} elseif (array_key_exists('attrlat', $geoAnchor)) {
				$latitudeAttributeName = $geoAnchor['attrlat'];
			}
			$longitudeAttributeName = 'longitude';
			if (array_key_exists('attributelongitude', $geoAnchor)) {
				$longitudeAttributeName = $geoAnchor['attributelongitude'];
			} elseif (array_key_exists('attrlong', $geoAnchor)) {
				$longitudeAttributeName = $geoAnchor['attrlong'];
			}
			$latitudeValue = array_key_exists('lat',$geoAnchor) ? $geoAnchor['lat']: $geoAnchor['latitude'];
			$longitudeValue = array_key_exists('long',$geoAnchor) ? $geoAnchor['long']: $geoAnchor['longitude'];
			$sphinxClient->SetGeoAnchor($latitudeAttributeName, $longitudeAttributeName, (float)$latitudeValue, (float)$longitudeValue);
		}
		return $sphinxClient;
	}

	/**
	 * Merges with another criteria.
	 * In general, the merging makes the resulting criteria more restrictive.
	 * For example, if both criterias have conditions, they will be 'AND' together.
	 * Also, the criteria passed as the parameter takes precedence in case
	 * two options cannot be merged (e.g. LIMIT, OFFSET).
	 * @param QsSphinxCriteria|array $criteria the criteria to be merged with.
	 * @param boolean $useAnd whether to use 'AND' to merge condition and having options.
	 * If false, 'OR' will be used instead. Defaults to 'AND'.
	 * @return boolean success.
	 */
	public function mergeWith($criteria, $useAnd=true) {
		$logicConnector = $useAnd ? '&' : '|';
		if (is_array($criteria)) {
			$selfClassName = get_class($this);
			$criteria = new $selfClassName($criteria);
		}
		
		// Index:
		if ($this->index!==$criteria->index) {
			if ($this->index=='*' || $criteria->index=='*') {
				$this->index = '*';
			} else {
				$index = $this->index.','.$criteria->index;
				$index = trim($index, ',');
				$this->index = $index;
			}
		}
		
		// Match Mode:
		if ($this->matchMode!==$criteria->matchMode) {
			if ($criteria->matchMode!==null) {
				$this->matchMode = $criteria->matchMode;
			}
		}
		
		// Select:
		if ($this->select!==$criteria->select) {
			$select = $this->select.','.$criteria->select;
			$select = trim($select, ',');
			$this->select = $select;
		}
		
		// Condition:
		if ($this->condition!==$criteria->condition) {
			if($this->condition==='') {
				$this->condition = $criteria->condition;
			} elseif($criteria->condition!=='') {
				$this->condition = "({$this->condition}) {$logicConnector} ({$criteria->condition})";
			}
		}
		
		// Filters:
		if ($this->filters!==$criteria->filters) {
			$this->filters = array_merge($this->filters, $criteria->filters);
		}
		
		// Field Weights:
		if ($this->fieldWeights!==$criteria->fieldWeights) {
			$this->fieldWeights = CMap::mergeArray($this->fieldWeights, $criteria->fieldWeights);
		}
		
		// Order:
		if ($this->order!==$criteria->order) {
			if (!empty($criteria->order)) {
				$this->order = $criteria->order;
			}
		}

		// Group:
		if ($this->group!==$criteria->group) {
			if (!empty($criteria->group)) {
				$this->group=$criteria->group;
			}
		}
		
		// Limits:
		if ($criteria->limit>0) {
			$this->limit = $criteria->limit;
		}
		if ($criteria->offset>=0) {
			$this->offset = $criteria->offset;
		}
		if ($criteria->cutoff>0) {
			$this->cutoff = $criteria->cutoff;
		}
		if ($criteria->maxMatches>0) {
			$this->maxMatches = $criteria->maxMatches;
		}
		
		// Geo Anchor:
		if ($this->geoAnchor !== $criteria->geoAnchor) {
			if (!empty($criteria->geoAnchor)) {
				$this->geoAnchor = $criteria->geoAnchor;
			}
		}
		
		// Comment:
		if ($this->comment!==$criteria->comment) {
			$comment = $this->comment.' '.$criteria->comment;
			$this->comment = trim($comment);
		}
		
		// Scopes:
		if (empty($this->scopes)) {
			$this->scopes = $criteria->scopes;
		} elseif (!empty($criteria->scopes)) {
			$scopes = array();
			$scopesThis = $this->scopes;
			$scopesCriteria = $criteria->scopes;
			foreach ($scopesThis as $k => $v) {
				if (is_integer($k)) {
					$scopes[] = $v;
				} elseif (isset($scopesCriteria[$k])) {
					$scopes[] = array($k => $v);
				} else {
					$scopes[$k] = $v;
				}
			}
			foreach ($scopesCriteria as $k => $v) {
				if (is_integer($k)) {
					$scopes[] = $v;
				} elseif (isset($scopesThis[$k])) {
					$scopes[] = array($k => $v);
				} else {
					$scopes[$k] = $v;
				}
			}
			$this->scopes = $scopes;
		}
		
		return true;
	}
}