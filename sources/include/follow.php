<?php /** @file */


//
// Takes a $uid and the channel associated with the uid, and a url/handle and adds a new channel

// Returns an array
//  $return['success'] boolean true if successful
//  $return['abook'] Address book entry joined with xchan if successful
//  $return['message'] error text if success is false.

require_once('include/zot.php');

function new_contact($uid,$url,$channel,$interactive = false, $confirm = false) {



	$result = array('success' => false,'message' => '');

	$is_red = false;
	$is_http = ((strpos($url,'://') !== false) ? true : false);

	if($is_http && substr($url,-1,1) === '/')
		$url = substr($url,0,-1);

	if(! allowed_url($url)) {
		$result['message'] = t('Channel is blocked on this site.');
		return $result;
	}

	if(! $url) {
		$result['message'] = t('Channel location missing.');
		return $result;
	}


	// check service class limits

	$r = q("select count(*) as total from abook where abook_channel = %d and abook_self = 0 ",
		intval($uid)
	);
	if($r)
		$total_channels = $r[0]['total'];

	if(! service_class_allows($uid,'total_channels',$total_channels)) {
		$result['message'] = upgrade_message();
		return $result;
	}


	$arr = array('url' => $url, 'channel' => array());

	call_hooks('follow', $arr);

	if($arr['channel']['success']) 
		$ret = $arr['channel'];
	elseif(! $is_http)
		$ret = Zotlabs\Zot\Finger::run($url,$channel);

	if($ret && is_array($ret) && $ret['success']) {
		$is_red = true;
		$j = $ret;
	}

	$my_perms = get_channel_default_perms($uid);

	$role = get_pconfig($uid,'system','permissions_role');
	if($role) {
		$x = \Zotlabs\Access\PermissionRoles::role_perms($role);
		if($x['perms_connect'])
			$my_perms = $x['perms_connect'];
	}

	if($is_red && $j) {

		logger('follow: ' . $url . ' ' . print_r($j,true), LOGGER_DEBUG);


		if(! ($j['success'] && $j['guid'])) {
			$result['message'] = t('Response from remote channel was incomplete.');
			logger('mod_follow: ' . $result['message']);
			return $result;
		}

		// Premium channel, set confirm before callback to avoid recursion

		if(array_key_exists('connect_url',$j) && ($interactive) && (! $confirm))
			goaway(zid($j['connect_url']));


		// do we have an xchan and hubloc?
		// If not, create them.	

		$x = import_xchan($j);

		if(array_key_exists('deleted',$j) && intval($j['deleted'])) {
			$result['message'] = t('Channel was deleted and no longer exists.');
			return $result;
		}

		if(! $x['success']) 
			return $x;

		$xchan_hash = $x['hash'];

		if( array_key_exists('permissions',$j) && array_key_exists('data',$j['permissions'])) {
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

		if(is_array($permissions) && $permissions) {
			foreach($permissions as $k => $v) {
				set_abconfig($channel['channel_uid'],$xchan_hash,'their_perms',$k,intval($v));
			}
		}
	}
	else {

		$xchan_hash = '';

		$r = q("select * from xchan where xchan_hash = '%s' or xchan_url = '%s' limit 1",
			dbesc($url),
			dbesc($url)
		);

		if(! $r) {
			// attempt network auto-discovery

			$d = discover_by_webbie($url);

			if((! $d) && ($is_http)) {

				// try RSS discovery

				if(get_config('system','feed_contacts')) {
					$d = discover_by_url($url);
				}
				else {
					$result['message'] = t('Protocol disabled.');
					return $result;
				}
			}

			if($d) {
				$r = q("select * from xchan where xchan_hash = '%s' or xchan_url = '%s' limit 1",
					dbesc($url),
					dbesc($url)
				);
			}
		}

		// if discovery was a success we should have an xchan record in $r

		if($r) {
			$xchan = $r[0];
			$xchan_hash = $r[0]['xchan_hash'];
			$their_perms = 0;
		}
	}


	if(! $xchan_hash) {
		$result['message'] = t('Channel discovery failed.');
		logger('follow: ' . $result['message']);
		return $result;
	}

	$allowed = (($is_red || $r[0]['xchan_network'] === 'rss') ? 1 : 0);

	$x = array('channel_id' => $uid, 'follow_address' => $url, 'xchan' => $r[0], 'allowed' => $allowed, 'singleton' => 0);

	call_hooks('follow_allow',$x);

	if(! $x['allowed']) {
		$result['message'] = t('Protocol disabled.');
		return $result;
	}

	$singleton = intval($x['singleton']);

	$aid = $channel['channel_account_id'];
	$hash = get_observer_hash();
	$default_group = $channel['channel_default_group'];

	if($xchan['xchan_network'] === 'rss') {

		// check service class feed limits

		$r = q("select count(*) as total from abook where abook_account = %d and abook_feed = 1 ",
			intval($aid)
		);
		if($r)
			$total_feeds = $r[0]['total'];

		if(! service_class_allows($uid,'total_feeds',$total_feeds)) {
			$result['message'] = upgrade_message();
			return $result;
		}
	}

	if($hash == $xchan_hash) {
		$result['message'] = t('Cannot connect to yourself.');
		return $result;
	}

	$r = q("select abook_id, abook_xchan, abook_pending, abook_instance from abook where abook_xchan = '%s' and abook_channel = %d limit 1",
		dbesc($xchan_hash),
		intval($uid)
	);

	if($is_http) {

		// Always set these "remote" permissions for feeds since we cannot interact with them
		// to negotiate a suitable permission response

		set_abconfig($uid,$xchan_hash,'their_perms','view_stream',1);
		set_abconfig($uid,$xchan_hash,'their_perms','republish',1);
	}

	if($r) {
		$abook_instance = $r[0]['abook_instance'];

		if(($singleton) && strpos($abook_instance,z_root()) === false) {
			if($abook_instance)
				$abook_instance .= ',';
			$abook_instance .= z_root();
		}

		$x = q("update abook set abook_instance = '%s' where abook_id = %d",
			dbesc($abook_instance),
			intval($r[0]['abook_id'])
		);

		if(intval($r[0]['abook_pending'])) {

			$abook_my_perms = get_channel_default_perms($uid);
            $role = get_pconfig($uid,'system','permissions_role');
            if($role) {
                $x = \Zotlabs\Access\PermissionRoles::role_perms($role);
                if($x['perms_connect']) {
                    $abook_my_perms = $x['perms_connect'];
                }
            }

            $filled_perms = \Zotlabs\Access\Permissions::FilledPerms($abook_my_perms);
            foreach($filled_perms as $k => $v) {
                set_abconfig($uid,$r[0]['abook_xchan'],'my_perms',$k,$v);
            }

			$x = q("update abook set abook_pending = 0 where abook_id = %d",
				intval($r[0]['abook_id'])
			);
		}
	}
	else {
		$closeness = get_pconfig($uid,'system','new_abook_closeness');
		if($closeness === false)
			$closeness = 80;

		$r = q("insert into abook ( abook_account, abook_channel, abook_closeness, abook_xchan, abook_feed, abook_created, abook_updated, abook_instance )
			values( %d, %d, %d, '%s', %d, '%s', '%s', '%s' ) ",
			intval($aid),
			intval($uid),
			intval($closeness),
			dbesc($xchan_hash),
			intval(($is_http) ? 1 : 0),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			dbesc(($singleton) ? z_root() : '')
		);
	}

	if(! $r)
		logger('mod_follow: abook creation failed');

	$all_perms = \Zotlabs\Access\Permissions::Perms();
	if($all_perms) {
		foreach($all_perms as $k => $v) {
			if(in_array($k,$my_perms))
				set_abconfig($uid,$xchan_hash,'my_perms',$k,1);
			else
				set_abconfig($uid,$xchan_hash,'my_perms',$k,0);
		}
	}

	$r = q("select abook.*, xchan.* from abook left join xchan on abook_xchan = xchan_hash 
		where abook_xchan = '%s' and abook_channel = %d limit 1",
		dbesc($xchan_hash),
		intval($uid)
	);

	if($r) {
		$result['abook'] = $r[0];
		Zotlabs\Daemon\Master::Summon(array('Notifier', 'permission_create', $result['abook']['abook_id']));
	}

	$arr = array('channel_id' => $uid, 'channel' => $channel, 'abook' => $result['abook']);

	call_hooks('follow', $arr);

	/** If there is a default group for this channel, add this connection to it */

	if($default_group) {
		require_once('include/group.php');
		$g = group_rec_byhash($uid,$default_group);
		if($g)
			group_add_member($uid,'',$xchan_hash,$g['id']);
	}

	$result['success'] = true;
	return $result;
}
