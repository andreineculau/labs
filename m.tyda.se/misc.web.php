<?php

/**
 * Gets file using curl
 *
 * @param string $handle
 * @param string $url
 * @param string $fields
 * @param mixed $info
 * @return mixed
 */
function curl_get(&$handle, $url, $fields = '', &$info = NULL) {
	global $curl_last_timestamp, $curl_throttle;
	if (! $curl_throttle) {
		$curl_throttle = 5;
	}
	if ($curl_last_timestamp && time() - $curl_last_timestamp < $curl_throttle * 60) {
		$delay = min(array(
			$curl_throttle, time() - $curl_last_timestamp
		));
		if ($delay) {
			//log_msg('CURL', "Sleeping for $delay second(s)");
			usleep($delay * 1000000);
		}
	}
	if ($fields) {
		if (is_array($fields))
			$fields = http_build_query2($fields, '', '&');
		$url = "$url?$fields";
	}
	curl_setopt($handle, CURLOPT_HTTPGET, TRUE);
	curl_setopt($handle, CURLOPT_URL, $url);
	curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
	$result = curl_exec($handle);
	$info = curl_getinfo($handle);
	$curl_last_timestamp = time();
	//log_msg('CURL', "Get $url ; result info=" . json_encode($info));
	return $result;
}

/**
 * Posts file using curl
 *
 * @param string $handle
 * @param string $url
 * @param string $fields
 * @param mixed $info
 * @return mixed
 */
function curl_post(&$handle, $url, $fields = '', &$info = NULL) {
	global $curl_last_timestamp, $curl_throttle;
	if (! $curl_throttle) {
		$curl_throttle = 2;
	}
	if ($curl_last_timestamp && time() - $curl_last_timestamp < $curl_throttle * 60) {
		$delay = min(array(
			$curl_throttle * 60, time() - $curl_last_timestamp
		));
		if ($delay) {
			//log_msg('CURL', "Sleeping for $delay second(s)");
			usleep($delay * 1000000);
		}
	}
	$curl_last_timestamp = time();
	if ($fields && is_array($fields))
		$fields = http_build_query2($fields, '', '&');
	curl_setopt($handle, CURLOPT_POST, TRUE);
	if ($fields)
		curl_setopt($handle, CURLOPT_POSTFIELDS, $fields);
	curl_setopt($handle, CURLOPT_URL, $url);
	curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
	$result = curl_exec($handle);
	$info = curl_getinfo($handle);
	//log_msg('CURL', "Post $url?$fields ; result info=" . json_encode($info));
	return $result;
}

/**
 * Builds url-friendly query
 *
 * @param array $data
 * @param string $prefix
 * @param string $sep
 * @param string $key
 * @return string
 */
function http_build_query2($data, $prefix = '', $sep = '', $key = '') {
	$ret = array();
	foreach ((array) $data as $k => $v) {
		if (is_int($k) && $prefix != null) {
			$k = urlencode($prefix . $k);
		}
		if ((! empty($key)) || ($key === 0))
			$k = $key . '[' . urlencode($k) . ']';
		if (is_array($v) || is_object($v)) {
			array_push($ret, http_build_query2($v, '', $sep, $k));
		} else {
			array_push($ret, $k . '=' . urlencode($v));
		}
	}
	if (empty($sep))
		$sep = ini_get('arg_separator.output');
	return implode($sep, $ret);
}

