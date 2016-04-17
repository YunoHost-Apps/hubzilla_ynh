<?php

/**
 * Name: Noembed
 * Description: Use noembed.com as an addition to Hubzilla's native oembed functionality
 * Version: 1.0
 * Author: Jeroen van Riet Paap <jeroenpraat@hubzilla.nl>, Mike Macgirvin <mike@zothub.com>
 * Maintainer: Jeroen van Riet Paap <jeroenpraat@hubzilla.nl>
 * 
 */

function noembed_load() {
	register_hook('oembed_probe','addon/noembed/noembed.php','noembed_oembed_probe');
}

function noembed_unload() {
	unregister_hook('oembed_probe','addon/noembed/noembed.php','noembed_oembed_probe');
}

function noembed_oembed_probe(&$a,&$b) {
	// try noembed service
	$ourl = 'https://noembed.com/embed?url=' . urlencode($b['url']);  
	$result = z_fetch_url($ourl);
	if($result['success'])
		$b['embed'] = $result['body'];
}
