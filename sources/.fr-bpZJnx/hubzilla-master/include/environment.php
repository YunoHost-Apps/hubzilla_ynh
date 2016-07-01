<?php
/**
 * @file include/environment.php
 * @brief Functions related to system/environment tasks.
 *
 * This file contains some functions to check the environment/system.
 */

/**
 * @brief Get some upload related limits from php.ini.
 *
 * This function returns values from php.ini like \b post_max_size,
 * \b max_file_uploads, \b upload_max_filesize.
 *
 * @return array associative array
 *   * \e int \b post_max_size the maximum size of a complete POST in bytes
 *   * \e int \b upload_max_filesize the maximum size of one file in bytes
 *   * \e int \b max_file_uploads maximum number of files in one POST
 *   * \e int \b max_upload_filesize min(post_max_size, upload_max_filesize)
 */
function getPhpiniUploadLimits() {
	$ret = array();

	// max size of the complete POST
	$ret['post_max_size'] = phpiniSizeToBytes(ini_get('post_max_size'));
	// max size of one file
	$ret['upload_max_filesize'] = phpiniSizeToBytes(ini_get('upload_max_filesize'));
	// catch a configuration error where post_max_size < upload_max_filesize
	$ret['max_upload_filesize'] = min(
			$ret['post_max_size'],
			$ret['upload_max_filesize']
			);
	// maximum number of files in one POST
	$ret['max_file_uploads'] = intval(ini_get('max_file_uploads'));

	return $ret;
}

/**
 * @brief Parses php_ini size settings to bytes.
 *
 * This function parses common size setting from php.ini files to bytes.
 * e.g. post_max_size = 8M ==> 8388608
 *
 * \note This method does not recognise other human readable formats like
 * 8MB, etc.
 *
 * @todo Make this function more universal useable. MB, T, etc.
 *
 * @param string $val value from php.ini e.g. 2M, 8M
 * @return int size in bytes
 */
function phpiniSizeToBytes($val) {
	$val = trim($val);
	$unit = strtolower($val[strlen($val)-1]);
	switch($unit) {
		case 'g':
			$val *= 1024;
		case 'm':
			$val *= 1024;
		case 'k':
			$val *= 1024;
		default:
			break;
	}

	return (int)$val;
}