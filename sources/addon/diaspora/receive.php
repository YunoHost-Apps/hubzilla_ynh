<?php

/**
 * Diaspora endpoint
 */

require_once('include/crypto.php');
require_once('addon/diaspora/diaspora.php');

	
function receive_post(&$a) {

	$public = false;

	logger('diaspora_receive: ' . print_r(App::$argv, true), LOGGER_DEBUG);

	if((argc() == 2) && (argv(1) === 'public')) {
		$public = true;
	}
	else {

		if(argc() != 3 || argv(1) !== 'users')
			http_status_exit(500);

		$guid = argv(2);
		$hn = str_replace('.','',App::get_hostname());
		if(($x = strpos($guid,$hn)) > 0)
			$guid = substr($guid,0,$x);

		// Diaspora sites *may* provide a truncated guid. 

		$r = q("SELECT * FROM channel left join xchan on channel_hash = xchan_hash WHERE channel_guid like '%s' AND channel_removed = 0 LIMIT 1",
			dbesc($guid . '%')
		);

		if(! $r)
			http_status_exit(500);

		$importer = $r[0];
	}

	// It is an application/x-www-form-urlencoded that has been urlencoded twice.

	logger('mod-diaspora: receiving post', LOGGER_DEBUG);

	$xml = urldecode($_POST['xml']);

	logger('mod-diaspora: new salmon ' . $xml, LOGGER_DATA);

	if(! $xml)
		http_status_exit(500);

	logger('mod-diaspora: message is okay', LOGGER_DEBUG);

	$msg = diaspora_decode($importer,$xml);

	logger('mod-diaspora: decoded', LOGGER_DEBUG);

	logger('mod-diaspora: decoded msg: ' . print_r($msg,true), LOGGER_DATA);

	if(! is_array($msg))
		http_status_exit(500);

	logger('mod-diaspora: dispatching', LOGGER_DEBUG);

	$ret = 0;
	if($public)
		diaspora_dispatch_public($msg);
	else
		$ret = diaspora_dispatch($importer,$msg);

	http_status_exit(($ret) ? $ret : 200);
	// NOTREACHED
}

