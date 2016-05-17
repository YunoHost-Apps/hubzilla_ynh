<?php


/**
 * Name: Diaspora Protocol
 * Description: Diaspora Protocol (Experimental, Unsupported)
 * Version: 1.0
 * Author: Mike Macgirvin
 * Maintainer: none
 */




require_once('include/crypto.php');
require_once('include/items.php');
require_once('include/bb2diaspora.php');
require_once('include/contact_selectors.php');
require_once('include/queue_fn.php');

require_once('addon/diaspora/inbound.php');
require_once('addon/diaspora/outbound.php');
require_once('addon/diaspora/util.php');


function diaspora_load() {
	register_hook('notifier_hub', 'addon/diaspora/diaspora.php', 'diaspora_process_outbound');
	register_hook('notifier_process', 'addon/diaspora/diaspora.php', 'diaspora_notifier_process');
	register_hook('permissions_create', 'addon/diaspora/diaspora.php', 'diaspora_permissions_create');
	register_hook('permissions_update', 'addon/diaspora/diaspora.php', 'diaspora_permissions_update');
	register_hook('module_loaded', 'addon/diaspora/diaspora.php', 'diaspora_load_module');
	register_hook('follow_allow', 'addon/diaspora/diaspora.php', 'diaspora_follow_allow');
	register_hook('feature_settings_post', 'addon/diaspora/diaspora.php', 'diaspora_feature_settings_post');
	register_hook('feature_settings', 'addon/diaspora/diaspora.php', 'diaspora_feature_settings');
	register_hook('post_local','addon/diaspora/diaspora.php','diaspora_post_local');
	register_hook('well_known','addon/diaspora/diaspora.php','diaspora_well_known');

	if(! get_config('diaspora','relay_handle')) {
		$x = import_author_diaspora(array('address' => 'relay@relay.iliketoast.net'));
		if($x) {
			set_config('diaspora','relay_handle',$x);
			// Now register
			$url = "http://the-federation.info/register/" . App::get_hostname();
			$ret = z_fetch_url($url);
		}
	}

}

function diaspora_unload() {
	unregister_hook('notifier_hub', 'addon/diaspora/diaspora.php', 'diaspora_process_outbound');
	unregister_hook('notifier_process', 'addon/diaspora/diaspora.php', 'diaspora_notifier_process');
	unregister_hook('permissions_create', 'addon/diaspora/diaspora.php', 'diaspora_permissions_create');
	unregister_hook('permissions_update', 'addon/diaspora/diaspora.php', 'diaspora_permissions_update');
	unregister_hook('module_loaded', 'addon/diaspora/diaspora.php', 'diaspora_load_module');
	unregister_hook('follow_allow', 'addon/diaspora/diaspora.php', 'diaspora_follow_allow');
	unregister_hook('feature_settings_post', 'addon/diaspora/diaspora.php', 'diaspora_feature_settings_post');
	unregister_hook('feature_settings', 'addon/diaspora/diaspora.php', 'diaspora_feature_settings');
	unregister_hook('post_local','addon/diaspora/diaspora.php','diaspora_post_local');
	unregister_hook('well_known','addon/diaspora/diaspora.php','diaspora_well_known');
}


function diaspora_load_module(&$a, &$b) {
	if($b['module'] === 'receive') {
		require_once('addon/diaspora/receive.php');
		$b['installed'] = true;
	}
	if($b['module'] === 'p') {
		require_once('addon/diaspora/p.php');
		$b['installed'] = true;
	}
}


function diaspora_well_known(&$a,&$b) {
	if(argc() > 1 && argv(1) === 'x-social-relay') {
		$disabled = (get_config('system','disable_discover_tab') || get_config('system','disable_diaspora_discover_tab'));
		$scope = 'all';
		$tags = get_config('diaspora','relay_tags');
		if($tags) {
			$disabled = false;
			$scope = 'tags';
		}

		$arr = array(
			'subscribe' => (($disabled) ? false : true),
			'scope' => $scope
		);
		if($tags)
			$arr['tags'] = $tags;

		header('Content-type: application/json');
		echo json_encode($arr);
		killme();			

	}
}







function diaspora_permissions_create(&$a,&$b) {
	if($b['recipient']['xchan_network'] === 'diaspora' || $b['recipient']['xchan_network'] === 'friendica-over-diaspora') {

		$b['deliveries'] = diaspora_share($b['sender'],$b['recipient']);
		if($b['deliveries'])
			$b['success'] = 1;
	}
}

function diaspora_permissions_update(&$a,&$b) {
	if($b['recipient']['xchan_network'] === 'diaspora' || $b['recipient']['xchan_network'] === 'friendica-over-diaspora') {
		discover_by_webbie($b['recipient']['xchan_hash']);
		$b['success'] = 1;
	}
}

function diaspora_notifier_process(&$a,&$arr) {

	// if it is a public post (reply, etc.), add the chosen relay channel to the recipients

	if(! array_key_exists('item_wall',$item))
		return;

	if(($arr['normal_mode']) && (! $arr['env_recips']) && (! $arr['private']) && (! $arr['relay_to_owner'])) {
		$relay = get_config('diaspora','relay_handle');
		if($relay) {
			$arr['recipients'][] = "'" . $relay . "'";
		}
	}
}


function diaspora_process_outbound(&$a, &$arr) {

/*

	We are passed the following array from the notifier, providing everything we need to make delivery decisions.

			$arr = array(
				'channel' => $channel,
				'env_recips' => $env_recips,
				'packet_recips' => $packet_recips,
				'recipients' => $recipients,
				'item' => $item,
				'target_item' => $target_item,
				'hub' => $hub,
				'top_level_post' => $top_level_post,
				'private' => $private,
				'relay_to_owner' => $relay_to_owner,
				'uplink' => $uplink,
				'cmd' => $cmd,
				'mail' => $mail,
				'location' => $location,
				'normal_mode' => $normal_mode,
				'packet_type' => $packet_type,
				'walltowall' => $walltowall,
				'queued' => pass these queued items (outq_hash) back to notifier.php for delivery
			);
*/

//	logger('notifier_array: ' . print_r($arr,true), LOGGER_ALL, LOG_INFO);

	// allow this to be set per message

	if(strpos($arr['target_item']['postopts'],'nodspr') !== false)
		return;

	$allowed = get_pconfig($arr['channel']['channel_id'],'system','diaspora_allowed');

	if(! intval($allowed)) {
		logger('mod-diaspora: disallowed for channel ' . $arr['channel']['channel_name']);
		return;
	}


	if($arr['location'])
		return;

	// send to public relay server - not ready for prime time

	if(($arr['top_level_post']) && (! $arr['env_recips'])) {
		// Add the relay server to the list of hubs.	
		// = array('hubloc_callback' => 'https://relay.iliketoast.net/receive', 'xchan_pubkey' => 'bogus');
	}

	$target_item = $arr['target_item'];

	if($target_item && array_key_exists('item_obscured',$target_item) && intval($target_item['item_obscured'])) {
		$key = get_config('system','prvkey');
		if($target_item['title'])
			$target_item['title'] = crypto_unencapsulate(json_decode($target_item['title'],true),$key);
		if($target_item['body'])
			$target_item['body'] = crypto_unencapsulate(json_decode($target_item['body'],true),$key);
	}

	$prv_recips = $arr['env_recips'];

	// The Diaspora profile message is unusual in that it is sent privately. 

	if($arr['cmd'] === 'refresh_all' && $arr['recipients']) {
		$prv_recips = array();
		foreach($arr['recipients'] as $r) {
			$prv_recips[] = array('hash' => trim($r,"'"));
		}
	}
			
	if($prv_recips) {
		$hashes = array();

		// re-explode the recipients, but only for this hub/pod

		foreach($prv_recips as $recip)
			$hashes[] = "'" . $recip['hash'] . "'";

		$r = q("select * from xchan left join hubloc on xchan_hash = hubloc_hash where hubloc_url = '%s' 
			and xchan_hash in (" . implode(',', $hashes) . ") and xchan_network in ('diaspora', 'friendica-over-diaspora') ",
			dbesc($arr['hub']['hubloc_url'])
		);


		if(! $r) {
			logger('diaspora_process_outbound: no recipients');
			return; 
		}

		foreach($r as $contact) {

			if(! deliverable_singleton($arr['channel']['channel_id'],$contact)) {
				logger('not deliverable from this hub');
				continue;
			}
	
			if($arr['packet_type'] == 'refresh') {
				$qi = diaspora_profile_change($arr['channel'],$contact);
				if($qi)
					$arr['queued'][] = $qi;
				return;
			}
			if($arr['mail']) {
				$qi = diaspora_send_mail($arr['item'],$arr['channel'],$contact);
				if($qi)
					$arr['queued'][] = $qi;
				continue;
			}

			if(! $arr['normal_mode'])
				continue;

			// special handling for send_upstream to public post
			// all other public posts processed as public batches further below

			if((! $arr['private']) && ($arr['relay_to_owner'])) {
				$qi = diaspora_send_upstream($target_item,$arr['channel'],$contact, true);
				if($qi)
					$arr['queued'][] = $qi;
				continue;
			}

			if(! $contact['xchan_pubkey'])
				continue;


			if(intval($target_item['item_deleted']) 
				&& (($target_item['mid'] === $target_item['parent_mid']) || $arr['relay_to_owner'])) {
				// send both top-level retractions and relayable retractions for owner to relay
				$qi = diaspora_send_retraction($target_item,$arr['channel'],$contact);
				if($qi)
					$arr['queued'][] = $qi;
				continue;
			}
			elseif($arr['relay_to_owner']) {
				// send comments and likes to owner to relay
				$qi = diaspora_send_upstream($target_item,$arr['channel'],$contact);
				if($qi)
					$arr['queued'][] = $qi;
				continue;
			}

			elseif($target_item['mid'] !== $target_item['parent_mid']) {
				// we are the relay - send comments, likes and relayable_retractions
				// (of comments and likes) to our conversants
				$qi = diaspora_send_downstream($target_item,$arr['channel'],$contact);
				if($qi)
					$arr['queued'][] = $qi;
				continue;
			}
			elseif($arr['top_level_post']) {
				$qi = diaspora_send_status($target_item,$arr['channel'],$contact);
				if($qi) {
					foreach($qi as $q)
						$arr['queued'][] = $q;
				}
				continue;
			}
		}
	}
	else {
		// public message

		$contact = $arr['hub'];

		if(intval($target_item['item_deleted']) 
			&& ($target_item['mid'] === $target_item['parent_mid'])) {
			// top-level retraction
			logger('delivery: diaspora retract: ' . $loc);
			$qi = diaspora_send_retraction($target_item,$arr['channel'],$contact,true);
			if($qi)
				$arr['queued'][] = $qi;
			return;
		}
		elseif($target_item['mid'] !== $target_item['parent_mid']) {
			// we are the relay - send comments, likes and relayable_retractions to our conversants
			logger('delivery: diaspora relay: ' . $loc);
			$qi = diaspora_send_downstream($target_item,$arr['channel'],$contact,true);
			if($qi)
				$arr['queued'][] = $qi;
			return;
		}
		elseif($arr['top_level_post']) {
			if(perm_is_allowed($arr['channel'],'','view_stream')) {
				logger('delivery: diaspora status: ' . $loc);
				$qi = diaspora_send_status($target_item,$arr['channel'],$contact,true);
				if($qi) {
					foreach($qi as $q)
						$arr['queued'][] = $q;
				}
				return;
			}
		}
	}
}





function diaspora_queue($owner,$contact,$slap,$public_batch,$message_id = '') {


	$allowed = get_pconfig($owner['channel_id'],'system','diaspora_allowed');
	if($allowed === false)
		$allowed = 1;

	if(! intval($allowed)) {
		return false;
	}

	if($public_batch)
		$dest_url = $contact['hubloc_callback'] . '/public';
	else
		$dest_url = $contact['hubloc_callback'] . '/users/' . $contact['hubloc_guid'];


	logger('diaspora_queue: URL: ' . $dest_url, LOGGER_DEBUG);	

	if(intval(get_config('system','diaspora_test')) || intval(get_pconfig($owner['channel_id'],'system','diaspora_test')))
		return false;

	$a = get_app();

	$hash = random_string();

	logger('diaspora_queue: ' . $hash . ' ' . $dest_url, LOGGER_DEBUG);

	queue_insert(array(
		'hash'       => $hash,
		'account_id' => $owner['channel_account_id'],
		'channel_id' => $owner['channel_id'],
		'driver'     => 'post',
		'posturl'    => $dest_url,
		'notify'     => '',
		'msg'        => $slap
	));

	if($message_id && (! get_config('system','disable_dreport'))) {
		q("insert into dreport ( dreport_mid, dreport_site, dreport_recip, dreport_result, dreport_time, dreport_xchan, dreport_queue ) values ( '%s','%s','%s','%s','%s','%s','%s' ) ",
			dbesc($message_id),
			dbesc($dest_url),
			dbesc($dest_url),
			dbesc('queued'),
			dbesc(datetime_convert()),
			dbesc($owner['channel_hash']),
			dbesc($hash)
		);
	}

	return $hash;

}


function diaspora_follow_allow(&$a, &$b) {

	if($b['xchan']['xchan_network'] !== 'diaspora' && $b['xchan']['xchan_network'] !== 'friendica-over-diaspora')
		return;

	$allowed = get_pconfig($b['channel_id'],'system','diaspora_allowed');
	if($allowed === false)
		$allowed = 1;
	$b['allowed'] = $allowed;
	$b['singleton'] = 1;  // this network does not support channel clones
}


function diaspora_discover(&$a,&$b) {

	require_once('include/network.php');

	$result = array();
	$network = null;
	$diaspora = false;

	$diaspora_base = '';
	$diaspora_guid = '';
	$diaspora_key = '';
	$dfrn = false;

	$x = old_webfinger($webbie);			
	if($x) {
		logger('old_webfinger: ' . print_r($x,true));
		foreach($x as $link) {
			if($link['@attributes']['rel'] === NAMESPACE_DFRN)
				$dfrn = unamp($link['@attributes']['href']);				
			if($link['@attributes']['rel'] === 'salmon')
				$notify = unamp($link['@attributes']['href']);
 			if($link['@attributes']['rel'] === NAMESPACE_FEED)
				$poll = unamp($link['@attributes']['href']);
			if($link['@attributes']['rel'] === 'http://microformats.org/profile/hcard')
				$hcard = unamp($link['@attributes']['href']);
			if($link['@attributes']['rel'] === 'http://webfinger.net/rel/profile-page')
				$profile = unamp($link['@attributes']['href']);
			if($link['@attributes']['rel'] === 'http://portablecontacts.net/spec/1.0')
				$poco = unamp($link['@attributes']['href']);
			if($link['@attributes']['rel'] === 'http://joindiaspora.com/seed_location') {
				$diaspora_base = unamp($link['@attributes']['href']);
				$diaspora = true;
			}
			if($link['@attributes']['rel'] === 'http://joindiaspora.com/guid') {
				$diaspora_guid = unamp($link['@attributes']['href']);
				$diaspora = true;
			}
			if($link['@attributes']['rel'] === 'diaspora-public-key') {
				$diaspora_key = base64_decode(unamp($link['@attributes']['href']));
				if(strstr($diaspora_key,'RSA '))
					$pubkey = rsatopem($diaspora_key);
				else
					$pubkey = $diaspora_key;
				$diaspora = true;
			}
		}

		if($diaspora && $diaspora_base && $diaspora_guid) {
			$guid = $diaspora_guid;
			$diaspora_base = trim($diaspora_base,'/');

			$notify = $diaspora_base . '/receive';

			if(strpos($webbie,'@')) {
				$addr = str_replace('acct:', '', $webbie);
				$hostname = substr($webbie,strpos($webbie,'@')+1);
			}
			$network = 'diaspora';
			// until we get a dfrn layer, we'll use diaspora protocols for Friendica,
			// but give it a different network so we can go back and fix these when we get proper support. 
			// It really should be just 'friendica' but we also want to distinguish
			// between Friendica sites that we can use D* protocols with and those we can't.
			// Some Friendica sites will have Diaspora disabled. 
			if($dfrn)
				$network = 'friendica-over-diaspora';
			if($hcard) {
				$vcard = scrape_vcard($hcard);
				$vcard['nick'] = substr($webbie,0,strpos($webbie,'@'));
				if(! $vcard['fn'])
					$vcard['fn'] = $webbie;
			} 

			$r = q("select * from xchan where xchan_hash = '%s' limit 1",
				dbesc($addr)
			);

			/**
			 *
			 * Diaspora communications are notoriously unreliable and receiving profile update messages (indeed any messages) 
			 * are pretty much random luck. We'll check the timestamp of the xchan_name_date at a higher level and refresh
			 * this record once a month; because if you miss a profile update message and they update their profile photo or name 
			 * you're otherwise stuck with stale info until they change their profile again - which could be years from now. 
			 *
			 */  			

			if($r) {
				$r = q("update xchan set xchan_name = '%s', xchan_network = '%s', xchan_name_date = '%s' where xchan_hash = '%s' limit 1",
					dbesc($vcard['fn']),
					dbesc($network),
					dbesc(datetime_convert()),
					dbesc($addr)
				);
			}
			else {

				$r = q("insert into xchan ( xchan_hash, xchan_guid, xchan_pubkey, xchan_addr, xchan_url, xchan_name, xchan_network, xchan_name_date ) values ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s') ",
					dbesc($addr),
					dbesc($guid),
					dbesc($pubkey),
					dbesc($addr),
					dbesc($profile),
					dbesc($vcard['fn']),
					dbesc($network),
					dbescdate(datetime_convert())
				);
			}

			$r = q("select * from hubloc where hubloc_hash = '%s' limit 1",
				dbesc($webbie)
			);

			if(! $r) {

				$r = q("insert into hubloc ( hubloc_guid, hubloc_hash, hubloc_addr, hubloc_network, hubloc_url, hubloc_host, hubloc_callback, hubloc_updated, hubloc_primary ) values ('%s','%s','%s','%s','%s','%s','%s','%s', 1)",
					dbesc($guid),
					dbesc($addr),
					dbesc($addr),
					dbesc($network),
					dbesc(trim($diaspora_base,'/')),
					dbesc($hostname),
					dbesc($notify),
					dbescdate(datetime_convert())
				);
			}
			$photos = import_xchan_photo($vcard['photo'],$addr);
			$r = q("update xchan set xchan_photo_date = '%s', xchan_photo_l = '%s', xchan_photo_m = '%s', xchan_photo_s = '%s', xchan_photo_mimetype = '%s' where xchan_hash = '%s'",
				dbescdate(datetime_convert('UTC','UTC',$arr['photo_updated'])),
				dbesc($photos[0]),
				dbesc($photos[1]),
				dbesc($photos[2]),
				dbesc($photos[3]),
				dbesc($addr)
			);
			return true;

		}

	return false;

/*
	$vcard['fn'] = notags($vcard['fn']);
	$vcard['nick'] = str_replace(' ','',notags($vcard['nick']));

	$result['name'] = $vcard['fn'];
	$result['nick'] = $vcard['nick'];
	$result['guid'] = $guid;
	$result['url'] = $profile;
	$result['hostname'] = $hostname;
	$result['addr'] = $addr;
	$result['batch'] = $batch;
	$result['notify'] = $notify;
	$result['poll'] = $poll;
	$result['request'] = $request;
	$result['confirm'] = $confirm;
	$result['poco'] = $poco;
	$result['photo'] = $vcard['photo'];
	$result['priority'] = $priority;
	$result['network'] = $network;
	$result['alias'] = $alias;
	$result['pubkey'] = $pubkey;

	logger('probe_url: ' . print_r($result,true), LOGGER_DEBUG);

	return $result;

*/

/* Sample Diaspora result.

Array
(
	[name] => Mike Macgirvin
	[nick] => macgirvin
	[guid] => a9174a618f8d269a
	[url] => https://joindiaspora.com/u/macgirvin
	[hostname] => joindiaspora.com
	[addr] => macgirvin@joindiaspora.com
	[batch] => 
	[notify] => https://joindiaspora.com/receive
	[poll] => https://joindiaspora.com/public/macgirvin.atom
	[request] => 
	[confirm] => 
	[poco] => 
	[photo] => https://joindiaspora.s3.amazonaws.com/uploads/images/thumb_large_fec4e6eef13ae5e56207.jpg
	[priority] => 
	[network] => diaspora
	[alias] => 
	[pubkey] => -----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAtihtyIuRDWkDpCA+I1UaQ
jI4S7k625+A7EEJm+pL2ZVSJxeCKiFeEgHBQENjLMNNm8l8F6blxgQqE6ZJ9Spa7f
tlaXYTRCrfxKzh02L3hR7sNA+JS/nXJaUAIo+IwpIEspmcIRbD9GB7Wv/rr+M28uH
31EeYyDz8QL6InU/bJmnCdFvmEMBQxJOw1ih9tQp7UNJAbUMCje0WYFzBz7sfcaHL
OyYcCOqOCBLdGucUoJzTQ9iDBVzB8j1r1JkIHoEb2moUoKUp+tkCylNfd/3IVELF9
7w1Qjmit3m50OrJk2DQOXvCW9KQxaQNdpRPSwhvemIt98zXSeyZ1q/YjjOwG0DWDq
AF8aLj3/oQaZndTPy/6tMiZogKaijoxj8xFLuPYDTw5VpKquriVC0z8oxyRbv4t9v
8JZZ9BXqzmayvY3xZGGp8NulrfjW+me2bKh0/df1aHaBwpZdDTXQ6kqAiS2FfsuPN
vg57fhfHbL1yJ4oDbNNNeI0kJTGchXqerr8C20khU/cQ2Xt31VyEZtnTB665Ceugv
kp3t2qd8UpAVKl430S5Quqx2ymfUIdxdW08CEjnoRNEL3aOWOXfbf4gSVaXmPCR4i
LSIeXnd14lQYK/uxW/8cTFjcmddsKxeXysoQxbSa9VdDK+KkpZdgYXYrTTofXs6v+
4afAEhRaaY+MCAwEAAQ==
-----END PUBLIC KEY-----

)
*/




	}
}


function diaspora_feature_settings_post(&$a,&$b) {

	if($_POST['diaspora-submit']) {
		set_pconfig(local_channel(),'system','diaspora_allowed',intval($_POST['dspr_allowed']));
		set_pconfig(local_channel(),'system','diaspora_public_comments',intval($_POST['dspr_pubcomment']));
		set_pconfig(local_channel(),'system','prevent_tag_hijacking',intval($_POST['dspr_hijack']));

		$followed = $_POST['dspr_followed'];
		$ntags = array();
		if($followed) {
			$tags = explode(',', $followed);
			foreach($tags as $t) {
				$t = trim($t);
				if($t)
					$ntags[] = $t;
			}
		}
		set_pconfig(local_channel(),'diaspora','followed_tags',$ntags);
		diaspora_build_relay_tags();
		
		info( t('Diaspora Protocol Settings updated.') . EOL);
	}
}


function diaspora_feature_settings(&$a,&$s) {
	$dspr_allowed = get_pconfig(local_channel(),'system','diaspora_allowed');
	$pubcomments = get_pconfig(local_channel(),'system','diaspora_public_comments');
	if($pubcomments === false)
		$pubcomments = 1;
	$hijacking = get_pconfig(local_channel(),'system','prevent_tag_hijacking');
	$followed = get_pconfig(local_channel(),'diaspora','followed_tags');
	if(is_array($followed))
		$hashtags = implode(',',$followed);
	else
		$hashtags = '';

	$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
		'$field'	=> array('dspr_allowed', t('Enable the (experimental) Diaspora protocol for this channel'), $dspr_allowed, '', $yes_no),
	));

	$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
		'$field'	=> array('dspr_pubcomment', t('Allow any Diaspora member to comment on your public posts'), $pubcomments, '', $yes_no),
	));

	$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
		'$field'	=> array('dspr_hijack', t('Prevent your hashtags from being redirected to other sites'), $hijacking, '', $yes_no),
	));

	$sc .= replace_macros(get_markup_template('field_input.tpl'), array(
		'$field'	=> array('dspr_followed', t('Followed hashtags (comma separated, do not include the #)'), $hashtags, '')
	));


	$s .= replace_macros(get_markup_template('generic_addon_settings.tpl'), array(
		'$addon' 	=> array('diaspora', '<img src="addon/diaspost/diaspora.png" style="width:auto; height:1em; margin:-3px 5px 0px 0px;">' . t('Diaspora Protocol Settings'), '', t('Submit')),
		'$content'	=> $sc
	));

	return;

}



function diaspora_post_local(&$a,&$item) {

	/**
	 * If all the conditions are met, generate an instance of the Diaspora Comment Virus
	 *
	 * Previously all comments from any Hubzilla source (including those who have not opted in to
	 * Diaspora federation), were required to locally generate a Diaspora comment signature.
	 * The only exception was wall-to-wall posts which have no local signing authority.
	 *
	 * Going forward, if we are asked to propagate the virus and it is not present (due to the post author
	 * not opting in to Diaspora federation); we will generate a "wall-to-wall" comment and not require 
	 * a source signature. This allows hubs and communities to opt-out of Diaspora federation and not be
	 * forced to generate the comment virus regardless. This is necessary because Diaspora now requires
	 * the virus not just to provide a stored signature and Diaspora formatted text body, but must also 
	 * include all XML fields presented by the Diaspora protocol when transmitting the comment, while
	 * maintaining their source order. This is fine for federated communities using UNO, but it makes 
	 * no sense to require this low-level baggage in channels and communities that have chosen not to use
	 * the Diaspora protocol and services.
	 *   
	 */

	require_once('include/bb2diaspora.php');


	if($item['mid'] === $item['parent_mid'])
		return;
	if($item['created'] != $item['edited'])
		return;

	$meta = null;

	$author = channelx_by_hash($item['author_xchan']);
	if($author) {

		// The author has a local channel, If they have this connector installed,
		// sign the comment and create a Diaspora Comment Virus. 

		$dspr_allowed = get_pconfig($author['channel_id'],'system','diaspora_allowed');
		if(! $dspr_allowed)
			return;

		$handle = $author['channel_address'] . '@' . App::get_hostname();

		$body = bb2diaspora_itembody($item,true,true);

		$meta = array(
			'guid' => $item['mid'],
			'parent_guid' => $item['parent_mid'],
			'text' => $body,
			'diaspora_handle' => $handle
		);

		$meta['author_signature'] = diaspora_sign_fields($meta, $author['channel_prvkey']);
		if($item['author_xchan'] === $item['owner_xchan'])
			$meta['parent_author_signature'] = diaspora_sign_fields($meta,$author['channel_prvkey']);
	}

	if((! $meta) && ($item['author_xchan'] !== $item['owner_xchan'])) {

		// A local comment arrived but the commenter does not have a local channel
		// or the commenter doesn't have the Diaspora plugin enabled.
		// The owner *should* have a local channel
		// Find the owner and if the owner has this addon installed, turn the comment into
		// a 'wall-to-wall' message containing the author attribution,
		// with the comment signed by the owner.

		$owner = channelx_by_hash($item['owner_xchan']);
		if(! $owner)
			return;

		$dspr_allowed = get_pconfig($owner['channel_id'],'system','diaspora_allowed');
		if(! $dspr_allowed)
			return;

		$handle = $owner['channel_address'] . '@' . App::get_hostname();

		$body = bb2diaspora_itembody($item,true,false);

		$meta = array(
			'guid' => $item['mid'],
			'parent_guid' => $item['parent_mid'],
			'text' => $body,
			'diaspora_handle' => $handle
		);

		$meta['author_signature'] = diaspora_sign_fields($meta, $owner['channel_prvkey']);
		$meta['parent_author_signature'] = diaspora_sign_fields($meta,$owner['channel_prvkey']);
	}


	if($meta)
		set_iconfig($item,'diaspora','fields', $meta, true);

	// otherwise, neither the author or owner have this plugin installed. Do nothing. 


// 	logger('ditem: ' . print_r($item,true));

}
