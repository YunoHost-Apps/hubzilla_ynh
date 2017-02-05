<?php
/**
 * @file include/security.php
 *
 * @brief Some security related functions.
 */

/**
 * @param int $user_record The account_id
 * @param array $channel
 * @param bool $login_initial default false
 * @param bool $interactive default false
 * @param bool $return
 * @param bool $update_lastlog
 */
function authenticate_success($user_record, $channel = null, $login_initial = false, $interactive = false, $return = false, $update_lastlog = false) {

	$_SESSION['addr'] = $_SERVER['REMOTE_ADDR'];

	$lastlog_updated = false;

	if(x($user_record, 'account_id')) {
		App::$account = $user_record;
		$_SESSION['account_id'] = $user_record['account_id'];
		$_SESSION['authenticated'] = 1;

		if($channel)
			$uid_to_load = $channel['channel_id'];

		if(! $uid_to_load) {
			$uid_to_load = (((x($_SESSION,'uid')) && (intval($_SESSION['uid'])))
				? intval($_SESSION['uid'])
				: intval(App::$account['account_default_channel'])
			);
		}

		if($uid_to_load) {
			change_channel($uid_to_load);
		}

		if($login_initial || $update_lastlog) {
			q("update account set account_lastlog = '%s' where account_id = %d",
				dbesc(datetime_convert()),
				intval($_SESSION['account_id'])
			);
			App::$account['account_lastlog'] = datetime_convert();
			$lastlog_updated = true;
			call_hooks('logged_in', App::$account);
		}

	}

	if(($login_initial) && (! $lastlog_updated)) {

		call_hooks('logged_in', $user_record);

		// might want to log success here
	}

	if($return || x($_SESSION, 'workflow')) {
		unset($_SESSION['workflow']);
		return;
	}

	if((App::$module !== 'home') && x($_SESSION,'login_return_url') && strlen($_SESSION['login_return_url'])) {
		$return_url = $_SESSION['login_return_url'];

		// don't let members get redirected to a raw ajax page update - this can happen
		// if DHCP changes the IP address at an unfortunate time and paranoia is turned on
		if(strstr($return_url,'update_'))
			$return_url = '';

		unset($_SESSION['login_return_url']);
		goaway(z_root() . '/' . $return_url);
	}

	/* This account has never created a channel. Send them to new_channel by default */

	if(App::$module === 'login') {
		$r = q("select count(channel_id) as total from channel where channel_account_id = %d and channel_removed = 0 ",
			intval(App::$account['account_id'])
		);
		if(($r) && (! $r[0]['total']))
			goaway(z_root() . '/new_channel');
	}

	/* else just return */
}

function atoken_login($atoken) {
	if(! $atoken)
		return false;

	$_SESSION['authenticated'] = 1;
	$_SESSION['visitor_id'] = $atoken['xchan_hash'];
	$_SESSION['atoken'] = $atoken['atoken_id'];

	\App::set_observer($atoken);

	return true;
}

/**
 * @brief
 *
 * @param array $atoken
 * @return array|null
 */
function atoken_xchan($atoken) {

	$c = channelx_by_n($atoken['atoken_uid']);
	if($c) {
		return [
			'atoken_id' => $atoken['atoken_id'],
			'xchan_hash' =>  substr($c['channel_hash'],0,16) . '.' . $atoken['atoken_name'],
			'xchan_name' => $atoken['atoken_name'],
			'xchan_addr' => t('guest:') . $atoken['atoken_name'] . '@' . \App::get_hostname(),
			'xchan_network' => 'unknown',
			'xchan_url' => z_root(),
			'xchan_hidden' => 1,
			'xchan_photo_mimetype' => 'image/jpeg',
			'xchan_photo_l' => get_default_profile_photo(300),
			'xchan_photo_m' => get_default_profile_photo(80),
			'xchan_photo_s' => get_default_profile_photo(48)
		];
	}

	return null;
}

function atoken_delete($atoken_id) {

	$r = q("select * from atoken where atoken_id = %d",
		intval($atoken_id)
	);
	if(! $r)
		return;

	$c = q("select channel_id, channel_hash from channel where channel_id = %d",
		intval($r[0]['atoken_uid'])
	);
	if(! $c)
		return;

	$atoken_xchan = substr($c[0]['channel_hash'],0,16) . '.' . $r[0]['atoken_name'];

	q("delete from atoken where atoken_id = %d",
		intval($atoken_id)
	);
	q("delete from abconfig where chan = %d and xchan = '%s'",
		intval($c[0]['channel_id']),
		dbesc($atoken_xchan)
	);
}

/**
 * @brief
 *
 * In order for atoken logins to create content (such as posts) they need a stored xchan.
 * we'll create one on the first atoken_login; it can't really ever go away but perhaps
 * @fixme we should set xchan_deleted if it's expired or removed
 *
 * @param array $xchan
 * @return void|boolean
 */
function atoken_create_xchan($xchan) {

	$r = q("select xchan_hash from xchan where xchan_hash = '%s'",
		dbesc($xchan['xchan_hash'])
	);
	if($r)
		return;

	$r = q("insert into xchan ( xchan_hash, xchan_guid, xchan_addr, xchan_url, xchan_name, xchan_network, xchan_photo_mimetype, xchan_photo_l, xchan_photo_m, xchan_photo_s )
		values ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s') ",
		dbesc($xchan['xchan_hash']),
		dbesc($xchan['xchan_hash']),
		dbesc($xchan['xchan_addr']),
		dbesc($xchan['xchan_url']),
		dbesc($xchan['xchan_name']),
		dbesc($xchan['xchan_network']),
		dbesc($xchan['xchan_photo_mimetype']),
		dbesc($xchan['xchan_photo_l']),
		dbesc($xchan['xchan_photo_m']),
		dbesc($xchan['xchan_photo_s'])
	);

	return true;
}

function atoken_abook($uid,$xchan_hash) {

	if(substr($xchan_hash,16,1) != '.')
		return false;

	$r = q("select channel_hash from channel where channel_id = %d limit 1",
		intval($uid)
	);

	if(! $r)
		return false;

	$x = q("select * from atoken where atoken_uid = %d and atoken_name = '%s'",
		intval($uid),
		dbesc(substr($xchan_hash,17))
	);

	if($x) {
		$xchan = atoken_xchan($x[0]);
		$xchan['abook_blocked'] = 0;
		$xchan['abook_ignored'] = 0;
		$xchan['abook_pending'] = 0;
		return $xchan;
	}

	return false;
}


function pseudo_abook($xchan) {
	if(! $xchan)
		return false;

	// set abook_pseudo to flag that we aren't really connected.

	$xchan['abook_pseudo']  = 1;
	$xchan['abook_blocked'] = 0;
	$xchan['abook_ignored'] = 0;
	$xchan['abook_pending'] = 0;

	return $xchan;
}


/**
 * @brief Change to another channel with current logged-in account.
 *
 * @param int $change_channel The channel_id of the channel you want to change to
 *
 * @return bool|array false or channel record of the new channel
 */
function change_channel($change_channel) {

	$ret = false;

	if($change_channel) {

		$r = q("select channel.*, xchan.* from channel left join xchan on channel.channel_hash = xchan.xchan_hash where channel_id = %d and channel_account_id = %d and channel_removed = 0 limit 1",
			intval($change_channel),
			intval(get_account_id())
		);

		// It's not there.  Is this an administrator, and is this the sys channel?
		if (is_developer()) {
			if (! $r) {
				if (is_site_admin()) {
					$r = q("select channel.*, xchan.* from channel left join xchan on channel.channel_hash = xchan.xchan_hash where channel_id = %d and channel_system = 1 and channel_removed = 0 limit 1",
						intval($change_channel)
					);
				}
			}
		}

		if($r) {
			$hash = $r[0]['channel_hash'];
			$_SESSION['uid'] = intval($r[0]['channel_id']);
			App::set_channel($r[0]);
			$_SESSION['theme'] = $r[0]['channel_theme'];
			$_SESSION['mobile_theme'] = get_pconfig(local_channel(),'system', 'mobile_theme');
			date_default_timezone_set($r[0]['channel_timezone']);
			$ret = $r[0];
		}
		$x = q("select * from xchan where xchan_hash = '%s' limit 1",
			dbesc($hash)
		);
		if($x) {
			$_SESSION['my_url'] = $x[0]['xchan_url'];
			$_SESSION['my_address'] = channel_reddress($r[0]);

			App::set_observer($x[0]);
			App::set_perms(get_all_perms(local_channel(), $hash));
		}
		if(! is_dir('store/' . $r[0]['channel_address']))
			@os_mkdir('store/' . $r[0]['channel_address'], STORAGE_DEFAULT_PERMISSIONS,true);

		$arr = [ 'channel_id' => $change_channel, 'chanx' => $ret ];
		call_hooks('change_channel', $arr);
	}

	return $ret;
}

/**
 * @brief Creates an additional SQL where statement to check permissions.
 *
 * @param int $owner_id
 * @param bool $remote_observer (optional) use current observer if unset
 * @param $table (optional)
 *
 * @return string additional SQL where statement
 */
function permissions_sql($owner_id, $remote_observer = null, $table = '') {

	$local_channel = local_channel();

	/**
	 * Construct permissions
	 *
	 * default permissions - anonymous user
	 */

	if($table)
		$table .= '.';

	$sql = " AND {$table}allow_cid = ''
			 AND {$table}allow_gid = ''
			 AND {$table}deny_cid  = ''
			 AND {$table}deny_gid  = ''
	";

	/**
	 * Profile owner - everything is visible
	 */

	if(($local_channel) && ($local_channel == $owner_id)) {
		$sql = '';
	}

	/**
	 * Authenticated visitor. Unless pre-verified,
	 * check that the contact belongs to this $owner_id
	 * and load the groups the visitor belongs to.
	 * If pre-verified, the caller is expected to have already
	 * done this and passed the groups into this function.
	 */

	else {
		$observer = ((! is_null($remote_observer)) ? $remote_observer : get_observer_hash());
		if($observer) {
			$groups = init_groups_visitor($observer);

			$gs = '<<>>'; // should be impossible to match

			if(is_array($groups) && count($groups)) {
				foreach($groups as $g)
					$gs .= '|<' . $g . '>';
			}
			$regexop = db_getfunc('REGEXP');
			$sql = sprintf(
				" AND ( NOT ({$table}deny_cid like '%s' OR {$table}deny_gid $regexop '%s')
				  AND ( {$table}allow_cid like '%s' OR {$table}allow_gid $regexop '%s' OR ( {$table}allow_cid = '' AND {$table}allow_gid = '') )
				  )
				",
				dbesc(protect_sprintf( '%<' . $observer . '>%')),
				dbesc($gs),
				dbesc(protect_sprintf( '%<' . $observer . '>%')),
				dbesc($gs)
			);
		}
	}

	return $sql;
}

/**
 * @brief Creates an addiontal SQL where statement to check permissions for an item.
 *
 * @param int $owner_id
 * @param bool $remote_observer (optional) use current observer if unset
 *
 * @return string additional SQL where statement
 */
function item_permissions_sql($owner_id, $remote_observer = null) {

	$local_channel = local_channel();

	/**
	 * Construct permissions
	 *
	 * default permissions - anonymous user
	 */

	$sql = " AND item_private = 0 ";

	/**
	 * Profile owner - everything is visible
	 */

	if(($local_channel) && ($local_channel == $owner_id)) {
		$sql = '';
	}

	/**
	 * Authenticated visitor. Unless pre-verified,
	 * check that the contact belongs to this $owner_id
	 * and load the groups the visitor belongs to.
	 * If pre-verified, the caller is expected to have already
	 * done this and passed the groups into this function.
	 */

	else {
		$observer = (($remote_observer) ? $remote_observer : get_observer_hash());

		if($observer) {

			$s = scopes_sql($owner_id,$observer);

			$groups = init_groups_visitor($observer);

			$gs = '<<>>'; // should be impossible to match

			if(is_array($groups) && count($groups)) {
				foreach($groups as $g)
					$gs .= '|<' . $g . '>';
			}
			$regexop = db_getfunc('REGEXP');
			$sql = sprintf(
				" AND (( NOT (deny_cid like '%s' OR deny_gid $regexop '%s')
				  AND ( allow_cid like '%s' OR allow_gid $regexop '%s' OR ( allow_cid = '' AND allow_gid = '' AND item_private = 0 ))
				  ) OR ( item_private = 1 $s ))
				",
				dbesc(protect_sprintf( '%<' . $observer . '>%')),
				dbesc($gs),
				dbesc(protect_sprintf( '%<' . $observer . '>%')),
				dbesc($gs)
			);
		}
	}

	return $sql;
}

/**
 * Remote visitors also need to be checked against the public_scope parameter if item_private is set.
 * This function checks the various permutations of that field for any which apply to this observer.
 *
 */



function scopes_sql($uid,$observer) {
	$str = " and ( public_policy = 'authenticated' ";
	if(! is_foreigner($observer))
		$str .= " or public_policy = 'network: red' ";
	if(local_channel())
		$str .= " or public_policy = 'site: " . App::get_hostname() . "' ";

	$ab = q("select * from abook where abook_xchan = '%s' and abook_channel = %d limit 1",
		dbesc($observer),
		intval($uid)
	);
	if(! $ab)
		return $str . " ) ";
	if($ab[0]['abook_pending'])
		$str .= " or public_policy = 'any connections' ";
	$str .= " or public_policy = 'contacts' ) ";
	return $str;
}







/**
 * @param string $observer_hash
 *
 * @return string additional SQL where statement
 */
function public_permissions_sql($observer_hash) {

	//$observer = App::get_observer();
	$groups = init_groups_visitor($observer_hash);

	$gs = '<<>>'; // should be impossible to match

	if(is_array($groups) && count($groups)) {
		foreach($groups as $g)
			$gs .= '|<' . $g . '>';
	}
	$sql = '';
	if($observer_hash) {
		$regexop = db_getfunc('REGEXP');
		$sql = sprintf(
			" OR (( NOT (deny_cid like '%s' OR deny_gid $regexop '%s')
			  AND ( allow_cid like '%s' OR allow_gid $regexop '%s' OR ( allow_cid = '' AND allow_gid = '' AND item_private = 0 ) )
			  ))
			",
			dbesc(protect_sprintf( '%<' . $observer_hash . '>%')),
			dbesc($gs),
			dbesc(protect_sprintf( '%<' . $observer_hash . '>%')),
			dbesc($gs)
		);
	}

	return $sql;
}


/*
 * Functions used to protect against Cross-Site Request Forgery
 * The security token has to base on at least one value that an attacker can't know - here it's the session ID and the private key.
 * In this implementation, a security token is reusable (if the user submits a form, goes back and resubmits the form, maybe with small changes;
 * or if the security token is used for ajax-calls that happen several times), but only valid for a certain amout of time (3hours).
 * The "typename" seperates the security tokens of different types of forms. This could be relevant in the following case:
 *	  A security token is used to protekt a link from CSRF (e.g. the "delete this profile"-link).
 *    If the new page contains by any chance external elements, then the used security token is exposed by the referrer.
 *    Actually, important actions should not be triggered by Links / GET-Requests at all, but somethimes they still are,
 *    so this mechanism brings in some damage control (the attacker would be able to forge a request to a form of this type, but not to forms of other types).
 */
function get_form_security_token($typename = '') {

	$timestamp = time();
	$sec_hash = hash('whirlpool', App::$observer['xchan_guid'] . ((local_channel()) ? App::$channel['channel_prvkey'] : '') . session_id() . $timestamp . $typename);

	return $timestamp . '.' . $sec_hash;
}

function check_form_security_token($typename = '', $formname = 'form_security_token') {
	if (!x($_REQUEST, $formname)) return false;
	$hash = $_REQUEST[$formname];

	$max_livetime = 10800; // 3 hours

	$x = explode('.', $hash);
	if (time() > (IntVal($x[0]) + $max_livetime)) return false;

	$sec_hash = hash('whirlpool', App::$observer['xchan_guid'] . ((local_channel()) ? App::$channel['channel_prvkey'] : '') . session_id() . $x[0] . $typename);

	return ($sec_hash == $x[1]);
}

function check_form_security_std_err_msg() {
	return t('The form security token was not correct. This probably happened because the form has been opened for too long (>3 hours) before submitting it.') . EOL;
}
function check_form_security_token_redirectOnErr($err_redirect, $typename = '', $formname = 'form_security_token') {
	if (!check_form_security_token($typename, $formname)) {
		logger('check_form_security_token failed: user ' . App::$observer['xchan_name'] . ' - form element ' . $typename);
		logger('check_form_security_token failed: _REQUEST data: ' . print_r($_REQUEST, true), LOGGER_DATA);
		notice( check_form_security_std_err_msg() );
		goaway(z_root() . $err_redirect );
	}
}
function check_form_security_token_ForbiddenOnErr($typename = '', $formname = 'form_security_token') {
	if (!check_form_security_token($typename, $formname)) {
		logger('check_form_security_token failed: user ' . App::$observer['xchan_name'] . ' - form element ' . $typename);
		logger('check_form_security_token failed: _REQUEST data: ' . print_r($_REQUEST, true), LOGGER_DATA);
		header('HTTP/1.1 403 Forbidden');
		killme();
	}
}


// Returns an array of group hash id's on this entire site (across all channels) that this connection is a member of.
// var $contact_id = xchan_hash of connection

function init_groups_visitor($contact_id) {
	$groups = array();
	$r = q("SELECT hash FROM groups left join group_member on groups.id = group_member.gid WHERE xchan = '%s' ",
		dbesc($contact_id)
	);
	if($r) {
		foreach($r as $rr)
			$groups[] = $rr['hash'];
	}
	return $groups;
}



// This is used to determine which uid have posts which are visible to the logged in user (from the API) for the
// public_timeline, and we can use this in a community page by making
// $perms = (PERMS_NETWORK|PERMS_PUBLIC) unless logged in.
// Collect uids of everybody on this site who has opened their posts to everybody on this site (or greater visibility)
// We always include yourself if logged in because you can always see your own posts
// resolving granular permissions for the observer against every person and every post on the site
// will likely be too expensive.
// Returns a string list of comma separated channel_ids suitable for direct inclusion in a SQL query

function stream_perms_api_uids($perms = NULL, $limit = 0, $rand = 0 ) {
	$perms = is_null($perms) ? (PERMS_SITE|PERMS_NETWORK|PERMS_PUBLIC) : $perms;

	$ret = array();
	$limit_sql = (($limit) ? " LIMIT " . intval($limit) . " " : '');
	$random_sql = (($rand) ? " ORDER BY " . db_getfunc('RAND') . " " : '');
	if(local_channel())
		$ret[] = local_channel();
	$x = q("select uid from pconfig where cat = 'perm_limits' and k = 'view_stream' and ( v & %d ) > 0 ",
		intval($perms)
	);
	if($x) {
		$ids = ids_to_querystr($x,'uid');
		$r = q("select channel_id from channel where channel_id in ( $ids ) and ( channel_pageflags & %d ) = 0 and channel_system = 0 and channel_removed = 0 $random_sql $limit_sql ",
			intval(PAGE_ADULT|PAGE_CENSORED)
		);
		if($r) {
			foreach($r as $rr)
				if(! in_array($rr['channel_id'], $ret))
					$ret[] = $rr['channel_id'];
		}
	}

	$str = '';
	if($ret) {
		foreach($ret as $rr) {
			if($str)
				$str .= ',';
			$str .= intval($rr);
		}
	}
	else
		$str = "''";

	logger('stream_perms_api_uids: ' . $str, LOGGER_DEBUG);

	return $str;
}

function stream_perms_xchans($perms = NULL ) {
	$perms = is_null($perms) ? (PERMS_SITE|PERMS_NETWORK|PERMS_PUBLIC) : $perms;

	$ret = array();
	if(local_channel())
		$ret[] = get_observer_hash();

	$x = q("select uid from pconfig where cat = 'perm_limits' and k = 'view_stream' and ( v & %d ) > 0 ",
		intval($perms)
	);
	if($x) {
		$ids = ids_to_querystr($x,'uid');
		$r = q("select channel_hash from channel where channel_id in ( $ids ) and ( channel_pageflags & %d ) = 0 and channel_system = 0 and channel_removed = 0 ",
			intval(PAGE_ADULT|PAGE_CENSORED)
		);

		if($r) {
			foreach($r as $rr)
				if(! in_array($rr['channel_hash'], $ret))
					$ret[] = $rr['channel_hash'];
		}
	}
	$str = '';
	if($ret) {
		foreach($ret as $rr) {
			if($str)
				$str .= ',';
			$str .= "'" . dbesc($rr) . "'";
		}
	}
	else
		$str = "''";

	logger('stream_perms_xchans: ' . $str, LOGGER_DEBUG);

	return $str;
}
