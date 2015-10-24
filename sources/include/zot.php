<?php
/**
 * @file include/zot.php
 * @brief Hubzilla implementation of zot protocol.
 *
 * https://github.com/friendica/red/wiki/zot
 * https://github.com/friendica/red/wiki/Zot---A-High-Level-Overview
 *
 */

require_once('include/crypto.php');
require_once('include/items.php');
require_once('include/hubloc.php');
require_once('include/DReport.php');


/**
 * @brief Generates a unique string for use as a zot guid.
 *
 * Generates a unique string for use as a zot guid using our DNS-based url, the
 * channel nickname and some entropy.
 * The entropy ensures uniqueness against re-installs where the same URL and
 * nickname are chosen.
 *
 * @note zot doesn't require this to be unique. Internally we use a whirlpool
 * hash of this guid and the signature of this guid signed with the channel
 * private key. This can be verified and should make the probability of
 * collision of the verified result negligible within the constraints of our
 * immediate universe.
 *
 * @param string $channel_nick a unique nickname of controlling entity
 * @returns string
 */
function zot_new_uid($channel_nick) {
	$rawstr = z_root() . '/' . $channel_nick . '.' . mt_rand();

	return(base64url_encode(hash('whirlpool', $rawstr, true), true));
}

/**
 * @brief Generates a portable hash identifier for a channel.
 *
 * Generates a portable hash identifier for the channel identified by $guid and
 * signed with $guid_sig.
 *
 * @note This ID is portable across the network but MUST be calculated locally
 * by verifying the signature and can not be trusted as an identity.
 *
 * @param string $guid
 * @param string $guid_sig
 */
function make_xchan_hash($guid, $guid_sig) {
	return base64url_encode(hash('whirlpool', $guid . $guid_sig, true));
}

/**
 * @brief Given a zot hash, return all distinct hubs.
 *
 * This function is used in building the zot discovery packet and therefore
 * should only be used by channels which are defined on this hub.
 *
 * @param string $hash - xchan_hash
 * @returns array of hubloc (hub location structures)
 *  * \b hubloc_id          int
 *  * \b hubloc_guid        char(255)
 *  * \b hubloc_guid_sig    text
 *  * \b hubloc_hash        char(255)
 *  * \b hubloc_addr        char(255)
 *  * \b hubloc_flags       int
 *  * \b hubloc_status      int
 *  * \b hubloc_url         char(255)
 *  * \b hubloc_url_sig     text
 *  * \b hubloc_host        char(255)
 *  * \b hubloc_callback    char(255)
 *  * \b hubloc_connect     char(255)
 *  * \b hubloc_sitekey     text
 *  * \b hubloc_updated     datetime
 *  * \b hubloc_connected   datetime
 */
function zot_get_hublocs($hash) {

	/* Only search for active hublocs - e.g. those that haven't been marked deleted */

	$ret = q("select * from hubloc where hubloc_hash = '%s' and hubloc_deleted = 0 order by hubloc_url ",
		dbesc($hash)
	);

	return $ret;
}

/**
 * @brief Builds a zot notification packet.
 *
 * Builds a zot notification packet that you can either store in the queue with
 * a message array or call zot_zot to immediately zot it to the other side.
 *
 * @param array $channel
 *   sender channel structure
 * @param string $type
 *   packet type: one of 'ping', 'pickup', 'purge', 'refresh', 'force_refresh', 'notify', 'auth_check'
 * @param array $recipients
 *   envelope information, array ( 'guid' => string, 'guid_sig' => string ); empty for public posts
 * @param string $remote_key
 *   optional public site key of target hub used to encrypt entire packet
 *   NOTE: remote_key and encrypted packets are required for 'auth_check' packets, optional for all others
 * @param string $secret
 *   random string, required for packets which require verification/callback
 *   e.g. 'pickup', 'purge', 'notify', 'auth_check'. Packet types 'ping', 'force_refresh', and 'refresh' do not require verification
 * @param string $extra
 * @returns string json encoded zot packet
 */
function zot_build_packet($channel, $type = 'notify', $recipients = null, $remote_key = null, $secret = null, $extra = null) {

	$data = array(
		'type' => $type,
		'sender' => array(
			'guid' => $channel['channel_guid'],
			'guid_sig' => base64url_encode(rsa_sign($channel['channel_guid'],$channel['channel_prvkey'])),
			'url' => z_root(),
			'url_sig' => base64url_encode(rsa_sign(z_root(),$channel['channel_prvkey']))
		),
		'callback' => '/post',
		'version' => ZOT_REVISION
	);

	if ($recipients) {
		for ($x = 0; $x < count($recipients); $x ++)
			unset($recipients[$x]['hash']);

		$data['recipients'] = $recipients;
	}

	if ($secret) {
		$data['secret'] = $secret;
		$data['secret_sig'] = base64url_encode(rsa_sign($secret,$channel['channel_prvkey']));
	}

	if ($extra) {
		foreach ($extra as $k => $v)
			$data[$k] = $v;
	}

	logger('zot_build_packet: ' . print_r($data,true), LOGGER_DATA);

	// Hush-hush ultra top-secret mode

	if ($remote_key) {
		$data = crypto_encapsulate(json_encode($data),$remote_key);
	}

	return json_encode($data);
}

/**
 * @brief
 *
 * @see z_post_url()
 *
 * @param string $url
 * @param array $data
 * @return array see z_post_url() for returned data format
 */
function zot_zot($url, $data) {
	return z_post_url($url, array('data' => $data));
}

/**
 * @brief Look up information about channel.
 *
 * @param string $webbie
 *   does not have to be host qualified e.g. 'foo' is treated as 'foo\@thishub'
 * @param array $channel
 *   (optional), if supplied permissions will be enumerated specifically for $channel
 * @param boolean $autofallback
 *   fallback/failover to http if https connection cannot be established. Default is true.
 *
 * @return array see z_post_url() and \ref mod/zfinger.php
 */
function zot_finger($webbie, $channel = null, $autofallback = true) {

	if (strpos($webbie,'@') === false) {
		$address = $webbie;
		$host = get_app()->get_hostname();
	} else {
		$address = substr($webbie,0,strpos($webbie,'@'));
		$host = substr($webbie,strpos($webbie,'@')+1);
	}

	$xchan_addr = $address . '@' . $host;

	if ((! $address) || (! $xchan_addr)) {
		logger('zot_finger: no address :' . $webbie);
		return array('success' => false);
	}
	logger('using xchan_addr: ' . $xchan_addr, LOGGER_DATA);

	// potential issue here; the xchan_addr points to the primary hub.
	// The webbie we were called with may not, so it might not be found
	// unless we query for hubloc_addr instead of xchan_addr

	$r = q("select xchan.*, hubloc.* from xchan
			left join hubloc on xchan_hash = hubloc_hash
			where xchan_addr = '%s' and hubloc_primary = 1 limit 1",
		dbesc($xchan_addr)
	);

	if ($r) {
		$url = $r[0]['hubloc_url'];

		if ($r[0]['hubloc_network'] && $r[0]['hubloc_network'] !== 'zot') {
			logger('zot_finger: alternate network: ' . $webbie);
			logger('url: '.$url.', net: '.var_export($r[0]['hubloc_network'],true), LOGGER_DATA);
			return array('success' => false);
		}
	} else {
		$url = 'https://' . $host;
	}

	$rhs = '/.well-known/zot-info';
	$https = ((strpos($url,'https://') === 0) ? true : false);

	logger('zot_finger: ' . $address . ' at ' . $url, LOGGER_DEBUG);

	if ($channel) {
		$postvars = array(
			'address'    => $address,
			'target'     => $channel['channel_guid'],
			'target_sig' => $channel['channel_guid_sig'],
			'key'        => $channel['channel_pubkey']
		);

		$result = z_post_url($url . $rhs,$postvars);

		if ((! $result['success']) && ($autofallback)) {
			if ($https) {
				logger('zot_finger: https failed. falling back to http');
				$result = z_post_url('http://' . $host . $rhs,$postvars);
			}
		}
	} else {
		$rhs .= '?f=&address=' . urlencode($address);

		$result =  z_fetch_url($url . $rhs);
		if ((! $result['success']) && ($autofallback)) {
			if ($https) {
				logger('zot_finger: https failed. falling back to http');
				$result = z_fetch_url('http://' . $host . $rhs);
			}
		}
	}

	if (! $result['success'])
		logger('zot_finger: no results');

	return $result;
}

/**
 * @brief Refreshes after permission changed or friending, etc.
 *
 * zot_refresh is typically invoked when somebody has changed permissions of a channel and they are notified
 * to fetch new permissions via a finger/discovery operation. This may result in a new connection
 * (abook entry) being added to a local channel and it may result in auto-permissions being granted.
 *
 * Friending in zot is accomplished by sending a refresh packet to a specific channel which indicates a
 * permission change has been made by the sender which affects the target channel. The hub controlling
 * the target channel does targetted discovery (a zot-finger request requesting permissions for the local
 * channel). These are decoded here, and if necessary and abook structure (addressbook) is created to store
 * the permissions assigned to this channel.
 *
 * Initially these abook structures are created with a 'pending' flag, so that no reverse permissions are
 * implied until this is approved by the owner channel. A channel can also auto-populate permissions in
 * return and send back a refresh packet of its own. This is used by forum and group communication channels
 * so that friending and membership in the channel's "club" is automatic.
 *
 * @param array $them => xchan structure of sender
 * @param array $channel => local channel structure of target recipient, required for "friending" operations
 * @param array $force default false
 *
 * @returns boolean true if successful, else false
 */
function zot_refresh($them, $channel = null, $force = false) {

	if (array_key_exists('xchan_network', $them) && ($them['xchan_network'] !== 'zot')) {
		logger('zot_refresh: not got zot. ' . $them['xchan_name']);
		return true;
	}

	logger('zot_refresh: them: ' . print_r($them,true), LOGGER_DATA);
	if ($channel)
		logger('zot_refresh: channel: ' . print_r($channel,true), LOGGER_DATA);

	$url = null;

	if ($them['hubloc_url']) {
		$url = $them['hubloc_url'];
	} else {
		$r = null;

		// if they re-installed the server we could end up with the wrong record - pointing to the old install.
		// We'll order by reverse id to try and pick off the newest one first and hopefully end up with the
		// correct hubloc. If this doesn't work we may have to re-write this section to try them all. 

		if(array_key_exists('xchan_addr',$them) && $them['xchan_addr']) {
			$r = q("select hubloc_url, hubloc_primary from hubloc where hubloc_addr = '%s' order by hubloc_id desc",
				dbesc($them['xchan_addr'])
			);
		}
		if(! $r) {
			$r = q("select hubloc_url, hubloc_primary from hubloc where hubloc_hash = '%s' order by hubloc_id desc",
				dbesc($them['xchan_hash'])
			);
		}

		if ($r) {
			foreach ($r as $rr) {
				if (intval($rr['hubloc_primary'])) {
					$url = $rr['hubloc_url'];
					break;
				}
			}
			if (! $url)
				$url = $r[0]['hubloc_url'];
		}
	}
	if (! $url) {
		logger('zot_refresh: no url');
		return false;
	}

	$postvars = array();

	if($channel) {
		$postvars['target']     = $channel['channel_guid'];
		$postvars['target_sig'] = $channel['channel_guid_sig'];
		$postvars['key']        = $channel['channel_pubkey'];
	}

	if (array_key_exists('xchan_addr',$them) && $them['xchan_addr'])
		$postvars['address'] = $them['xchan_addr'];
	if (array_key_exists('xchan_hash',$them) && $them['xchan_hash'])
		$postvars['guid_hash'] = $them['xchan_hash'];
	if (array_key_exists('xchan_guid',$them) && $them['xchan_guid']
		&& array_key_exists('xchan_guid_sig',$them) && $them['xchan_guid_sig']) {

		$postvars['guid'] = $them['xchan_guid'];
		$postvars['guid_sig'] = $them['xchan_guid_sig'];
	}

	$rhs = '/.well-known/zot-info';

	$result = z_post_url($url . $rhs,$postvars);

	logger('zot_refresh: zot-info: ' . print_r($result,true), LOGGER_DATA);

	if ($result['success']) {

		$j = json_decode($result['body'],true);

		if (! (($j) && ($j['success']))) {
			logger('zot_refresh: result not decodable');
			return false;
		}

		$x = import_xchan($j, (($force) ? UPDATE_FLAGS_FORCED : UPDATE_FLAGS_UPDATED));

		if(! $x['success'])
			return false;

		$their_perms = 0;

		if($channel) {
			$global_perms = get_perms();
			if($j['permissions']['data']) {
				$permissions = crypto_unencapsulate(array(
					'data' => $j['permissions']['data'],
					'key'  => $j['permissions']['key'],
					'iv'   => $j['permissions']['iv']),
					$channel['channel_prvkey']);
				if($permissions)
					$permissions = json_decode($permissions,true);
				logger('decrypted permissions: ' . print_r($permissions,true), LOGGER_DATA);
			}
			else
				$permissions = $j['permissions'];

			$connected_set = false;

			if($permissions && is_array($permissions)) {
				foreach($permissions as $k => $v) {
					// The connected permission means you are in their address book
					if($k === 'connected') {
						$connected_set = intval($v);
						continue;
					}
					if(($v) && (array_key_exists($k,$global_perms))) {
						$their_perms = $their_perms | intval($global_perms[$k][1]);
					}
				}
			}

			if(array_key_exists('profile',$j) && array_key_exists('next_birthday',$j['profile'])) {
				$next_birthday = datetime_convert('UTC','UTC',$j['profile']['next_birthday']);
			}
			else {
				$next_birthday = NULL_DATE;
			}

			$r = q("select * from abook where abook_xchan = '%s' and abook_channel = %d and abook_self = 0 limit 1",
				dbesc($x['hash']),
				intval($channel['channel_id'])
			);

			if($r) {

				// connection exists

				// if the dob is the same as what we have stored (disregarding the year), keep the one
				// we have as we may have updated the year after sending a notification; and resetting
				// to the one we just received would cause us to create duplicated events.

				if(substr($r[0]['abook_dob'],5) == substr($next_birthday,5))
					$next_birthday = $r[0]['abook_dob'];

				$current_abook_connected = (intval($r[0]['abook_unconnected']) ? 0 : 1);

				$y = q("update abook set abook_their_perms = %d, abook_dob = '%s'
					where abook_xchan = '%s' and abook_channel = %d
					and abook_self = 0 ",
					intval($their_perms),
					dbescdate($next_birthday),
					dbesc($x['hash']),
					intval($channel['channel_id'])
				);

//				if(($connected_set === 0 || $connected_set === 1) && ($connected_set !== $current_abook_unconnected)) {

					// if they are in your address book but you aren't in theirs, and/or this does not
					// match your current connected state setting, toggle it.
					/** @FIXME uncoverted to postgres */
					/** @FIXME when this was enabled, all contacts became unconnected. Currently disabled intentionally */
//					$y1 = q("update abook set abook_unconnected = 1
//						where abook_xchan = '%s' and abook_channel = %d
//						and abook_self = 0 limit 1",
//						dbesc($x['hash']),
//						intval($channel['channel_id'])
//					);
//				}

				if(! $y)
					logger('abook update failed');
				else {
					// if we were just granted read stream permission and didn't have it before, try to pull in some posts
					if((! ($r[0]['abook_their_perms'] & PERMS_R_STREAM)) && ($their_perms & PERMS_R_STREAM))
						proc_run('php','include/onepoll.php',$r[0]['abook_id']);
				}
			}
			else {

				// new connection

				$role = get_pconfig($channel['channel_id'],'system','permissions_role');
				if($role) {
					$xx = get_role_perms($role);
					if($xx['perms_auto'])
						$default_perms = $xx['perms_accept'];
				}
				if(! $default_perms)
					$default_perms = intval(get_pconfig($channel['channel_id'],'system','autoperms'));


				// Keep original perms to check if we need to notify them
				$previous_perms = get_all_perms($channel['channel_id'],$x['hash']);


				$closeness = get_pconfig($channel['channel_id'],'system','new_abook_closeness');
				if($closeness === false)
					$closeness = 80;

				$y = q("insert into abook ( abook_account, abook_channel, abook_closeness, abook_xchan, abook_their_perms, abook_my_perms, abook_created, abook_updated, abook_dob, abook_pending ) values ( %d, %d, %d, '%s', %d, %d, '%s', '%s', '%s', %d )",
					intval($channel['channel_account_id']),
					intval($channel['channel_id']),
					intval($closeness),
					dbesc($x['hash']),
					intval($their_perms),
					intval($default_perms),
					dbesc(datetime_convert()),
					dbesc(datetime_convert()),
					dbesc($next_birthday),
					intval(($default_perms) ? 0 : 1)
				);

				if($y) {
					logger("New introduction received for {$channel['channel_name']}");
					$new_perms = get_all_perms($channel['channel_id'],$x['hash']);

					// Send a clone sync packet and a permissions update if permissions have changed

					$new_connection = q("select * from abook left join xchan on abook_xchan = xchan_hash where abook_xchan = '%s' and abook_channel = %d and abook_self = 0 order by abook_created desc limit 1",
						dbesc($x['hash']),
						intval($channel['channel_id'])
					);

					if($new_connection) {
						if($new_perms != $previous_perms)
							proc_run('php','include/notifier.php','permission_create',$new_connection[0]['abook_id']);
						require_once('include/enotify.php');
						notification(array(
							'type'       => NOTIFY_INTRO,
							'from_xchan' => $x['hash'],
							'to_xchan'   => $channel['channel_hash'],
							'link'       => z_root() . '/connedit/' . $new_connection[0]['abook_id'],
						));
					
						if($their_perms & PERMS_R_STREAM) {
							if(($channel['channel_w_stream'] & PERMS_PENDING)
								|| (! intval($new_connection[0]['abook_pending'])) )
								proc_run('php','include/onepoll.php',$new_connection[0]['abook_id']);
						}

						unset($new_connection[0]['abook_id']);
						unset($new_connection[0]['abook_account']);
						unset($new_connection[0]['abook_channel']);
						build_sync_packet($channel['channel_id'], array('abook' => $new_connection));
					}
				}
			}
		}
		return true;
	}

	return false;
}

/**
 * @brief Look up if channel is known and previously verified.
 *
 * A guid and a url, both signed by the sender, distinguish a known sender at a
 * known location.
 * This function looks these up to see if the channel is known and therefore
 * previously verified. If not, we will need to verify it.
 *
 * @param array $arr an associative array which must contain:
 *  * \e string \b guid => guid of conversant
 *  * \e string \b guid_sig => guid signed with conversant's private key
 *  * \e string \b url => URL of the origination hub of this communication
 *  * \e string \b url_sig => URL signed with conversant's private key
 *
 * @returns array|null null if site is blacklisted or not found, otherwise an
 *  array with an hubloc record
 */
function zot_gethub($arr,$multiple = false) {

	if($arr['guid'] && $arr['guid_sig'] && $arr['url'] && $arr['url_sig']) {

		$blacklisted = false;
		$bl1 = get_config('system','blacklisted_sites');
		if(is_array($bl1) && $bl1) {
			foreach($bl1 as $bl) {
				if($bl && strpos($arr['url'],$bl) !== false) {
					$blacklisted = true;
					break;
				}
			}
		}
		if($blacklisted) {
			logger('zot_gethub: blacklisted site: ' . $arr['url']);
			return null;
		}

		$limit = (($multiple) ? '' : ' limit 1 ');

		$r = q("select * from hubloc
				where hubloc_guid = '%s' and hubloc_guid_sig = '%s'
				and hubloc_url = '%s' and hubloc_url_sig = '%s'
				$limit",
			dbesc($arr['guid']),
			dbesc($arr['guid_sig']),
			dbesc($arr['url']),
			dbesc($arr['url_sig'])
		);
		if($r) {
			logger('zot_gethub: found', LOGGER_DEBUG);
			return (($multiple) ? $r : $r[0]);
		}
	}
	logger('zot_gethub: not found: ' . print_r($arr,true), LOGGER_DEBUG);

	return null;
}

/**
 * @brief Registers an unknown hub.
 *
 * A communication has been received which has an unknown (to us) sender.
 * Perform discovery based on our calculated hash of the sender at the
 * origination address. This will fetch the discovery packet of the sender,
 * which contains the public key we need to verify our guid and url signatures.
 *
 * @param array $arr an associative array which must contain:
 *  * \e string \b guid => guid of conversant
 *  * \e string \b guid_sig => guid signed with conversant's private key
 *  * \e string \b url => URL of the origination hub of this communication
 *  * \e string \b url_sig => URL signed with conversant's private key
 *
 * @returns array an associative array with
 *  * \b success boolean true or false
 *  * \b message (optional) error string only if success is false
 */
function zot_register_hub($arr) {

	$result = array('success' => false);

	if($arr['url'] && $arr['url_sig'] && $arr['guid'] && $arr['guid_sig']) {

		$guid_hash = make_xchan_hash($arr['guid'],$arr['guid_sig']);

		$url = $arr['url'] . '/.well-known/zot-info/?f=&guid_hash=' . $guid_hash;

		logger('zot_register_hub: ' . $url, LOGGER_DEBUG);

		$x = z_fetch_url($url);

		logger('zot_register_hub: ' . print_r($x,true), LOGGER_DATA);

		if($x['success']) {
			$record = json_decode($x['body'],true);

			/*
			 * We now have a key - only continue registration if our signatures are valid
			 * AND the guid and guid sig in the returned packet match those provided in
			 * our current communication.
			 */

			if((rsa_verify($arr['guid'],base64url_decode($arr['guid_sig']),$record['key']))
				&& (rsa_verify($arr['url'],base64url_decode($arr['url_sig']),$record['key']))
				&& ($arr['guid'] === $record['guid'])
				&& ($arr['guid_sig'] === $record['guid_sig'])) {

				$c = import_xchan($record);
				if($c['success'])
					$result['success'] = true;
			}
			else {
				logger('zot_register_hub: failure to verify returned packet.');
			}
		}
	}

	return $result;
}

/**
 * @brief Takes an associative array of a fetched discovery packet and updates
 *   all internal data structures which need to be updated as a result.
 *
 * @param array $arr => json_decoded discovery packet
 * @param int $ud_flags
 *    Determines whether to create a directory update record if any changes occur, default is UPDATE_FLAGS_UPDATED
 *    $ud_flags = UPDATE_FLAGS_FORCED indicates a forced refresh where we unconditionally create a directory update record
 *      this typically occurs once a month for each channel as part of a scheduled ping to notify the directory
 *      that the channel still exists
 * @param array $ud_arr
 *    If set [typically by update_directory_entry()] indicates a specific update table row and more particularly
 *    contains a particular address (ud_addr) which needs to be updated in that table.
 *
 * @return associative array
 *   * \e boolean \b success boolean true or false
 *   * \e string \b message (optional) error string only if success is false
 */
function import_xchan($arr,$ud_flags = UPDATE_FLAGS_UPDATED, $ud_arr = null) {

	call_hooks('import_xchan', $arr);

	$ret = array('success' => false);
	$dirmode = intval(get_config('system','directory_mode'));

	$changed = false;
	$what = '';

	if(! (is_array($arr) && array_key_exists('success',$arr) && $arr['success'])) {
		logger('import_xchan: invalid data packet: ' . print_r($arr,true));
		$ret['message'] = t('Invalid data packet');
		return $ret;
	}

	if(! ($arr['guid'] && $arr['guid_sig'])) {
		logger('import_xchan: no identity information provided. ' . print_r($arr,true));
		return $ret;
	}

	$xchan_hash = make_xchan_hash($arr['guid'],$arr['guid_sig']);
	$arr['hash'] = $xchan_hash;

	$import_photos = false;

	if(! rsa_verify($arr['guid'],base64url_decode($arr['guid_sig']),$arr['key'])) {
		logger('import_xchan: Unable to verify channel signature for ' . $arr['address']);
		$ret['message'] = t('Unable to verify channel signature');
		return $ret;
	}

	logger('import_xchan: ' . $xchan_hash, LOGGER_DEBUG);

	$r = q("select * from xchan where xchan_hash = '%s' limit 1",
		dbesc($xchan_hash)
	);

	if(! array_key_exists('connect_url', $arr))
		$arr['connect_url'] = '';

	if(strpos($arr['address'],'/') !== false)
		$arr['address'] = substr($arr['address'],0,strpos($arr['address'],'/'));

	if($r) {
		if($r[0]['xchan_photo_date'] != $arr['photo_updated'])
			$import_photos = true;

		// if we import an entry from a site that's not ours and either or both of us is off the grid - hide the entry.
		/** @TODO: check if we're the same directory realm, which would mean we are allowed to see it */

		$dirmode = get_config('system','directory_mode');

		if((($arr['site']['directory_mode'] === 'standalone') || ($dirmode & DIRECTORY_MODE_STANDALONE)) && ($arr['site']['url'] != z_root()))
			$arr['searchable'] = false;

		$hidden = (1 - intval($arr['searchable']));

		$hidden_changed = $adult_changed = $deleted_changed = $pubforum_changed = 0;

		if(intval($r[0]['xchan_hidden']) != (1 - intval($arr['searchable'])))
			$hidden_changed = 1;
		if(intval($r[0]['xchan_selfcensored']) != intval($arr['adult_content']))
			$adult_changed = 1;
		if(intval($r[0]['xchan_deleted']) != intval($arr['deleted']))
			$deleted_changed = 1;
		if(intval($r[0]['xchan_pubforum']) != intval($arr['public_forum']))
			$pubforum_changed = 1;

		if(($r[0]['xchan_name_date'] != $arr['name_updated']) 
			|| ($r[0]['xchan_connurl'] != $arr['connections_url']) 
			|| ($r[0]['xchan_addr'] != $arr['address'])
			|| ($r[0]['xchan_follow'] != $arr['follow_url'])
			|| ($r[0]['xchan_connpage'] != $arr['connect_url']) 
			|| ($r[0]['xchan_url'] != $arr['url'])
			|| $hidden_changed || adult_changed || deleted_changed || $pubforum_changed ) {
			$r = q("update xchan set xchan_name = '%s', xchan_name_date = '%s', xchan_connurl = '%s', xchan_follow = '%s', 
				xchan_connpage = '%s', xchan_hidden = %d, xchan_selfcensored = %d, xchan_deleted = %d, xchan_pubforum = %d, 
				xchan_addr = '%s', xchan_url = '%s' where xchan_hash = '%s'",
				dbesc(($arr['name']) ? $arr['name'] : '-'),
				dbesc($arr['name_updated']),
				dbesc($arr['connections_url']),
				dbesc($arr['follow_url']),
				dbesc($arr['connect_url']),
				intval(1 - intval($arr['searchable'])),
				intval($arr['adult_content']),
				intval($arr['deleted']),
				intval($arr['public_forum']),
				dbesc($arr['address']),
				dbesc($arr['url']),
				dbesc($xchan_hash)
			);

			logger('import_xchan: existing: ' . print_r($r[0],true), LOGGER_DATA);
			logger('import_xchan: new: ' . print_r($arr,true), LOGGER_DATA);
			$what .= 'xchan ';
			$changed = true;
		}
	} else {
		$import_photos = true;

		if((($arr['site']['directory_mode'] === 'standalone')
				|| ($dirmode & DIRECTORY_MODE_STANDALONE))
				&& ($arr['site']['url'] != z_root()))
			$arr['searchable'] = false;

		$x = q("insert into xchan ( xchan_hash, xchan_guid, xchan_guid_sig, xchan_pubkey, xchan_photo_mimetype,
				xchan_photo_l, xchan_addr, xchan_url, xchan_connurl, xchan_follow, xchan_connpage, xchan_name, xchan_network, xchan_photo_date, xchan_name_date, xchan_hidden, xchan_selfcensored, xchan_deleted, xchan_pubforum )
				values ( '%s', '%s', '%s', '%s' , '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, %d, %d) ",
			dbesc($xchan_hash),
			dbesc($arr['guid']),
			dbesc($arr['guid_sig']),
			dbesc($arr['key']),
			dbesc($arr['photo_mimetype']),
			dbesc($arr['photo']),
			dbesc($arr['address']),
			dbesc($arr['url']),
			dbesc($arr['connections_url']),
			dbesc($arr['follow_url']),
			dbesc($arr['connect_url']),
			dbesc(($arr['name']) ? $arr['name'] : '-'),
			dbesc('zot'),
			dbescdate($arr['photo_updated']),
			dbescdate($arr['name_updated']),
			intval(1 - intval($arr['searchable'])),
			intval($arr['adult_content']),
			intval($arr['deleted']),
			intval($arr['public_forum'])
		);

		$what .= 'new_xchan';
		$changed = true;
	}

	if ($import_photos) {

		require_once('include/photo/photo_driver.php');

		// see if this is a channel clone that's hosted locally - which we treat different from other xchans/connections

		$local = q("select channel_account_id, channel_id from channel where channel_hash = '%s' limit 1",
			dbesc($xchan_hash)
		);
		if ($local) {
			$ph = z_fetch_url($arr['photo'], true);
			if ($ph['success']) {
				import_channel_photo($ph['body'], $arr['photo_mimetype'], $local[0]['channel_account_id'],$local[0]['channel_id']);
				// reset the names in case they got messed up when we had a bug in this function
				$photos = array(
					z_root() . '/photo/profile/l/' . $local[0]['channel_id'],
					z_root() . '/photo/profile/m/' . $local[0]['channel_id'],
					z_root() . '/photo/profile/s/' . $local[0]['channel_id'],
					$arr['photo_mimetype'],
					false
				);
			}
		} else {
			$photos = import_xchan_photo($arr['photo'], $xchan_hash);
		}
		if ($photos) {
			if ($photos[4]) {
				// importing the photo failed somehow. Leave the photo_date alone so we can try again at a later date.
				// This often happens when somebody joins the matrix with a bad cert.
				$r = q("update xchan set xchan_photo_l = '%s', xchan_photo_m = '%s', xchan_photo_s = '%s', xchan_photo_mimetype = '%s'
					where xchan_hash = '%s'",
					dbesc($photos[0]),
					dbesc($photos[1]),
					dbesc($photos[2]),
					dbesc($photos[3]),
					dbesc($xchan_hash)
				);
			} else {
				$r = q("update xchan set xchan_photo_date = '%s', xchan_photo_l = '%s', xchan_photo_m = '%s', xchan_photo_s = '%s', xchan_photo_mimetype = '%s'
					where xchan_hash = '%s'",
					dbescdate(datetime_convert('UTC','UTC',$arr['photo_updated'])),
					dbesc($photos[0]),
					dbesc($photos[1]),
					dbesc($photos[2]),
					dbesc($photos[3]),
					dbesc($xchan_hash)
				);
			}
			$what .= 'photo ';
			$changed = true;
		}
	}

	// what we are missing for true hub independence is for any changes in the primary hub to
	// get reflected not only in the hublocs, but also to update the URLs and addr in the appropriate xchan

	$s = sync_locations($arr, $arr);

	if($s) {
		if($s['change_message'])
			$what .= $s['change_message'];
		if($s['changed'])
			$changed = $s['changed'];
		if($s['message'])
			$ret['message'] .= $s['message'];
	}

	// Which entries in the update table are we interested in updating?

	$address = (($ud_arr && $ud_arr['ud_addr']) ? $ud_arr['ud_addr'] : $arr['address']);


	// Are we a directory server of some kind?

	$other_realm = false;
	$realm = get_directory_realm();
	if(array_key_exists('site',$arr)
		&& array_key_exists('realm',$arr['site'])
		&& (strpos($arr['site']['realm'],$realm) === false))
		$other_realm = true;

	if($dirmode != DIRECTORY_MODE_NORMAL) {

		// We're some kind of directory server. However we can only add directory information
		// if the entry is in the same realm (or is a sub-realm). Sub-realms are denoted by
		// including the parent realm in the name. e.g. 'RED_GLOBAL:foo' would allow an entry to
		// be in directories for the local realm (foo) and also the RED_GLOBAL realm.

		if(array_key_exists('profile',$arr) && is_array($arr['profile']) && (! $other_realm)) {
			$profile_changed = import_directory_profile($xchan_hash,$arr['profile'],$address,$ud_flags, 1);
			if($profile_changed) {
				$what .= 'profile ';
				$changed = true;
			}
		} else {
			logger('import_xchan: profile not available - hiding');
			// they may have made it private
			$r = q("delete from xprof where xprof_hash = '%s'",
				dbesc($xchan_hash)
			);
			$r = q("delete from xtag where xtag_hash = '%s' and xtag_flags = 0",
				dbesc($xchan_hash)
			);
		}
	}

	if(array_key_exists('site',$arr) && is_array($arr['site'])) {
		$profile_changed = import_site($arr['site'],$arr['key']);
		if($profile_changed) {
			$what .= 'site ';
			$changed = true;
		}
	}

	if(($changed) || ($ud_flags == UPDATE_FLAGS_FORCED)) {
		$guid = random_string() . '@' . get_app()->get_hostname();
		update_modtime($xchan_hash,$guid,$address,$ud_flags);
		logger('import_xchan: changed: ' . $what,LOGGER_DEBUG);
	}
	elseif(! $ud_flags) {
		// nothing changed but we still need to update the updates record
		q("update updates set ud_flags = ( ud_flags | %d ) where ud_addr = '%s' and not (ud_flags & %d)>0 ",
			intval(UPDATE_FLAGS_UPDATED),
			dbesc($address),
			intval(UPDATE_FLAGS_UPDATED)
		);
	}

	if(! x($ret,'message')) {
		$ret['success'] = true;
		$ret['hash'] = $xchan_hash;
	}

	logger('import_xchan: result: ' . print_r($ret,true), LOGGER_DATA);
	return $ret;
}

/**
 * @brief Called immediately after sending a zot message which is using queue processing.
 *
 * Updates the queue item according to the response result and logs any information
 * returned to aid communications troubleshooting.
 *
 * @param string $hub - url of site we just contacted
 * @param array $arr - output of z_post_url()
 * @param array $outq - The queue structure attached to this request
 */
function zot_process_response($hub, $arr, $outq) {

	if (! $arr['success']) {
		logger('zot_process_response: failed: ' . $hub);
		return;
	}

	$x = json_decode($arr['body'], true);

	if (! $x) {
		logger('zot_process_response: No json from ' . $hub);
		logger('zot_process_response: headers: ' . print_r($arr['header'],true), LOGGER_DATA);
	}

	if(is_array($x) && array_key_exists('delivery_report',$x) && is_array($x['delivery_report'])) {
		foreach($x['delivery_report'] as $xx) {
			if(is_array($xx) && array_key_exists('message_id',$xx) && delivery_report_is_storable($xx)) {
				q("insert into dreport ( dreport_mid, dreport_site, dreport_recip, dreport_result, dreport_time, dreport_xchan ) values ( '%s', '%s','%s','%s','%s','%s' ) ",
					dbesc($xx['message_id']),
					dbesc($xx['location']),
					dbesc($xx['recipient']),
					dbesc($xx['status']),
					dbesc(datetime_convert($xx['date'])),
					dbesc($xx['sender'])
				);
			}
		}
	}

	q("delete from dreport where dreport_queue = '%s' limit 1",
		dbesc($outq['outq_hash'])
	);
								
	// update the timestamp for this site

	q("update site set site_dead = 0, site_update = '%s' where site_url = '%s'",
		dbesc(datetime_convert()),
		dbesc(dirname($hub))
	);

	// synchronous message types are handled immediately
	// async messages remain in the queue until processed.

	if (intval($outq['outq_async'])) {
		q("update outq set outq_delivered = 1, outq_updated = '%s' where outq_hash = '%s' and outq_channel = %d",
			dbesc(datetime_convert()),
			dbesc($outq['outq_hash']),
			intval($outq['outq_channel'])
		);
	} else {
		q("delete from outq where outq_hash = '%s' and outq_channel = %d",
			dbesc($outq['outq_hash']),
			intval($outq['outq_channel'])
		);
	}

	logger('zot_process_response: ' . print_r($x,true), LOGGER_DEBUG);
}

/**
 * @brief
 *
 * We received a notification packet (in mod/post.php) that a message is waiting for us, and we've verified the sender.
 * Now send back a pickup message, using our message tracking ID ($arr['secret']), which we will sign with our site private key.
 * The entire pickup message is encrypted with the remote site's public key.
 * If everything checks out on the remote end, we will receive back a packet containing one or more messages,
 * which will be processed and delivered before this function ultimately returns.
 *
 * @see zot_import()
 *
 * @param array $arr
 *     decrypted and json decoded notify packet from remote site
 * @return array from zot_import()
 */
function zot_fetch($arr) {

	logger('zot_fetch: ' . print_r($arr,true), LOGGER_DATA);

	$url = $arr['sender']['url'] . $arr['callback'];

	// set $multiple param on zot_gethub() to return all matching hubs
	// This allows us to recover from re-installs when a redundant (but invalid) hubloc for
	// this identity is widely dispersed throughout the network. 

	$ret_hubs = zot_gethub($arr['sender'],true);
	if(! $ret_hubs) {
		logger('zot_fetch: no hub: ' . print_r($arr['sender'],true));
		return;
	}

	foreach($ret_hubs as $ret_hub) {
		$data = array(
			'type'    => 'pickup',
			'url'     => z_root(),
			'callback_sig' => base64url_encode(rsa_sign(z_root() . '/post',get_config('system','prvkey'))),	
			'callback' => z_root() . '/post',
			'secret' => $arr['secret'],
			'secret_sig' => base64url_encode(rsa_sign($arr['secret'],get_config('system','prvkey')))
		);

		$datatosend = json_encode(crypto_encapsulate(json_encode($data),$ret_hub['hubloc_sitekey']));

		$fetch = zot_zot($url,$datatosend);

		$result = zot_import($fetch, $arr['sender']['url']);
		
		if($result)
			return $result;
	}

	return;

}

/**
 * @brief Process incoming array of messages.
 *
 * Process an incoming array of messages which were obtained via pickup, and
 * import, update, delete as directed.
 *
 * The message types handled here are 'activity' (e.g. posts), 'mail' ,
 * 'profile', 'location' and 'channel_sync'.
 *
 * @param array $arr
 *  'pickup' structure returned from remote site
 * @param string $sender_url
 *  the url specified by the sender in the initial communication.
 *  We will verify the sender and url in each returned message structure and
 *  also verify that all the messages returned match the site url that we are
 *  currently processing.
 *
 * @returns array
 * suitable for logging remotely, enumerating the processing results of each message/recipient combination
 *  * [0] => \e string $channel_hash
 *  * [1] => \e string $delivery_status
 *  * [2] => \e string $address
 */
function zot_import($arr, $sender_url) {

	$data = json_decode($arr['body'], true);

	if(! $data) {
		logger('zot_import: empty body');
		return array();
	}

	if(array_key_exists('iv', $data)) {
		$data = json_decode(crypto_unencapsulate($data,get_config('system','prvkey')),true);
	}

	if(! $data['success']) {
		if($data['message'])
			logger('remote pickup failed: ' . $data['message']);
		return false;
	}

	$incoming = $data['pickup'];

	$return = array();

	if(is_array($incoming)) {
		foreach($incoming as $i) {
			if(! is_array($i)) {
				logger('incoming is not an array');
				continue;
			}

			$result = null;

			if(array_key_exists('iv',$i['notify'])) {
				$i['notify'] = json_decode(crypto_unencapsulate($i['notify'],get_config('system','prvkey')),true);
			}

			logger('zot_import: notify: ' . print_r($i['notify'],true), LOGGER_DATA);

			$hub = zot_gethub($i['notify']['sender']);
			if((! $hub) || ($hub['hubloc_url'] != $sender_url)) {
				logger('zot_import: potential forgery: wrong site for sender: ' . $sender_url . ' != ' . print_r($i['notify'],true));
				continue;
			}

			$message_request = ((array_key_exists('message_id',$i['notify'])) ? true : false);
			if($message_request)
				logger('processing message request');

			$i['notify']['sender']['hash'] = make_xchan_hash($i['notify']['sender']['guid'],$i['notify']['sender']['guid_sig']);
			$deliveries = null;

			if(array_key_exists('message',$i) && array_key_exists('type',$i['message']) && $i['message']['type'] === 'rating') {
				// rating messages are processed only by directory servers
				logger('Rating received: ' . print_r($arr,true), LOGGER_DATA);
				$result = process_rating_delivery($i['notify']['sender'],$i['message']);
				continue;
			}

			if(array_key_exists('recipients',$i['notify']) && count($i['notify']['recipients'])) {
				logger('specific recipients');
				$recip_arr = array();
				foreach($i['notify']['recipients'] as $recip) {
					if(is_array($recip)) {
						$recip_arr[] =  make_xchan_hash($recip['guid'],$recip['guid_sig']);
					}
				}

				$r = false;
				if($recip_arr) {
					stringify_array_elms($recip_arr);
					$recips = implode(',',$recip_arr);
					$r = q("select channel_hash as hash from channel where channel_hash in ( " . $recips . " ) 
						and channel_removed = 0 ");
				}

				if(! $r) {
					logger('recips: no recipients on this site');
					continue;
				}

				// It's a specifically targetted post. If we were sent a public_scope hint (likely),
				// get rid of it so that it doesn't get stored and cause trouble.

				if(($i) && is_array($i) && array_key_exists('message',$i) && is_array($i['message'])
					&& $i['message']['type'] === 'activity' && array_key_exists('public_scope',$i['message']))
					unset($i['message']['public_scope']);

				$deliveries = $r;

				// We found somebody on this site that's in the recipient list.

			}
			else {
				if(($i['message']) && (array_key_exists('flags',$i['message'])) && (in_array('private',$i['message']['flags'])) && $i['message']['type'] === 'activity') {
					if(array_key_exists('public_scope',$i['message']) && $i['message']['public_scope'] === 'public') {
						// This should not happen but until we can stop it...
						logger('private message was delivered with no recipients.');
						continue;
					}
				}

				logger('public post');

				// Public post. look for any site members who are or may be accepting posts from this sender
				// and who are allowed to see them based on the sender's permissions

				$deliveries = allowed_public_recips($i);

				if($i['message'] && array_key_exists('type',$i['message']) && $i['message']['type'] === 'location') {
					$sys = get_sys_channel();
					$deliveries = array(array('hash' => $sys['xchan_hash']));
				}

				// if the scope is anything but 'public' we're going to store it as private regardless
				// of the private flag on the post.

				if($i['message'] && array_key_exists('public_scope',$i['message'])
					&& $i['message']['public_scope'] !== 'public') {

					if(! array_key_exists('flags',$i['message']))
						$i['message']['flags'] = array();
					if(! in_array('private',$i['message']['flags']))
						$i['message']['flags'][] = 'private';
				}
			}

			// Go through the hash array and remove duplicates. array_unique() won't do this because the array is more than one level.

			$no_dups = array();
			if($deliveries) {
				foreach($deliveries as $d) {
					if(! in_array($d['hash'],$no_dups))
						$no_dups[] = $d['hash'];
				}

				if($no_dups) {
					$deliveries = array();
					foreach($no_dups as $n) {
						$deliveries[] = array('hash' => $n);
					}
				}
			}

			if(! $deliveries) {
				logger('zot_import: no deliveries on this site');
				continue;
			}

			if($i['message']) {
				if($i['message']['type'] === 'activity') {
					$arr = get_item_elements($i['message']);

					$v = validate_item_elements($i['message'],$arr);

					if(! $v['success']) {
						logger('Activity rejected: ' . $v['message'] . ' ' . print_r($i['message'],true));
						continue;
					}

					logger('Activity received: ' . print_r($arr,true), LOGGER_DATA);
					logger('Activity recipients: ' . print_r($deliveries,true), LOGGER_DATA);

					$relay = ((array_key_exists('flags',$i['message']) && in_array('relay',$i['message']['flags'])) ? true : false);
					$result = process_delivery($i['notify']['sender'],$arr,$deliveries,$relay,false,$message_request);
				}
				elseif($i['message']['type'] === 'mail') {
					$arr = get_mail_elements($i['message']);

					logger('Mail received: ' . print_r($arr,true), LOGGER_DATA);
					logger('Mail recipients: ' . print_r($deliveries,true), LOGGER_DATA);

					$result = process_mail_delivery($i['notify']['sender'],$arr,$deliveries);
				}
				elseif($i['message']['type'] === 'profile') {
					$arr = get_profile_elements($i['message']);

					logger('Profile received: ' . print_r($arr,true), LOGGER_DATA);
					logger('Profile recipients: ' . print_r($deliveries,true), LOGGER_DATA);

					$result = process_profile_delivery($i['notify']['sender'],$arr,$deliveries);
				}
				elseif($i['message']['type'] === 'channel_sync') {
					// $arr = get_channelsync_elements($i['message']);

					$arr = $i['message'];

					logger('Channel sync received: ' . print_r($arr,true), LOGGER_DATA);
					logger('Channel sync recipients: ' . print_r($deliveries,true), LOGGER_DATA);

					$result = process_channel_sync_delivery($i['notify']['sender'],$arr,$deliveries);
				}
				elseif($i['message']['type'] === 'location') {
					$arr = $i['message'];

					logger('Location message received: ' . print_r($arr,true), LOGGER_DATA);
					logger('Location message recipients: ' . print_r($deliveries,true), LOGGER_DATA);

					$result = process_location_delivery($i['notify']['sender'],$arr,$deliveries);
				}
			}
			if($result){
				$return = array_merge($return, $result);
			}
		}
	}

	return $return;
}

/**
 * @brief
 *
 * A public message with no listed recipients can be delivered to anybody who
 * has PERMS_NETWORK for that type of post, PERMS_AUTHED (in-network senders are
 * by definition authenticated) or PERMS_SITE and is one the same site,
 * or PERMS_SPECIFIC and the sender is a contact who is granted permissions via
 * their connection permissions in the address book.
 * Here we take a given message and construct a list of hashes of everybody
 * on the site that we should try and deliver to.
 * Some of these will be rejected, but this gives us a place to start.
 *
 * @param array $msg
 * @return NULL|array
 */
function public_recips($msg) {

	require_once('include/identity.php');

	$check_mentions = false;
	$include_sys = false;

	if($msg['message']['type'] === 'activity') {
		if(! get_config('system','disable_discover_tab'))
			$include_sys = true;
		$col = 'channel_w_stream';
		$field = PERMS_W_STREAM;
		if(array_key_exists('flags',$msg['message']) && in_array('thread_parent', $msg['message']['flags'])) {
			// check mention recipient permissions on top level posts only
			$check_mentions = true;
		}
		else {

			// This doesn't look like it works so I have to explain what happened. These are my
			// notes (below) from when I got this section of code working. You would think that
			// we only have to find those with the requisite stream or comment permissions,
			// depending on whether this is a top-level post or a comment - but you would be wrong.

			// ... so public_recips and allowed_public_recips is working so much better
			// than before, but was still not quite right. We seem to be getting all the right
			// results for top-level posts now, but comments aren't getting through on channels
			// for which we've allowed them to send us their stream, but not comment on our posts.
			// The reason is we were seeing if they could comment - and we only need to do that if
			// we own the post. If they own the post, we only need to check if they can send us their stream.

			// if this is a comment and it wasn't sent by the post owner, check to see who is allowing them to comment.
			// We should have one specific recipient and this step shouldn't be needed unless somebody stuffed up
			// their software. We may need this step to protect us from bad guys intentionally stuffing up their software.
			// If it is sent by the post owner, we don't need to do this. We only need to see who is receiving the
			// owner's stream (which was already set above) - as they control the comment permissions, not us.

			// Note that by doing this we introduce another bug because some public forums have channel_w_stream
			// permissions set to themselves only. We also need in this function to add these public forums to the
			// public recipient list based on if they are tagged or not and have tag permissions. This is complicated
			// by the fact that this activity doesn't have the public forum tag. It's the parent activity that
			// contains the tag. we'll solve that further below.

			if($msg['notify']['sender']['guid_sig'] != $msg['message']['owner']['guid_sig']) {
				$col = 'channel_w_comment';
				$field = PERMS_W_COMMENT;
			}
		}
	}
	elseif($msg['message']['type'] === 'mail') {
		$col = 'channel_w_mail';
		$field = PERMS_W_MAIL;
	}

	if(! $col)
		return NULL;

	$col = dbesc($col);

	// First find those channels who are accepting posts from anybody, or at least
	// something greater than just their connections.

	if($msg['notify']['sender']['url'] === z_root()) {
		$sql = " where (( " . $col . " & " . intval(PERMS_NETWORK) . " ) > 0
					or (  " . $col . " & " . intval(PERMS_SITE) . " ) > 0
					or (  " . $col . " & " . intval(PERMS_PUBLIC) . ") > 0
					or (  " . $col . " & " . intval(PERMS_AUTHED)  . ") > 0 ) ";
	} else {
		$sql = " where ( " . $col . " = " . intval(PERMS_NETWORK) . " 
					or " . $col . " = " . intval(PERMS_PUBLIC) . "
					or " . $col . " = " . intval(PERMS_AUTHED) . " ) ";
	}

	$r = q("select channel_hash as hash from channel $sql or channel_hash = '%s'
		and channel_removed = 0 ",
		dbesc($msg['notify']['sender']['hash'])
	);

	if(! $r)
		$r = array();

	// Now we have to get a bit dirty. Find every channel that has the sender in their connections (abook)
	// and is allowing this sender at least at a high level.

	$x = q("select channel_hash as hash from channel left join abook on abook_channel = channel_id
		where abook_xchan = '%s' and channel_removed = 0
		and (( " . $col . " = " . intval(PERMS_SPECIFIC) . " and ( abook_my_perms & " . intval($field) . " ) > 0 )
		OR   " . $col . " = " . intval(PERMS_PENDING) . " 
		OR  ( " . $col . " = " . intval(PERMS_CONTACTS) . " and abook_pending = 0 )) ",
		dbesc($msg['notify']['sender']['hash'])
	);

	if(! $x)
		$x = array();

	$r = array_merge($r,$x);

	//logger('message: ' . print_r($msg['message'],true));

	if($include_sys && array_key_exists('public_scope',$msg['message']) && $msg['message']['public_scope'] === 'public') {
		$sys = get_sys_channel();
		if($sys)
			$r[] = array('hash' => $sys['channel_hash']);
	}

	// look for any public mentions on this site
	// They will get filtered by tgroup_check() so we don't need to check permissions now

	if($check_mentions) {
		// It's a top level post. Look at the tags. See if any of them are mentions and are on this hub.
		if($msg['message']['tags']) {
			if(is_array($msg['message']['tags']) && $msg['message']['tags']) {
				foreach($msg['message']['tags'] as $tag) {
					if(($tag['type'] === 'mention') && (strpos($tag['url'],z_root()) !== false)) {
						$address = basename($tag['url']);
						if($address) {
							$z = q("select channel_hash as hash from channel where channel_address = '%s' 
								and channel_removed = 0 limit 1",
								dbesc($address)
							);
							if($z)
								$r = array_merge($r,$z);
						}
					}
				}
			}
		}
	}
	else {
		// This is a comment. We need to find any parent with ITEM_UPLINK set. But in fact, let's just return
		// everybody that stored a copy of the parent. This way we know we're covered. We'll check the
		// comment permissions when we deliver them.

		if($msg['message']['message_top']) {
			$z = q("select owner_xchan as hash from item where parent_mid = '%s' ",
				dbesc($msg['message']['message_top'])
			);
			if($z)
				$r = array_merge($r,$z);
		}
	}

	// There are probably a lot of duplicates in $r at this point. We need to filter those out.
	// It's a bit of work since it's a multi-dimensional array

	if($r) {
		$uniq = array();

		foreach($r as $rr) {
			if(! in_array($rr['hash'],$uniq))
				$uniq[] = $rr['hash'];
		}
		$r = array();
		foreach($uniq as $rr) {
			$r[] = array('hash' => $rr);
		}
	}

	logger('public_recips: ' . print_r($r,true), LOGGER_DATA);
	return $r;
}

/**
 * @brief
 *
 * This is the second part of public_recipes().
 * We'll find all the channels willing to accept public posts from us, then
 * match them against the sender privacy scope and see who in that list that
 * the sender is allowing.
 *
 * @see public_recipes()
 * @param array $msg
 * @return array
 */
function allowed_public_recips($msg) {

	logger('allowed_public_recips: ' . print_r($msg,true),LOGGER_DATA);

	if(array_key_exists('public_scope',$msg['message']))
		$scope = $msg['message']['public_scope'];

	// Mail won't have a public scope.
	// in fact, it's doubtful mail will ever get here since it almost universally
	// has a recipient, but in fact we don't require this, so it's technically
	// possible to send mail to anybody that's listening.

	$recips = public_recips($msg);

	if(! $recips)
		return $recips;

	if($msg['message']['type'] === 'mail')
		return $recips;

	if($scope === 'public' || $scope === 'network: red' || $scope === 'authenticated')
		return $recips;

	if(strpos($scope,'site:') === 0) {
		if(($scope === 'site: ' . get_app()->get_hostname()) && ($msg['notify']['sender']['url'] === z_root()))
			return $recips;
		else
			return array();
	}

	$hash = make_xchan_hash($msg['notify']['sender']['guid'],$msg['notify']['sender']['guid_sig']);

	if($scope === 'self') {
		foreach($recips as $r)
			if($r['hash'] === $hash)
				return array('hash' => $hash);
	}

	// note: we shouldn't ever see $scope === 'specific' in this function, but handle it anyway

	if($scope === 'contacts' || $scope === 'any connections' || $scope === 'specific') {
		$condensed_recips = array();
		foreach($recips as $rr)
			$condensed_recips[] = $rr['hash'];

		$results = array();
		$r = q("select channel_hash as hash from channel left join abook on abook_channel = channel_id where abook_xchan = '%s' and channel_removed = 0 ",
			dbesc($hash)
		);
		if($r) {
			foreach($r as $rr)
				if(in_array($rr['hash'],$condensed_recips))
					$results[] = array('hash' => $rr['hash']);
		}
		return $results;
	}

	return array();
}

/**
 * @brief
 *
 * @param array $sender
 * @param array $arr
 * @param array $deliveries
 * @param boolean $relay
 * @param boolean $public (optional) default false
 * @param boolean $request (optional) default false
 * @return array
 */
function process_delivery($sender, $arr, $deliveries, $relay, $public = false, $request = false) {

	$result = array();

	$result['site'] = z_root();

	// We've validated the sender. Now make sure that the sender is the owner or author

	if(! $public) {
		if($sender['hash'] != $arr['owner_xchan'] && $sender['hash'] != $arr['author_xchan']) {
			logger("process_delivery: sender {$sender['hash']} is not owner {$arr['owner_xchan']} or author {$arr['author_xchan']} - mid {$arr['mid']}");
			return;
		}
	}


	foreach($deliveries as $d) {
		$local_public = $public;

		$DR = new DReport(z_root(),$sender['hash'],$d['hash'],$arr['mid']);

		$r = q("select * from channel where channel_hash = '%s' limit 1",
			dbesc($d['hash'])
		);

		if(! $r) {
			$DR->update('recipient not found');
			$result[] = $DR->get();
			continue;
		}

		$channel = $r[0];
		$DR->addto_recipient($channel['channel_name'] . ' <' . $channel['channel_address'] . '@' . get_app()->get_hostname() . '>');

		/**
		 * @FIXME: Somehow we need to block normal message delivery from our clones, as the delivered
		 * message doesn't have ACL information in it as the cloned copy does. That copy 
		 * will normally arrive first via sync delivery, but this isn't guaranteed. 
		 * There's a chance the current delivery could take place before the cloned copy arrives
		 * hence the item could have the wrong ACL and *could* be used in subsequent deliveries or
		 * access checks. So far all attempts at identifying this situation precisely
		 * have caused issues with delivery of relayed comments. 
		 */

//		if(($d['hash'] === $sender['hash']) && ($sender['url'] !== z_root()) && (! $relay)) {
//			$DR->update('self delivery ignored');
//			$result[] = $DR->get();
//			continue;
//		}

		// allow public postings to the sys channel regardless of permissions, but not
		// for comments travelling upstream. Wait and catch them on the way down.
		// They may have been blocked by the owner.

		if(intval($channel['channel_system']) && (! $arr['item_private']) && (! $relay)) {
			$local_public = true;

			$r = q("select xchan_selfcensored from xchan where xchan_hash = '%s' limit 1",
				dbesc($sender['hash'])
			);
			// don't import sys channel posts from selfcensored authors
			if($r && (intval($r[0]['xchan_selfcensored']))) {
				$local_public = false;
				continue;
			}
		}

		$tag_delivery = tgroup_check($channel['channel_id'],$arr);

		$perm = 'send_stream';
		if(($arr['mid'] !== $arr['parent_mid']) && ($relay))
			$perm = 'post_comments';

		// This is our own post, possibly coming from a channel clone

		if($arr['owner_xchan'] == $d['hash']) {
			$arr['item_wall'] = 1;
		}
		else {
			$arr['item_wall'] = 0;
		}

		if((! perm_is_allowed($channel['channel_id'],$sender['hash'],$perm)) && (! $tag_delivery) && (! $local_public)) {
			logger("permission denied for delivery to channel {$channel['channel_id']} {$channel['channel_address']}");
			$DR->update('permission denied');
			$result[] = $DR->get();
			continue;
		}

		if($arr['mid'] != $arr['parent_mid']) {

			// check source route.
			// We are only going to accept comments from this sender if the comment has the same route as the top-level-post,
			// this is so that permissions mismatches between senders apply to the entire conversation
			// As a side effect we will also do a preliminary check that we have the top-level-post, otherwise
			// processing it is pointless.

			$r = q("select route, id from item where mid = '%s' and uid = %d limit 1",
				dbesc($arr['parent_mid']),
				intval($channel['channel_id'])
			);
			if(! $r) {
				$DR->update('comment parent not found');
				$result[] = $DR->get();

				// We don't seem to have a copy of this conversation or at least the parent
				// - so request a copy of the entire conversation to date.
				// Don't do this if it's a relay post as we're the ones who are supposed to
				// have the copy and we don't want the request to loop.
				// Also don't do this if this comment came from a conversation request packet.
				// It's possible that comments are allowed but posting isn't and that could
				// cause a conversation fetch loop. We can detect these packets since they are
				// delivered via a 'notify' packet type that has a message_id element in the
				// initial zot packet (just like the corresponding 'request' packet type which
				// makes the request).
				// We'll also check the send_stream permission - because if it isn't allowed,
				// the top level post is unlikely to be imported and
				// this is just an exercise in futility.

				if((! $relay) && (! $request) && (! $local_public)
					&& perm_is_allowed($channel['channel_id'],$sender['hash'],'send_stream')) {
					proc_run('php', 'include/notifier.php', 'request', $channel['channel_id'], $sender['hash'], $arr['parent_mid']);
				}
				continue;
			}
			if($relay) {
				// reset the route in case it travelled a great distance upstream
				// use our parent's route so when we go back downstream we'll match
				// with whatever route our parent has.
				$arr['route'] = $r[0]['route'];
			}
			else {

				// going downstream check that we have the same upstream provider that
				// sent it to us originally. Ignore it if it came from another source
				// (with potentially different permissions).
				// only compare the last hop since it could have arrived at the last location any number of ways.
				// Always accept empty routes and firehose items (route contains 'undefined') .

				$existing_route = explode(',', $r[0]['route']);
				$routes = count($existing_route);
				if($routes) {
					$last_hop = array_pop($existing_route);
					$last_prior_route = implode(',',$existing_route);
				}
				else {
					$last_hop = '';
					$last_prior_route = '';
				}

				if(in_array('undefined',$existing_route) || $last_hop == 'undefined' || $sender['hash'] == 'undefined')
					$last_hop = '';

				$current_route = (($arr['route']) ? $arr['route'] . ',' : '') . $sender['hash'];

				if($last_hop && $last_hop != $sender['hash']) {
					logger('comment route mismatch: parent route = ' . $r[0]['route'] . ' expected = ' . $current_route, LOGGER_DEBUG);
					logger('comment route mismatch: parent msg = ' . $r[0]['id'],LOGGER_DEBUG);
					$DR->update('comment route mismatch');
					$result[] = $DR->get();
					continue;
				}

				// we'll add sender['hash'] onto this when we deliver it. $last_prior_route now has the previously stored route
				// *except* for the sender['hash'] which would've been the last hop before it got to us.

				$arr['route'] = $last_prior_route;
			}
		}

		$ab = q("select * from abook where abook_channel = %d and abook_xchan = '%s'",
			intval($channel['channel_id']),
			dbesc($arr['owner_xchan'])
		);
		$abook = (($ab) ? $ab[0] : null); 

		if(intval($arr['item_deleted'])) {

			// remove_community_tag is a no-op if this isn't a community tag activity
			remove_community_tag($sender,$arr,$channel['channel_id']);

			// set these just in case we need to store a fresh copy of the deleted post.
			// This could happen if the delete got here before the original post did.

			$arr['aid'] = $channel['channel_account_id'];
			$arr['uid'] = $channel['channel_id'];

			$item_id = delete_imported_item($sender,$arr,$channel['channel_id'],$relay);
			$DR->update(($item_id) ? 'deleted' : 'delete_failed');			
			$result[] = $DR->get();

			if($relay && $item_id) {
				logger('process_delivery: invoking relay');
				proc_run('php','include/notifier.php','relay',intval($item_id));
				$DR->update('relayed');
				$result[] = $DR->get();
			}

			continue;
		}

		$r = q("select * from item where mid = '%s' and uid = %d limit 1",
			dbesc($arr['mid']),
			intval($channel['channel_id'])
		);
		if($r) {
			// We already have this post.
			$item_id = $r[0]['id'];

			if(intval($r[0]['item_deleted'])) {
				// It was deleted locally. 
				$DR->update('update ignored');
				$result[] = $DR->get();

				continue;
			}
			// Maybe it has been edited?
			elseif($arr['edited'] > $r[0]['edited']) {
				$arr['id'] = $r[0]['id'];
				$arr['uid'] = $channel['channel_id'];
				if(($arr['mid'] == $arr['parent_mid']) && (! post_is_importable($arr,$abook))) {
					$DR->update('update ignored');
					$result[] = $DR->get();
				}
				else {
					update_imported_item($sender,$arr,$r[0],$channel['channel_id']);
					$DR->update('updated');
					$result[] = $DR->get();
					if(! $relay)
						add_source_route($item_id,$sender['hash']);
				}
			}
			else {
				$DR->update('update ignored');
				$result[] = $DR->get();


				// We need this line to ensure wall-to-wall comments are relayed (by falling through to the relay bit), 
				// and at the same time not relay any other relayable posts more than once, because to do so is very wasteful. 
				if(! intval($r[0]['item_origin']))
					continue;
			}
		}
		else {
			$arr['aid'] = $channel['channel_account_id'];
			$arr['uid'] = $channel['channel_id'];

			// if it's a sourced post, call the post_local hooks as if it were
			// posted locally so that crosspost connectors will be triggered.

			if(check_item_source($arr['uid'], $arr))
				call_hooks('post_local', $arr);
				
			$item_id = 0;

			if(($arr['mid'] == $arr['parent_mid']) && (! post_is_importable($arr,$abook))) {
				$DR->update('post ignored');
				$result[] = $DR->get();
			}
			else {
				$item_result = item_store($arr);
				if($item_result['success']) {
					$item_id = $item_result['item_id'];
					$parr = array('item_id' => $item_id,'item' => $arr,'sender' => $sender,'channel' => $channel);
					call_hooks('activity_received',$parr);
					// don't add a source route if it's a relay or later recipients will get a route mismatch
					if(! $relay)
						add_source_route($item_id,$sender['hash']);
				}
				$DR->update(($item_id) ? 'posted' : 'storage failed: ' . $item_result['message']);
				$result[] = $DR->get();
			}
		}

		if($relay && $item_id) {
			logger('process_delivery: invoking relay');
			proc_run('php','include/notifier.php','relay',intval($item_id));
			$DR->addto_update('relayed');
			$result[] = $DR->get();
		}
	}

	if(! $deliveries)
		$result[] = array('', 'no recipients', '', $arr['mid']);

	logger('process_delivery: local results: ' . print_r($result, true), LOGGER_DEBUG);

	return $result;
}

/**
 * @brief
 *
 * @param array $sender an associative array with
 *   * \e string \b hash a xchan_hash
 * @param array $arr an associative array
 *   * \e int \b verb
 *   * \e int \b obj_type
 *   * \e int \b mid
 * @param int $uid
 */
function remove_community_tag($sender, $arr, $uid) {

	if(! (activity_match($arr['verb'], ACTIVITY_TAG) && ($arr['obj_type'] == ACTIVITY_OBJ_TAGTERM)))
		return;

	logger('remove_community_tag: invoked');

	if(! get_pconfig($uid,'system','blocktags')) {
		logger('remove_community tag: permission denied.');
		return;
	}

	$r = q("select * from item where mid = '%s' and uid = %d limit 1",
		dbesc($arr['mid']),
		intval($uid)
	);
	if(! $r) {
		logger('remove_community_tag: no item');
		return;
	}

	if(($sender['hash'] != $r[0]['owner_xchan']) && ($sender['hash'] != $r[0]['author_xchan'])) {
		logger('remove_community_tag: sender not authorised.');
		return;
	}

	$i = $r[0];

	if($i['target'])
		$i['target'] = json_decode_plus($i['target']);
	if($i['object'])
		$i['object'] = json_decode_plus($i['object']);

	if(! ($i['target'] && $i['object'])) {
		logger('remove_community_tag: no target/object');
		return;
	}

	$message_id = $i['target']['id'];

	$r = q("select id from item where mid = '%s' and uid = %d limit 1",
		dbesc($message_id),
		intval($uid)
	);
	if(! $r) {
		logger('remove_community_tag: no parent message');
		return;
	}

	q("delete from term where uid = %d and oid = %d and otype = %d and type = %d and term = '%s' and url = '%s'",
		intval($uid),
		intval($r[0]['id']),
		intval(TERM_OBJ_POST),
		intval(TERM_HASHTAG),
		dbesc($i['object']['title']),
		dbesc(get_rel_link($i['object']['link'],'alternate'))
	);
}

/**
 * @brief Just calls item_store_update() and logs result.
 *
 * @see item_store_update()
 * @param array $sender (unused)
 * @param array $item
 * @param int $uid (unused)
 */
function update_imported_item($sender, $item, $orig, $uid) {


	$x = item_store_update($item);

	// If we're updating an event that we've saved locally, we store the item info first
	// because event_addtocal will parse the body to get the 'new' event details

	if($orig['resource_type'] === 'event') {
		$res = event_addtocal($orig['id'],$uid);
		if(! $res)
			logger('update event: failed');
	}

	if(! $x['item_id'])
		logger('update_imported_item: failed: ' . $x['message']);
	else
		logger('update_imported_item');
}

/**
 * @brief Deletes an imported item.
 *
 * @param array $sender
 *   * \e string \b hash a xchan_hash
 * @param array $item
 * @param int $uid
 * @param boolean $relay
 * @return boolean|int post_id
 */
function delete_imported_item($sender, $item, $uid, $relay) {

	logger('delete_imported_item invoked', LOGGER_DEBUG);

	$ownership_valid = false;
	$item_found = false;
	$post_id = 0;

	$r = q("select id, author_xchan, owner_xchan, source_xchan, item_deleted from item where ( author_xchan = '%s' or owner_xchan = '%s' or source_xchan = '%s' )
		and mid = '%s' and uid = %d limit 1",
		dbesc($sender['hash']),
		dbesc($sender['hash']),
		dbesc($sender['hash']),
		dbesc($item['mid']),
		intval($uid)
	);

	if ($r) {
		if ($r[0]['author_xchan'] === $sender['hash'] || $r[0]['owner_xchan'] === $sender['hash'] || $r[0]['source_xchan'] === $sender['hash'])
			$ownership_valid = true;

		$post_id = $r[0]['id'];
		$item_found = true;
	} else {

		// perhaps the item is still in transit and the delete notification got here before the actual item did. Store it with the deleted flag set.
		// item_store() won't try to deliver any notifications or start delivery chains if this flag is set.
		// This means we won't end up with potentially even more delivery threads trying to push this delete notification.
		// But this will ensure that if the (undeleted) original post comes in at a later date, we'll reject it because it will have an older timestamp.

		logger('delete received for non-existent item - storing item data.');

		/** @BUG $arr is undefined here, so this is dead code */
		if ($arr['author_xchan'] === $sender['hash'] || $arr['owner_xchan'] === $sender['hash'] || $arr['source_xchan'] === $sender['hash']) {
			$ownership_valid = true;
			$item_result = item_store($arr);
			$post_id = $item_result['item_id'];
		}
	}

	if ($ownership_valid === false) {
		logger('delete_imported_item: failed: ownership issue');
		return false;
	}

	require_once('include/items.php');

	if ($item_found) {
		if (intval($r[0]['item_deleted'])) {
			logger('delete_imported_item: item was already deleted');
			if (! $relay)
				return false;

			// This is a bit hackish, but may have to suffice until the notification/delivery loop is optimised
			// a bit further. We're going to strip the ITEM_ORIGIN on this item if it's a comment, because
			// it was already deleted, and we're already relaying, and this ensures that no other process or
			// code path downstream can relay it again (causing a loop). Since it's already gone it's not coming
			// back, and we aren't going to (or shouldn't at any rate) delete it again in the future - so losing
			// this information from the metadata should have no other discernible impact.

			if (($r[0]['id'] != $r[0]['parent']) && intval($r[0]['item_origin'])) {
				q("update item set item_origin = 0 where id = %d and uid = %d",
					intval($r[0]['id']),
					intval($r[0]['uid'])
				);
			}
		}

		require_once('include/items.php');

		// Use phased deletion to set the deleted flag, call both tag_deliver and the notifier to notify downstream channels
		// and then clean up after ourselves with a cron job after several days to do the delete_item_lowlevel() (DROPITEM_PHASE2).

		drop_item($post_id, false, DROPITEM_PHASE1);
		tag_deliver($uid, $post_id);
	}

	return $post_id;
}

function process_mail_delivery($sender, $arr, $deliveries) {

	$result = array();

	if($sender['hash'] != $arr['from_xchan']) {
		logger('process_mail_delivery: sender is not mail author');
		return;
	}

	foreach($deliveries as $d) {

		$DR = new DReport(z_root(),$sender['hash'],$d['hash'],$arr['mid']);

		$r = q("select * from channel where channel_hash = '%s' limit 1",
			dbesc($d['hash'])
		);

		if(! $r) {
			$DR->update('recipient not found');
			$result[] = $DR->get();
			continue;
		}

		$channel = $r[0];
		$DR->addto_recipient($channel['channel_name'] . ' <' . $channel['channel_address'] . '@' . get_app()->get_hostname() . '>');

		if(! perm_is_allowed($channel['channel_id'],$sender['hash'],'post_mail')) {
			logger("permission denied for mail delivery {$channel['channel_id']}");
			$DR->update('permission denied');
			$result[] = $DR->get();
			continue;
		}

		$r = q("select id from mail where mid = '%s' and channel_id = %d limit 1",
			dbesc($arr['mid']),
			intval($channel['channel_id'])
		);
		if($r) {
			if(intval($arr['mail_recalled'])) {
				$x = q("delete from mail where id = %d and channel_id = %d",
					intval($r[0]['id']),
					intval($channel['channel_id'])
				);
				$DR->update('mail recalled');
				$result[] = $DR->get();
				logger('mail_recalled');
			}
			else {
				$DR->update('duplicate mail received');
				$result[] = $DR->get();
				logger('duplicate mail received');
			}
			continue;
		}
		else {
			$arr['account_id'] = $channel['channel_account_id'];
			$arr['channel_id'] = $channel['channel_id'];
			$item_id = mail_store($arr);
			$DR->update('mail delivered');
			$result[] = $DR->get();
		}
	}

	return $result;
}

/**
 * @brief Processes delivery of rating.
 *
 * @param array $sender
 *   * \e string \b hash a xchan_hash
 * @param array $arr
 */
function process_rating_delivery($sender, $arr) {

	logger('process_rating_delivery: ' . print_r($arr,true));

	if(! $arr['target'])
		return;

	$z = q("select xchan_pubkey from xchan where xchan_hash = '%s' limit 1",
		dbesc($sender['hash'])
	);

	if((! $z) || (! rsa_verify($arr['target'] . '.' . $arr['rating'] . '.' . $arr['rating_text'], base64url_decode($arr['signature']),$z[0]['xchan_pubkey']))) {
		logger('failed to verify rating');
		return;
	}

	$r = q("select * from xlink where xlink_xchan = '%s' and xlink_link = '%s' and xlink_static = 1 limit 1",
		dbesc($sender['hash']),
		dbesc($arr['target'])
	);

	if($r) {
		if($r[0]['xlink_updated'] >= $arr['edited']) {
			logger('rating message duplicate');
			return;
		}

		$x = q("update xlink set xlink_rating = %d, xlink_rating_text = '%s', xlink_sig = '%s', xlink_updated = '%s' where xlink_id = %d",
			intval($arr['rating']),
			dbesc($arr['rating_text']),
			dbesc($arr['signature']),
			dbesc(datetime_convert()),
			intval($r[0]['xlink_id'])
		);
		logger('rating updated');
	}
	else {
		$x = q("insert into xlink ( xlink_xchan, xlink_link, xlink_rating, xlink_rating_text, xlink_sig, xlink_updated, xlink_static )
			values( '%s', '%s', %d, '%s', '%s', '%s', 1 ) ",
			dbesc($sender['hash']),
			dbesc($arr['target']),
			intval($arr['rating']),
			dbesc($arr['rating_text']),
			dbesc($arr['signature']),
			dbesc(datetime_convert())
		);
		logger('rating created');
	}
}

/**
 * @brief Processes delivery of profile.
 *
 * @see import_directory_profile()
 * @param array $sender an associative array
 *   * \e string \b hash a xchan_hash
 * @param array $arr
 * @param array $deliveries (unused)
 */
function process_profile_delivery($sender, $arr, $deliveries) {

	logger('process_profile_delivery', LOGGER_DEBUG);

	$r = q("select xchan_addr from xchan where xchan_hash = '%s' limit 1",
			dbesc($sender['hash'])
	);
	if($r)
		import_directory_profile($sender['hash'], $arr, $r[0]['xchan_addr'], UPDATE_FLAGS_UPDATED, 0);
}

function process_location_delivery($sender,$arr,$deliveries) {

	// deliveries is irrelevant
	logger('process_location_delivery', LOGGER_DEBUG);

	$r = q("select xchan_pubkey from xchan where xchan_hash = '%s' limit 1",
			dbesc($sender['hash'])
	);
	if($r)
		$sender['key'] = $r[0]['xchan_pubkey'];

	if(array_key_exists('locations',$arr) && $arr['locations']) {
		$x = sync_locations($sender,$arr,true);
		logger('process_location_delivery: results: ' . print_r($x,true), LOGGER_DEBUG);
		if($x['changed']) {
			$guid = random_string() . '@' . get_app()->get_hostname();
			update_modtime($sender['hash'],$sender['guid'],$arr['locations'][0]['address'],UPDATE_FLAGS_UPDATED);
		}
	}
}

/**
 * @brief Synchronises locations.
 *
 * @param array $sender
 * @param array $arr
 * @param boolean $absolute (optional) default false
 * @return array
 */
function sync_locations($sender, $arr, $absolute = false) {

	$ret = array();

	if($arr['locations']) {

		$xisting = q("select hubloc_id, hubloc_url, hubloc_sitekey from hubloc where hubloc_hash = '%s'",
			dbesc($sender['hash'])
		);

		// See if a primary is specified

		$has_primary = false;
		foreach($arr['locations'] as $location) {
			if($location['primary']) {
				$has_primary = true;
				break;
			}
		}

		// Ensure that they have one primary hub

		if(! $has_primary)
			$arr['locations'][0]['primary'] = true;

		foreach($arr['locations'] as $location) {
			if(! rsa_verify($location['url'],base64url_decode($location['url_sig']),$sender['key'])) {
				logger('sync_locations: Unable to verify site signature for ' . $location['url']);
				$ret['message'] .= sprintf( t('Unable to verify site signature for %s'), $location['url']) . EOL;
				continue;
			}

			for($x = 0; $x < count($xisting); $x ++) {
				if(($xisting[$x]['hubloc_url'] === $location['url'])
					&& ($xisting[$x]['hubloc_sitekey'] === $location['sitekey'])) {
					$xisting[$x]['updated'] = true;
				}
			}

			if(! $location['sitekey']) {
				logger('sync_locations: empty hubloc sitekey. ' . print_r($location,true));
				continue;
			}

			// Catch some malformed entries from the past which still exist

			if(strpos($location['address'],'/') !== false)
				$location['address'] = substr($location['address'],0,strpos($location['address'],'/'));

			// match as many fields as possible in case anything at all changed.

			$r = q("select * from hubloc where hubloc_hash = '%s' and hubloc_guid = '%s' and hubloc_guid_sig = '%s' and hubloc_url = '%s' and hubloc_url_sig = '%s' and hubloc_host = '%s' and hubloc_addr = '%s' and hubloc_callback = '%s' and hubloc_sitekey = '%s' ",
				dbesc($sender['hash']),
				dbesc($sender['guid']),
				dbesc($sender['guid_sig']),
				dbesc($location['url']),
				dbesc($location['url_sig']),
				dbesc($location['host']),
				dbesc($location['address']),
				dbesc($location['callback']),
				dbesc($location['sitekey'])
			);
			if($r) {
				logger('sync_locations: hub exists: ' . $location['url'], LOGGER_DEBUG);

				// update connection timestamp if this is the site we're talking to
				// This only happens when called from import_xchan

				$current_site = false;

				if(array_key_exists('site',$arr) && $location['url'] == $arr['site']['url']) {
					q("update hubloc set hubloc_connected = '%s', hubloc_updated = '%s' where hubloc_id = %d",
						dbesc(datetime_convert()),
						dbesc(datetime_convert()),
						intval($r[0]['hubloc_id'])
					);
					$current_site = true;
				}

				if($current_site && intval($r[0]['hubloc_error'])) {
					q("update hubloc set hubloc_error = 0 where hubloc_id = %d",
						intval($r[0]['hubloc_id'])
					);
					if(intval($r[0]['hubloc_orphancheck'])) {
						q("update hubloc set hubloc_orphancheck = 0 where hubloc_id = %d",
							intval($r[0]['hubloc_id'])
						);
					}
					q("update xchan set xchan_orphan = 0 where xchan_orphan = 1 and xchan_hash = '%s'",
						dbesc($sender['hash'])
					);
				}

				// Remove pure duplicates
				if(count($r) > 1) {
					for($h = 1; $h < count($r); $h ++) {
						q("delete from hubloc where hubloc_id = %d",
							intval($r[$h]['hubloc_id'])
						);
						$what .= 'duplicate_hubloc_removed ';
						$changed = true;
					}
				}

				if(intval($r[0]['hubloc_primary']) && (! $location['primary'])) {
					$m = q("update hubloc set hubloc_primary = 0, hubloc_updated = '%s' where hubloc_id = %d",
						dbesc(datetime_convert()),
						intval($r[0]['hubloc_id'])
					);
					$r[0]['hubloc_primary'] = intval($location['primary']);
					hubloc_change_primary($r[0]);
					$what .= 'primary_hub ';
					$changed = true;
				}
				elseif((! intval($r[0]['hubloc_primary'])) && ($location['primary'])) {
					$m = q("update hubloc set hubloc_primary = 1, hubloc_updated = '%s' where hubloc_id = %d",
						dbesc(datetime_convert()),
						intval($r[0]['hubloc_id'])
					);
					// make sure hubloc_change_primary() has current data
					$r[0]['hubloc_primary'] = intval($location['primary']);
					hubloc_change_primary($r[0]);
					$what .= 'primary_hub ';
					$changed = true;
				}
				elseif($absolute) {
					// Absolute sync - make sure the current primary is correctly reflected in the xchan
					$pr = hubloc_change_primary($r[0]);
					if($pr) {
						$what .= 'xchan_primary ';
						$changed = true;
					}
				}
				if((intval($r[0]['hubloc_deleted']) && (! $location['deleted']))
					|| ((! (intval($r[0]['hubloc_deleted']))) && ($location['deleted']))) {
					$n = q("update hubloc set hubloc_deleted = 0, hubloc_updated = '%s' where hubloc_id = %d",
						dbesc(datetime_convert()),
						intval($r[0]['hubloc_id'])
					);
					$what .= 'delete_hub ';
					$changed = true;
				}
				elseif((! intval($r[0]['hubloc_deleted'])) && ($location['deleted'])) {
					$n = q("update hubloc set hubloc_deleted = 1, hubloc_updated = '%s' where hubloc_id = %d",
						dbesc(datetime_convert()),
						intval($r[0]['hubloc_id'])
					);
					$what .= 'delete_hub ';
					$changed = true;
				}
				continue;
			}

			// Existing hubs are dealt with. Now let's process any new ones.
			// New hub claiming to be primary. Make it so by removing any existing primaries.

			if(intval($location['primary'])) {
				$r = q("update hubloc set hubloc_primary = 0, hubloc_updated = '%s' where hubloc_hash = '%s' and hubloc_primary = 1",
					dbesc(datetime_convert()),
					dbesc($sender['hash'])
				);
			}
			logger('sync_locations: new hub: ' . $location['url']);
			$r = q("insert into hubloc ( hubloc_guid, hubloc_guid_sig, hubloc_hash, hubloc_addr, hubloc_network, hubloc_primary, hubloc_url, hubloc_url_sig, hubloc_host, hubloc_callback, hubloc_sitekey, hubloc_updated, hubloc_connected)
					values ( '%s','%s','%s','%s', '%s', %d ,'%s','%s','%s','%s','%s','%s','%s')",
				dbesc($sender['guid']),
				dbesc($sender['guid_sig']),
				dbesc($sender['hash']),
				dbesc($location['address']),
				dbesc('zot'),
				intval($location['primary']),
				dbesc($location['url']),
				dbesc($location['url_sig']),
				dbesc($location['host']),
				dbesc($location['callback']),
				dbesc($location['sitekey']),
				dbesc(datetime_convert()),
				dbesc(datetime_convert())
			);
			$what .= 'newhub ';
			$changed = true;

			if($location['primary']) {
				$r = q("select * from hubloc where hubloc_addr = '%s' and hubloc_sitekey = '%s' limit 1",
					dbesc($location['address']),
					dbesc($location['sitekey'])
				);
				if($r)
					hubloc_change_primary($r[0]);
			}
		}

		// get rid of any hubs we have for this channel which weren't reported.

		if($absolute && $xisting) {
			foreach($xisting as $x) {
				if(! array_key_exists('updated',$x)) {
					logger('sync_locations: deleting unreferenced hub location ' . $x['hubloc_url']);
					$r = q("update hubloc set hubloc_deleted = 1, hubloc_updated = '%s' where hubloc_id = %d",
						dbesc(datetime_convert()),
						intval($x['hubloc_id'])
					);
					$what .= 'removed_hub ';
					$changed = true;
				}
			}
		}
	}
	else {
		logger('No locations to sync!');
	}

	$ret['change_message'] = $what;
	$ret['changed'] = $changed;

	return $ret;
}

/**
 * @brief Returns an array with all known distinct hubs for this channel.
 *
 * @see zot_get_hublocs()
 * @param array $channel an associative array which must contain
 *  * \e string \b channel_hash the hash of the channel
 * @return array an array with associative arrays
 */
function zot_encode_locations($channel) {
	$ret = array();

	$x = zot_get_hublocs($channel['channel_hash']);

	if($x && count($x)) {
		foreach($x as $hub) {
			$ret[] = array(
				'host'     => $hub['hubloc_host'],
				'address'  => $hub['hubloc_addr'],
				'primary'  => (intval($hub['hubloc_primary']) ? true : false),
				'url'      => $hub['hubloc_url'],
				'url_sig'  => $hub['hubloc_url_sig'],
				'callback' => $hub['hubloc_callback'],
				'sitekey'  => $hub['hubloc_sitekey'],
				'deleted'  => (intval($hub['hubloc_deleted']) ? true : false)
			);
		}
	}

	return $ret;
}

/**
 * @brief Imports a directory profile.
 *
 * @param string $hash
 * @param array $profile
 * @param string $addr
 * @param number $ud_flags
 * @param number $suppress_update default 0
 * @return boolean $updated if something changed
 */
function import_directory_profile($hash, $profile, $addr, $ud_flags = UPDATE_FLAGS_UPDATED, $suppress_update = 0) {

	logger('import_directory_profile', LOGGER_DEBUG);
	if (! $hash)
		return false;

	$arr = array();

	$arr['xprof_hash']         = $hash;
	$arr['xprof_dob']          = datetime_convert('','',$profile['birthday'],'Y-m-d'); // !!!! check this for 0000 year
	$arr['xprof_age']          = (($profile['age'])         ? intval($profile['age']) : 0);
	$arr['xprof_desc']         = (($profile['description']) ? htmlspecialchars($profile['description'], ENT_COMPAT,'UTF-8',false) : '');
	$arr['xprof_gender']       = (($profile['gender'])      ? htmlspecialchars($profile['gender'],      ENT_COMPAT,'UTF-8',false) : '');
	$arr['xprof_marital']      = (($profile['marital'])     ? htmlspecialchars($profile['marital'],     ENT_COMPAT,'UTF-8',false) : '');
	$arr['xprof_sexual']       = (($profile['sexual'])      ? htmlspecialchars($profile['sexual'],      ENT_COMPAT,'UTF-8',false) : '');
	$arr['xprof_locale']       = (($profile['locale'])      ? htmlspecialchars($profile['locale'],      ENT_COMPAT,'UTF-8',false) : '');
	$arr['xprof_region']       = (($profile['region'])      ? htmlspecialchars($profile['region'],      ENT_COMPAT,'UTF-8',false) : '');
	$arr['xprof_postcode']     = (($profile['postcode'])    ? htmlspecialchars($profile['postcode'],    ENT_COMPAT,'UTF-8',false) : '');
	$arr['xprof_country']      = (($profile['country'])     ? htmlspecialchars($profile['country'],     ENT_COMPAT,'UTF-8',false) : '');
	$arr['xprof_about']        = (($profile['about'])       ? htmlspecialchars($profile['about'],       ENT_COMPAT,'UTF-8',false) : '');
	$arr['xprof_homepage']     = (($profile['homepage'])    ? htmlspecialchars($profile['homepage'],    ENT_COMPAT,'UTF-8',false) : '');
	$arr['xprof_hometown']     = (($profile['hometown'])    ? htmlspecialchars($profile['hometown'],    ENT_COMPAT,'UTF-8',false) : '');

	$clean = array();
	if (array_key_exists('keywords', $profile) and is_array($profile['keywords'])) {
		import_directory_keywords($hash,$profile['keywords']);
		foreach ($profile['keywords'] as $kw) {
			$kw = trim(htmlspecialchars($kw,ENT_COMPAT, 'UTF-8', false));
			$kw = trim($kw, ',');
			$clean[] = $kw;
		}
	}

	$arr['xprof_keywords'] = implode(' ',$clean);

	// Self censored, make it so
	// These are not translated, so the German "erwachsenen" keyword will not censor the directory profile. Only the English form - "adult".


	if(in_arrayi('nsfw',$clean) || in_arrayi('adult',$clean)) {
		q("update xchan set xchan_selfcensored = 1 where xchan_hash = '%s'",
			dbesc($hash)
		);
	}

	$r = q("select * from xprof where xprof_hash = '%s' limit 1",
		dbesc($hash)
	);

	if ($arr['xprof_age'] > 150)
		$arr['xprof_age'] = 150;
	if ($arr['xprof_age'] < 0)
		$arr['xprof_age'] = 0;

	if ($r) {
		$update = false;
		foreach ($r[0] as $k => $v) {
			if ((array_key_exists($k,$arr)) && ($arr[$k] != $v)) {
				logger('import_directory_profile: update ' . $k . ' => ' . $arr[$k]);
				$update = true;
				break;
			}
		}
		if ($update) {
			q("update xprof set
				xprof_desc = '%s',
				xprof_dob = '%s',
				xprof_age = %d,
				xprof_gender = '%s',
				xprof_marital = '%s',
				xprof_sexual = '%s',
				xprof_locale = '%s',
				xprof_region = '%s',
				xprof_postcode = '%s',
				xprof_country = '%s',
				xprof_about = '%s',
				xprof_homepage = '%s',
				xprof_hometown = '%s',
				xprof_keywords = '%s'
				where xprof_hash = '%s'",
				dbesc($arr['xprof_desc']),
				dbesc($arr['xprof_dob']),
				intval($arr['xprof_age']),
				dbesc($arr['xprof_gender']),
				dbesc($arr['xprof_marital']),
				dbesc($arr['xprof_sexual']),
				dbesc($arr['xprof_locale']),
				dbesc($arr['xprof_region']),
				dbesc($arr['xprof_postcode']),
				dbesc($arr['xprof_country']),
				dbesc($arr['xprof_about']),
				dbesc($arr['xprof_homepage']),
				dbesc($arr['xprof_hometown']),
				dbesc($arr['xprof_keywords']),
				dbesc($arr['xprof_hash'])
			);
		}
	} else {
		$update = true;
		logger('import_directory_profile: new profile ');
		q("insert into xprof (xprof_hash, xprof_desc, xprof_dob, xprof_age, xprof_gender, xprof_marital, xprof_sexual, xprof_locale, xprof_region, xprof_postcode, xprof_country, xprof_about, xprof_homepage, xprof_hometown, xprof_keywords) values ('%s', '%s', '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s') ",
			dbesc($arr['xprof_hash']),
			dbesc($arr['xprof_desc']),
			dbesc($arr['xprof_dob']),
			intval($arr['xprof_age']),
			dbesc($arr['xprof_gender']),
			dbesc($arr['xprof_marital']),
			dbesc($arr['xprof_sexual']),
			dbesc($arr['xprof_locale']),
			dbesc($arr['xprof_region']),
			dbesc($arr['xprof_postcode']),
			dbesc($arr['xprof_country']),
			dbesc($arr['xprof_about']),
			dbesc($arr['xprof_homepage']),
			dbesc($arr['xprof_hometown']),
			dbesc($arr['xprof_keywords'])
		);
	}

	$d = array('xprof' => $arr, 'profile' => $profile, 'update' => $update);
	call_hooks('import_directory_profile', $d);

	if (($d['update']) && (! $suppress_update))
		update_modtime($arr['xprof_hash'],random_string() . '@' . get_app()->get_hostname(), $addr, $ud_flags);

	return $d['update'];
}

/**
 * @brief
 *
 * @param string $hash
 * @param array $keywords
 */
function import_directory_keywords($hash, $keywords) {

	$existing = array();
	$r = q("select * from xtag where xtag_hash = '%s' and xtag_flags = 0",
		dbesc($hash)
	);

	if($r) {
		foreach($r as $rr)
			$existing[] = $rr['xtag_term'];
	}

	$clean = array();
	foreach($keywords as $kw) {
		$kw = trim(htmlspecialchars($kw,ENT_COMPAT, 'UTF-8', false));
		$kw = trim($kw, ',');
		$clean[] = $kw;
	}

	foreach($existing as $x) {
		if(! in_array($x, $clean))
			$r = q("delete from xtag where xtag_hash = '%s' and xtag_term = '%s' and xtag_flags = 0",
				dbesc($hash),
				dbesc($x)
			);
	}
	foreach($clean as $x) {
		if(! in_array($x, $existing)) {
			$r = q("insert into xtag ( xtag_hash, xtag_term, xtag_flags) values ( '%s' ,'%s', 0 )",
				dbesc($hash),
				dbesc($x)
			);
		}
	}
}

/**
 * @brief
 *
 * @param string $hash
 * @param string $guid
 * @param string $addr
 * @param int $flags (optional) default 0
 */
function update_modtime($hash, $guid, $addr, $flags = 0) {

	$dirmode = intval(get_config('system', 'directory_mode'));

	if($dirmode == DIRECTORY_MODE_NORMAL)
		return;

	if($flags) {
		q("insert into updates (ud_hash, ud_guid, ud_date, ud_flags, ud_addr ) values ( '%s', '%s', '%s', %d, '%s' )",
			dbesc($hash),
			dbesc($guid),
			dbesc(datetime_convert()),
			intval($flags),
			dbesc($addr)
		);
	}
	else {
		q("update updates set ud_flags = ( ud_flags | %d ) where ud_addr = '%s' and not (ud_flags & %d)>0 ",
			intval(UPDATE_FLAGS_UPDATED),
			dbesc($addr),
			intval(UPDATE_FLAGS_UPDATED)
		);
	}
}

/**
 * @brief
 *
 * @param array $arr
 * @param string $pubkey
 * @return boolean true if updated or inserted
 */
function import_site($arr, $pubkey) {
	if( (! is_array($arr)) || (! $arr['url']) || (! $arr['url_sig']))
		return false;

	if(! rsa_verify($arr['url'], base64url_decode($arr['url_sig']), $pubkey)) {
		logger('import_site: bad url_sig');
		return false;
	}

	$update = false;
	$exists = false;

	$r = q("select * from site where site_url = '%s' limit 1",
		dbesc($arr['url'])
	);
	if($r) {
		$exists = true;
		$siterecord = $r[0];
	}

	$site_directory = 0;
	if($arr['directory_mode'] == 'normal')
		$site_directory = DIRECTORY_MODE_NORMAL;
	if($arr['directory_mode'] == 'primary')
		$site_directory = DIRECTORY_MODE_PRIMARY;
	if($arr['directory_mode'] == 'secondary')
		$site_directory = DIRECTORY_MODE_SECONDARY;
	if($arr['directory_mode'] == 'standalone')
		$site_directory = DIRECTORY_MODE_STANDALONE;

	$register_policy = 0;
	if($arr['register_policy'] == 'closed')
		$register_policy = REGISTER_CLOSED;
	if($arr['register_policy'] == 'open')
		$register_policy = REGISTER_OPEN;
	if($arr['register_policy'] == 'approve')
		$register_policy = REGISTER_APPROVE;

	$access_policy = 0;
	if(array_key_exists('access_policy',$arr)) {
		if($arr['access_policy'] === 'private')
			$access_policy = ACCESS_PRIVATE;
		if($arr['access_policy'] === 'paid')
			$access_policy = ACCESS_PAID;
		if($arr['access_policy'] === 'free')
			$access_policy = ACCESS_FREE;
		if($arr['access_policy'] === 'tiered')
			$access_policy = ACCESS_TIERED;
	}

	// don't let insecure sites register as public hubs

	if(strpos($arr['url'],'https://') === false)
		$access_policy = ACCESS_PRIVATE;

	if($access_policy != ACCESS_PRIVATE) {
		$x = z_fetch_url($arr['url'] . '/siteinfo/json');
		if(! $x['success'])
			$access_policy = ACCESS_PRIVATE;
	}

	$directory_url = htmlspecialchars($arr['directory_url'],ENT_COMPAT,'UTF-8',false);
	$url = htmlspecialchars(strtolower($arr['url']),ENT_COMPAT,'UTF-8',false);
	$sellpage = htmlspecialchars($arr['sellpage'],ENT_COMPAT,'UTF-8',false);
	$site_location = htmlspecialchars($arr['location'],ENT_COMPAT,'UTF-8',false);
	$site_realm = htmlspecialchars($arr['realm'],ENT_COMPAT,'UTF-8',false);
	$site_project = htmlspecialchars($arr['project'],ENT_COMPAT,'UTF-8',false);

	// You can have one and only one primary directory per realm.
	// Downgrade any others claiming to be primary. As they have
	// flubbed up this badly already, don't let them be directory servers at all.

	if(($site_directory === DIRECTORY_MODE_PRIMARY)
			&& ($site_realm === get_directory_realm())
			&& ($arr['url'] != get_directory_primary())) {
		$site_directory = DIRECTORY_MODE_NORMAL;
	}

	if($exists) {
		if(($siterecord['site_flags'] != $site_directory)
			|| ($siterecord['site_access'] != $access_policy)
			|| ($siterecord['site_directory'] != $directory_url)
			|| ($siterecord['site_sellpage'] != $sellpage)
			|| ($siterecord['site_location'] != $site_location)
			|| ($siterecord['site_register'] != $register_policy)
			|| ($siterecord['site_project'] != $site_project)
			|| ($siterecord['site_realm'] != $site_realm)) {
			$update = true;

//			logger('import_site: input: ' . print_r($arr,true));
//			logger('import_site: stored: ' . print_r($siterecord,true));


			$r = q("update site set site_dead = 0, site_location = '%s', site_flags = %d, site_access = %d, site_directory = '%s', site_register = %d, site_update = '%s', site_sellpage = '%s', site_realm = '%s', site_type = %d, site_project = '%s'
				where site_url = '%s'",
				dbesc($site_location),
				intval($site_directory),
				intval($access_policy),
				dbesc($directory_url),
				intval($register_policy),
				dbesc(datetime_convert()),
				dbesc($sellpage),
				dbesc($site_realm),
				intval(SITE_TYPE_ZOT),
				dbesc($site_project),
				dbesc($url)
			);
			if(! $r) {
				logger('import_site: update failed. ' . print_r($arr,true));
			}
		}
		else {
			// update the timestamp to indicate we communicated with this site
			q("update site set site_dead = 0, site_update = '%s' where site_url = '%s'",
				dbesc(datetime_convert()),
				dbesc($url)
			);
		}
	}
	else {
		$update = true;

		$r = q("insert into site ( site_location, site_url, site_access, site_flags, site_update, site_directory, site_register, site_sellpage, site_realm, site_type, site_project )
			values ( '%s', '%s', %d, %d, '%s', '%s', %d, '%s', '%s', %d, '%s' )",
			dbesc($site_location),
			dbesc($url),
			intval($access_policy),
			intval($site_directory),
			dbesc(datetime_convert()),
			dbesc($directory_url),
			intval($register_policy),
			dbesc($sellpage),
			dbesc($site_realm),
			intval(SITE_TYPE_ZOT),
			dbesc($site_project)
		);
		if(! $r) {
			logger('import_site: record create failed. ' . print_r($arr,true));
		}
	}

	return $update;
}


/**
 * Send a zot packet to all hubs where this channel is duplicated, refreshing
 * such things as personal settings, channel permissions, address book updates, etc.
 *
 * @param int $uid
 * @param array $packet (optional) default null
 * @param boolean $groups_changed (optional) default false
 */
function build_sync_packet($uid = 0, $packet = null, $groups_changed = false) {

	$a = get_app();

	logger('build_sync_packet');

	if($packet)
		logger('packet: ' . print_r($packet, true),LOGGER_DATA);

	if(! $uid)
		$uid = local_channel();

	if(! $uid)
		return;

	$r = q("select * from channel where channel_id = %d limit 1",
		intval($uid)
	);
	if(! $r)
		return;

	$channel = $r[0];

	if(intval($channel['channel_removed']))
		return;

	$h = q("select * from hubloc where hubloc_hash = '%s' and hubloc_deleted = 0",
		dbesc($channel['channel_hash'])
	);

	if(! $h)
		return;

	$synchubs = array();

	foreach($h as $x) {
		if($x['hubloc_host'] == $a->get_hostname())
			continue;

		$synchubs[] = $x;
	}

	if(! $synchubs)
		return;

	$r = q("select xchan_guid, xchan_guid_sig from xchan where xchan_hash  = '%s' limit 1",
		dbesc($channel['channel_hash'])
	);
	if(! $r)
		return;

	$env_recips = array();
	$env_recips[] = array('guid' => $r[0]['xchan_guid'],'guid_sig' => $r[0]['xchan_guid_sig']);

	$info = (($packet) ? $packet : array());
	$info['type'] = 'channel_sync';
	$info['encoding'] = 'red'; // note: not zot, this packet is very red specific

	if(array_key_exists($uid,$a->config) && array_key_exists('transient',$a->config[$uid])) {
		$settings = $a->config[$uid]['transient'];
		if($settings) {
			$info['config'] = $settings;
		}
	}

	if($channel) {
		$info['channel'] = array();
		foreach($channel as $k => $v) {

			// filter out any joined tables like xchan

			if(strpos($k,'channel_') !== 0)
				continue;

			// don't pass these elements, they should not be synchronised

			$disallowed = array('channel_id','channel_account_id','channel_primary','channel_prvkey','channel_address','channel_deleted','channel_removed','channel_system');

			if(in_array($k,$disallowed))
				continue;

			$info['channel'][$k] = $v;
		}
	}

	if($groups_changed) {
		$r = q("select hash as collection, visible, deleted, name from groups where uid = %d",
			intval($uid)
		);
		if($r)
			$info['collections'] = $r;
		$r = q("select groups.hash as collection, group_member.xchan as member from groups left join group_member on groups.id = group_member.gid where group_member.uid = %d",
			intval($uid)
		);
		if($r)
			$info['collection_members'] = $r;
	}

	$interval = ((get_config('system','delivery_interval') !== false)
			? intval(get_config('system','delivery_interval')) : 2 );


	logger('build_sync_packet: packet: ' . print_r($info,true), LOGGER_DATA);

	foreach($synchubs as $hub) {
		$hash = random_string();
		$n = zot_build_packet($channel,'notify',$env_recips,$hub['hubloc_sitekey'],$hash);
		q("insert into outq ( outq_hash, outq_account, outq_channel, outq_driver, outq_posturl, outq_async, outq_created, outq_updated, outq_notify, outq_msg ) values ( '%s', %d, %d, '%s', '%s', %d, '%s', '%s', '%s', '%s' )",
			dbesc($hash),
			intval($channel['channel_account']),
			intval($channel['channel_id']),
			dbesc('zot'),
			dbesc($hub['hubloc_callback']),
			intval(1),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			dbesc($n),
			dbesc(json_encode($info))
		);

		proc_run('php', 'include/deliver.php', $hash);
		if($interval)
			@time_sleep_until(microtime(true) + (float) $interval);
	}
}

/**
 * @brief
 *
 * @param array $sender
 * @param array $arr
 * @param array $deliveries
 * @return array
 */
function process_channel_sync_delivery($sender, $arr, $deliveries) {

	require_once('include/import.php');

	/** @FIXME this will sync red structures (channel, pconfig and abook). Eventually we need to make this application agnostic. */

	$result = array();

	foreach ($deliveries as $d) {
		$r = q("select * from channel where channel_hash = '%s' limit 1",
			dbesc($d['hash'])
		);

		if (! $r) {
			$result[] = array($d['hash'],'not found');
			continue;
		}

		$channel = $r[0];

		$max_friends = service_class_fetch($channel['channel_id'],'total_channels');
		$max_feeds = account_service_class_fetch($channel['channel_account_id'],'total_feeds');

		if($channel['channel_hash'] != $sender['hash']) {
			logger('process_channel_sync_delivery: possible forgery. Sender ' . $sender['hash'] . ' is not ' . $channel['channel_hash']);
			$result[] = array($d['hash'],'channel mismatch',$channel['channel_name'],'');
			continue;
		}

		if(array_key_exists('config',$arr) && is_array($arr['config']) && count($arr['config'])) {
			foreach($arr['config'] as $cat => $k) {
				foreach($arr['config'][$cat] as $k => $v)
					set_pconfig($channel['channel_id'],$cat,$k,$v);
			}
		}

		if(array_key_exists('obj',$arr) && $arr['obj'])
			sync_objs($channel,$arr['obj']);

		if(array_key_exists('likes',$arr) && $arr['likes'])
			import_likes($channel,$arr['likes']);

		if(array_key_exists('app',$arr) && $arr['app'])
			sync_apps($channel,$arr['app']);

		if(array_key_exists('chatroom',$arr) && $arr['chatroom'])
			sync_chatrooms($channel,$arr['chatroom']);

		if(array_key_exists('conv',$arr) && $arr['conv'])
			import_conv($channel,$arr['conv']);

		if(array_key_exists('mail',$arr) && $arr['mail'])
			import_mail($channel,$arr['mail']);

		if(array_key_exists('event',$arr) && $arr['event'])
			sync_events($channel,$arr['event']);

		if(array_key_exists('event_item',$arr) && $arr['event_item'])
			sync_items($channel,$arr['event_item']);

		if(array_key_exists('item',$arr) && $arr['item'])
			sync_items($channel,$arr['item']);

		if(array_key_exists('item_id',$arr) && $arr['item_id'])
			sync_items($channel,$arr['item_id']);

		if(array_key_exists('menu',$arr) && $arr['menu'])
			sync_menus($channel,$arr['menu']);

		if(array_key_exists('channel',$arr) && is_array($arr['channel']) && count($arr['channel'])) {

			if(array_key_exists('channel_pageflags',$arr['channel']) && intval($arr['channel']['channel_pageflags'])) {
				// These flags cannot be sync'd.
				// remove the bits from the incoming flags.

				// These correspond to PAGE_REMOVED and PAGE_SYSTEM on redmatrix

				if($arr['channel']['channel_pageflags'] & 0x8000)
					$arr['channel']['channel_pageflags'] = $arr['channel']['channel_pageflags'] - 0x8000;
				if($arr['channel']['channel_pageflags'] & 0x1000)
					$arr['channel']['channel_pageflags'] = $arr['channel']['channel_pageflags'] - 0x1000;

			}
			
			$disallowed = array('channel_id','channel_account_id','channel_primary','channel_prvkey', 'channel_address', 'channel_notifyflags', 'channel_removed', 'channel_deleted', 'channel_system');

			$clean = array();
			foreach($arr['channel'] as $k => $v) {
				if(in_array($k,$disallowed))
					continue;
				$clean[$k] = $v;
			}
			if(count($clean)) {
				foreach($clean as $k => $v) {
					$r = dbq("UPDATE channel set " . dbesc($k) . " = '" . dbesc($v)
						. "' where channel_id = " . intval($channel['channel_id']) );
				}
			}
		}

		if(array_key_exists('abook',$arr) && is_array($arr['abook']) && count($arr['abook'])) {
			$total_friends = 0;
			$total_feeds = 0;

			$r = q("select abook_id, abook_feed from abook where abook_channel = %d",
				intval($channel['channel_id'])
			);
			if($r) {
				// don't count yourself
				$total_friends = ((count($r) > 0) ? count($r) - 1 : 0);
				foreach($r as $rr)
					if(intval($rr['abook_feed']))
						$total_feeds ++;
			}


			$disallowed = array('abook_id','abook_account','abook_channel','abook_rating','abook_rating_text');

			foreach($arr['abook'] as $abook) {

				if(! array_key_exists('abook_blocked',$abook)) {
					// convert from redmatrix
					$abook['abook_blocked']     = (($abook['abook_flags'] & 0x0001) ? 1 : 0);
					$abook['abook_ignored']     = (($abook['abook_flags'] & 0x0002) ? 1 : 0);
					$abook['abook_hidden']      = (($abook['abook_flags'] & 0x0004) ? 1 : 0);
					$abook['abook_archived']    = (($abook['abook_flags'] & 0x0008) ? 1 : 0);
					$abook['abook_pending']     = (($abook['abook_flags'] & 0x0010) ? 1 : 0);
					$abook['abook_unconnected'] = (($abook['abook_flags'] & 0x0020) ? 1 : 0);
					$abook['abook_self']        = (($abook['abook_flags'] & 0x0080) ? 1 : 0);
					$abook['abook_feed']        = (($abook['abook_flags'] & 0x0100) ? 1 : 0);
				}

				$clean = array();
				if($abook['abook_xchan'] && $abook['entry_deleted']) {
					logger('process_channel_sync_delivery: removing abook entry for ' . $abook['abook_xchan']);
					require_once('include/Contact.php');

					$r = q("select abook_id, abook_feed from abook where abook_xchan = '%s' and abook_channel = %d and abook_self = 0 limit 1",
						dbesc($abook['abook_xchan']),
						intval($channel['channel_id'])
					);
					if($r) {
						contact_remove($channel['channel_id'],$r[0]['abook_id']);
						if($total_friends)
							$total_friends --;
						if(intval($r[0]['abook_feed']))
							$total_feeds --;
					}
					continue;
				}

				// Perform discovery if the referenced xchan hasn't ever been seen on this hub.
				// This relies on the undocumented behaviour that red sites send xchan info with the abook
				// and import_author_xchan will look them up on all federated networks

				if($abook['abook_xchan'] && $abook['xchan_addr']) {
					$h = zot_get_hublocs($abook['abook_xchan']);
					if(! $h) {
						$xhash = import_author_xchan(encode_item_xchan($abook));
						if(! $xhash) {
							logger('process_channel_sync_delivery: import of ' . $abook['xchan_addr'] . ' failed.');
							continue;
						}
					}
				}

				foreach($abook as $k => $v) {
					if(in_array($k,$disallowed) || (strpos($k,'abook') !== 0))
						continue;
					$clean[$k] = $v;
				}

				if(! array_key_exists('abook_xchan',$clean))
					continue;

				$r = q("select * from abook where abook_xchan = '%s' and abook_channel = %d limit 1",
					dbesc($clean['abook_xchan']),
					intval($channel['channel_id'])
				);

				// make sure we have an abook entry for this xchan on this system

				if(! $r) {
					if($max_friends !== false && $total_friends > $max_friends) {
						logger('process_channel_sync_delivery: total_channels service class limit exceeded');
						continue;
					}
					if($max_feeds !== false && intval($clean['abook_feed']) && $total_feeds > $max_feeds) {
						logger('process_channel_sync_delivery: total_feeds service class limit exceeded');
						continue;
					}
					q("insert into abook ( abook_xchan, abook_channel ) values ('%s', %d ) ",
						dbesc($clean['abook_xchan']),
						intval($channel['channel_id'])
					);
					$total_friends ++;
					if(intval($clean['abook_feed']))
						$total_feeds ++;
				}

				if(count($clean)) {
					foreach($clean as $k => $v) {
						if($k == 'abook_dob')
							$v = dbescdate($v);
						$r = dbq("UPDATE abook set " . dbesc($k) . " = '" . dbesc($v)
						. "' where abook_xchan = '" . dbesc($clean['abook_xchan']) . "' and abook_channel = " . intval($channel['channel_id']));
					}
				}



			}
		}

		// sync collections (privacy groups) oh joy...

		if(array_key_exists('collections',$arr) && is_array($arr['collections']) && count($arr['collections'])) {
			$x = q("select * from groups where uid = %d",
				intval($channel['channel_id'])
			);
			foreach($arr['collections'] as $cl) {
				$found = false;
				if($x) {
					foreach($x as $y) {
						if($cl['collection'] == $y['hash']) {
							$found = true;
							break;
						}
					}
					if($found) {
						if(($y['name'] != $cl['name'])
							|| ($y['visible'] != $cl['visible'])
							|| ($y['deleted'] != $cl['deleted'])) {
							q("update groups set name = '%s', visible = %d, deleted = %d where hash = '%s' and uid = %d",
								dbesc($cl['name']),
								intval($cl['visible']),
								intval($cl['deleted']),
								dbesc($cl['hash']),
								intval($channel['channel_id'])
							);
						}
						if(intval($cl['deleted']) && (! intval($y['deleted']))) {
							q("delete from group_member where gid = %d",
								intval($y['id'])
							);
						}
					}
				}
				if(! $found) {
					$r = q("INSERT INTO `groups` ( hash, uid, visible, deleted, name )
						VALUES( '%s', %d, %d, %d, '%s' ) ",
						dbesc($cl['collection']),
						intval($channel['channel_id']),
						intval($cl['visible']),
						intval($cl['deleted']),
						dbesc($cl['name'])
					);
				}

				// now look for any collections locally which weren't in the list we just received.
				// They need to be removed by marking deleted and removing the members.
				// This shouldn't happen except for clones created before this function was written.

				if($x) {
					$found_local = false;
					foreach($x as $y) {
						foreach($arr['collections'] as $cl) {
							if($cl['collection'] == $y['hash']) {
								$found_local = true;
								break;
							}
						}
						if(! $found_local) {
							q("delete from group_member where gid = %d",
								intval($y['id'])
							);
							q("update groups set deleted = 1 where id = %d and uid = %d",
								intval($y['id']),
								intval($channel['channel_id'])
							);
						}
					}
				}
			}

			// reload the group list with any updates
			$x = q("select * from groups where uid = %d",
				intval($channel['channel_id'])
			);

			// now sync the members

			if(array_key_exists('collection_members', $arr)
					&& is_array($arr['collection_members'])
					&& count($arr['collection_members'])) {

				// first sort into groups keyed by the group hash
				$members = array();
				foreach($arr['collection_members'] as $cm) {
					if(! array_key_exists($cm['collection'],$members))
						$members[$cm['collection']] = array();

					$members[$cm['collection']][] = $cm['member'];
				}

				// our group list is already synchronised
				if($x) {
					foreach($x as $y) {

						// for each group, loop on members list we just received
						foreach($members[$y['hash']] as $member) {
							$found = false;
							$z = q("select xchan from group_member where gid = %d and uid = %d and xchan = '%s' limit 1",
								intval($y['id']),
								intval($channel['channel_id']),
								dbesc($member)
							);
							if($z)
								$found = true;

							// if somebody is in the group that wasn't before - add them

							if(! $found) {
								q("INSERT INTO `group_member` (`uid`, `gid`, `xchan`)
									VALUES( %d, %d, '%s' ) ",
									intval($channel['channel_id']),
									intval($y['id']),
									dbesc($member)
								);
							}
						}

						// now retrieve a list of members we have on this site
						$m = q("select xchan from group_member where gid = %d and uid = %d",
							intval($y['id']),
							intval($channel['channel_id'])
						);
						if($m) {
							foreach($m as $mm) {
								// if the local existing member isn't in the list we just received - remove them
								if(! in_array($mm['xchan'],$members[$y['hash']])) {
									q("delete from group_member where xchan = '%s' and gid = %d and uid = %d",
										dbesc($mm['xchan']),
										intval($y['id']),
										intval($channel['channel_id'])
									);
								}
							}
						}
					}
				}
			}
		}

		if(array_key_exists('profile',$arr) && is_array($arr['profile']) && count($arr['profile'])) {

			$disallowed = array('id','aid','uid');

			foreach($arr['profile'] as $profile) {
				$x = q("select * from profile where profile_guid = '%s' and uid = %d limit 1",
					dbesc($profile['profile_guid']),
					intval($channel['channel_id'])
				);
				if(! $x) {
					q("insert into profile ( profile_guid, aid, uid ) values ('%s', %d, %d)",
						dbesc($profile['profile_guid']),
						intval($channel['channel_account_id']),
						intval($channel['channel_id'])
					);
					$x = q("select * from profile where profile_guid = '%s' and uid = %d limit 1",
						dbesc($profile['profile_guid']),
						intval($channel['channel_id'])
					);
					if(! $x)
						continue;
				}
				$clean = array();
				foreach($profile as $k => $v) {
					if(in_array($k,$disallowed))
						continue;

					$clean[$k] = $v;
					/**
					 * @TODO check if these are allowed, otherwise we'll error
					 * We also need to import local photos if a custom photo is selected
					 */
				}
				if(count($clean)) {
					foreach($clean as $k => $v) {
						$r = dbq("UPDATE profile set `" . dbesc($k) . "` = '" . dbesc($v)
						. "' where profile_guid = '" . dbesc($profile['profile_guid']) . "' and uid = " . intval($channel['channel_id']));
					}
				}
			}
		}


		if(array_key_exists('item',$arr) && $arr['item'])
			sync_items($channel,$arr['item']);

		if(array_key_exists('item_id',$arr) && $arr['item_id'])
			sync_items($channel,$arr['item_id']);

		$addon = array('channel' => $channel,'data' => $arr);
		call_hooks('process_channel_sync_delivery',$addon);

		// we should probably do this for all items, but usually we only send one.

		require_once('include/DReport.php');

		if(array_key_exists('item',$arr) && is_array($arr['item'][0])) {
			$DR = new DReport(z_root(),$d['hash'],$d['hash'],$arr['item'][0]['message_id'],'channel sync processed');
			$DR->addto_recipient($channel['channel_name'] . ' <' . $channel['channel_address'] . '@' . get_app()->get_hostname() . '>');
		}
		else
			$DR = new DReport(z_root(),$d['hash'],$d['hash'],'sync packet','channel sync delivered');

		$result[] = $DR->get();

	}

	return $result;
}

/**
 * @brief Returns path to /rpost
 *
 * @todo We probably should make rpost discoverable.
 *
 * @param array $observer
 *   * \e string \b xchan_url
 * @return string
 */
function get_rpost_path($observer) {
	if(! $observer)
		return '';

	$parsed = parse_url($observer['xchan_url']);

	return $parsed['scheme'] . '://' . $parsed['host'] . (($parsed['port']) ? ':' . $parsed['port'] : '') . '/rpost?f=';
}

/**
 * @brief
 *
 * @param array $x
 * @return boolean|string return false or a hash
 */
function import_author_zot($x) {

	$hash = make_xchan_hash($x['guid'],$x['guid_sig']);
	$r = q("select hubloc_url from hubloc where hubloc_guid = '%s' and hubloc_guid_sig = '%s' and hubloc_primary = 1 limit 1",
		dbesc($x['guid']),
		dbesc($x['guid_sig'])
	);

	if ($r) {
		logger('import_author_zot: in cache', LOGGER_DEBUG);
		return $hash;
	}

	logger('import_author_zot: entry not in cache - probing: ' . print_r($x,true), LOGGER_DEBUG);

	$them = array('hubloc_url' => $x['url'], 'xchan_guid' => $x['guid'], 'xchan_guid_sig' => $x['guid_sig']);
	if (zot_refresh($them))
		return $hash;

	return false;
}

/**
 * @brief Process a message request.
 *
 * If a site receives a comment to a post but finds they have no parent to attach it with, they
 * may send a 'request' packet containing the message_id of the missing parent. This is the handler
 * for that packet. We will create a message_list array of the entire conversation starting with
 * the missing parent and invoke delivery to the sender of the packet.
 *
 * include/deliver.php (for local delivery) and mod/post.php (for web delivery) detect the existence of
 * this 'message_list' at the destination and split it into individual messages which are
 * processed/delivered in order.
 *
 * Called from mod/post.php
 *
 * @param array $data
 * @return array
 */
function zot_process_message_request($data) {
	$ret = array('success' => false);

	if (! $data['message_id']) {
		$ret['message'] = 'no message_id';
		logger('no message_id');
		return $ret;
	}

	$sender = $data['sender'];
	$sender_hash = make_xchan_hash($sender['guid'], $sender['guid_sig']);

	/*
	 * Find the local channel in charge of this post (the first and only recipient of the request packet)
	 */

	$arr = $data['recipients'][0];
	$recip_hash = make_xchan_hash($arr['guid'],$arr['guid_sig']);
	$c = q("select * from channel left join xchan on channel_hash = xchan_hash where channel_hash = '%s' limit 1",
		dbesc($recip_hash)
	);
	if (! $c) {
		logger('recipient channel not found.');
		$ret['message'] .= 'recipient not found.' . EOL;
		return $ret;
	}

	/*
	 * fetch the requested conversation
	 */

	$messages = zot_feed($c[0]['channel_id'],$sender_hash,array('message_id' => $data['message_id']));

	if ($messages) {
		$env_recips = null;

		$r = q("select * from hubloc where hubloc_hash = '%s' and not hubloc_error and not hubloc_deleted 
			group by hubloc_sitekey",
			dbesc($sender_hash)
		);
		if (! $r) {
			logger('no hubs');
			return $ret;
		}
		$hubs = $r;

		$private = ((array_key_exists('flags', $messages[0]) && in_array('private',$messages[0]['flags'])) ? true : false);
		if($private)
			$env_recips = array('guid' => $sender['guid'], 'guid_sig' => $sender['guid_sig'], 'hash' => $sender_hash);

		$data_packet = json_encode(array('message_list' => $messages));

		foreach($hubs as $hub) {
			$hash = random_string();

			/*
			 * create a notify packet and drop the actual message packet in the queue for pickup
			 */

			$n = zot_build_packet($c[0],'notify',$env_recips,(($private) ? $hub['hubloc_sitekey'] : null),$hash,array('message_id' => $data['message_id']));
			q("insert into outq ( outq_hash, outq_account, outq_channel, outq_driver, outq_posturl, outq_async,
				outq_created, outq_updated, outq_notify, outq_msg )
				values ( '%s', %d, %d, '%s', '%s', %d, '%s', '%s', '%s', '%s' )",
				dbesc($hash),
				intval($c[0]['channel_account_id']),
				intval($c[0]['channel_id']),
				dbesc('zot'),
				dbesc($hub['hubloc_callback']),
				intval(1),
				dbesc(datetime_convert()),
				dbesc(datetime_convert()),
				dbesc($n),
				dbesc($data_packet)
			);

			/*
			 * invoke delivery to send out the notify packet
			 */

			proc_run('php', 'include/deliver.php', $hash);
		}
	}
	$ret['success'] = true;

	return $ret;
}


function zotinfo($arr) {

	$ret = array('success' => false);

	$zhash     = ((x($arr,'guid_hash'))  ? $arr['guid_hash']   : '');
	$zguid     = ((x($arr,'guid'))       ? $arr['guid']        : '');
	$zguid_sig = ((x($arr,'guid_sig'))   ? $arr['guid_sig']    : '');
	$zaddr     = ((x($arr,'address'))    ? $arr['address']     : '');
	$ztarget   = ((x($arr,'target'))     ? $arr['target']      : '');
	$zsig      = ((x($arr,'target_sig')) ? $arr['target_sig']  : '');
	$zkey      = ((x($arr,'key'))        ? $arr['key']         : '');
	$mindate   = ((x($arr,'mindate'))    ? $arr['mindate']     : '');
	$feed      = ((x($arr,'feed'))       ? intval($arr['feed']) : 0);

	if($ztarget) {
		if((! $zkey) || (! $zsig) || (! rsa_verify($ztarget,base64url_decode($zsig),$zkey))) {
			logger('zfinger: invalid target signature');
			$ret['message'] = t("invalid target signature");
			return($ret);
		}
	}

	$r = null;

	if(strlen($zhash)) {
		$r = q("select channel.*, xchan.* from channel left join xchan on channel_hash = xchan_hash 
			where channel_hash = '%s' limit 1",
			dbesc($zhash)
		);
	}
	elseif(strlen($zguid) && strlen($zguid_sig)) {
		$r = q("select channel.*, xchan.* from channel left join xchan on channel_hash = xchan_hash 
			where channel_guid = '%s' and channel_guid_sig = '%s' limit 1",
			dbesc($zguid),
			dbesc($zguid_sig)
		);
	}
	elseif(strlen($zaddr)) {
		if(strpos($zaddr,'[system]') === false) {       /* normal address lookup */
			$r = q("select channel.*, xchan.* from channel left join xchan on channel_hash = xchan_hash
				where ( channel_address = '%s' or xchan_addr = '%s' ) limit 1",
				dbesc($zaddr),
				dbesc($zaddr)
			);
		}

		else {

			/**
			 * The special address '[system]' will return a system channel if one has been defined,
			 * Or the first valid channel we find if there are no system channels. 
			 *
			 * This is used by magic-auth if we have no prior communications with this site - and
			 * returns an identity on this site which we can use to create a valid hub record so that
			 * we can exchange signed messages. The precise identity is irrelevant. It's the hub
			 * information that we really need at the other end - and this will return it.
			 *
			 */

			$r = q("select channel.*, xchan.* from channel left join xchan on channel_hash = xchan_hash
				where channel_system = 1 order by channel_id limit 1");
			if(! $r) {
				$r = q("select channel.*, xchan.* from channel left join xchan on channel_hash = xchan_hash
					where channel_removed = 0 order by channel_id limit 1");
			}
		} 
	}
	else {
		$ret['message'] = 'Invalid request';
		return($ret);
	}

	if(! $r) {
		$ret['message'] = 'Item not found.';
		return($ret);
	}

	$e = $r[0];

	$id = $e['channel_id'];

	$sys_channel     = (intval($e['channel_system'])   ? true : false);
	$special_channel = (($e['channel_pageflags'] & PAGE_PREMIUM)  ? true : false);
	$adult_channel   = (($e['channel_pageflags'] & PAGE_ADULT)    ? true : false);
	$censored        = (($e['channel_pageflags'] & PAGE_CENSORED) ? true : false);
	$searchable      = (($e['channel_pageflags'] & PAGE_HIDDEN)   ? false : true);
	$deleted         = (intval($e['xchan_deleted']) ? true : false);

	if($deleted || $censored || $sys_channel)
		$searchable = false;
	 
	$public_forum = false;

	$role = get_pconfig($e['channel_id'],'system','permissions_role');
	if($role === 'forum' || $role === 'repository') {
		$public_forum = true;
	}
	else {
		// check if it has characteristics of a public forum based on custom permissions.
		$t = q("select abook_my_perms from abook where abook_channel = %d and abook_self = 1 limit 1",
			intval($e['channel_id'])
		);
		if(($t) && (($t[0]['abook_my_perms'] & PERMS_W_TAGWALL) && (! ($t[0]['abook_my_perms'] & PERMS_W_STREAM))))
			$public_forum = true;
	}


	//  This is for birthdays and keywords, but must check access permissions
	$p = q("select * from profile where uid = %d and is_default = 1",
		intval($e['channel_id'])
	);

	$profile = array();

	if($p) {

		if(! intval($p[0]['publish']))
			$searchable = false; 

		$profile['description']   = $p[0]['pdesc'];
		$profile['birthday']      = $p[0]['dob'];
		if(($profile['birthday'] != '0000-00-00') && (($bd = z_birthday($p[0]['dob'],$e['channel_timezone'])) !== ''))
			$profile['next_birthday'] = $bd;

		if($age = age($p[0]['dob'],$e['channel_timezone'],''))  
			$profile['age'] = $age;
		$profile['gender']        = $p[0]['gender'];
		$profile['marital']       = $p[0]['marital'];
		$profile['sexual']        = $p[0]['sexual'];
		$profile['locale']        = $p[0]['locality'];
		$profile['region']        = $p[0]['region'];
		$profile['postcode']      = $p[0]['postal_code'];
		$profile['country']       = $p[0]['country_name'];
		$profile['about']         = $p[0]['about'];
		$profile['homepage']      = $p[0]['homepage'];
		$profile['hometown']      = $p[0]['hometown'];

		if($p[0]['keywords']) {
			$tags = array();
			$k = explode(' ',$p[0]['keywords']);
			if($k) {
				foreach($k as $kk) {
					if(trim($kk," \t\n\r\0\x0B,")) {
						$tags[] = trim($kk," \t\n\r\0\x0B,");
					}
				}
			}
			if($tags)
				$profile['keywords'] = $tags;
		}
	}

	$ret['success'] = true;

	// Communication details

	$ret['guid']           = $e['xchan_guid'];
	$ret['guid_sig']       = $e['xchan_guid_sig'];
	$ret['key']            = $e['xchan_pubkey'];
	$ret['name']           = $e['xchan_name'];
	$ret['name_updated']   = $e['xchan_name_date'];
	$ret['address']        = $e['xchan_addr'];
	$ret['photo_mimetype'] = $e['xchan_photo_mimetype'];
	$ret['photo']          = $e['xchan_photo_l'];
	$ret['photo_updated']  = $e['xchan_photo_date'];
	$ret['url']            = $e['xchan_url'];
	$ret['connections_url']= (($e['xchan_connurl']) ? $e['xchan_connurl'] : z_root() . '/poco/' . $e['channel_address']);
	$ret['target']         = $ztarget;
	$ret['target_sig']     = $zsig;
	$ret['searchable']     = $searchable;
	$ret['adult_content']  = $adult_channel;
	$ret['public_forum']   = $public_forum;
	if($deleted)
		$ret['deleted']        = $deleted;	

	// premium or other channel desiring some contact with potential followers before connecting.
	// This is a template - %s will be replaced with the follow_url we discover for the return channel.

	if($special_channel) 
		$ret['connect_url'] = z_root() . '/connect/' . $e['channel_address'];

	// This is a template for our follow url, %s will be replaced with a webbie

	$ret['follow_url'] = z_root() . '/follow?f=&url=%s';

	$ztarget_hash = (($ztarget && $zsig) 
			? make_xchan_hash($ztarget,$zsig)
			: '' ); 

	$permissions = get_all_perms($e['channel_id'],$ztarget_hash,false);

	if($ztarget_hash) {
		$permissions['connected'] = false;
		$b = q("select * from abook where abook_xchan = '%s' and abook_channel = %d limit 1",
			dbesc($ztarget_hash),
			intval($e['channel_id'])
		);
		if($b)
			$permissions['connected'] = true;
	}

	$ret['permissions'] = (($ztarget && $zkey) ? crypto_encapsulate(json_encode($permissions),$zkey) : $permissions);

	if($permissions['view_profile'])
		$ret['profile']  = $profile;

	// array of (verified) hubs this channel uses

	$x = zot_encode_locations($e);
	if($x)
		$ret['locations'] = $x;

	$ret['site'] = array();
	$ret['site']['url'] = z_root();
	$ret['site']['url_sig'] = base64url_encode(rsa_sign(z_root(),$e['channel_prvkey']));

	$dirmode = get_config('system','directory_mode');
	if(($dirmode === false) || ($dirmode == DIRECTORY_MODE_NORMAL))
		$ret['site']['directory_mode'] = 'normal';

	if($dirmode == DIRECTORY_MODE_PRIMARY)
		$ret['site']['directory_mode'] = 'primary';
	elseif($dirmode == DIRECTORY_MODE_SECONDARY)
		$ret['site']['directory_mode'] = 'secondary';
	elseif($dirmode == DIRECTORY_MODE_STANDALONE)
		$ret['site']['directory_mode'] = 'standalone';
	if($dirmode != DIRECTORY_MODE_NORMAL)
		$ret['site']['directory_url'] = z_root() . '/dirsearch';


	// hide detailed site information if you're off the grid

	if($dirmode != DIRECTORY_MODE_STANDALONE) {

		$register_policy = intval(get_config('system','register_policy'));

		if($register_policy == REGISTER_CLOSED)
			$ret['site']['register_policy'] = 'closed';
		if($register_policy == REGISTER_APPROVE)
			$ret['site']['register_policy'] = 'approve';
		if($register_policy == REGISTER_OPEN)
			$ret['site']['register_policy'] = 'open';


		$access_policy = intval(get_config('system','access_policy'));

		if($access_policy == ACCESS_PRIVATE)
			$ret['site']['access_policy'] = 'private';
		if($access_policy == ACCESS_PAID)
			$ret['site']['access_policy'] = 'paid';
		if($access_policy == ACCESS_FREE)
			$ret['site']['access_policy'] = 'free';
		if($access_policy == ACCESS_TIERED)
			$ret['site']['access_policy'] = 'tiered';

		$ret['site']['accounts'] = account_total();
	
		require_once('include/identity.php');
		$ret['site']['channels'] = channel_total();


		$ret['site']['version'] = PLATFORM_NAME . ' ' . RED_VERSION . '[' . DB_UPDATE_VERSION . ']';

		$ret['site']['admin'] = get_config('system','admin_email');

		$a = get_app();

		$visible_plugins = array();
		if(is_array($a->plugins) && count($a->plugins)) {
			$r = q("select * from addon where hidden = 0");
			if($r)
				foreach($r as $rr)
					$visible_plugins[] = $rr['name'];
		}

		$ret['site']['plugins'] = $visible_plugins;
		$ret['site']['sitehash'] = get_config('system','location_hash');
		$ret['site']['sitename'] = get_config('system','sitename');
		$ret['site']['sellpage'] = get_config('system','sellpage');
		$ret['site']['location'] = get_config('system','site_location');
		$ret['site']['realm'] = get_directory_realm();
		$ret['site']['project'] = PLATFORM_NAME;

	}

	check_zotinfo($e,$x,$ret);


	call_hooks('zot_finger',$ret);
	return($ret);

}


function check_zotinfo($channel,$locations,&$ret) {


//	logger('locations: ' . print_r($locations,true),LOGGER_DATA);

	// This function will likely expand as we find more things to detect and fix.
	// 1. Because magic-auth is reliant on it, ensure that the system channel has a valid hubloc
	//    Force this to be the case if anything is found to be wrong with it. 

	// @FIXME ensure that the system channel exists in the first place and has an xchan

	if($channel['channel_system']) {
		// the sys channel must have a location (hubloc)
		$valid_location = false;
		if((count($locations) === 1) && ($locations[0]['primary']) && (! $locations[0]['deleted'])) {
			if((rsa_verify($locations[0]['url'],base64url_decode($locations[0]['url_sig']),$channel['channel_pubkey']))
				&& ($locations[0]['sitekey'] === get_config('system','pubkey'))
				&& ($locations[0]['url'] === z_root()))
				$valid_location = true;
			else
				logger('sys channel: invalid url signature');
		}

		if((! $locations) || (! $valid_location)) {

			logger('System channel locations are not valid. Attempting repair.');

			// Don't trust any existing records. Just get rid of them, but only do this 
			// for the sys channel as normal channels will be trickier.
 
			q("delete from hubloc where hubloc_hash = '%s'",
				dbesc($channel['channel_hash'])
			);
			$r = q("insert into hubloc ( hubloc_guid, hubloc_guid_sig, hubloc_hash, hubloc_addr, hubloc_primary,
				hubloc_url, hubloc_url_sig, hubloc_host, hubloc_callback, hubloc_sitekey, hubloc_network )
				values ( '%s', '%s', '%s', '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s' )",
				dbesc($channel['channel_guid']),
				dbesc($channel['channel_guid_sig']),
				dbesc($channel['channel_hash']),
				dbesc($channel['channel_address'] . '@' . get_app()->get_hostname()),
				intval(1),
				dbesc(z_root()),
				dbesc(base64url_encode(rsa_sign(z_root(),$channel['channel_prvkey']))),
				dbesc(get_app()->get_hostname()),
				dbesc(z_root() . '/post'),
				dbesc(get_config('system','pubkey')),
				dbesc('zot')
			);
			if($r) {
				$x = zot_encode_locations($channel);
				if($x) {
					$ret['locations'] = $x;
				}
			}
			else {
				logger('Unable to store sys hub location');
			}
		}
	}
}

function delivery_report_is_storable($dr) {

	call_hooks('dreport_is_storable',$dr);

	// let plugins accept or reject - if neither, continue on
	if(array_key_exists('accept',$dr) && intval($dr['accept']))
		return true;
	if(array_key_exists('reject',$dr) && intval($dr['reject']))
		return false;

	if(! ($dr['sender']))
		return false;

	// Is the sender one of our channels?

	$c = q("select channel_id from channel where channel_hash = '%s' limit 1",
		dbesc($dr['sender'])
	);
	if(! $c)
		return false;

	// is the recipient one of our connections, or do we want to store every report? 

	$r = explode(' ', $dr['recipient']);
	$rxchan = $r[0];
	$pcf = get_pconfig($c[0]['channel_id'],'system','dreport_store_all');
	if($pcf)
		return true;

	$r = q("select abook_id from abook where abook_xchan = '%s' and abook_channel = %d limit 1",
		dbesc($rxchan),
		intval($c[0]['channel_id'])
	);
	if($r)
		return true;

	return false;

}


