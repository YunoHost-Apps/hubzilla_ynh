<?php


/**
 * Name: Send ZID
 * Description: Provides an optional feature to send your identity to all websites
 * Version: 1.0
 * Author: Mike Macgirvin <mike@zothub.com>
 * Maintainer: none
 */

function sendzid_load() {
	register_hook('get_features','addon/sendzid/sendzid.php','sendzid_get_features');
}

function sendzid_unload() {
	unregister_hook('get_features','addon/sendzid/sendzid.php','sendzid_get_features');
}

function sendzid_get_features(&$a,&$b) {

	//FIXME - needs a better description
	$b['general'][] = array(
		'sendzid',
		t('Extended Identity Sharing'),
		t('Share your identity with all websites on the internet. When disabled, identity is only shared with sites in the matrix.'),false);

}

