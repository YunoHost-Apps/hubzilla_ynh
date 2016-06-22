<?php


function diaspora_handle_from_contact($contact_hash) {

	logger("diaspora_handle_from_contact: contact id is " . $contact_hash, LOGGER_DEBUG);

	$r = q("SELECT xchan_addr from xchan where xchan_hash = '%s' limit 1",
		dbesc($contact_hash)
	);
	if($r) {
		return $r[0]['xchan_addr'];
	}
	return false;
}

function diaspora_get_contact_by_handle($uid,$handle) {

	if(diaspora_is_blacklisted($handle))
		return false;
	require_once('include/channel.php');

	$sys = get_sys_channel();
	if(($sys) && ($sys['channel_id'] == $uid)) {
		$r = q("SELECT * FROM xchan where xchan_addr = '%s' limit 1",
			dbesc($handle)
		);
	}
	else {
		$r = q("SELECT * FROM abook left join xchan on xchan_hash = abook_xchan where xchan_addr = '%s' and abook_channel = %d limit 1",
			dbesc($handle),
			intval($uid)
		);
	}

	return (($r) ? $r[0] : false);
}

function find_diaspora_person_by_handle($handle) {

	$person = false;
	$refresh = false;

	if(diaspora_is_blacklisted($handle))
		return false;

	$r = q("select * from xchan where xchan_addr = '%s' limit 1",
		dbesc($handle)
	);
	if($r) {
		$person = $r[0];
		logger('find_diaspora_person_by handle: in cache ' . print_r($r,true), LOGGER_DATA, LOG_DEBUG);
		if($person['xchan_name_date'] < datetime_convert('UTC','UTC', 'now - 1 month')) {
			logger('Updating Diaspora cached record for ' . $handle);
			$refresh = true;
		}
	}

	if((! $person) || ($refresh)) {

		// try webfinger. Make sure to distinguish between diaspora, 
		// hubzilla w/diaspora protocol and friendica w/diaspora protocol.

		$result = discover_by_webbie($handle);
		if($result) {
			$r = q("select * from xchan where xchan_addr = '%s' limit 1",
				dbesc(str_replace('acct:','',$handle))
			);
			if($r) {
				$person = $r[0];
				logger('find_diaspora_person_by handle: discovered ' . print_r($r,true), LOGGER_DATA, LOG_DEBUG);
			}
		}
	}

	return $person;
}


function get_diaspora_key($handle) {
	logger('Fetching diaspora key for: ' . $handle, LOGGER_DEBUG);
	$r = find_diaspora_person_by_handle($handle);
	return(($r) ? $r['xchan_pubkey'] : '');
}



/**
 * Some utility functions for processing the Diaspora comment virus.
 *
 */  




function diaspora_sign_fields($fields,$prvkey) {

	if(! $fields)
		return '';

	$n = array();
	foreach($fields as $k => $v) {
		if($k !== 'author_signature' && $k !== 'parent_author_signature')
			$n[$k] = $v;
	}

	$s = implode($n,';');
	logger('signing_string: ' . $s);
	return base64_encode(rsa_sign($s,$prvkey));

}


function diaspora_verify_fields($fields,$sig,$pubkey) {

	if(! $fields)
		return false;

	$n = array();
	foreach($fields as $k => $v) {
		if($k !== 'author_signature' && $k !== 'parent_author_signature')
			$n[$k] = $v;
	}

	$s = implode($n,';');
	logger('signing_string: ' . $s);
	return rsa_verify($s,base64_decode($sig),$pubkey);

}

function diaspora_fields_to_xml($fields) {

	if(! $fields)
		return '';
	$s = '';
	foreach($fields as $k => $v) {
		$s .= '<' . $k . '>' . xmlify($v) . '</' . $k . '>' . "\n";
	}
	return rtrim($s);
}


function diaspora_build_relay_tags() {

	$alltags = array();

	$r = q("select * from pconfig where cat = 'diaspora' and k = 'followed_tags'");
	if($r) {
		foreach($r as $rr) {
			if(preg_match('|^a:[0-9]+:{.*}$|s',$rr['v'])) {
				$x = unserialize($rr['v']);
				if($x && is_array($x))
					$alltags = array_unique(array_merge($alltags,$x));
			}
		}
	}
	set_config('diaspora','relay_tags',$alltags);
	// Now register to pick up any changes
	$url = "http://the-federation.info/register/" . App::get_hostname();
	$ret = z_fetch_url($url);

}
	
						