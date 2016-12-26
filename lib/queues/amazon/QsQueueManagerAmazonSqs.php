<?php
/**
 * QsQueueManagerAmazonSqs class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2008-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * QsQueueManagerAmazonSqs manages queues based on Amazon Simple Queue Service (SQS).
 *
 * @see QsQueueAmazonSqs
 * @see https://github.com/aws/aws-sdk-php
 * @see http://docs.aws.amazon.com/aws-sdk-php-2/guide/latest/service-sqs.html
 *
 * @property string $awsSdkAutoloaderPath public alias of {@link _awsSdkAutoloaderPath}.
 * @property \Aws\Sqs\SqsClient $amazonSqs public alias of {@link _amazonSqs}.
 * @property string $awsKey public alias of {@link _awsKey}.
 * @property string $awsSecretKey public alias of {@link _awsSecretKey}.
 * @property array $defaultQueueAttributes public alias of {@link _defaultQueueAttributes}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qsextra.queues.amazon
 */
class QsQueueManagerAmazonSqs extends QsQueueManager {    
	/**
	 * @var string name of the queue class.
	 */
	protected $_queueClassName = 'QsQueueAmazonSqs';
	/**
	 * @var string real path to the AWS SDK autoloader.
	 * By default this will be set to the path of alias "qsextra.lib.vendors.aws-sdk".
	 */
	protected $_awsSdkAutoloaderPath = '';
	/**
	 * @var \Aws\Sqs\SqsClient instance of the Amazon SQS client.
	 */
	protected $_amazonSqs = null;
	/**
	 * @var string AWS (Amazon Web Service) key. 
	 * If constant 'AWS_KEY' has been defined, this field can be left blank.
	 */
	protected $_awsKey = null;
	/**
	 * @var string AWS (Amazon Web Service) secret key. 
	 * If constant 'AWS_SECRET_KEY' has been defined, this field can be left blank.
	 */
	protected $_awsSecretKey = null;
	/**
	 * @var string Amazon region name of the queues.
	 * You can setup this value as a short alias of the real region name
	 * according the following map:
	 * <pre>
	 * 'us_e1' => \Aws\Common\Enum\Region::US_EAST_1
	 * 'us_w1' => \Aws\Common\Enum\Region::US_WEST_1
	 * 'us_w2' => \Aws\Common\Enum\Region::US_WEST_2
	 * 'eu_w1' => \Aws\Common\Enum\Region::EU_WEST_1
	 * 'apac_se1' => \Aws\Common\Enum\Region::AP_SOUTHEAST_1
	 * 'apac_se2' => \Aws\Common\Enum\Region::AP_SOUTHEAST_2
	 * 'apac_ne1' => \Aws\Common\Enum\Region::AP_NORTHEAST_1
	 * 'sa_e1' => \Aws\Common\Enum\Region::SA_EAST_1
	 * </pre>
	 * @see AmazonS3
	 */
	public $region = 'us_e1';
	/**
	 * @var array default Amazon SQS queue attributes.
	 * These attributes will be applied to all internal queues by default.
	 * @see QsQueueAmazonSqs::attributes
	 * @see \Aws\Sqs\Enum\QueueAttribute
	 */
	protected $_defaultQueueAttributes = array(
		'VisibilityTimeout' => 60,
	);
	/**
	 * @var string actual value of {@link region}.
	 * This field is for the internal usage only.
	 */
	protected $_actualRegion = '';

	public function setAmazonSqs($amazonSqs) {
		if (!is_object($amazonSqs)) {
			throw new CException('"' . get_class($this) . '::amazonSqs" should be an object!');
		}
		$this->_amazonSqs = $amazonSqs;
		return true;
	}

	/**
	 * @return \Aws\Sqs\SqsClient SQS client instance.
	 */
	public function getAmazonSqs() {
		if (!is_object($this->_amazonSqs)) {
			$this->initAmazonSqs();
		}
		return $this->_amazonSqs;
	}

	public function setAwsKey($awsKey) {
		$this->_awsKey = $awsKey;
		return true;
	}

	public function getAwsKey() {
		return $this->_awsKey;
	}

	public function setAwsSecretKey($awsSecretKey) {
		$this->_awsSecretKey = $awsSecretKey;
		return true;
	}

	public function getAwsSecretKey() {
		return $this->_awsSecretKey;
	}

	public function setDefaultQueueAttributes(array $defaultQueueAttributes) {
		$this->_defaultQueueAttributes = $defaultQueueAttributes;
		return true;
	}

	public function getDefaultQueueAttributes() {
		return $this->_defaultQueueAttributes;
	}

	/**
	 * Returns the actual Amazon region value from the {@link region}.
	 * @throws CException on invalid region.
	 * @return string actual Amazon region.
	 */
	protected function getActualRegion() {
		if (empty($this->_actualRegion)) {
			$region = $this->region;
			if (empty($region)) {
				throw new CException('"' . get_class($this) . '::region" can not be empty.');
			}
			$this->_actualRegion = $this->fetchActualRegion($region);
		}
		return $this->_actualRegion;
	}

	/**
	 * Returns the actual Amazon region value from the {@link region}.
	 * @param string $region raw region value.
	 * @return string actual Amazon region.
	 */
	protected function fetchActualRegion($region) {
		switch ($region) {
			// USA :
			case 'us_e1': {
				return \Aws\Common\Enum\Region::US_EAST_1;
			}
			case 'us_w1': {
				return \Aws\Common\Enum\Region::US_WEST_1;
			}
			case 'us_w2': {
				return \Aws\Common\Enum\Region::US_WEST_2;
			}
			// Europe :
			case 'eu_w1': {
				return \Aws\Common\Enum\Region::EU_WEST_1;
			}
			// AP :
			case 'apac_se1': {
				return \Aws\Common\Enum\Region::AP_SOUTHEAST_1;
			}
			case 'apac_se2': {
				return \Aws\Common\Enum\Region::AP_SOUTHEAST_2;
			}
			case 'apac_ne1': {
				return \Aws\Common\Enum\Region::AP_NORTHEAST_1;
			}
			// South America :
			case 'sa_e1': {
				return \Aws\Common\Enum\Region::SA_EAST_1;
			}
			default: {
				return $region;
			}
		}
	}

	/**
	 * Initializes the instance of the Amazon SQS service gateway.
	 * @return boolean success.
	 */
	protected function initAmazonSqs() {
		$amazonSqsOptions = array(
			'key' => $this->getAwsKey(),
			'secret' => $this->getAwsSecretKey(),
			'region' => $this->getActualRegion(),
		);
		$this->_amazonSqs = \Aws\Sqs\SqsClient::factory($amazonSqsOptions);
		return true;
	}
}