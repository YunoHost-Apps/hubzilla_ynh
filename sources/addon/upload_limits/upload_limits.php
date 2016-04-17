<?php

/**
 * Name: Upload Limits
 * Description: visit $yoursite/upload_limits to show what system limits may apply to file/photo uploads
 * Version: 1.0
 * Author: Mike Macgirvin
 * Maintainer: none
 */





function upload_limits_load() {}
function upload_limits_unload() {}

function upload_limits_module() {}

function upload_limits_content(&$a) {


	$o = '';

	$o .= '<h3>' . t('Show Upload Limits') . '</h3>';

	$o .= t('Hubzilla configured maximum size: ') . get_config('system','maximagesize') . EOL;
	$o .= t('PHP upload_max_filesize: ') . ini_get('upload_max_filesize') . EOL;
	$o .= t('PHP post_max_size (must be larger than upload_max_filesize): ') . ini_get('post_max_size') . EOL;
	return $o;

}
