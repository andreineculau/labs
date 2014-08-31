<?php

/**
 * Implements WebSource class
 * 
 * This is the class that adds web crawling features that child classes will use to interface with specific webdata sources
 * 
 * @package lib
 * @author	Andrei Neculau <andrei.neculau@gmail.com>, http://www.andreineculau.com
 * @author	Aron Henriksson
 */

class WebSource {
	/**
	 * @var array $curl_options	 associative array with cURL options
	 * @var array $curl_header associative array with cURL extra headers
	 * @var string $_domain	source domain
	 * @var string domain_ssl ssl domain
	 * @var string $_domain_url	proper domain URL
	 * @var string _path path
	 * @var string _url url
	 */
	protected $curl_options;
	protected $curl_header;
	protected $_domain;
	protected $_domain_ssl = FALSE;
	protected $_domain_url;
	protected $_path;
	protected $_url;

	/**
	 * Constructor: Create an instance of WebSource with default cURL options and extra headers
	 * 
	 * @param integer $source_id
	 * @param array $extra
	 * @return void
	 */
	function __construct($extra = array()) {
		$this->curl_handle = curl_init();
		
		//Set default cURL options; leave here for reset purposes
		$this->curl_options = array();
		$this->curl_options[CURLOPT_AUTOREFERER] = TRUE;
		$this->curl_options[CURLOPT_FILETIME] = TRUE;
		$this->curl_options[CURLOPT_FOLLOWLOCATION] = TRUE;
		$this->curl_options[CURLOPT_FRESH_CONNECT] = TRUE;
		$this->curl_options[CURLOPT_RETURNTRANSFER] = TRUE;
		$this->curl_options[CURLOPT_VERBOSE] = TRUE;
		$this->curl_options[CURLOPT_CONNECTTIMEOUT] = 60;
		$this->curl_options[CURLOPT_TIMEOUT] = 60;
		$this->curl_options[CURLOPT_LOW_SPEED_LIMIT] = 10000;
		$this->curl_options[CURLOPT_LOW_SPEED_TIME] = 10;
		
		$suffix = $this->_domain;
		if ($this->_account_id) {
			$suffix .= '_' . $this->_user_id;
		} else {
			$suffix .= '_' . $this->_source_id;
		}
		$this->curl_options[CURLOPT_COOKIEFILE] = ROOT_DIR . '/logs/cURL.' . $suffix . '.cookies';
		$this->curl_options[CURLOPT_COOKIEJAR] = ROOT_DIR . '/logs/cURL.' . $suffix . '.jar.cookies';
		$this->curl_options[CURLOPT_USERAGENT] = 'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.1.3) Gecko/20090824 Firefox/3.5.3 (.NET CLR 3.5.30729)';
		$this->curl_options[CURLOPT_ENCODING] = 'gzip,deflate';
		
		//Set default cURL extra headers; leave here for reset purposes
		$this->curl_header = array();
		$this->curl_header[] = 'Accept = */*';
		$this->curl_header[] = 'Accept-Language = en-us,en;q=0.5';
		$this->curl_header[] = 'Accept-Charset = ISO-8859-1,utf-8;q=0.7,*;q=0.7';
		$this->curl_header[] = 'Keep-Alive = 300';
		$this->curl_header[] = 'Connection = keep-alive';
		$this->curl_header[] = 'Cache-Control = max-age=0';
		$this->curl_options[CURLOPT_HTTPHEADER] = $this->curl_header;
		
		//Open error log
		//$fh = fopen(ROOT_DIR . '/logs/cURL.' . $suffix . '.err', 'w');
		//$this->curl_options[CURLOPT_STDERR] = $fh;
		
		//Set domain certificate
		if ($this->_domain_ssl) {
			$this->curl_options[CURLOPT_CAINFO] = ROOT_DIR . '/plugins/' . $this->_domain . '.crt';
			$this->curl_options[CURLOPT_SSL_VERIFYPEER] = TRUE;
			$this->curl_options[CURLOPT_SSL_VERIFYHOST] = 2;
		}
		
		curl_setopt_array($this->curl_handle, $this->curl_options);
		
		$this->_domain_url = 'http' . ($this->_domain_ssl ? 's' : '') . '://' . $this->_domain;
		$this->_url = $this->_domain_url . $this->_path;
	}
}
?>