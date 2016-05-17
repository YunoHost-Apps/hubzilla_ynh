<?php

/**
 * Diaspora endpoint
 */

require_once('include/crypto.php');
require_once('addon/diaspora/diaspora.php');

	
function receive_post(&$a) {

	$public = false;

	logger('diaspora_receive: ' . print_r(App::$argv, true), LOGGER_DEBUG, LOG_INFO);

	if((argc() == 2) && (argv(1) === 'public')) {
		$public = true;
	}
	else {

		if(argc() != 3 || argv(1) !== 'users')
			http_status_exit(500);

		$guid = argv(2);

		// So that the Diaspora GUID will work with nomadic identity, we append
		// the hostname but remove any periods so that it doesn't mess up URLs.
		// (This was an occasional issue with message_ids that include the hostname,
		// and we learned from that experience).
		// Without the hostname bit the Diaspora side would not be able to share 
		// with two channels which have the same GUID (e.g. channel clones). In this step 
		// we're stripping the hostname part which Diaspora thinks is our GUID so
		// that we can locate our channel by the channel_guid. On our network, 
		// channel clones have the same GUID even if they are on different sites. 

		$hn = str_replace('.','',App::get_hostname());
		if(($x = strpos($guid,$hn)) > 0)
			$guid = substr($guid,0,$x);

		// Sites running old code *may* provide a truncated guid, when GUIDs were 16 hex chars.
		// This might also be true for older Friendica sites that stored the guid separately. 
		// We may not require this truncation check any more, but it probably does no harm to leave it.
		// Forgeries and mischief will be caught out by permission checks later. 

		$r = q("SELECT * FROM channel left join xchan on channel_hash = xchan_hash WHERE channel_guid like '%s' AND channel_removed = 0 LIMIT 1",
			dbesc($guid . '%')
		);

		if(! $r)
			http_status_exit(500);

		$importer = $r[0];
	}

	logger('mod-diaspora: receiving post', LOGGER_DEBUG, LOG_INFO);

	// Diaspora traditionally urlencodes or base64 encodes things a superfluous number of times.
	// The legacy format is double url-encoded for an unknown reason. At the time of this writing
	// the new formats have not yet been seen crossing the wire, so we're being proactive and decoding
	// until we see something reasonable. Once we know how many times we are expected to decode we can 
	// refine this.   

	if($_POST['xml']) {
		$xml = ltrim($_POST['xml']);
		$format = 'legacy';
		// PHP performed the first decode when populating the $_POST variable.
		// Here we do the second - which has been required since 2010-2011.
		if(substr($xml,0,1) !== '<')
			$xml = ltrim(urldecode($xml));
	}
	else {
		$xml = ltrim(file_get_contents('php://input'));
		$format = 'bis';
		$decode_counter = 0;
		while($decode_counter < 3) {
			if((substr($xml,0,1) === '{') || (substr($xml,0,1) === '<'))
				break;
			$decode_counter ++;
			$xml = ltrim(urldecode($xml));
		}
		logger('decode_counter: ' . $decode_counter, LOGGER_DEBUG, LOG_INFO);
	}

	if($format === 'bis') {
		switch(substr($xml,0,1)) {
			case '{':
				$format = 'json';
				break;
			case '<':
				$format = 'salmon';
				break;
			default:
				break;
		}
	}

	logger('diaspora salmon format: ' . $format, LOGGER_DEBUG, LOG_INFO);

	logger('mod-diaspora: new salmon ' . $xml, LOGGER_DATA);

	if((! $xml) || ($format === 'bis'))
		http_status_exit(500);

	logger('mod-diaspora: message is okay', LOGGER_DEBUG);

	$msg = diaspora_decode($importer,$xml,$format);

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

