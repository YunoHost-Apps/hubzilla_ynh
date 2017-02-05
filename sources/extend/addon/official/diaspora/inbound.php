<?php

function diaspora_dispatch_public($msg) {

	$sys_disabled = false;

	if(get_config('system','disable_discover_tab') || get_config('system','disable_diaspora_discover_tab')) {
		$sys_disabled = true;
	}
	$sys = (($sys_disabled) ? null : get_sys_channel());

	// find everybody following or allowing this author

	$r = q("SELECT * from channel where channel_id in ( SELECT abook_channel from abook left join xchan on abook_xchan = xchan_hash WHERE xchan_network like '%%diaspora%%' and xchan_addr = '%s' ) and channel_removed = 0 ",
		dbesc($msg['author'])
	);

	// look for those following tags - we'll check tag validity for each specific channel later 

	$y = q("select * from channel where channel_id in ( SELECT uid from pconfig where cat = 'diaspora' and k = 'followed_tags' and v != '') and channel_removed = 0 ");

	if(is_array($y) && is_array($r))
		$r = array_merge($r,$y);

	// @FIXME we should also enumerate channels that allow postings by anybody

	$msg['public'] = 1;

	if($r) {
		foreach($r as $rr) {
			logger('diaspora_public: delivering to: ' . $rr['channel_name'] . ' (' . $rr['channel_address'] . ') ');
			diaspora_dispatch($rr,$msg);
		}
	}
	else {
		if(! $sys)
			logger('diaspora_public: no subscribers');
	}

	if($sys) {
		$sys['system'] = true;
		logger('diaspora_public: delivering to sys.');
		
		diaspora_dispatch($sys,$msg);
	}
}



function diaspora_dispatch($importer,$msg) {

	$ret = 0;

	if(! array_key_exists('system',$importer))
		$importer['system'] = false;

	if(! array_key_exists('public',$msg))
		$msg['public'] = 0;

	$host = substr($msg['author'],strpos($msg['author'],'@')+1);
	$ssl = ((array_key_exists('HTTPS',$_SERVER) && strtolower($_SERVER['HTTPS']) === 'on') ? true : false);
	$url = (($ssl) ? 'https://' : 'http://') . $host;

	q("update site set site_dead = 0, site_update = '%s' where site_type = %d and site_url = '%s'",
		dbesc(datetime_convert()),
		intval(SITE_TYPE_NOTZOT),
		dbesc($url)
	);

	$allowed = (($importer['system']) ? 1 : get_pconfig($importer['channel_id'],'system','diaspora_allowed'));

	if(! intval($allowed)) {
		logger('mod-diaspora: disallowed for channel ' . $importer['channel_name']);
		return;
	}

	$parsed_xml = xml2array($msg['message'],false,0,'tag');

	if($parsed_xml) {
		if(array_key_exists('xml',$parsed_xml) && array_key_exists('post',$parsed_xml['xml']))
			$xmlbase = $parsed_xml['xml']['post'];
		else
			$xmlbase = $parsed_xml;
	}

//	logger('diaspora_dispatch: ' . print_r($xmlbase,true), LOGGER_DATA);


	if($xmlbase['request']) {
		$ret = diaspora_request($importer,$xmlbase['request']);
	}
	elseif($xmlbase['contact']) {
		$ret = diaspora_request($importer,$xmlbase['contact']);
	}
	elseif($xmlbase['status_message']) {
		$ret = diaspora_post($importer,$xmlbase['status_message'],$msg);
	}
	elseif($xmlbase['profile']) {
		$ret = diaspora_profile($importer,$xmlbase['profile'],$msg);
	}
	elseif($xmlbase['comment']) {
		$ret = diaspora_comment($importer,$xmlbase['comment'],$msg);
	}
	elseif($xmlbase['like']) {
		$ret = diaspora_like($importer,$xmlbase['like'],$msg);
	}
	elseif($xmlbase['reshare']) {
		$ret = diaspora_reshare($importer,$xmlbase['reshare'],$msg);
	}
	elseif($xmlbase['retraction']) {
		$ret = diaspora_retraction($importer,$xmlbase['retraction'],$msg);
	}
	elseif($xmlbase['signed_retraction']) {
		$ret = diaspora_retraction($importer,$xmlbase['signed_retraction'],$msg);
	}
	elseif($xmlbase['relayable_retraction']) {
		$ret = diaspora_retraction($importer,$xmlbase['relayable_retraction'],$msg);
	}
	elseif($xmlbase['photo']) {
		$ret = diaspora_photo($importer,$xmlbase['photo'],$msg);
	}
	elseif($xmlbase['conversation']) {
		$ret = diaspora_conversation($importer,$xmlbase['conversation'],$msg);
	}
	elseif($xmlbase['message']) {
		$ret = diaspora_message($importer,$xmlbase['message'],$msg);
	}
	elseif($xmlbase['participation']) {
		$ret = diaspora_participation($importer,$xmlbase['participation'],$msg);
	}
	elseif($xmlbase['account_deletion']) {
		$ret = diaspora_account_deletion($importer,$xmlbase['account_deletion'],$msg);
	}
	elseif($xmlbase['poll_participation']) {
		$ret = diaspora_poll_participation($importer,$xmlbase['poll_participation'],$msg);
	}
	else {
		logger('diaspora_dispatch: unknown message type: ' . print_r($xmlbase,true));
	}
	return $ret;
}


function diaspora_is_blacklisted($s) {

	if(! check_siteallowed($s)) {
		logger('blacklisted site: ' . $s);
		return true;
	}

	return false;
}


/**
 *
 * diaspora_decode($importer,$xml,$format)
 *   array $importer -> from user table
 *   string $xml -> urldecoded Diaspora salmon
 *   string $format 'legacy', 'salmon', or 'json' 
 *
 * Returns array
 * 'message' -> decoded Diaspora XML message
 * 'author' -> author diaspora handle
 * 'key' -> author public key (converted to pkcs#8)
 *
 * Author and key are used elsewhere to save a lookup for verifying replies and likes
 */


function diaspora_decode($importer,$xml,$format) {

	$public = false;

	if($format === 'json') {
		$json = json_decode($xml,true);
		if($json['aes_key']) {
			$key_bundle = '';
			$result = openssl_private_decrypt(base64_decode($json['aes_key']),$key_bundle,$importer['channel_prvkey']);
			if(! $result) {
				logger('decrypting key_bundle for ' . $importer['channel_address'] . ' failed: ' . $json['aes_key'],LOGGER_NORMAL, LOG_ERR);
				http_status_exit(400);
			}
			$jkey = json_decode($key_bundle,true);
			$xml = AES256CBC_decrypt(base64_decode($json['encrypted_magic_envelope']),base64_decode($jkey['key']),base64_decode($jkey['iv']));
			if(! $xml) {
				logger('decrypting magic_envelope for ' . $importer['channel_address'] . ' failed: ' . $json['aes_key'],LOGGER_NORMAL, LOG_ERR);
				http_status_exit(400);
			}
		}
	}

	$basedom = parse_xml_string($xml);

	if($format !== 'legacy') {
		$children = $basedom->children('http://salmon-protocol.org/ns/magic-env');
		$public = true;
		$author_link = str_replace('acct:','',base64url_decode($children->key_id));

		/**
			SimpleXMLElement Object
			(
			    [encoding] => base64url
			    [alg] => RSA-SHA256
			    [data] => ((base64url-encoded payload message))
			    [sig] => ((the RSA-SHA256 signature of the above data))
			    [key_id] => ((base64url-encoded Alice's diaspora ID))
			)
		**/
	} 
	else {

		$children = $basedom->children('https://joindiaspora.com/protocol');

		if($children->header) {
			$public = true;
			$author_link = str_replace('acct:','',$children->header->author_id);
		}
		else {

			$encrypted_header = json_decode(base64_decode($children->encrypted_header));
			$encrypted_aes_key_bundle = base64_decode($encrypted_header->aes_key);
			$ciphertext = base64_decode($encrypted_header->ciphertext);

			$outer_key_bundle = '';
			openssl_private_decrypt($encrypted_aes_key_bundle,$outer_key_bundle,$importer['channel_prvkey']);

			$j_outer_key_bundle = json_decode($outer_key_bundle);

			$outer_iv = base64_decode($j_outer_key_bundle->iv);
			$outer_key = base64_decode($j_outer_key_bundle->key);

			$decrypted = AES256CBC_decrypt($ciphertext,$outer_key,$outer_iv);

			/**
			 * $decrypted now contains something like
			 *
			 *  <decrypted_header>
			 *	 <iv>8e+G2+ET8l5BPuW0sVTnQw==</iv>
			 *	 <aes_key>UvSMb4puPeB14STkcDWq+4QE302Edu15oaprAQSkLKU=</aes_key>
			 ***** OBSOLETE
			 *	 <author>
			 *	   <name>Ryan Hughes</name>
			 *	   <uri>acct:galaxor@diaspora.pirateship.org</uri>
			 *	 </author>
			 ***** CURRENT/LEGACY
			 *	 <author_id>galaxor@diaspora.pirateship.org</author_id>
			 ***** END DIFFS
			 *  </decrypted_header>
			 */

			logger('decrypted: ' . $decrypted, LOGGER_DATA);
			$idom = parse_xml_string($decrypted,false);

			$inner_iv = base64_decode($idom->iv);
			$inner_aes_key = base64_decode($idom->aes_key);

			$author_link = str_replace('acct:','',$idom->author_id);
		}
	}
	
	$dom = $basedom->children(NAMESPACE_SALMON_ME);

	// figure out where in the DOM tree our data is hiding

	if($dom->provenance->data)
		$base = $dom->provenance;
	elseif($dom->env->data)
		$base = $dom->env;
	elseif($dom->data)
		$base = $dom;

	if(! $base) {
		logger('mod-diaspora: unable to locate salmon data in xml ', LOGGER_NORMAL, LOG_ERR);
		http_status_exit(400);
	}


	// Stash the signature away for now. We have to find their key or it won't be good for anything.
	$signature = base64url_decode($base->sig);

	// unpack the  data

	// strip whitespace so our data element will return to one big base64 blob
	$data = str_replace(array(" ","\t","\r","\n"),array("","","",""),$base->data);

	// stash away some other stuff for later

	$type     = $base->data[0]->attributes()->type[0];
	$keyhash  = $base->sig[0]->attributes()->keyhash[0];
	$encoding = $base->encoding;
	$alg      = $base->alg;

	$signed_data = $data  . '.' . base64url_encode($type,false) . '.' . base64url_encode($encoding,false) . '.' . base64url_encode($alg,false);


	// decode the data
	$data = base64url_decode($data);

	if(($format === 'legacy') && (! $public)) {
		// Decode the encrypted blob
		$final_msg = AES256CBC_decrypt(base64_decode($data),$inner_aes_key,$inner_iv);
	}
	else {
		$final_msg = $data;
	}

	if(! $author_link) {
		logger('mod-diaspora: Could not retrieve author URI.');
		http_status_exit(400);
	}

	// Once we have the author URI, go to the web and try to find their public key
	// (first this will look it up locally if it is in the fcontact cache)
	// This will also convert diaspora public key from pkcs#1 to pkcs#8

	logger('mod-diaspora: Fetching key for ' . $author_link );
 	$key = get_diaspora_key($author_link);

	if(! $key) {
		logger('mod-diaspora: Could not retrieve author key.', LOGGER_NORMAL, LOG_WARNING);
		http_status_exit(400);
	}

	$verify = rsa_verify($signed_data,$signature,$key);

	if(! $verify) {
		logger('mod-diaspora: Message did not verify. Discarding.', LOGGER_NORMAL, LOG_ERR);
		http_status_exit(400);
	}

	logger('mod-diaspora: Message verified.');

	return array('message' => $final_msg, 'author' => $author_link, 'key' => $key);

}

/* sender is now sharing with recipient */

function diaspora_request($importer,$xml) {

	$a = get_app();

	$sender_handle = unxmlify(diaspora_get_author($xml));
	$recipient_handle = unxmlify(diaspora_get_recipient($xml));

	// @TODO - map these perms to $newperms below

	if(array_key_exists('following',$xml) && array_key_exists('sharing',$xml)) {
		$following = ((unxmlify($xml['following'])) === 'true' ? true : false);
		$sharing = ((unxmlify($xml['sharing'])) === 'true' ? true : false);
	}
	else {
		$following = true;
		$sharing = true;
	}

	if((! $sender_handle) || (! $recipient_handle))
		return;


	// Do we already have an abook record? 

	$contact = diaspora_get_contact_by_handle($importer['channel_id'],$sender_handle);

	// Please note some permissions such as PERMS_R_PAGES are impossible for Disapora.
	// They cannot currently authenticate to our system.

	$x = \Zotlabs\Access\PermissionRoles::role_perms('social');
	$their_perms = \Zotlabs\Access\Permissions::FilledPerms($x['perms_connect']);

	if($contact && $contact['abook_id']) {

		// perhaps we were already sharing with this person. Now they're sharing with us.
		// That makes us friends. Maybe.

		foreach($their_perms as $k => $v)
			set_abconfig($importer['channel_id'],$contact['abook_xchan'],'their_perms',$k,$v);

		$abook_instance = $contact['abook_instance'];
		if($abook_instance) 
			$abook_instance .= ',';
		$abook_instance .= z_root();

		$r = q("update abook set abook_instance = '%s' where abook_id = %d and abook_channel = %d",
			dbesc($abook_instance),
			intval($contact['abook_id']),
			intval($importer['channel_id'])
		);

		return;
	}

	$ret = find_diaspora_person_by_handle($sender_handle);

	if((! $ret) || (! strstr($ret['xchan_network'],'diaspora'))) {
		logger('diaspora_request: Cannot resolve diaspora handle ' . $sender_handle . ' for ' . $recipient_handle);
		return;
	}

	$my_perms = false;

	$role = get_pconfig($importer['channel_id'],'system','permissions_role');
	if($role) {
		$x = \Zotlabs\Access\PermissionRoles::role_perms($role);
		if($x['perms_auto'])
			$my_perms = \Zotlabs\Access\Permissions::FilledPerms($x['perms_connect']);
	}
	if(! $my_perms)
		$my_perms = \Zotlabs\Access\Permissions::FilledAutoperms($importer['channel_id']);
				
	$closeness = get_pconfig($importer['channel_id'],'system','new_abook_closeness');
	if($closeness === false)
		$closeness = 80;

	$r = q("insert into abook ( abook_account, abook_channel, abook_xchan, abook_my_perms, abook_their_perms, abook_closeness, abook_created, abook_updated, abook_connected, abook_dob, abook_pending, abook_instance ) values ( %d, %d, '%s', %d, %d, %d, '%s', '%s', '%s', '%s', %d, '%s' )",
		intval($importer['channel_account_id']),
		intval($importer['channel_id']),
		dbesc($ret['xchan_hash']),
		intval($default_perms),
		intval($their_perms),
		intval($closeness),
		dbesc(datetime_convert()),
		dbesc(datetime_convert()),
		dbesc(datetime_convert()),
		dbesc(NULL_DATE),
		intval(($my_perms) ? 0 : 1),
		dbesc(z_root())
	);
		
	if($my_perms)
		foreach($my_perms as $k => $v)
			set_abconfig($importer['channel_id'],$ret['xchan_hash'],'my_perms',$k,$v);

	if($their_perms)
		foreach($their_perms as $k => $v)
			set_abconfig($importer['channel_id'],$ret['xchan_hash'],'their_perms',$k,$v);


	if($r) {
		logger("New Diaspora introduction received for {$importer['channel_name']}");

		$new_connection = q("select * from abook left join xchan on abook_xchan = xchan_hash left join hubloc on hubloc_hash = xchan_hash where abook_channel = %d and abook_xchan = '%s' order by abook_created desc limit 1",
			intval($importer['channel_id']),
			dbesc($ret['xchan_hash'])
		);
		if($new_connection) {
			\Zotlabs\Lib\Enotify::submit(
				[
					'type'	       => NOTIFY_INTRO,
					'from_xchan'   => $ret['xchan_hash'],
					'to_xchan'     => $importer['channel_hash'],
					'link'         => z_root() . '/connedit/' . $new_connection[0]['abook_id'],
				]
			);

			if($my_perms) {
				// Send back a sharing notification to them
				$x = diaspora_share($importer,$new_connection[0]);
				if($x)
					Zotlabs\Daemon\Master::Summon(array('Deliver',$x));
		
			}

			$clone = array();
			foreach($new_connection[0] as $k => $v) {
				if(strpos($k,'abook_') === 0) {
					$clone[$k] = $v;
				}
			}
			unset($clone['abook_id']);
			unset($clone['abook_account']);
			unset($clone['abook_channel']);
		
			$abconfig = load_abconfig($importer['channel_id'],$clone['abook_xchan']);

			if($abconfig)
				$clone['abconfig'] = $abconfig;

			build_sync_packet($importer['channel_id'], [ 'abook' => array($clone) ] );

		}
	}

	// find the abook record we just created

	$contact_record = diaspora_get_contact_by_handle($importer['channel_id'],$sender_handle);

	if(! $contact_record) {
		logger('diaspora_request: unable to locate newly created contact record.');
		return;
	}

	/** If there is a default group for this channel, add this member to it */

	if($importer['channel_default_group']) {
		require_once('include/group.php');
		$g = group_rec_byhash($importer['channel_id'],$importer['channel_default_group']);
		if($g)
			group_add_member($importer['channel_id'],'',$contact_record['xchan_hash'],$g['id']);
	}

	return;
}



function diaspora_post($importer,$xml,$msg) {

	$guid = notags(unxmlify($xml['guid']));
	$diaspora_handle = notags(diaspora_get_author($xml));
	$app = ((array_key_exists('provider_display_name',$xml)) ? notags(unxmlify($xml['provider_display_name'])) : '');


	if($diaspora_handle != $msg['author']) {
		logger('diaspora_post: Potential forgery. Message handle is not the same as envelope sender.');
		return 202;
	}

	$xchan = find_diaspora_person_by_handle($diaspora_handle);

	if((! $xchan) || (! strstr($xchan['xchan_network'],'diaspora'))) {
		logger('Cannot resolve diaspora handle ' . $diaspora_handle);
		return;
	}

	$contact = diaspora_get_contact_by_handle($importer['channel_id'],$diaspora_handle);

	if(! $app) {
		if(strstr($xchan['xchan_network'],'friendica'))
			$app = 'Friendica';
		else
			$app = 'Diaspora';
	}


	$search_guid = ((strlen($guid) == 64) ? $guid . '%' : $guid);

	$r = q("SELECT id FROM item WHERE uid = %d AND mid like '%s' LIMIT 1",
		intval($importer['channel_id']),
		dbesc($search_guid)
	);

	if($r) {
		// check dates if post editing is implemented
		logger('diaspora_post: message exists: ' . $guid);
		return;
	}

	$created = unxmlify($xml['created_at']);
	$private = ((unxmlify($xml['public']) == 'false') ? 1 : 0);

	$body = markdown_to_bb(diaspora_get_body($xml));

	if($xml['photo']) {
		$body = '[img]' . $xml['photo']['remote_photo_path'] . $xml['photo']['remote_photo_name'] . '[/img]' . "\n\n" . $body;
		$body = scale_external_images($body);
	}

	$maxlen = get_max_import_size();

	if($maxlen && mb_strlen($body) > $maxlen) {
		$body = mb_substr($body,0,$maxlen,'UTF-8');
		logger('message length exceeds max_import_size: truncated');
	}

	$datarray = array();

	// Look for tags and linkify them
	$results = linkify_tags(get_app(), $body, $importer['channel_id'], true);

	$datarray['term'] = array();

	if($results) {
		foreach($results as $result) {
			$success = $result['success'];
			if($success['replaced']) {
				$datarray['term'][] = array(
					'uid'   => $importer['channel_id'],
					'ttype'  => $success['termtype'],
					'otype' => TERM_OBJ_POST,
					'term'  => $success['term'],
					'url'   => $success['url']
				);
			}
		}
	}

	$found_tags = false;
	$followed_tags = get_pconfig($importer['channel_id'],'diaspora','followed_tags');
	if($followed_tags && $datarray['term']) {
		foreach($datarray['term'] as $t) {
			if(in_array($t['term'],$followed_tags)) {
				$found_tags = true;
				break;
			}
		}
	}


	$cnt = preg_match_all('/@\[url=(.*?)\](.*?)\[\/url\]/ism',$body,$matches,PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			$datarray['term'][] = array(
				'uid'   => $importer['channel_id'],
				'ttype'  => TERM_MENTION,
				'otype' => TERM_OBJ_POST,
				'term'  => $mtch[2],
				'url'   => $mtch[1]
			);
		}
	}

	$cnt = preg_match_all('/@\[zrl=(.*?)\](.*?)\[\/zrl\]/ism',$body,$matches,PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			// don't include plustags in the term
			$term = ((substr($mtch[2],-1,1) === '+') ? substr($mtch[2],0,-1) : $mtch[2]);
			$datarray['term'][] = array(
				'uid'   => $importer['channel_id'],
				'ttype'  => TERM_MENTION,
				'otype' => TERM_OBJ_POST,
				'term'  => $term,
				'url'   => $mtch[1]
			);
		}
	}

	$plink = service_plink($xchan,$guid);

	$datarray['aid'] = $importer['channel_account_id'];
	$datarray['uid'] = $importer['channel_id'];

	$datarray['verb'] = ACTIVITY_POST;
	$datarray['mid'] = $datarray['parent_mid'] = $guid;

	$datarray['changed'] = $datarray['created'] = $datarray['edited'] = datetime_convert('UTC','UTC',$created);
	$datarray['item_private'] = $private;

	$datarray['plink'] = $plink;

	$datarray['author_xchan'] = $xchan['xchan_hash'];
	$datarray['owner_xchan']  = $xchan['xchan_hash'];

	$datarray['body'] = $body;

	$datarray['app']  = $app;

	$datarray['item_unseen'] = 1;
	$datarray['item_thread_top'] = 1;

	$tgroup = tgroup_check($importer['channel_id'],$datarray);

	if((! $importer['system']) && (! perm_is_allowed($importer['channel_id'],$xchan['xchan_hash'],'send_stream')) && (! $tgroup) && (! $found_tags)) {
		logger('diaspora_post: Ignoring this author.');
		return 202;
	}

	if($importer['system'] || $msg['public']) {
		$datarray['comment_policy'] = 'network: diaspora';
	}

	if(($contact) && (! post_is_importable($datarray,$contact))) {
		logger('diaspora_post: filtering this author.');
		return 202;
	}

	$result = item_store($datarray);

	if($result['success']) {
		sync_an_item($importer['channel_id'],$result['item_id']);
	}

	return;

}


function get_diaspora_reshare_xml($url,$recurse = 0) {

	$x = z_fetch_url($url);
	if(! $x['success'])
		$x = z_fetch_url(str_replace('https://','http://',$url));
	if(! $x['success']) {
		logger('get_diaspora_reshare_xml: unable to fetch source url ' . $url);
		return;
	}

	logger('get_diaspora_reshare_xml: source: ' . $x['body'], LOGGER_DEBUG);

//	$source_xml = parse_xml_string($x['body'],false);

	$source_xml = xml2array($x['body'],false,0,'tag');

	if(! $source_xml) {
		logger('get_diaspora_reshare_xml: unparseable result from ' . $url);
		return '';
	}

	if($source_xml) {
		if(array_key_exists('xml',$source_xml) && array_key_exists('post',$source_xml['xml'])) 
			$source_xml = $source_xml['xml']['post'];
	}

	if($source_xml['status_message']) {
		return $source_xml;
	}

	// see if it's a reshare of a reshare

	if($source_xml['reshare'])
		$xml = $source_xml['reshare'];
	else 
		return false;

	if(($xml['root_diaspora_id'] || $xml['root_author']) && $xml['root_guid'] && $recurse < 15) {
		$orig_author = notags(diaspora_get_root_author($xml));
		$orig_guid = notags(unxmlify($xml['root_guid']));
		$source_url = 'https://' . substr($orig_author,strpos($orig_author,'@')+1) . '/p/' . $orig_guid . '.xml';
		$y = get_diaspora_reshare_xml($source_url,$recurse+1);
		if($y)
			return $y;
	}
	return false;
}



function diaspora_reshare($importer,$xml,$msg) {

	logger('diaspora_reshare: init: ' . print_r($xml,true), LOGGER_DATA);

	$a = get_app();
	$guid = notags(unxmlify($xml['guid']));
	$diaspora_handle = notags(diaspora_get_author($xml));


	if($diaspora_handle != $msg['author']) {
		logger('Potential forgery. Message handle is not the same as envelope sender.');
		return 202;
	}

	$contact = diaspora_get_contact_by_handle($importer['channel_id'],$diaspora_handle);
	if(! $contact)
		return;

	$search_guid = ((strlen($guid) == 64) ? $guid . '%' : $guid);
	$r = q("SELECT id FROM item WHERE uid = %d AND mid like '%s' LIMIT 1",
		intval($importer['channel_id']),
		dbesc($search_guid)
	);
	if($r) {
		logger('diaspora_reshare: message exists: ' . $guid);
		return;
	}

	$orig_author = notags(diaspora_get_root_author($xml));
	$orig_guid = notags(unxmlify($xml['root_guid']));

	$source_url = 'https://' . substr($orig_author,strpos($orig_author,'@')+1) . '/p/' . $orig_guid . '.xml';
	$orig_url = 'https://'.substr($orig_author,strpos($orig_author,'@')+1).'/posts/'.$orig_guid;

	$source_xml = get_diaspora_reshare_xml($source_url);

	if($source_xml['status_message']) {
		$body = markdown_to_bb(diaspora_get_body($source_xml['status_message']));

		
		$orig_author = diaspora_get_author($source_xml['status_message']);
		$orig_guid = notags(unxmlify($source_xml['status_message']['guid']));


		// Checking for embedded pictures
		if($source_xml['status_message']['photo']['remote_photo_path'] &&
			$source_xml['status_message']['photo']['remote_photo_name']) {

			$remote_photo_path = notags(unxmlify($source_xml['status_message']['photo']['remote_photo_path']));
			$remote_photo_name = notags(unxmlify($source_xml['status_message']['photo']['remote_photo_name']));

			$body = '[img]'.$remote_photo_path.$remote_photo_name.'[/img]'."\n".$body;

			logger('diaspora_reshare: embedded picture link found: '.$body, LOGGER_DEBUG);
		}

		$body = scale_external_images($body);

		// Add OEmbed and other information to the body
//		$body = add_page_info_to_body($body, false, true);
	}
	else {
		// Maybe it is a reshare of a photo that will be delivered at a later time (testing)
		logger('diaspora_reshare: no reshare content found: ' . print_r($source_xml,true));
		$body = "";
		//return;
	}

	$maxlen = get_max_import_size();

	if($maxlen && mb_strlen($body) > $maxlen) {
		$body = mb_substr($body,0,$maxlen,'UTF-8');
		logger('message length exceeds max_import_size: truncated');
	}

	$person = find_diaspora_person_by_handle($orig_author);

	if($person) {
		$orig_author_name = $person['xchan_name'];
		$orig_author_link = $person['xchan_url'];
		$orig_author_photo = $person['xchan_photo_m'];
	}


	$created = unxmlify($xml['created_at']);
	$private = ((unxmlify($xml['public']) == 'false') ? 1 : 0);

	$datarray = array();

	// Look for tags and linkify them
	$results = linkify_tags(get_app(), $body, $importer['channel_id'], true);

	$datarray['term'] = array();

	if($results) {
		foreach($results as $result) {
			$success = $result['success'];
			if($success['replaced']) {
				$datarray['term'][] = array(
					'uid'   => $importer['channel_id'],
					'ttype'  => $success['termtype'],
					'otype' => TERM_OBJ_POST,
					'term'  => $success['term'],
					'url'   => $success['url']
				);
			}
		}
	}

	$cnt = preg_match_all('/@\[url=(.*?)\](.*?)\[\/url\]/ism',$body,$matches,PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			$datarray['term'][] = array(
				'uid'   => $importer['channel_id'],
				'ttype'  => TERM_MENTION,
				'otype' => TERM_OBJ_POST,
				'term'  => $mtch[2],
				'url'   => $mtch[1]
			);
		}
	}

	$cnt = preg_match_all('/@\[zrl=(.*?)\](.*?)\[\/zrl\]/ism',$body,$matches,PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			// don't include plustags in the term
			$term = ((substr($mtch[2],-1,1) === '+') ? substr($mtch[2],0,-1) : $mtch[2]);
			$datarray['term'][] = array(
				'uid'   => $importer['channel_id'],
				'ttype'  => TERM_MENTION,
				'otype' => TERM_OBJ_POST,
				'term'  => $term,
				'url'   => $mtch[1]
			);
		}
	}





	$newbody = "[share author='" . urlencode($orig_author_name) 
		. "' profile='" . $orig_author_link 
		. "' avatar='" . $orig_author_photo 
		. "' link='" . $orig_url
		. "' posted='" . datetime_convert('UTC','UTC',unxmlify($source_xml['status_message']['created_at']))
		. "' message_id='" . unxmlify($source_xml['status_message']['guid'])
 		. "']" . $body . "[/share]";


	$plink = service_plink($contact,$guid);
	$datarray['aid'] = $importer['channel_account_id'];
	$datarray['uid'] = $importer['channel_id'];
	$datarray['mid'] = $datarray['parent_mid'] = $guid;
	$datarray['changed'] = $datarray['created'] = $datarray['edited'] = datetime_convert('UTC','UTC',$created);
	$datarray['item_private'] = $private;
	$datarray['plink'] = $plink;
	$datarray['owner_xchan'] = $contact['xchan_hash'];
	$datarray['author_xchan'] = $contact['xchan_hash'];

	$datarray['body'] = $newbody;
	$datarray['app']  = 'Diaspora';


	$tgroup = tgroup_check($importer['channel_id'],$datarray);

	if((! $importer['system']) && (! perm_is_allowed($importer['channel_id'],$contact['xchan_hash'],'send_stream')) && (! $tgroup)) {
		logger('diaspora_reshare: Ignoring this author.');
		return 202;
	}

	if(! post_is_importable($datarray,$contact)) {
		logger('diaspora_reshare: filtering this author.');
		return 202;
	}

	$result = item_store($datarray);

	if($result['success']) {
		sync_an_item($importer['channel_id'],$result['item_id']);
	}

	return;

}


function diaspora_comment($importer,$xml,$msg) {

	$a = get_app();
	$guid = notags(unxmlify($xml['guid']));
	$parent_guid = notags(unxmlify($xml['parent_guid']));
	$diaspora_handle = notags(diaspora_get_author($xml));

	$text = unxmlify($xml['text']);
	$author_signature = notags(unxmlify($xml['author_signature']));
	$parent_author_signature = (($xml['parent_author_signature']) ? notags(unxmlify($xml['parent_author_signature'])) : '');


	$xchan = find_diaspora_person_by_handle($diaspora_handle);

	if(! $xchan) {
		logger('Cannot resolve diaspora handle ' . $diaspora_handle);
		return;
	}

	$contact = diaspora_get_contact_by_handle($importer['channel_id'],$msg['author']);


	$pubcomment = get_pconfig($importer['channel_id'],'system','diaspora_public_comments');

	// by default comments on public posts are allowed from anybody on Diaspora. That is their policy.
	// Once this setting is set to something we'll track your preference and it will over-ride the default. 

	if($pubcomment === false)
		$pubcomment = 1;

	if(($pubcomment) && (! $contact))
		$contact = find_diaspora_person_by_handle($msg['author']);


	// Friendica is currently truncating guids at 64 chars
	$search_guid = $parent_guid;
	if(strlen($parent_guid) == 64)
		$search_guid = $parent_guid . '%';

	$r = q("SELECT * FROM item WHERE uid = %d AND mid LIKE '%s' LIMIT 1",
		intval($importer['channel_id']),
		dbesc($search_guid)
	);
	if(! $r) {
		logger('diaspora_comment: parent item not found: parent: ' . $parent_guid . ' item: ' . $guid);
		return;
	}

	$parent_item = $r[0];

	if(intval($parent_item['item_nocomment']) || $parent_item['comment_policy'] === 'none' 
		|| ($parent_item['comments_closed'] > NULL_DATE && $parent_item['comments_closed'] < datetime_convert())) {
		logger('diaspora_comment: comments disabled for post ' . $parent_item['mid']);
		return;
	}

	if(intval($parent_item['item_private']))
		$pubcomment = 0;	

	$search_guid = $guid;
	if(strlen($guid) == 64)
		$search_guid = $guid . '%';


	$r = q("SELECT * FROM item WHERE uid = %d AND mid like '%s' LIMIT 1",
		intval($importer['channel_id']),
		dbesc($search_guid)
	);
	if($r) {
		logger('diaspora_comment: our comment just got relayed back to us (or there was a guid collision) : ' . $guid);
		return;
	}

	/* How Diaspora performs comment signature checking:

	   - If an item has been sent by the comment author to the top-level post owner to relay on
	     to the rest of the contacts on the top-level post, the top-level post owner should check
	     the author_signature, then create a parent_author_signature before relaying the comment on
	   - If an item has been relayed on by the top-level post owner, the contacts who receive it
	     check only the parent_author_signature. Basically, they trust that the top-level post
	     owner has already verified the authenticity of anything he/she sends out
	   - In either case, the signature that get checked is the signature created by the person
	     who sent the pseudo-salmon
	*/

	$signed_data = $guid . ';' . $parent_guid . ';' . $text . ';' . $diaspora_handle;
	$key = $msg['key'];

	$unxml = array_map('unxmlify',$xml);

	if($parent_author_signature) {
		// If a parent_author_signature exists, then we've received the comment
		// relayed from the top-level post owner. 

		$x = diaspora_verify_fields($unxml,$parent_author_signature,$key);
		if(! $x) {
			logger('diaspora_comment: top-level owner verification failed.');
			return;
		}

		//$parent_author_signature = base64_decode($parent_author_signature);

		//if(! rsa_verify($signed_data,$parent_author_signature,$key,'sha256')) {
		//	logger('diaspora_comment: top-level owner verification failed.');
		//	return;
		//}
	}
	else {

		// the comment is being sent to the owner to relay

		if($importer['system']) {
			// don't relay to the sys channel
			logger('diaspora_comment: relay to sys channel blocked.');
			return;
		}

		// Note: Diaspora verifies both signatures. We only verify the 
		// author_signature when relaying.
		// 
		// If there's no parent_author_signature, then we've received the comment
		// from the comment creator. In that case, the person is commenting on
		// our post, so he/she must be a contact of ours and his/her public key
		// should be in $msg['key']


		$x = diaspora_verify_fields($unxml,$author_signature,$key);
		if(! $x) {
			logger('diaspora_comment: comment author verification failed.');
			return;
		}

		//$author_signature = base64_decode($author_signature);

		//if(! rsa_verify($signed_data,$author_signature,$key,'sha256')) {
		//	logger('diaspora_comment: comment author verification failed.');
		//	return;
		//}

		// No parent_author_signature, so let's assume we're relaying the post. Create one. 
	
		$unxml['parent_author_signature'] = diaspora_sign_fields($unxml,$importer['channel_prvkey']);

	}

	// Phew! Everything checks out. Now create an item.

	// Find the original comment author information.
	// We need this to make sure we display the comment author
	// information (name and avatar) correctly.

	if(strcasecmp($diaspora_handle,$msg['author']) == 0)
		$person = $contact;
	else
		$person = $xchan;

	if(! is_array($person)) {
		logger('diaspora_comment: unable to find author details');
		return;
	}

	$body = markdown_to_bb($text);

	$maxlen = get_max_import_size();

	if($maxlen && mb_strlen($body) > $maxlen) {
		$body = mb_substr($body,0,$maxlen,'UTF-8');
		logger('message length exceeds max_import_size: truncated');
	}

	$datarray = array();

	// Look for tags and linkify them
	$results = linkify_tags(get_app(), $body, $importer['channel_id'], true);

	$datarray['term'] = array();

	if($results) {
		foreach($results as $result) {
			$success = $result['success'];
			if($success['replaced']) {
				$datarray['term'][] = array(
					'uid'   => $importer['channel_id'],
					'ttype'  => $success['termtype'],
					'otype' => TERM_OBJ_POST,
					'term'  => $success['term'],
					'url'   => $success['url']
				);
			}
		}
	}

	$cnt = preg_match_all('/@\[url=(.*?)\](.*?)\[\/url\]/ism',$body,$matches,PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			$datarray['term'][] = array(
				'uid'   => $importer['channel_id'],
				'ttype'  => TERM_MENTION,
				'otype' => TERM_OBJ_POST,
				'term'  => $mtch[2],
				'url'   => $mtch[1]
			);
		}
	}

	$cnt = preg_match_all('/@\[zrl=(.*?)\](.*?)\[\/zrl\]/ism',$body,$matches,PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			// don't include plustags in the term
			$term = ((substr($mtch[2],-1,1) === '+') ? substr($mtch[2],0,-1) : $mtch[2]);
			$datarray['term'][] = array(
				'uid'   => $importer['channel_id'],
				'ttype'  => TERM_MENTION,
				'otype' => TERM_OBJ_POST,
				'term'  => $term,
				'url'   => $mtch[1]
			);
		}
	}

	$datarray['aid'] = $importer['channel_account_id'];
	$datarray['uid'] = $importer['channel_id'];
	$datarray['verb'] = ACTIVITY_POST;
	$datarray['mid'] = $guid;
	$datarray['parent_mid'] = $parent_item['mid'];

	// set the route to that of the parent so downstream hubs won't reject it.
	$datarray['route'] = $parent_item['route'];

	// No timestamps for comments? OK, we'll the use current time.
	$datarray['changed'] = $datarray['created'] = $datarray['edited'] = datetime_convert();
	$datarray['item_private'] = $parent_item['item_private'];

	$datarray['owner_xchan'] = $parent_item['owner_xchan'];
	$datarray['author_xchan'] = $person['xchan_hash'];

	$datarray['body'] = $body;

	if(strstr($person['xchan_network'],'friendica'))
		$app = 'Friendica';
	elseif($person['xchan_network'] == 'diaspora')
		$app = 'Diaspora';
	else
		$app = '';

	$datarray['app'] = $app;
	
	if(! $parent_author_signature) {
		$key = get_config('system','pubkey');
		$x = array('signer' => $diaspora_handle, 'body' => $text, 
			'signed_text' => $signed_data, 'signature' => base64_encode($author_signature));
		$datarray['diaspora_meta'] = json_encode($x);
	}


	// So basically if something arrives at the sys channel it's by definition public and we allow it.
	// If $pubcomment and the parent was public, we allow it.
	// In all other cases, honour the permissions for this Diaspora connection

	$tgroup = tgroup_check($importer['channel_id'],$datarray);


	// If it's a comment to one of our own posts, check if the commenter has permission to comment.
	// We should probably check send_stream permission if the stream owner isn't us,
	// but we did import the parent post so at least at that time we did allow it and
	// the check would nearly always be superfluous and redundant.

	if($parent_item['owner_xchan'] == $importer['channel_hash']) 
		$allowed = perm_is_allowed($importer['channel_id'],$xchan['xchan_hash'],'post_comments');
	else
		$allowed = true;

	if((! $importer['system']) && (! $pubcomment) && (! $allowed) && (! $tgroup)) {
		logger('diaspora_comment: Ignoring this author.');
		return 202;
	}

	set_iconfig($datarray,'diaspora','fields',$unxml,true);

	$result = item_store($datarray);

	if($result && $result['success'])
		$message_id = $result['item_id'];


	$upstream_leg = false;
	if(intval($parent_item['item_origin']) && (! $parent_author_signature)) {
		// if the message isn't already being relayed, notify others
		// the existence of parent_author_signature means the parent_author or owner
		// is already relaying.
		$upstream_leg = true;
		Zotlabs\Daemon\Master::Summon(array('Notifier','comment-import',$message_id));
	}

	if($result['success']) {
		$r = q("select * from item where id = %d limit 1",
			intval($result['item_id'])
		);
		if($r) {
			send_status_notifications($result['item_id'],$r[0]);
			sync_an_item($importer['channel_id'],$result['item_id']);
		}
	}

	return;
}




function diaspora_conversation($importer,$xml,$msg) {

	$a = get_app();

	$guid = notags(unxmlify($xml['guid']));
	$subject = notags(unxmlify($xml['subject']));
	$diaspora_handle = notags(diaspora_get_author($xml));
	$participant_handles = notags(diaspora_get_participants($xml));
	$created_at = datetime_convert('UTC','UTC',notags(unxmlify($xml['created_at'])));

	$parent_uri = $guid;
 
	$messages = $xml['message'];

	if(! count($messages)) {
		logger('diaspora_conversation: empty conversation');
		return;
	}

	$contact = diaspora_get_contact_by_handle($importer['channel_id'],$msg['author']);
	if(! $contact) {
		logger('diaspora_conversation: cannot find contact: ' . $msg['author']);
		return;
	}


	if(! perm_is_allowed($importer['channel_id'],$contact['xchan_hash'],'post_mail')) {
		logger('diaspora_conversation: Ignoring this author.');
		return 202;
	}

	$conversation = null;

	$c = q("select * from conv where uid = %d and guid = '%s' limit 1",
		intval($importer['channel_id']),
		dbesc($guid)
	);
	if(count($c))
		$conversation = $c[0];
	else {
		if($subject)
			$nsubject = str_rot47(base64url_encode($subject));

		$r = q("insert into conv (uid,guid,creator,created,updated,subject,recips) values(%d, '%s', '%s', '%s', '%s', '%s', '%s') ",
			intval($importer['channel_id']),
			dbesc($guid),
			dbesc($diaspora_handle),
			dbesc(datetime_convert('UTC','UTC',$created_at)),
			dbesc(datetime_convert()),
			dbesc($nsubject),
			dbesc($participant_handles)
		);
		if($r)
			$c = q("select * from conv where uid = %d and guid = '%s' limit 1",
			intval($importer['channel_id']),
			dbesc($guid)
		);
		if(count($c))
			$conversation = $c[0];
	}
	if(! $conversation) {
		logger('diaspora_conversation: unable to create conversation.');
		return;
	}

	$conversation['subject'] = base64url_decode(str_rot47($conversation['subject']));

	foreach($messages as $mesg) {

		$reply = 0;

		$msg_guid = notags(unxmlify($mesg['guid']));
		$msg_parent_guid = notags(unxmlify($mesg['parent_guid']));
		$msg_parent_author_signature = notags(unxmlify($mesg['parent_author_signature']));
		$msg_author_signature = notags(unxmlify($mesg['author_signature']));
		$msg_text = unxmlify($mesg['text']);
		$msg_created_at = datetime_convert('UTC','UTC',notags(unxmlify($mesg['created_at'])));
		$msg_diaspora_handle = notags(diaspora_get_author($mesg));
		$msg_conversation_guid = notags(unxmlify($mesg['conversation_guid']));
		if($msg_conversation_guid != $guid) {
			logger('diaspora_conversation: message conversation guid does not belong to the current conversation. ' . $xml);
			continue;
		}

		$body = markdown_to_bb($msg_text);


		$maxlen = get_max_import_size();

		if($maxlen && mb_strlen($body) > $maxlen) {
			$body = mb_substr($body,0,$maxlen,'UTF-8');
			logger('message length exceeds max_import_size: truncated');
		}


		$author_signed_data = $msg_guid . ';' . $msg_parent_guid . ';' . $msg_text . ';' . unxmlify($mesg['created_at']) . ';' . $msg_diaspora_handle . ';' . $msg_conversation_guid;

		$author_signature = base64_decode($msg_author_signature);

		if(strcasecmp($msg_diaspora_handle,$msg['author']) == 0) {
			$person = $contact;
			$key = $msg['key'];
		}
		else {
			$person = find_diaspora_person_by_handle($msg_diaspora_handle);	

			if(is_array($person) && x($person,'xchan_pubkey'))
				$key = $person['xchan_pubkey'];
			else {
				logger('diaspora_conversation: unable to find author details');
				continue;
			}
		}

		if(! rsa_verify($author_signed_data,$author_signature,$key,'sha256')) {
			logger('diaspora_conversation: verification failed.');
			continue;
		}

		if($msg_parent_author_signature) {
			$owner_signed_data = $msg_guid . ';' . $msg_parent_guid . ';' . $msg_text . ';' . unxmlify($mesg['created_at']) . ';' . $msg_diaspora_handle . ';' . $msg_conversation_guid;

			$parent_author_signature = base64_decode($msg_parent_author_signature);

			$key = $msg['key'];

			if(! rsa_verify($owner_signed_data,$parent_author_signature,$key,'sha256')) {
				logger('diaspora_conversation: owner verification failed.');
				continue;
			}
		}

		$stored_parent_mid = (($msg_parent_guid == $msg_conversation_guid) ? $msg_guid : $msg_parent_guid);

		$r = q("select id from mail where mid = '%s' limit 1",
			dbesc($message_id)
		);
		if(count($r)) {
			logger('diaspora_conversation: duplicate message already delivered.', LOGGER_DEBUG);
			continue;
		}

		if($subject)
			$subject = str_rot47(base64url_encode($subject));
		if($body)
			$body  = str_rot47(base64url_encode($body));

		$sig = ''; // placeholder

		q("insert into mail ( account_id, channel_id, convid, conv_guid, from_xchan,to_xchan,title,body, sig, mail_obscured,mid,parent_mid,created) values ( %d, %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', %d, '%s', '%s', '%s')",
			intval($importer['channel_account_id']),
			intval($importer['channel_id']),
			intval($conversation['id']),
			dbesc($conversation['guid']),
			dbesc($person['xchan_hash']),
			dbesc($importer['channel_hash']),
			dbesc($subject),
			dbesc($body),
			dbesc($sig),
			intval(1),
			dbesc($msg_guid),
			dbesc($stored_parent_mid),
			dbesc($msg_created_at)
		);

		q("update conv set updated = '%s' where id = %d",
			dbesc(datetime_convert()),
			intval($conversation['id'])
		);

		$z = q("select * from mail where mid = '%s' and channel_id = %d limit 1",
			dbesc($msg_guid),
			intval($importer['channel_id'])
		);

		\Zotlabs\Lib\Enotify::submit(array(
			'from_xchan' => $person['xchan_hash'],
			'to_xchan' => $importer['channel_hash'],
			'type' => NOTIFY_MAIL,
			'item' => $z[0],
			'verb' => ACTIVITY_POST,
			'otype' => 'mail'
		));
	}

	return;
}

function diaspora_message($importer,$xml,$msg) {

	$a = get_app();

	$msg_guid = notags(unxmlify($xml['guid']));
	$msg_parent_guid = notags(unxmlify($xml['parent_guid']));
	$msg_parent_author_signature = notags(unxmlify($xml['parent_author_signature']));
	$msg_author_signature = notags(unxmlify($xml['author_signature']));
	$msg_text = unxmlify($xml['text']);
	$msg_created_at = datetime_convert('UTC','UTC',notags(unxmlify($xml['created_at'])));
	$msg_diaspora_handle = notags(diaspora_get_author($xml));
	$msg_conversation_guid = notags(unxmlify($xml['conversation_guid']));

	$parent_uri = $msg_parent_guid;
 
	$contact = diaspora_get_contact_by_handle($importer['channel_id'],$msg_diaspora_handle);
	if(! $contact) {
		logger('diaspora_message: cannot find contact: ' . $msg_diaspora_handle);
		return;
	}

	if(! perm_is_allowed($importer['channel_id'],$contact['xchan_hash'],'post_mail')) {
		logger('Ignoring this author.');
		return 202;
	}

	$conversation = null;

	$c = q("select * from conv where uid = %d and guid = '%s' limit 1",
		intval($importer['channel_id']),
		dbesc($msg_conversation_guid)
	);
	if($c)
		$conversation = $c[0];
	else {
		logger('diaspora_message: conversation not available.');
		return;
	}

	$reply = 0;

	$subject = $conversation['subject']; //this is already encoded
	$body = markdown_to_bb($msg_text);


	$maxlen = get_max_import_size();

	if($maxlen && mb_strlen($body) > $maxlen) {
		$body = mb_substr($body,0,$maxlen,'UTF-8');
		logger('message length exceeds max_import_size: truncated');
	}


	$parent_ptr = $msg_parent_guid;
	if($parent_ptr === $conversation['guid']) {
		// this should always be the case
		$x = q("select mid from mail where conv_guid = '%s' and channel_id = %d order by id asc limit 1",
			dbesc($conversation['guid']),
			intval($importer['channel_id'])
		);
		if($x)
			$parent_ptr = $x[0]['mid'];
	}


	$author_signed_data = $msg_guid . ';' . $msg_parent_guid . ';' . $msg_text . ';' . unxmlify($xml['created_at']) . ';' . $msg_diaspora_handle . ';' . $msg_conversation_guid;


	$author_signature = base64_decode($msg_author_signature);

	$person = find_diaspora_person_by_handle($msg_diaspora_handle);	
	if(is_array($person) && x($person,'xchan_pubkey'))
		$key = $person['xchan_pubkey'];
	else {
		logger('diaspora_message: unable to find author details');
		return;
	}

	if(! rsa_verify($author_signed_data,$author_signature,$key,'sha256')) {
		logger('diaspora_message: verification failed.');
		return;
	}

	$r = q("select id from mail where mid = '%s' and channel_id = %d limit 1",
		dbesc($msg_guid),
		intval($importer['channel_id'])
	);
	if($r) {
		logger('diaspora_message: duplicate message already delivered.', LOGGER_DEBUG);
		return;
	}


	$key = get_config('system','pubkey');
	// $subject is a copy of the already obscured subject from the conversation structure
	if($body)
		$body  = str_rot47(base64url_encode($body));

	$sig = '';

	q("insert into mail ( account_id, channel_id, convid, conv_guid, from_xchan,to_xchan,title,body, sig, mail_obscured,mid,parent_mid,created, mail_isreply) values ( %d, %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', %d, '%s', '%s', '%s', %d)",
		intval($importer['channel_account_id']),
		intval($importer['channel_id']),
		intval($conversation['id']),
		dbesc($conversation['guid']),
		dbesc($person['xchan_hash']),
		dbesc($importer['xchan_hash']),
		dbesc($subject),
		dbesc($body),
		dbesc($sig),
		intval(1),
		dbesc($msg_guid),
		dbesc($parent_ptr),
		dbesc($msg_created_at),
		intval(1)
	);

	q("update conv set updated = '%s' where id = %d",
		dbesc(datetime_convert()),
		intval($conversation['id'])
	);

	$z = q("select * from mail where mid = '%s' and channel_id = %d limit 1",
		dbesc($msg_guid),
		intval($importer['channel_id'])
	);

	\Zotlabs\Lib\Enotify::submit(array(
		'from_xchan' => $person['xchan_hash'],
		'to_xchan' => $importer['channel_hash'],
		'type' => NOTIFY_MAIL,
		'item' => $z[0],
		'verb' => ACTIVITY_POST,
		'otype' => 'mail'
	));

	return;
}


function diaspora_photo($importer,$xml,$msg) {

	$a = get_app();

	logger('diaspora_photo: init',LOGGER_DEBUG);

	$remote_photo_path = notags(unxmlify($xml['remote_photo_path']));

	$remote_photo_name = notags(unxmlify($xml['remote_photo_name']));

	$status_message_guid = notags(unxmlify($xml['status_message_guid']));

	$guid = notags(unxmlify($xml['guid']));

	$diaspora_handle = notags(diaspora_get_author($xml));

	$public = notags(unxmlify($xml['public']));

	$created_at = notags(unxmlify($xml['created_at']));

	logger('diaspora_photo: status_message_guid: ' . $status_message_guid, LOGGER_DEBUG);

	$contact = diaspora_get_contact_by_handle($importer['channel_id'],$msg['author']);
	if(! $contact) {
		logger('diaspora_photo: contact record not found: ' . $msg['author'] . ' handle: ' . $diaspora_handle);
		return;
	}

	if((! $importer['system']) && (! perm_is_allowed($importer['channel_id'],$contact['xchan_hash'],'send_stream'))) {
		logger('diaspora_photo: Ignoring this author.');
		return 202;
	}

	$r = q("SELECT * FROM item WHERE uid = %d AND mid = '%s' LIMIT 1",
		intval($importer['channel_id']),
		dbesc($status_message_guid)
	);
	if(! $r) {
		logger('diaspora_photo: attempt = ' . $attempt . '; status message not found: ' . $status_message_guid . ' for photo: ' . $guid);
		return;
	}

//	$parent_item = $r[0];

//	$link_text = '[img]' . $remote_photo_path . $remote_photo_name . '[/img]' . "\n";

//	$link_text = scale_external_images($link_text, true,
//									   array($remote_photo_name, 'scaled_full_' . $remote_photo_name));

//	if(strpos($parent_item['body'],$link_text) === false) {
//		$r = q("update item set body = '%s', visible = 1 where id = %d and uid = %d",
//			dbesc($link_text . $parent_item['body']),
//			intval($parent_item['id']),
//			intval($parent_item['uid'])
//		);
//	}

	return;
}




function diaspora_like($importer,$xml,$msg) {

	$a = get_app();
	$guid = notags(unxmlify($xml['guid']));
	$parent_guid = notags(unxmlify($xml['parent_guid']));
	$diaspora_handle = notags(diaspora_get_author($xml));
	$target_type = notags(diaspora_get_ptype($xml));
	$positive = notags(unxmlify($xml['positive']));
	$author_signature = notags(unxmlify($xml['author_signature']));

	$parent_author_signature = (($xml['parent_author_signature']) ? notags(unxmlify($xml['parent_author_signature'])) : '');

	// likes on comments not supported here and likes on photos not supported by Diaspora

//	if($target_type !== 'Post')
//		return;

	$contact = diaspora_get_contact_by_handle($importer['channel_id'],$msg['author']);
	if(! $contact) {
		logger('diaspora_like: cannot find contact: ' . $msg['author'] . ' for channel ' . $importer['channel_name']);
		return;
	}


	if((! $importer['system']) && (! perm_is_allowed($importer['channel_id'],$contact['xchan_hash'],'post_comments'))) {
		logger('diaspora_like: Ignoring this author.');
		return 202;
	}

	$r = q("SELECT * FROM item WHERE uid = %d AND mid = '%s' LIMIT 1",
		intval($importer['channel_id']),
		dbesc($parent_guid)
	);
	if(! count($r)) {
		logger('diaspora_like: parent item not found: ' . $guid);
		return;
	}

	xchan_query($r);
	$parent_item = $r[0];

	if(intval($parent_item['item_nocomment']) || $parent_item['comment_policy'] === 'none' 
		|| ($parent_item['comments_closed'] > NULL_DATE && $parent_item['comments_closed'] < datetime_convert())) {
		logger('diaspora_like: comments disabled for post ' . $parent_item['mid']);
		return;
	}

	$r = q("SELECT * FROM item WHERE uid = %d AND mid = '%s' LIMIT 1",
		intval($importer['channel_id']),
		dbesc($guid)
	);
	if(count($r)) {
		if($positive === 'true') {
			logger('diaspora_like: duplicate like: ' . $guid);
			return;
		}

		// Note: I don't think "Like" objects with positive = "false" are ever actually used
		// It looks like "RelayableRetractions" are used for "unlike" instead

		if($positive === 'false') {
			logger('diaspora_like: received a like with positive set to "false"...ignoring');
			// perhaps call drop_item()
			// FIXME--actually don't unless it turns out that Diaspora does indeed send out "false" likes
			//  send notification via proc_run()
			return;
		}
	}

	$i = q("select * from xchan where xchan_hash = '%s' limit 1",
		dbesc($parent_item['author_xchan'])
	);
	if($i)
		$item_author = $i[0];

	// Note: I don't think "Like" objects with positive = "false" are ever actually used
	// It looks like "RelayableRetractions" are used for "unlike" instead

	if($positive === 'false') {
		logger('diaspora_like: received a like with positive set to "false"');
		logger('diaspora_like: unlike received with no corresponding like...ignoring');
		return;	
	}


	/* How Diaspora performs "like" signature checking:

	   - If an item has been sent by the like author to the top-level post owner to relay on
	     to the rest of the contacts on the top-level post, the top-level post owner should check
	     the author_signature, then create a parent_author_signature before relaying the like on
	   - If an item has been relayed on by the top-level post owner, the contacts who receive it
	     check only the parent_author_signature. Basically, they trust that the top-level post
	     owner has already verified the authenticity of anything he/she sends out
	   - In either case, the signature that get checked is the signature created by the person
	     who sent the salmon
	*/

	// 2014-09-10 let's try this: signatures are failing. I'll try and make a signable string from
	// the parameters in the order they were presented in the post. This is how D* creates the signable string.


	$signed_data = $positive . ';' . $guid . ';' . $target_type . ';' . $parent_guid . ';' . $diaspora_handle;

	$key = $msg['key'];

	if($parent_author_signature) {
		// If a parent_author_signature exists, then we've received the like
		// relayed from the top-level post owner. There's no need to check the
		// author_signature if the parent_author_signature is valid

		$x = diaspora_verify_fields($xml,$parent_author_signature,$key);
		if(! $x) {
			logger('diaspora_like: top-level owner verification failed.');
			return;
		}

		//$parent_author_signature = base64_decode($parent_author_signature);

		//if(! rsa_verify($signed_data,$parent_author_signature,$key,'sha256')) {
		//	if (intval(get_config('system','ignore_diaspora_like_signature')))
		//		logger('diaspora_like: top-level owner verification failed. Proceeding anyway.');
		//	else {
		//		logger('diaspora_like: top-level owner verification failed.');
		//		return;
		//	}
		//}
	}
	else {
		// If there's no parent_author_signature, then we've received the like
		// from the like creator. In that case, the person is "like"ing
		// our post, so he/she must be a contact of ours and his/her public key
		// should be in $msg['key']

		$x = diaspora_verify_fields($xml,$author_signature,$key);
		if(! $x) {
			logger('diaspora_like: author verification failed.');
			return;
		}


		//$author_signature = base64_decode($author_signature);

		//if(! rsa_verify($signed_data,$author_signature,$key,'sha256')) {
		//	if (intval(get_config('system','ignore_diaspora_like_signature')))
		//		logger('diaspora_like: like creator verification failed. Proceeding anyway');
		//	else {
		//		logger('diaspora_like: like creator verification failed.');
		//		return;
		//	}
		//}

		$xml['parent_author_signature'] = diaspora_sign_fields($xml,$importer['channel_prvkey']);

	}
	
	logger('diaspora_like: signature check complete.',LOGGER_DEBUG);

	// Phew! Everything checks out. Now create an item.

	// Find the original comment author information.
	// We need this to make sure we display the comment author
	// information (name and avatar) correctly.
	if(strcasecmp($diaspora_handle,$msg['author']) == 0)
		$person = $contact;
	else {
		$person = find_diaspora_person_by_handle($diaspora_handle);

		if(! is_array($person)) {
			logger('diaspora_like: unable to find author details');
			return;
		}
	}

	$uri = $diaspora_handle . ':' . $guid;

	$activity = ACTIVITY_LIKE;

	$post_type = (($parent_item['resource_type'] === 'photo') ? t('photo') : t('status'));

	$links = array(array('rel' => 'alternate','type' => 'text/html', 'href' => $parent_item['plink']));
	$objtype = (($parent_item['resource_type'] === 'photo') ? ACTIVITY_OBJ_PHOTO : ACTIVITY_OBJ_NOTE );

	$body = $parent_item['body'];


	$object = json_encode(array(
		'type'    => $post_type,
		'id'	  => $parent_item['mid'],
		'parent'  => (($parent_item['thr_parent']) ? $parent_item['thr_parent'] : $parent_item['parent_mid']),
		'link'	  => $links,
		'title'   => $parent_item['title'],
		'content' => $parent_item['body'],
		'created' => $parent_item['created'],
		'edited'  => $parent_item['edited'],
		'author'  => array(
			'name'     => $item_author['xchan_name'],
			'address'  => $item_author['xchan_addr'],
			'guid'     => $item_author['xchan_guid'],
			'guid_sig' => $item_author['xchan_guid_sig'],
			'link'     => array(
				array('rel' => 'alternate', 'type' => 'text/html', 'href' => $item_author['xchan_url']),
				array('rel' => 'photo', 'type' => $item_author['xchan_photo_mimetype'], 'href' => $item_author['xchan_photo_m'])),
			),
		));


	$bodyverb = t('%1$s likes %2$s\'s %3$s');

	$arr = array();

	$arr['uid'] = $importer['channel_id'];
	$arr['aid'] = $importer['channel_account_id'];
	$arr['mid'] = $guid;
	$arr['parent_mid'] = $parent_item['mid'];
	$arr['owner_xchan'] = $parent_item['owner_xchan'];
	$arr['author_xchan'] = $person['xchan_hash'];

	$ulink = '[url=' . $item_author['xchan_url'] . ']' . $item_author['xchan_name'] . '[/url]';
	$alink = '[url=' . $parent_item['author']['xchan_url'] . ']' . $parent_item['author']['xchan_name'] . '[/url]';
	$plink = '[url='. z_root() .'/display/'.$guid.']'.$post_type.'[/url]';
	$arr['body'] =  sprintf( $bodyverb, $ulink, $alink, $plink );

	$arr['app']  = 'Diaspora';

	// set the route to that of the parent so downstream hubs won't reject it.
	$arr['route'] = $parent_item['route'];

	$arr['item_private'] = $parent_item['item_private'];
	$arr['verb'] = $activity;
	$arr['obj_type'] = $objtype;
	$arr['obj'] = $object;

	if(! $parent_author_signature) {
		$key = get_config('system','pubkey');
		$x = array('signer' => $diaspora_handle, 'body' => $text, 
			'signed_text' => $signed_data, 'signature' => base64_encode($author_signature));
		$arr['diaspora_meta'] = json_encode($x);
	}

	set_iconfig($arr,'diaspora','fields',array_map('unxmlify',$xml),true);

	$result = item_store($arr);

	if($result['success']) {
		// if the message isn't already being relayed, notify others
		// the existence of parent_author_signature means the parent_author or owner
		// is already relaying. The parent_item['origin'] indicates the message was created on our system

		if(intval($parent_item['item_origin']) && (! $parent_author_signature))
			Zotlabs\Daemon\Master::Summon(array('Notifier','comment-import',$result['item_id']));
		sync_an_item($importer['channel_id'],$result['item_id']);
	}

	return;
}

function diaspora_retraction($importer,$xml,$msg = null) {


	$guid = notags(diaspora_get_target_guid($xml));
	$diaspora_handle = notags(diaspora_get_author($xml));
	$type = notags(diaspora_get_type($xml));

	$contact = diaspora_get_contact_by_handle($importer['channel_id'],$diaspora_handle);
	if(! $contact)
		return;

	if($type === 'Person' || $type === 'Contact') {
		contact_remove($importer['channel_id'],$contact['abook_id']);
	}
	elseif(($type === 'Post') || ($type === 'StatusMessage') || ($type === 'Comment') || ($type === 'Like')) {
		$r = q("select * from item where mid = '%s' and uid = %d limit 1",
			dbesc('guid'),
			intval($importer['channel_id'])
		);
		if($r) {
			if(link_compare($r[0]['author_xchan'],$contact['xchan_hash'])
				|| link_compare($r[0]['owner_xchan'],$contact['xchan_hash'])) {
				drop_item($r[0]['id'],false);
			}
			// @FIXME - ensure that relay is performed if this was an upstream
			// Could probably check if we're the owner and it is a like or comment
			// This may or may not be handled by drop_item
		}
	}

	return 202;
	// NOTREACHED
}

function diaspora_signed_retraction($importer,$xml,$msg) {
	
	// obsolete - see https://github.com/SuperTux88/diaspora_federation/issues/27


	$guid = notags(diaspora_get_target_guid($xml));
	$diaspora_handle = notags(diaspora_get_author($xml));
	$type = notags(diaspora_get_type($xml));
	$sig = notags(unxmlify($xml['target_author_signature']));

	$parent_author_signature = (($xml['parent_author_signature']) ? notags(unxmlify($xml['parent_author_signature'])) : '');

	$contact = diaspora_get_contact_by_handle($importer['channel_id'],$diaspora_handle);
	if(! $contact) {
		logger('diaspora_signed_retraction: no contact ' . $diaspora_handle . ' for ' . $importer['channel_id']);
		return;
	}


	$signed_data = $guid . ';' . $type ;
	$key = $msg['key'];

	/* How Diaspora performs relayable_retraction signature checking:

	   - If an item has been sent by the item author to the top-level post owner to relay on
	     to the rest of the contacts on the top-level post, the top-level post owner checks
	     the author_signature, then creates a parent_author_signature before relaying the item on
	   - If an item has been relayed on by the top-level post owner, the contacts who receive it
	     check only the parent_author_signature. Basically, they trust that the top-level post
	     owner has already verified the authenticity of anything he/she sends out
	   - In either case, the signature that get checked is the signature created by the person
	     who sent the salmon
	*/

	if($parent_author_signature) {

		$parent_author_signature = base64_decode($parent_author_signature);

		if(! rsa_verify($signed_data,$parent_author_signature,$key,'sha256')) {
			logger('diaspora_signed_retraction: top-level post owner verification failed');
			return;
		}

	}
	else {

		$sig_decode = base64_decode($sig);

		if(! rsa_verify($signed_data,$sig_decode,$key,'sha256')) {
			logger('diaspora_signed_retraction: retraction owner verification failed.' . print_r($msg,true));
			return;
		}
	}

	if($type === 'StatusMessage' || $type === 'Comment' || $type === 'Like') {
		$r = q("select * from item where mid = '%s' and uid = %d limit 1",
			dbesc($guid),
			intval($importer['channel_id'])
		);
		if($r) {
			if($r[0]['author_xchan'] == $contact['xchan_hash']) {

				drop_item($r[0]['id'],false, DROPITEM_PHASE1);

				// Now check if the retraction needs to be relayed by us
				//
				// The first item in the item table with the parent id is the parent. However, MySQL doesn't always
				// return the items ordered by item.id, in which case the wrong item is chosen as the parent.
				// The only item with parent and id as the parent id is the parent item.
				$p = q("select item_flags from item where parent = %d and id = %d limit 1",
					$r[0]['parent'],
					$r[0]['parent']
				);
				if($p) {
					if(intval($p[0]['item_origin']) && (! $parent_author_signature)) {

						// the existence of parent_author_signature would have meant the parent_author or owner
						// is already relaying.

						logger('diaspora_signed_retraction: relaying relayable_retraction');
						Zotlabs\Daemon\Master::Summon(array('Notifier','drop',$r[0]['id']));
					}
				}
			}
		}
	}
	else
		logger('diaspora_signed_retraction: unknown type: ' . $type);

	return 202;
	// NOTREACHED
}

function diaspora_profile($importer,$xml,$msg) {

	$a = get_app();
	$diaspora_handle = notags(diaspora_get_author($xml));

	logger('xml: ' . print_r($xml,true), LOGGER_DEBUG);

	if($diaspora_handle != $msg['author']) {
		logger('diaspora_post: Potential forgery. Message handle is not the same as envelope sender.');
		return 202;
	}

	$contact = diaspora_get_contact_by_handle($importer['channel_id'],$diaspora_handle);
	if(! $contact)
		return;

	if($contact['blocked']) {
		logger('diaspora_profile: Ignoring this author.');
		return 202;
	}

	$name = unxmlify($xml['first_name'] . (($xml['last_name']) ? ' ' . $xml['last_name'] : ''));
	$image_url = unxmlify($xml['image_url']);
	$birthday = unxmlify($xml['birthday']);


	$handle_parts = explode("@", $diaspora_handle);
	if($name === '') {
		$name = $handle_parts[0];
	}
		 
	if( preg_match("|^https?://|", $image_url) === 0) {
		$image_url = "http://" . $handle_parts[1] . $image_url;
	}

	require_once('include/photo/photo_driver.php');

	$images = import_xchan_photo($image_url,$contact['xchan_hash']);
	
	// Generic birthday. We don't know the timezone. The year is irrelevant. 

	$birthday = str_replace('1000','1901',$birthday);

	$birthday = datetime_convert('UTC','UTC',$birthday,'Y-m-d');

	// this is to prevent multiple birthday notifications in a single year
	// if we already have a stored birthday and the 'm-d' part hasn't changed, preserve the entry, which will preserve the notify year

	if(substr($birthday,5) === substr($contact['bd'],5))
		$birthday = $contact['bd'];

	$r = q("update xchan set xchan_name = '%s', xchan_name_date = '%s', xchan_photo_l = '%s', xchan_photo_m = '%s', xchan_photo_s = '%s', xchan_photo_mimetype = '%s' where xchan_hash = '%s' ",
		dbesc($name),
		dbesc(datetime_convert()),
		dbesc($images[0]),
		dbesc($images[1]),
		dbesc($images[2]),
		dbesc($images[3]),
		dbesc(datetime_convert()),
		intval($contact['xchan_hash'])
	); 

	return;

}

function diaspora_participation($importer,$xml,$msg) {

	$diaspora_handle = notags(diaspora_get_author($xml));
	$type = notags(diaspora_get_ptype($xml));

	// not currently handled

	logger('participation: ' . print_r($xml,true), LOGGER_DATA);


}

function diaspora_poll_participation($importer,$xml,$msg) {

	$diaspora_handle = notags(diaspora_get_author($xml));

	// not currently handled

	logger('poll_participation: ' . print_r($xml,true), LOGGER_DATA);


}

function diaspora_account_deletion($importer,$xml,$msg) {

	$diaspora_handle = notags(diaspora_get_author($xml));

	// not currently handled

	logger('account_deletion: ' . print_r($xml,true), LOGGER_DATA);


}


function diaspora_get_author($xml) {
	if(array_key_exists('diaspora_handle',$xml))
		return unxmlify($xml['diaspora_handle']);
	elseif(array_key_exists('sender_handle',$xml))
		return unxmlify($xml['sender_handle']);
	elseif(array_key_exists('author',$xml))
		return unxmlify($xml['author']);
	else
		return '';
}

function diaspora_get_root_author($xml) {
	if(array_key_exists('root_diaspora_id',$xml))
		return unxmlify($xml['root_diaspora_id']);
	elseif(array_key_exists('root_author',$xml))
		return unxmlify($xml['root_author']);
	else
		return '';
}


function diaspora_get_participants($xml) {
	if(array_key_exists('participant_handles',$xml))
		return unxmlify($xml['participant_handles']);
	elseif(array_key_exists('participants',$xml))
		return unxmlify($xml['participants']);
	else
		return '';
}

function diaspora_get_ptype($xml) {
	if(array_key_exists('target_type',$xml))
		return unxmlify($xml['target_type']);
	elseif(array_key_exists('parent_type',$xml))
		return unxmlify($xml['parent_type']);
	else
		return '';
}

function diaspora_get_type($xml) {
	if(array_key_exists('target_type',$xml))
		return unxmlify($xml['target_type']);
	elseif(array_key_exists('type',$xml))
		return unxmlify($xml['type']);
	else
		return '';
}


function diaspora_get_target_guid($xml) {
	if(array_key_exists('post_guid',$xml))
		return unxmlify($xml['post_guid']);
	elseif(array_key_exists('target_guid',$xml))
		return unxmlify($xml['target_guid']);
	elseif(array_key_exists('guid',$xml))
		return unxmlify($xml['guid']);
	else
		return '';
}


function diaspora_get_recipient($xml) {
	if(array_key_exists('recipient_handle',$xml))
		return unxmlify($xml['recipient_handle']);
	elseif(array_key_exists('recipient',$xml))
		return unxmlify($xml['recipient']);
	else
		return '';
}

function diaspora_get_body($xml) {
	if(array_key_exists('raw_message',$xml))
		return unxmlify($xml['raw_message']);
	elseif(array_key_exists('text',$xml))
		return unxmlify($xml['text']);
	else
		return '';
}

