<?php

/**
 * Name: Embedly
 * Description: Use oohemebed.com to resolve oembeds that can't be discovered directly
 * Version: 1.0
 * Author: Mike Macgirvin <mike@zothub.com>
 * 
 */

function embedly_load() {
	register_hook('oembed_probe','addon/embedly/embedly.php','embedly_oembed_probe');
}

function embedly_unload() {
	unregister_hook('oembed_probe','addon/embedly/embedly.php','embedly_oembed_probe');
}

function embedly_oembed_probe($a,$b) {
	// try oohembed service
	$ourl = "http://oohembed.com/oohembed/?url=".$b['url'].'&maxwidth=' . $b['videowidth'];  
	$result = z_fetch_url($ourl);
	if($result['success'])
		$b['embed'] = $result['body'];
}