<?php

/**
 * @file mod/post.php
 *
 * @brief Zot endpoint.
 *
 */

require_once('include/zot.php');

function post_init(&$a) {

	if (array_key_exists('auth', $_REQUEST)) {
		$x = new Zotlabs\Zot\Auth($_REQUEST);
		exit;
	}

}


function post_post(&$a) {

	$z = new Zotlabs\Zot\Receiver($_REQUEST['data'],get_config('system','prvkey'), new Zotlabs\Zot\ZotHandler());
	
	// notreached;

	exit;

}
