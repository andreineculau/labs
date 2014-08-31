<?php

/**
 * Implements Source class
 *
 * This is the class that interfaces with the database when updating with new info provided by child classes that implement an interface with specific data sources
 *
 * @package lib
 * @author	Andrei Neculau <andrei.neculau@gmail.com>, http://www.andreineculau.com
 * @author	Aron Henriksson
 */

class Source extends ExtendedClass {
	/**
	 * @var integer $_source_id
	 * @var integer $_user_id
	 * @var string $_user
	 * @var string $_pass
	 * @var array $_extra
	 * @var array $_capabilities
	 */
	protected $_source_id;
	protected $_user_id;
	protected $_user;
	protected $_pass;
	protected $_account_id;
	protected $_datetime_extract;
	protected $_status_extract;
	protected $_capabilities;
	protected $_extra;
	public $_error;

	/**
	 * Constructor: Creates an instance of Source
	 *
	 * @global mysqli database connection
	 * @param array $extra [optional]
	 *  int user_id
	 *  int source_id
	 */
	function __construct($extra = array()) {
		//log_msg('FLOW', "New Source, extra=" . json_encode($extra));
		
		//Check if the optional parameter was passed
		if (count($extra)) {
			//Go through array and assign parameter to this instance
			foreach ($extra as $param => $value) {
				$param = "_$param";
				if (! isset($this->$param))
					$this->$param = $value;
			}
		}
		
		//Set instance variables
		


		//Find source id
		if (! $this->_source_id) {
			$this->_find_source();
		}
		
		if ($this->_source_id) {
			//Find user id based on user and pass passed to $extra
			if ($this->_user_id) {
				$this->_find_account();
				if (! $this->_user) {
					$this->_error = 'Could not find source';
					return FALSE;
				}
			}
		} else {
			$this->_error = 'Could not find source';
			return FALSE;
		}
		$this->_extra = $extra;
	}

	/**
	 * Find a table's id/column given certain conditions (column_value=given_value)
	 *
	 * @global mysqli database connection
	 * @param string $table
	 * @param array $data
	 * @return integer
	 */
	function _find_id($table, array $data, $column = NULL) {
		//Databsae connection
		global $db;
		
		if (! $column) {
			$column = $table . '_id';
		}
		//Return id/column if already present in data array
		if (isset($data[$column])) {
			return $data[$column];
		}
		
		//Build up condition part of query based on available data content
		foreach ($data as $cond_column => $cond_value) {
			/**
			 * @todo imo this may not be 100% accurate, but for now..
			 */
			if (is_string($cond_value)) {
				$cond .= " AND `$cond_column` = '$cond_value'";
			} else {
				$cond .= " AND `$cond_column` = $cond_value";
			}
		}
		
		//Build up complete sql query
		$sql = "
			SELECT `$column`
			FROM `$table`
			WHERE 1=1$cond
			LIMIT 0,1
		";
		
		//Generate log message
		//log_msg('DB_SELECT', $sql);
		
		//Execute query
		$results = $db->query($sql);
		
		//Handle results
		if (! $results && $db->errno) {
			log_msg('DB_ERROR', $db->error);
			return FALSE;
		}
		if ($results->num_rows) {
			return array_pop($results->fetch_assoc());
		} else {
			return FALSE;
		}
	}

	/**
	 * Get user's credentials to use with this source
	 *
	 * @global mysqli database connection
	 * @return mixed
	 */
	function _find_account() {
		if (! $this->_user_id) {
			return FALSE;
		}
		
		//Database connection
		global $db;
		
		//Build up sql query
		$sql = "
			SELECT
				`account_id`,
				`username`,
				`password`,
				`datetime_extract`
			FROM
				`account`
			WHERE 1=1
				AND `user_id_fk` = {$this->_user_id}
				AND `source_id_fk` = {$this->_source_id}
		";
		
		//Generate log message
		log_msg('DB_SELECT', $sql);
		
		//Execute query
		$results = $db->query($sql);
		
		//Handle results
		if (! $results && $db->errno) {
			log_msg('DB_ERROR', $db->error);
			return FALSE;
		}
		if ($results->num_rows) {
			$record = $results->fetch_assoc();
			$this->_account_id = $record['account_id'];
			$this->_user = $record['username'];
			$this->_pass = base64_decode($record['password']);
			$this->_datetime_extract = strtotime($record['datetime_extract']);
			$this->_status_extract = strtotime($record['status_extract']);
			if (! $this->_decrypt_password()) {
				return FALSE;
			}
			return $record;
		} else {
			return FALSE;
		}
	}

	/**
	 * Decrypt password with user's iv
	 *
	 * @global mysqli database connection
	 * @return mixed
	 */
	function _decrypt_password() {
		if (! $this->_pass) {
			return FALSE;
		}
		
		//Database connection
		global $db;
		
		//Build up sql query
		$sql = "
			SELECT `iv`
			FROM `user`
			WHERE 1=1
				AND `user_id` = {$this->_user_id}
		";
		
		//Generate log message
		log_msg('DB_SELECT', $sql);
		
		//Execute query
		$results = $db->query($sql);
		
		//Handle results
		if (! $results && $db->errno) {
			log_msg('DB_ERROR', $db->error);
			return FALSE;
		}
		if ($results->num_rows) {
			$iv = base64_decode(array_pop($results->fetch_assoc()));
			$this->_pass = decrypt($this->_pass, $iv);
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Find a defined source with this name
	 *
	 * @global mysqli database connection
	 * @return integer
	 */
	function _find_source() {
		global $db;
		
		$source_names = split('[|,]', $this->_source_names);
		foreach ($source_names as $source_name) {
			$sql = "
				SELECT
					`source_id`,
					`name`,
					`datetime_extract`
				FROM
					`source`
				WHERE 1=1
					AND `name` = '$source_name'
				LIMIT 0,1
			";
			
			log_msg('DB_SELECT', $sql);
			$results = $db->query($sql);
			if (! $results && $db->errno) {
				log_msg('DB_ERROR', $db->error);
				return FALSE;
			}
			if ($results->num_rows) {
				$source = $results->fetch_assoc();
				$this->_source_id = $source['source_id'];
				$this->_datetime_extract = $source['datetime_extract'];
				$this->_source_names = $source['name'];
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Find active forums that the user is subscribed to
	 *
	 * @return mixed
	 */
	function _find_forums() {
		if (! $this->_user_id || ! in_array('public_messages', $this->_capabilities)) {
			return FALSE;
		}
		
		//Database connection
		global $db;
		
		$sql = "
			SELECT `f`.`uid`
			FROM `forum` `f`
				JOIN `user_has_forum` `uf` ON `f`.`forum_id` = `uf`.`forum_id_fk`
			WHERE 1=1
				AND `uf`.`active`
				AND `uf`.`user_id_fk` = {$this->_user_id}
		";
		
		log_msg('DB_SELECT', $sql);
		$results = $db->query($sql);
		if (! $results && $db->errno) {
			log_msg('DB_ERROR', $db->error);
			return FALSE;
		}
		$forums = array();
		while ($forum = $results->fetch_assoc()) {
			$forums[] = array_pop($forum);
		}
		return $forums;
	}

	function _check_interval($timing, $force = FALSE) {
		global $restrictions;
		if (! isset($force)) {
			$force = FALSE;
		}
		if (isset($restrictions['extract_interval'])) {
			$extract_min_diff = compare_date(time(), $this->_datetime_extract, 60);
			if ($extract_min_diff < $restrictions['extract_interval']) {
				log_msg('FLOW.PREMATURE.' . $timing, "Since latest data extraction, less than {$restrictions['extract_interval']} (exactly $extract_min_diff) minutes have passed.");
				if (substr($this->_status_extract, 0 , 5) == 'error'){
					log_msg('FLOW.' . $timing, "Bypassing due to error encountered during the latest data extraction.");
				}elseif ($force) {
					log_msg('FLOW.' . $timing, "Bypassing due to 'force' parameter.");
				} else {
					return FALSE;
				}
			}
		}
		return TRUE;
	}

	/**
	 * Updates status in the database in order for the presentation side to take action accordingly
	 *
	 * @global mysqli database connection
	 * @param string $status [optional] status string ok/error/ongoing
	 * @return void
	 */
	function update_status($status = 'ongoing', $info = '') {
		//Database connection
		global $db;
		
		if ($status == 'error') {
			$status = array(
				'error', $this->get_called_class(), $this->_source_names, $info
			);
			$status = join(';', $status);
		}
		
		if ($this->_account_id) {
			$sql = "
				UPDATE `account`
				SET
					datetime_extract = NOW(),
					status_extract = '$status'
				WHERE 1=1
					AND account_id = {$this->_account_id}
			";
			
			log_msg('DB_UPDATE', $sql);
			$db->query($sql);
		} elseif (! $this->_user_id) {
			$sql = "
				UPDATE `source`
				SET
					datetime_extract = NOW(),
					status_extract = '$status'
				WHERE 1=1
					AND source_id = {$this->_source_id}
			";
			
			log_msg('DB_UPDATE', $sql);
			$db->query($sql);
		}
	}

	/**
	 * Updates database with new course information
	 *
	 * @global mysqli database connection
	 * @param array $extra [optional] associative array with extra parameters
	 * 	array data
	 * 	bool return_data
	 * @param integer $user_id
	 * @return mixed
	 */
	function update_db_with_courses($extra = array()) {
		if (! in_array('courses', $this->_capabilities)) {
			return FALSE;
		}
		
		//Generate log message
		$timing = log_msg('FLOW.START', "Called update_db_with_courses, source_id={$this->_source_id}, extra=" . json_encode($extra));
		
		//Database connection
		global $db;
		
		//Import variables from extra array
		extract($extra);
		
		//Check if it is a forced update, or if the interval is reasonable to do an update
		if (! $this->_check_interval($timing, $force)) {
			return FALSE;
		}
		
		$this->update_status();
		
		//Populate data array with courses
		if (! $data) {
			unset($extra['data']);
			$data = array();
			$request = $this->request_courses($data, $extra);
			if ($request === FALSE) {
				$this->update_status('error');
				return FALSE;
			}
		}
		
		log_msg('DUMP.courses', $data);
		
		//Add courses to database, or update if they already exist
		foreach ($data as $item) {
			$item = array_map('mysql_escape_string', $item);
			
			//Generate sql query (insert/update)
			$sql = "
				INSERT INTO `course`
				(
					`course_id_fk`,
					`source_id_fk`,
					`uid`,
					`code`,
					`name`,
					`department`,
					`credits`,
					`date_start`,
					`date_end`,
					`date_semester`,
					`date_year`,
					`date_periods`,
					`url`,
					`location`,
					`leaders`,
					`lecturers`,
					`guests`,
					`assistants`,
					`teachers`,
					`examination`,
					`literature`,
					`date_update`,
					`datetime_extract`
				) VALUES(
					" . nullify($sibling_id) . ",
					{$this->_source_id},
					'{$item['uid']}',
					'{$item['code']}',
					'{$item['name']}',
					" . nullify($item['department']) . ",
					" . nullify($item['credits'], FALSE) . ",
					" . nullify($item['date_start']) . ",
					" . nullify($item['date_end']) . ",
					" . nullify($item['date_semester']) . ",
					{$item['date_year']},
					" . nullify($item['date_periods']) . ",
					" . nullify($item['url']) . ",
					" . nullify($item['location']) . ",
					" . nullify($item['leaders']) . ",
					" . nullify($item['lecturers']) . ",
					" . nullify($item['guests']) . ",
					" . nullify($item['assistants']) . ",
					" . nullify($item['teachers']) . ",
					" . nullify($item['examination']) . ",
					" . nullify($item['literature']) . ",
					" . nullify($item['date_update']) . ",
					NOW()
				) ON DUPLICATE KEY UPDATE
					`name` = '{$item['name']}',
					`department` = " . nullify($item['department']) . ",
					`credits` = " . nullify($item['credits'], FALSE) . ",
					`date_start` = " . nullify($item['date_start']) . ",
					`date_end` = " . nullify($item['date_end']) . ",
					`date_semester` = " . nullify($item['date_semester']) . ",
					`date_year` = {$item['date_year']},
					`date_periods` = " . nullify($item['date_periods']) . ",
					`url` = " . nullify($item['url']) . ",
					`location` = " . nullify($item['location']) . ",
					`leaders` = " . nullify($item['leaders']) . ",
					`guests` = " . nullify($item['lecturers']) . ",
					`guests` = " . nullify($item['guests']) . ",
					`guests` = " . nullify($item['assistants']) . ",
					`guests` = " . nullify($item['teachers']) . ",
					`examination` = " . nullify($item['examination']) . ",
					`literature` = " . nullify($item['literature']) . ",
					`date_update` = " . nullify($item['date_update']) . ",
					`datetime_extract` = NOW()
			";
			
			//Generate log message
			log_msg('DB_INSERT', $sql);
			
			//Generate log message if there was any DB error
			if (! $db->query($sql) && $db->errno) {
				log_msg('DB_ERROR', $db->error);
				$this->update_status('error');
				return FALSE;
			}
		}
		
		//Generate log message
		log_msg('FLOW.END.' . $timing, "Exit update_db_with_courses, source_id={$this->_source_id}, extra=" . json_encode($extra));
		
		//Return if successful
		$this->update_status('ok');
		return ($return_data ? $data : TRUE);
	}

	/**
	 * Updates database with new associations user_has_course
	 *
	 * @param array $extra [optional] associative array with extra parameters
	 * @return mixed
	 */
	function update_db_with_user_courses($extra = array()) {
		if (! $this->_account_id || ! in_array('user_courses', $this->_capabilities)) {
			return FALSE;
		}
		
		//Generate log message
		$timing = log_msg('FLOW.START', "Called update_db_with_user_courses, source_id={$this->_source_id}, extra=" . json_encode($extra));
		
		//Database connection
		global $db;
		
		//Import variables from extra array
		extract($extra);
		
		//Check if it is a forced update, or if the interval is reasonable to do an update
		if (! $this->_check_interval($timing, $force)) {
			return FALSE;
		}
		
		//Get course source ID
		$course_source_id = $course_source_id ? $course_source_id : $this->_source_id;
		
		$this->update_status();
		
		//Set extra data to account data; populate data array with user courses
		if (! $data) {
			
			unset($extra['data']);
			$data = array();
			$request = $this->request_user_courses($data, $extra);
			if ($request === FALSE) {
				$this->update_status('error');
				return FALSE;
			}
		}
		
		log_msg('DUMP.courses', $data);
		
		//Genere sql query
		$sql = "
			SELECT `uc`.*
			FROM `user_has_course` `uc`
				JOIN `course` `c` ON `c`.`course_id` = `uc`.`course_id_fk`
			WHERE 1=1
				AND `uc`.`user_id_fk` = {$this->_user_id}
				AND `c`.`source_id_fk` = $course_source_id
				AND `uc`.`source_id_fk` = {$this->_source_id}
				AND `uc`.`extracted`
		";
		
		//Generate log message
		log_msg('DB_SELECT', $sql);
		
		//Execute query
		$results = $db->query($sql);
		
		//Handle results
		if (! $results && $db->errno) {
			log_msg('DB_ERROR', $db->error);
			$this->update_status('error');
			return FALSE;
		}
		$extracted = array();
		while ($result = $results->fetch_assoc()) {
			$extracted[$result['user_id_fk'] . ' - ' . $result['course_id_fk']] = TRUE;
		}
		
		$db_forum = new Db_Forum();
		
		//Add user courses to database, or set extracted flag if already exists
		foreach ($data as $item) {
			$item['source_id_fk'] = $course_source_id;
			$course_id = $this->_find_id('course', $item);
			
			if (! $course_id) {
				continue;
			}
			
			//Update with associated forum
			$course_forums = $db_forum->gets(array(
				'course_id_fk' => $course_id
			));
			if ($course_forums) {
				foreach ($course_forums as $course_forum) {
					//Generate sql query (insert/update)
					$sql = "
					INSERT INTO `user_has_forum`
					(
						`user_id_fk`,
						`forum_id_fk`,
						`active`
					) VALUES (
						{$this->_user_id},
						{$course_forum['forum_id']},
						TRUE
					) ON DUPLICATE KEY UPDATE
						`active` = TRUE
				";
					
					//Generate log message
					log_msg('DB_INSERT', $sql);
					
					//Generate log message if there was any DB error
					if (! $db->query($sql) && $db->errno) {
						log_msg('DB_ERROR', $db->error);
						$this->update_status('error');
						return FALSE;
					}
				}
			}
			
			//Ignore if it was already extracted
			if ($extracted[$this->_user_id . ' - ' . $course_id]) {
				unset($extracted[$this->_user_id . ' - ' . $course_id]);
				continue;
			}
			
			$item = array_map('mysql_escape_string', $item);
			
			//Generate sql query (insert/update)
			$sql = "
				INSERT INTO `user_has_course`
				(
					`user_id_fk`,
					`course_id_fk`,
					`source_id_fk`,
					`extracted`,
					`visible`
				) VALUES (
					{$this->_user_id},
					(
						SELECT
							`course_id_fk`
						FROM `course_source_view`
						WHERE
							`course_id` = $course_id
					),
					{$this->_source_id},
					TRUE,
					TRUE
				) ON DUPLICATE KEY UPDATE
					`extracted` = TRUE
			";
			
			//Generate log message
			log_msg('DB_INSERT', $sql);
			
			//Generate log message if there was any DB error
			if (! $db->query($sql) && $db->errno) {
				log_msg('DB_ERROR', $db->error);
				$this->update_status('error');
				return FALSE;
			}
		}
		
		//Update associations
		foreach ($extracted as $key => $value) {
			list ($this->_user_id, $course_id) = split(' - ', $key);
			
			//Mark the association as old
			$sql = "
				UPDATE `user_has_course`
				SET
					`extracted` = FALSE,
					`visible` = FALSE
				WHERE 1=1
					AND `user_id_fk` = {$this->_user_id}
					AND `course_id_fk` = $course_id
					AND `source_id_fk` = {$this->_source_id}
			";
			
			//Generate log message
			log_msg('DB_UPDATE', $sql);
			
			//Generate log message if there was any DB error
			if (! $db->query($sql) && $db->errno) {
				log_msg('DB_ERROR', $db->error);
				$this->update_status('error');
				return FALSE;
			}
		}
		
		//Generate log message
		log_msg('FLOW.END.' . $timing, "Exit update_db_with_user_courses, source_id={$this->_source_id}, extra=" . json_encode($extra));
		
		//Return if successful
		$this->update_status('ok');
		return ($return_data ? $data : TRUE);
	}

	/**
	 * Translate event type code to full name
	 *
	 * @param string $type
	 * @return string
	 */
	function _event_type($type) {
		$type = preg_replace('/(.*)\s?lecture\w*\s?(.*)/i', 'Lecture: ${1} ${2}', $type);
		$type = preg_replace('/(.*)\s?seminar\w*\s?(.*)/i', 'Seminar: ${1} ${2}', $type);
		$type = preg_replace('/(.*)\s?exam\w*\s?(.*)/i', 'Exam: ${1} ${2}', $type);
		$type = preg_replace('/(.*)\s?report\w*\s?(.*)/i', 'Report: ${1} ${2}', $type);
		$type = trim(clear_multispace($type), ": \t\n\r\0\x0B");
		return $type;
	}

	/**
	 * Updates database with new event information
	 *
	 * @global mysqli database connection
	 * @param array $course_data associative array with course data
	 * 	string uid
	 * @param array $extra [optional] associative array with extra parameters
	 * 	array data
	 * 	bool return_data
	 * 	int course_source_id
	 *  bool bulk
	 * @return mixed
	 */
	function update_db_with_events(array $course_data, $extra = array()) {
		if (! in_array('events', $this->_capabilities)) {
			return FALSE;
		}
		
		if (! $course_data) {
			return ($return_data ? array() : TRUE);
		}
		
		//Generate log message
		$timing = log_msg('FLOW.START', "Called update_db_with_events, source_id={$this->_source_id}, course_data=" . json_encode($course_data) . ", extra=" . json_encode($extra));
		
		//Database connection
		global $db;
		
		//Import variables from extra array
		extract($extra);
		
		//Check if it is a forced update, or if the interval is reasonable to do an update
		if (! $this->_check_interval($timing, $force)) {
			return FALSE;
		}
		
		$this->update_status();
		
		//Get course source ID
		$course_source_id = $course_source_id ? $course_source_id : $this->_source_id;
		
		//Populate data array with events
		if (! $data) {
			unset($extra['data']);
			$data = array();
			if (! $bulk) {
				$course_data = array(
					$course_data
				);
			}
			foreach ($course_data as $course_data_item) {
				$request = $this->request_events($course_data_item, $data, $extra);
				if ($request === FALSE) {
					$this->update_status('error');
					return FALSE;
				}
			}
		}
		
		//Add events to database, or update if already exists
		foreach ($data as $item) {
			$item = array_map('mysql_escape_string', $item);
			
			$title = $this->_event_type($item['title']);
			
			//Generate sql query (insert/update)
			$sql = "
				INSERT INTO `event`
				(
					`course_id_fk`,
					`source_id_fk`,
					`uid`,
					`title`,
					`datetime_start`,
					`datetime_end`,
					`description`,
					`location`,
					`teacher`,
					`datetime_extract`
				) VALUES(
					(SELECT `course`.`course_id` FROM `course` WHERE `course`.`uid`='{$item['parent_uid']}' AND `course`.`source_id_fk` = {$course_source_id}),
					{$this->_source_id},
					'{$item['uid']}',
					'{$title}',
					'{$item['datetime_start']}',
					'{$item['datetime_end']}',
					" . nullify($item['description']) . ",
					" . nullify($item['location']) . ",
					" . nullify($item['teacher']) . ",
					NOW()
				) ON DUPLICATE KEY UPDATE
					`title` = '{$title}',
					`datetime_start` = '{$item['datetime_start']}',
					`datetime_end` = '{$item['datetime_end']}',
					`description` = " . nullify($item['description']) . ",
					`location` = " . nullify($item['location']) . ",
					`teacher` = " . nullify($item['teacher']) . ",
					`datetime_extract` = NOW()
			";
			
			//Generate log message
			log_msg('DB_INSERT', $sql);
			
			//Generate log message if there was any DB error
			if (! $db->query($sql) && $db->errno) {
				log_msg('DB_ERROR', $db->error);
				$this->update_status('error');
				return FALSE;
			}
		
		}
		
		//Generate log message
		log_msg('FLOW.END.' . $timing, "Exit update_db_with_events, source_id={$this->_source_id}, course_data=" . json_encode($course_data) . ", extra=" . json_encode($extra));
		
		//Return if successful
		$this->update_status('ok');
		return ($return_data ? $data : TRUE);
	}

	/**
	 * Updates database with new person information
	 *
	 * @global mysqli database connection
	 * @param array $extra [optional] associative array with extra parameters
	 * @return mixed
	 */
	function update_db_with_persons($extra = array()) {
		if (! in_array('persons', $this->_capabilities)) {
			return FALSE;
		}
		
		//Database connection
		global $db;
	
	/**
	 * @todo Aron will implement this
	 */
	}

	/**
	 * Updates database with new public message information
	 *
	 * @global mysqli database connection
	 * @param array $extra [optional] associative array with extra parameters
	 * @return mixed
	 */
	function update_db_with_public_messages($extra = array()) {
		if (! $this->_account_id || ! in_array('public_messages', $this->_capabilities)) {
			return FALSE;
		}
		
		//Generate log message
		$timing = log_msg('FLOW.START', "Called update_db_with_public_messages, source_id={$this->_source_id}, extra=" . json_encode($extra));
		
		//Database connection
		global $db;
		
		//Import variables from extra array
		extract($extra);
		
		//Check if it is a forced update, or if the interval is reasonable to do an update
		if (! $this->_check_interval($timing, $force)) {
			return FALSE;
		}
		
		$this->update_status();
		
		//Populate data array with messages
		if (! $data) {
			unset($extra['data']);
			$data = array();
			$forums = $this->_find_forums();
			foreach ($forums as $forum) {
				$request = $this->request_public_messages($forum, $data, $extra);
				if ($request === FALSE) {
					$this->update_status('error');
					return FALSE;
				}
			}
		}
		
		//Add messages to database, or update if already exists
		if ($data) {
			foreach ($data as $item) {
				$item = array_map('mysql_escape_string', $item);
				$item['read'] = bool2str($item['read']);
				$item['has_attachments'] = bool2str($item['has_attachments']);
				
				//Generate sql query (insert/update)
				$sql = "
				INSERT INTO `message`
				(
					`forum_id_fk`,
					`source_id_fk`,
					`uid`,
					`type`,
					`datetime`,
					`title`,
					`from`,
					`to`,
					`has_attachments`,
					`url`,
					`body`,
					`datetime_extract`
				) VALUES(
					(SELECT `forum`.`forum_id` FROM `forum` WHERE `forum`.`uid`='{$item['parent_uid']}' AND `forum`.`source_id_fk` = {$this->_source_id}),
					{$this->_source_id},
					'{$item['uid']}',
					'public',
					'{$item['datetime']}',
					'{$item['title']}',
					" . nullify($item['from']) . ",
					" . nullify($item['to']) . ",
					{$item['has_attachments']},
					" . nullify($item['url']) . ",
					" . nullify($item['body']) . ",
					NOW()
				) ON DUPLICATE KEY UPDATE
					`datetime` = '{$item['datetime']}',
					`title` = '{$item['title']}',
					`from` = " . nullify($item['from']) . ",
					`to` = " . nullify($item['to']) . ",
					`has_attachments` = {$item['has_attachments']},
					`url` = " . nullify($item['url']) . ",
					`datetime_extract` = NOW()
			";
				
				//Generate log message
				log_msg('DB_INSERT', $sql);
				
				//Generate log message if there was any DB error
				if (! $db->query($sql) && $db->errno) {
					log_msg('DB_ERROR', $db->error);
					$this->update_status('error');
					return FALSE;
				}
				
				$message_id = $db->insert_id;
				//Generate sql query (insert/update)
				$sql = "
				INSERT INTO `user_has_message`
				(
					`user_id_fk`,
					`message_id_fk`,
					`read`
				) VALUES(
					{$this->_user_id},
					{$message_id},
					{$item['read']}
				) ON DUPLICATE KEY UPDATE
					`read` = {$item['read']}
			";
				
				//Generate log message
				log_msg('DB_INSERT', $sql);
				
				//Generate log message if there was any DB error
				if (! $db->query($sql) && $db->errno) {
					log_msg('DB_ERROR', $db->error);
					$this->update_status('error');
					return FALSE;
				}
			}
		}
		
		//Generate log message
		log_msg('FLOW.END.' . $timing, "Exit update_db_with_public_messages, source_id={$this->_source_id}, extra=" . json_encode($extra));
		
		//Return if successful
		$this->update_status('ok');
		return ($return_data ? $data : TRUE);
	}

	/**
	 * Updates database with new private message information
	 *
	 * @global mysqli database connection
	 * @param array $extra [optional] associative array with extra parameters
	 * @return mixed
	 */
	function update_db_with_private_messages($extra = array()) {
		if (! $this->_account_id || ! in_array('private_messages', $this->_capabilities)) {
			return FALSE;
		}
		
		//Generate log message
		$timing = log_msg('FLOW.START', "Called update_db_with_private_messages, source_id={$this->_source_id}, extra=" . json_encode($extra));
		
		//Database connection
		global $db;
		
		//Import variables from extra array
		extract($extra);
		
		//Check if it is a forced update, or if the interval is reasonable to do an update
		if (! $this->_check_interval($timing, $force)) {
			return FALSE;
		}
		
		$this->update_status();
		
		//Populate data array with messages
		if (! $data) {
			unset($extra['data']);
			$data = array();
			$request = $this->request_private_messages($data, $extra);
			if ($request === FALSE) {
				$this->update_status('error');
				return FALSE;
			}
		}
		
		//Add messages to database, or update if already exists
		if ($data) {
			foreach ($data as $item) {
				$item = array_map('mysql_escape_string', $item);
				$item['read'] = bool2str($item['read']);
				$item['has_attachments'] = bool2str($item['has_attachments']);
				
				//Generate sql query (insert/update)
				$sql = "
					INSERT INTO `message`
					(
						`source_id_fk`,
						`uid`,
						`type`,
						`datetime`,
						`title`,
						`from`,
						`to`,
						`has_attachments`,
						`url`,
						`body`,
						`datetime_extract`
					) VALUES(
						{$this->_source_id},
						'{$item['uid']}',
						'private',
						'{$item['datetime']}',
						'{$item['title']}',
						" . nullify($item['from']) . ",
						" . nullify($item['to']) . ",
						{$item['has_attachments']},
						" . nullify($item['url']) . ",
						" . nullify($item['body']) . ",
						NOW()
					) ON DUPLICATE KEY UPDATE
						`datetime` = '{$item['datetime']}',
						`title` = '{$item['title']}',
						`from` = " . nullify($item['from']) . ",
						`to` = " . nullify($item['to']) . ",
						`has_attachments` = {$item['has_attachments']},
						`url` = " . nullify($item['url']) . ",
						`datetime_extract` = NOW()
				";
				
				//Generate log message
				log_msg('DB_INSERT', $sql);
				
				//Generate log message if there was any DB error
				if (! $db->query($sql) && $db->errno) {
					log_msg('DB_ERROR', $db->error);
					$this->update_status('error');
					return FALSE;
				}
				
				$message_id = $db->insert_id;
				//Generate sql query (insert/update)
				$sql = "
				INSERT INTO `user_has_message`
				(
					`user_id_fk`,
					`message_id_fk`,
					`read`
				) VALUES(
					{$this->_user_id},
					{$message_id},
					{$item['read']}
				) ON DUPLICATE KEY UPDATE
					`read` = {$item['read']}
			";
				
				//Generate log message
				log_msg('DB_INSERT', $sql);
				
				//Generate log message if there was any DB error
				if (! $db->query($sql) && $db->errno) {
					log_msg('DB_ERROR', $db->error);
					$this->update_status('error');
					return FALSE;
				}
			}
		}
		
		//Generate log message
		log_msg('FLOW.END.' . $timing, "Exit update_db_with_private_messages, source_id={$this->_source_id}, extra=" . json_encode($extra));
		
		//Return if successful
		$this->update_status('ok');
		return ($return_data ? $data : TRUE);
	}
}

?>
