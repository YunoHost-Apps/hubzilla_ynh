<?php
/**
 * @file include/channel.php
 */

require_once('include/zot.php');
require_once('include/crypto.php');
require_once('include/menu.php');
require_once('include/perm_upgrade.php');

/**
 * @brief Called when creating a new channel.
 *
 * Checks the account's service class and number of current channels to determine
 * whether creating a new channel is within the current service class constraints.
 *
 * @param int $account_id
 *     Account_id used for this request
 *
 * @returns associative array with:
 *  * \e boolean \b success boolean true if creating a new channel is allowed for this account
 *  * \e string \b message (optional) if success is false, optional error text
 *  * \e int \b total_identities
 */
function identity_check_service_class($account_id) {
	$ret = array('success' => false, 'message' => '');

	$r = q("select count(channel_id) as total from channel where channel_account_id = %d and channel_removed = 0 ",
		intval($account_id)
	);
	if(! ($r && count($r))) {
		$ret['total_identities'] = 0;
		$ret['message'] = t('Unable to obtain identity information from database');
		return $ret;
	}

	$ret['total_identities'] = intval($r[0]['total']);

	if (! account_service_class_allows($account_id, 'total_identities', $r[0]['total'])) {
		$ret['message'] .= upgrade_message();
		return $ret;
	}

	$ret['success'] = true;

	return $ret;
}


/**
 * @brief Determine if the channel name is allowed when creating a new channel.
 *
 * This action is pluggable.
 * We're currently only checking for an empty name or one that exceeds our
 * storage limit (255 chars). 255 chars is probably going to create a mess on
 * some pages.
 * Plugins can set additional policies such as full name requirements, character
 * sets, multi-byte length, etc.
 *
 * @param string $name
 *
 * @returns nil return if name is valid, or string describing the error state.
 */
function validate_channelname($name) {

	if (! $name)
		return t('Empty name');

	if (strlen($name) > 255)
		return t('Name too long');

	$arr = array('name' => $name);
	call_hooks('validate_channelname', $arr);

	if (x($arr, 'message'))
		return $arr['message'];
}


/**
 * @brief Create a system channel - which has no account attached.
 *
 */
function create_sys_channel() {
	if (get_sys_channel())
		return;

	// Ensure that there is a host keypair.

	if ((! get_config('system', 'pubkey')) && (! get_config('system', 'prvkey'))) {
		require_once('include/crypto.php');
		$hostkey = new_keypair(4096);
		set_config('system', 'pubkey', $hostkey['pubkey']);
		set_config('system', 'prvkey', $hostkey['prvkey']);
	}

	create_identity(array(
		'account_id' => 'xxx',  // This will create an identity with an (integer) account_id of 0, but account_id is required
		'nickname' => 'sys',
		'name' => 'System',
		'pageflags' => 0,
		'publish' => 0,
		'system' => 1
	));
}


/**
 * @brief Returns the sys channel.
 *
 * @return array|boolean
 */
function get_sys_channel() {
	$r = q("select * from channel left join xchan on channel_hash = xchan_hash where channel_system = 1 limit 1");

	if ($r)
		return $r[0];

	return false;
}


/**
 * @brief Checks if $channel_id is sys channel.
 *
 * @param int $channel_id
 * @return boolean
 */
function is_sys_channel($channel_id) {
	$r = q("select channel_system from channel where channel_id = %d and channel_system = 1 limit 1",
		intval($channel_id)
	);

	if($r)
		return true;

	return false;
}


/**
 * @brief Return the total number of channels on this site.
 *
 * No filtering is performed except to check PAGE_REMOVED.
 *
 * @returns int|booleean
 *   on error returns boolean false
 */
function channel_total() {
	$r = q("select channel_id from channel where channel_removed = 0");

	if (is_array($r))
		return count($r);

	return false;
}


/**
 * @brief Create a new channel.
 *
 * Also creates the related xchan, hubloc, profile, and "self" abook records,
 * and an empty "Friends" group/collection for the new channel.
 *
 * @param array $arr associative array with:
 *  * \e string \b name full name of channel
 *  * \e string \b nickname "email/url-compliant" nickname
 *  * \e int \b account_id to attach with this channel
 *  * [other identity fields as desired]
 *
 * @returns array
 *     'success' => boolean true or false
 *     'message' => optional error text if success is false
 *     'channel' => if successful the created channel array
 */
function create_identity($arr) {

	$ret = array('success' => false);

	if(! $arr['account_id']) {
	$ret['message'] = t('No account identifier');
		return $ret;
	}
	$ret = identity_check_service_class($arr['account_id']);
	if (!$ret['success']) {
		return $ret;
	}
	// save this for auto_friending
	$total_identities = $ret['total_identities'];

	$nick = mb_strtolower(trim($arr['nickname']));
	if(! $nick) {
		$ret['message'] = t('Nickname is required.');
		return $ret;
	}

	$name = escape_tags($arr['name']);
	$pageflags = ((x($arr,'pageflags')) ? intval($arr['pageflags']) : PAGE_NORMAL);
	$system = ((x($arr,'system')) ? intval($arr['system']) : 0);
	$name_error = validate_channelname($arr['name']);
	if($name_error) {
		$ret['message'] = $name_error;
		return $ret;
	}

	if($nick === 'sys' && (! $system)) {
		$ret['message'] = t('Reserved nickname. Please choose another.');
		return $ret;
	}

	if(check_webbie(array($nick)) !== $nick) {
		$ret['message'] = t('Nickname has unsupported characters or is already being used on this site.');
		return $ret;
	}

	$guid = zot_new_uid($nick);
	$key = new_keypair(4096);

	$sig = base64url_encode(rsa_sign($guid,$key['prvkey']));
	$hash = make_xchan_hash($guid,$sig);

	// Force a few things on the short term until we can provide a theme or app with choice

	$publish = 1;

	if(array_key_exists('publish', $arr))
		$publish = intval($arr['publish']);

	$role_permissions = null;

	if(array_key_exists('permissions_role',$arr) && $arr['permissions_role']) {
		$role_permissions = \Zotlabs\Access\PermissionRoles::role_perms($arr['permissions_role']);
	}

	if($role_permissions && array_key_exists('directory_publish',$role_permissions))
		$publish = intval($role_permissions['directory_publish']);

	$primary = true;

	if(array_key_exists('primary', $arr))
		$primary = intval($arr['primary']);

	$expire = 0;

	$r = q("insert into channel ( channel_account_id, channel_primary,
		channel_name, channel_address, channel_guid, channel_guid_sig,
		channel_hash, channel_prvkey, channel_pubkey, channel_pageflags, channel_system, channel_expire_days, channel_timezone )
		values ( %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, %d, '%s' ) ",

		intval($arr['account_id']),
		intval($primary),
		dbesc($name),
		dbesc($nick),
		dbesc($guid),
		dbesc($sig),
		dbesc($hash),
		dbesc($key['prvkey']),
		dbesc($key['pubkey']),
		intval($pageflags),
		intval($system),
		intval($expire),
		dbesc(App::$timezone)
	);

	$r = q("select * from channel where channel_account_id = %d
		and channel_guid = '%s' limit 1",
		intval($arr['account_id']),
		dbesc($guid)
	);

	if(! $r) {
		$ret['message'] = t('Unable to retrieve created identity');
		return $ret;
	}

	if($role_permissions && array_key_exists('limits',$role_permissions))
		$perm_limits = $role_permissions['limits'];
	else
		$perm_limits = site_default_perms();

	foreach($perm_limits as $p => $v)
		\Zotlabs\Access\PermissionLimits::Set($r[0]['channel_id'],$p,$v);

	if($role_permissions && array_key_exists('perms_auto',$role_permissions))
		set_pconfig($r[0]['channel_id'],'system','autoperms',intval($role_permissions['perms_auto']));

	$ret['channel'] = $r[0];

	if(intval($arr['account_id']))
		set_default_login_identity($arr['account_id'],$ret['channel']['channel_id'],false);

	// Create a verified hub location pointing to this site.

	$r = q("insert into hubloc ( hubloc_guid, hubloc_guid_sig, hubloc_hash, hubloc_addr, hubloc_primary,
		hubloc_url, hubloc_url_sig, hubloc_host, hubloc_callback, hubloc_sitekey, hubloc_network )
		values ( '%s', '%s', '%s', '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s' )",
		dbesc($guid),
		dbesc($sig),
		dbesc($hash),
		dbesc(channel_reddress($ret['channel'])),
		intval($primary),
		dbesc(z_root()),
		dbesc(base64url_encode(rsa_sign(z_root(),$ret['channel']['channel_prvkey']))),
		dbesc(App::get_hostname()),
		dbesc(z_root() . '/post'),
		dbesc(get_config('system','pubkey')),
		dbesc('zot')
	);
	if(! $r)
		logger('create_identity: Unable to store hub location');

	$newuid = $ret['channel']['channel_id'];

	$r = q("insert into xchan ( xchan_hash, xchan_guid, xchan_guid_sig, xchan_pubkey, xchan_photo_l, xchan_photo_m, xchan_photo_s, xchan_addr, xchan_url, xchan_follow, xchan_connurl, xchan_name, xchan_network, xchan_photo_date, xchan_name_date, xchan_system ) values ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d)",
		dbesc($hash),
		dbesc($guid),
		dbesc($sig),
		dbesc($key['pubkey']),
		dbesc(z_root() . "/photo/profile/l/{$newuid}"),
		dbesc(z_root() . "/photo/profile/m/{$newuid}"),
		dbesc(z_root() . "/photo/profile/s/{$newuid}"),
		dbesc(channel_reddress($ret['channel'])),
		dbesc(z_root() . '/channel/' . $ret['channel']['channel_address']),
		dbesc(z_root() . '/follow?f=&url=%s'),
		dbesc(z_root() . '/poco/' . $ret['channel']['channel_address']),
		dbesc($ret['channel']['channel_name']),
		dbesc('zot'),
		dbesc(datetime_convert()),
		dbesc(datetime_convert()),
		intval($system)
	);

	// Not checking return value.
	// It's ok for this to fail if it's an imported channel, and therefore the hash is a duplicate

	$r = q("INSERT INTO profile ( aid, uid, profile_guid, profile_name, is_default, publish, fullname, photo, thumb)
		VALUES ( %d, %d, '%s', '%s', %d, %d, '%s', '%s', '%s') ",
		intval($ret['channel']['channel_account_id']),
		intval($newuid),
		dbesc(random_string()),
		t('Default Profile'),
		1,
		$publish,
		dbesc($ret['channel']['channel_name']),
		dbesc(z_root() . "/photo/profile/l/{$newuid}"),
		dbesc(z_root() . "/photo/profile/m/{$newuid}")
	);

	if($role_permissions) {
		$myperms = ((array_key_exists('perms_connect',$role_permissions)) ? $role_permissions['perms_connect'] : array());
	}
	else {
		$x = \Zotlabs\Access\PermissionRoles::role_perms('social');
		$myperms = $x['perms_connect'];
	}

	$r = q("insert into abook ( abook_account, abook_channel, abook_xchan, abook_closeness, abook_created, abook_updated, abook_self )
		values ( %d, %d, '%s', %d, '%s', '%s', %d ) ",
		intval($ret['channel']['channel_account_id']),
		intval($newuid),
		dbesc($hash),
		intval(0),
		dbesc(datetime_convert()),
		dbesc(datetime_convert()),
		intval(1)
	);

	$x = \Zotlabs\Access\Permissions::FilledPerms($myperms);
	foreach($x as $k => $v) {
		set_abconfig($newuid,$hash,'my_perms',$k,$v);
	}

	if(intval($ret['channel']['channel_account_id'])) {

		// Save our permissions role so we can perhaps call it up and modify it later.

		if($role_permissions) {
			set_pconfig($newuid,'system','permissions_role',$arr['permissions_role']);
			if(array_key_exists('online',$role_permissions))
				set_pconfig($newuid,'system','hide_presence',1-intval($role_permissions['online']));
			if(array_key_exists('perms_auto',$role_permissions)) {
				$autoperms = intval($role_permissions['perms_auto']);
				set_pconfig($newuid,'system','autoperms',$autoperms);
				if($autoperms) {
					$x = \Zotlabs\Access\Permissions::FilledPerms($role_permissions['perms_connect']);
					foreach($x as $k => $v) {
						set_pconfig($newuid,'autoperms',$k,$v);
					}
				}
				else {
					$r = q("delete from pconfig where uid = %d and cat = 'autoperms'",
						intval($newuid)
					);
				}
			}
		}

		// Create a group with yourself as a member. This allows somebody to use it
		// right away as a default group for new contacts.

		require_once('include/group.php');
		group_add($newuid, t('Friends'));
		group_add_member($newuid,t('Friends'),$ret['channel']['channel_hash']);

		// if our role_permissions indicate that we're using a default collection ACL, add it.

		if(is_array($role_permissions) && $role_permissions['default_collection']) {
			$r = q("select hash from groups where uid = %d and gname = '%s' limit 1",
				intval($newuid),
				dbesc( t('Friends') )
			);
			if($r) {
				q("update channel set channel_default_group = '%s', channel_allow_gid = '%s' where channel_id = %d",
					dbesc($r[0]['hash']),
					dbesc('<' . $r[0]['hash'] . '>'),
					intval($newuid)
				);
			}
		}

		if(! $system) {
			set_pconfig($ret['channel']['channel_id'],'system','photo_path', '%Y-%m');
			set_pconfig($ret['channel']['channel_id'],'system','attach_path','%Y-%m');
		}

		// auto-follow any of the hub's pre-configured channel choices.
		// Only do this if it's the first channel for this account;
		// otherwise it could get annoying. Don't make this list too big
		// or it will impact registration time.

		$accts = get_config('system','auto_follow');
		if(($accts) && (! $total_identities)) {
			require_once('include/follow.php');
			if(! is_array($accts))
				$accts = array($accts);
			foreach($accts as $acct) {
				if(trim($acct))
					new_contact($newuid,trim($acct),$ret['channel'],false);
			}
		}

		call_hooks('create_identity', $newuid);

		Zotlabs\Daemon\Master::Summon(array('Directory', $ret['channel']['channel_id']));
	}

	$ret['success'] = true;
	return $ret;
}

/**
 * @brief Set default channel to be used on login.
 *
 * @param int $account_id
 *       login account
 * @param int $channel_id
 *       channel id to set as default for this account
 * @param boolean $force
 *       if true, set this default unconditionally
 *       if $force is false only do this if there is no existing default
 */
function set_default_login_identity($account_id, $channel_id, $force = true) {
	$r = q("select account_default_channel from account where account_id = %d limit 1",
		intval($account_id)
	);
	if ($r) {
		if ((intval($r[0]['account_default_channel']) == 0) || ($force)) {
			$r = q("update account set account_default_channel = %d where account_id = %d",
				intval($channel_id),
				intval($account_id)
			);
		}
	}
}

/**
 * @brief Create an array representing the important channel information
 * which would be necessary to create a nomadic identity clone. This includes
 * most channel resources and connection information with the exception of content.
 *
 * @param int $channel_id
 *     Channel_id to export
 * @param boolean $items
 *     Include channel posts (wall items), default false
 *
 * @returns array
 *     See function for details
 */
function identity_basic_export($channel_id, $items = false) {

	/*
	 * Red basic channel export
	 */

	$ret = array();

	// use constants here as otherwise we will have no idea if we can import from a site
	// with a non-standard platform and version.
	$ret['compatibility'] = array('project' => PLATFORM_NAME, 'version' => STD_VERSION, 'database' => DB_UPDATE_VERSION, 'server_role' => Zotlabs\Lib\System::get_server_role());

	$r = q("select * from channel where channel_id = %d limit 1",
		intval($channel_id)
	);
	if($r) {
		translate_channel_perms_outbound($r[0]);
		$ret['channel'] = $r[0];
		$ret['relocate'] = [ 'channel_address' => $r[0]['channel_address'], 'url' => z_root()];
	}

	$r = q("select * from profile where uid = %d",
		intval($channel_id)
	);
	if($r)
		$ret['profile'] = $r;

	$xchans = array();
	$r = q("select * from abook where abook_channel = %d ",
		intval($channel_id)
	);
	if($r) {
		$ret['abook'] = $r;

		for($x = 0; $x < count($ret['abook']); $x ++) {
			$xchans[] = $ret['abook'][$x]['abook_xchan'];
			$abconfig = load_abconfig($channel_id,$ret['abook'][$x]['abook_xchan']);
			if($abconfig)
				$ret['abook'][$x]['abconfig'] = $abconfig;
			translate_abook_perms_outbound($ret['abook'][$x]);
		}
		stringify_array_elms($xchans);
	}

	if($xchans) {
		$r = q("select * from xchan where xchan_hash in ( " . implode(',',$xchans) . " ) ");
		if($r)
			$ret['xchan'] = $r;

		$r = q("select * from hubloc where hubloc_hash in ( " . implode(',',$xchans) . " ) ");
		if($r)
			$ret['hubloc'] = $r;
	}

	$r = q("select * from groups where uid = %d ",
		intval($channel_id)
	);

	if($r)
		$ret['group'] = $r;

	$r = q("select * from group_member where uid = %d ",
		intval($channel_id)
	);
	if($r)
		$ret['group_member'] = $r;

	$r = q("select * from pconfig where uid = %d",
		intval($channel_id)
	);
	if($r)
		$ret['config'] = $r;

	$r = q("select mimetype, content, os_storage from photo where imgscale = 4 and photo_usage = %d and uid = %d limit 1",
		intval(PHOTO_PROFILE),
		intval($channel_id)
	);

	if($r) {
		$ret['photo'] = array('type' => $r[0]['mimetype'], 'data' => (($r[0]['os_storage']) ? base64url_encode(file_get_contents($r[0]['content'])) : base64url_encode($r[0]['content'])));
	}

	// All other term types will be included in items, if requested.

	$r = q("select * from term where ttype in (%d,%d) and uid = %d",
		intval(TERM_SAVEDSEARCH),
		intval(TERM_THING),
		intval($channel_id)
	);
	if($r)
		$ret['term'] = $r;

	// add psuedo-column obj_baseurl to aid in relocations

	$r = q("select obj.*, '%s' as obj_baseurl from obj where obj_channel = %d",
		dbesc(z_root()),
		intval($channel_id)
	);

	if($r)
		$ret['obj'] = $r;

	$r = q("select * from app where app_channel = %d and app_system = 0",
		intval($channel_id)
	);
	if($r) {
		for($x = 0; $x < count($r); $x ++) {
			$r[$x]['term'] = q("select * from term where otype = %d and oid = %d",
				intval(TERM_OBJ_APP),
				intval($r[$x]['id'])
			);
		}
		$ret['app'] = $r;
	}

	$r = q("select * from chatroom where cr_uid = %d",
		intval($channel_id)
	);
	if($r)
		$ret['chatroom'] = $r;

	$r = q("select * from event where uid = %d",
		intval($channel_id)
	);
	if($r)
		$ret['event'] = $r;

	$r = q("select * from item where resource_type = 'event' and uid = %d",
		intval($channel_id)
	);
	if($r) {
		$ret['event_item'] = array();
		xchan_query($r);
		$r = fetch_post_tags($r,true);
		foreach($r as $rr)
			$ret['event_item'][] = encode_item($rr,true);
	}

	$x = menu_list($channel_id);
	if($x) {
		$ret['menu'] = array();
		for($y = 0; $y < count($x); $y ++) {
			$m = menu_fetch($x[$y]['menu_name'],$channel_id,$ret['channel']['channel_hash']);
			if($m)
				$ret['menu'][] = menu_element($ret['channel'],$m);
		}
	}

	$addon = array('channel_id' => $channel_id,'data' => $ret);
	call_hooks('identity_basic_export',$addon);
	$ret = $addon['data'];

	if(! $items)
		return $ret;

	$r = q("select * from likes where channel_id = %d",
		intval($channel_id)
	);

	if($r)
		$ret['likes'] = $r;


	$r = q("select * from conv where uid = %d",
		intval($channel_id)
	);
	if($r) {
		for($x = 0; $x < count($r); $x ++) {
			$r[$x]['subject'] = base64url_decode(str_rot47($r[$x]['subject']));
		}
		$ret['conv'] = $r;
	}

	$r = q("select * from mail where mail.uid = %d",
		intval($channel_id)
	);
	if($r) {
		$m = array();
		foreach($r as $rr) {
			xchan_mail_query($rr);
			$m[] = mail_encode($rr,true);
		}
		$ret['mail'] = $m;
	}


	/** @warning this may run into memory limits on smaller systems */


	/** export three months of posts. If you want to export and import all posts you have to start with
	  * the first year and export/import them in ascending order.
	  *
	  * Don't export linked resource items. we'll have to pull those out separately.
	  */

	$r = q("select * from item where item_wall = 1 and item_deleted = 0 and uid = %d and created > %s - INTERVAL %s and resource_type = '' order by created",
		intval($channel_id),
		db_utcnow(),
		db_quoteinterval('3 MONTH')
	);
	if($r) {
		$ret['item'] = array();
		xchan_query($r);
		$r = fetch_post_tags($r,true);
		foreach($r as $rr)
			$ret['item'][] = encode_item($rr,true);
	}

	return $ret;
}


function identity_export_year($channel_id,$year,$month = 0) {

	if(! $year)
		return array();

	if($month && $month <= 12) {
		$target_month = sprintf('%02d',$month);
		$target_month_plus = sprintf('%02d',$month+1);
	}
	else
		$target_month = '01';

	$ret = array();

	$ch = channelx_by_n($channel_id);
	if($ch) {
		$ret['relocate'] = [ 'channel_address' => $ch['channel_address'], 'url' => z_root()];
	}
	$mindate = datetime_convert('UTC','UTC',$year . '-' . $target_month . '-01 00:00:00');
	if($month && $month < 12)
		$maxdate = datetime_convert('UTC','UTC',$year . '-' . $target_month_plus . '-01 00:00:00');
	else
		$maxdate = datetime_convert('UTC','UTC',$year+1 . '-01-01 00:00:00');

	$r = q("select * from item where ( item_wall = 1 or item_type != %d ) and item_deleted = 0 and uid = %d and created >= '%s' and created < '%s'  and resource_type = '' order by created",
		intval(ITEM_TYPE_POST),
		intval($channel_id),
		dbesc($mindate),
		dbesc($maxdate)
	);

	if($r) {
		$ret['item'] = array();
		xchan_query($r);
		$r = fetch_post_tags($r,true);
		foreach($r as $rr)
			$ret['item'][] = encode_item($rr,true);
	}

	return $ret;
}

/**
 * @brief Export items within an arbitrary date range.
 *
 * Date/time is in UTC.
 *
 * @param int $channel_id The channel ID
 * @param string $start
 * @param string $finish
 * @return array
 */
function channel_export_items($channel_id, $start, $finish) {

	if(! $start)
		return array();
	else
		$start = datetime_convert('UTC', 'UTC', $start);

	$finish = datetime_convert('UTC', 'UTC', (($finish) ? $finish : 'now'));
	if($finish < $start)
		return array();

	$ret = array();

	$ch = channelx_by_n($channel_id);
	if($ch) {
		$ret['relocate'] = [ 'channel_address' => $ch['channel_address'], 'url' => z_root()];
	}

	$r = q("select * from item where ( item_wall = 1 or item_type != %d ) and item_deleted = 0 and uid = %d and created >= '%s' and created < '%s'  and resource_type = '' order by created",
		intval(ITEM_TYPE_POST),
		intval($channel_id),
		dbesc($start),
		dbesc($finish)
	);

	if($r) {
		$ret['item'] = array();
		xchan_query($r);
		$r = fetch_post_tags($r, true);
		foreach($r as $rr)
			$ret['item'][] = encode_item($rr, true);
	}

	return $ret;
}


/**
 * @brief Loads a profile into the App structure.
 *
 * The function requires a writeable copy of the main App structure, and the
 * nickname of a valid channel.
 *
 * Permissions of the current observer are checked. If a restricted profile is available
 * to the current observer, that will be loaded instead of the channel default profile.
 *
 * The channel owner can set $profile to a valid profile_guid to preview that profile.
 *
 * The channel default theme is also selected for use, unless over-riden elsewhere.
 *
 * @param string $nickname
 * @param string $profile
 */
function profile_load($nickname, $profile = '') {

//	logger('profile_load: ' . $nickname . (($profile) ? ' profile: ' . $profile : ''));

	$user = q("select channel_id from channel where channel_address = '%s' and channel_removed = 0  limit 1",
		dbesc($nickname)
	);

	if(! $user) {
		logger('profile error: ' . App::$query_string, LOGGER_DEBUG);
		notice( t('Requested channel is not available.') . EOL );
		App::$error = 404;
		return;
	}

	// get the current observer
	$observer = App::get_observer();

	$can_view_profile = true;

	// Can the observer see our profile?
	require_once('include/permissions.php');
	if(! perm_is_allowed($user[0]['channel_id'],$observer['xchan_hash'],'view_profile')) {
		$can_view_profile = false;
	}

	if(! $profile) {
		$r = q("SELECT abook_profile FROM abook WHERE abook_xchan = '%s' and abook_channel = '%d' limit 1",
			dbesc($observer['xchan_hash']),
			intval($user[0]['channel_id'])
		);
		if($r)
			$profile = $r[0]['abook_profile'];
	}
	$p = null;

	if($profile) {
		$p = q("SELECT profile.uid AS profile_uid, profile.*, channel.* FROM profile
				LEFT JOIN channel ON profile.uid = channel.channel_id
				WHERE channel.channel_address = '%s' AND profile.profile_guid = '%s' LIMIT 1",
				dbesc($nickname),
				dbesc($profile)
		);
	}

	if(! $p) {
		$p = q("SELECT profile.uid AS profile_uid, profile.*, channel.* FROM profile
			LEFT JOIN channel ON profile.uid = channel.channel_id
			WHERE channel.channel_address = '%s' and channel_removed = 0
			AND profile.is_default = 1 LIMIT 1",
			dbesc($nickname)
		);
	}

	if(! $p) {
		logger('profile error: ' . App::$query_string, LOGGER_DEBUG);
		notice( t('Requested profile is not available.') . EOL );
		App::$error = 404;
		return;
	}

	$q = q("select * from profext where hash = '%s' and channel_id = %d",
		dbesc($p[0]['profile_guid']),
		intval($p[0]['profile_uid'])
	);
	if($q) {
		$extra_fields = array();

		require_once('include/channel.php');
		$profile_fields_basic    = get_profile_fields_basic();
		$profile_fields_advanced = get_profile_fields_advanced();

		$advanced = ((feature_enabled(local_channel(),'advanced_profiles')) ? true : false);
		if($advanced)
			$fields = $profile_fields_advanced;
		else
			$fields = $profile_fields_basic;

		foreach($q as $qq) {
			foreach($fields as $k => $f) {
				if($k == $qq['k']) {
					$p[0][$k] = $qq['v'];
					$extra_fields[] = $k;
					break;
				}
			}
		}
	}

	$p[0]['extra_fields'] = $extra_fields;

	$z = q("select xchan_photo_date, xchan_addr from xchan where xchan_hash = '%s' limit 1",
		dbesc($p[0]['channel_hash'])
	);
	if($z) {
		$p[0]['picdate'] = $z[0]['xchan_photo_date'];
		$p[0]['reddress'] = str_replace('@','&#x40;',$z[0]['xchan_addr']);
	}

	// fetch user tags if this isn't the default profile

	if(! $p[0]['is_default']) {
		$x = q("select keywords from profile where uid = %d and is_default = 1 limit 1",
				intval($p[0]['profile_uid'])
		);
		if($x && $can_view_profile)
			$p[0]['keywords'] = $x[0]['keywords'];
	}

	if($p[0]['keywords']) {
		$keywords = str_replace(array('#',',',' ',',,'),array('',' ',',',','),$p[0]['keywords']);
		if(strlen($keywords) && $can_view_profile)
			App::$page['htmlhead'] .= '<meta name="keywords" content="' . htmlentities($keywords,ENT_COMPAT,'UTF-8') . '" />' . "\r\n" ;
	}

	App::$profile = $p[0];
	App::$profile_uid = $p[0]['profile_uid'];
	App::$page['title'] = App::$profile['channel_name'] . " - " . channel_reddress(App::$profile);

	App::$profile['permission_to_view'] = $can_view_profile;

	if($can_view_profile) {
		$online = get_online_status($nickname);
		App::$profile['online_status'] = $online['result'];
	}

	if(local_channel()) {
		App::$profile['channel_mobile_theme'] = get_pconfig(local_channel(),'system', 'mobile_theme');
		$_SESSION['mobile_theme'] = App::$profile['channel_mobile_theme'];
	}

	/*
	 * load/reload current theme info
	 */

//	$_SESSION['theme'] = $p[0]['channel_theme'];

}

function profile_edit_menu($uid) {

	$ret = array();

	$is_owner = (($uid == local_channel()) ? true : false);

	// show edit profile to profile owner
	if($is_owner) {
		$ret['menu'] = array(
			'chg_photo' => t('Change profile photo'),
			'entries' => array(),
		);

		$multi_profiles = feature_enabled(local_channel(), 'multi_profiles');
		if($multi_profiles) {
			$ret['multi'] = 1;
			$ret['edit'] = array(z_root(). '/profiles', t('Edit Profiles'), '', t('Edit'));
			$ret['menu']['cr_new'] = t('Create New Profile');
		}
		else {
			$ret['edit'] = array(z_root() . '/profiles/' . $uid, t('Edit Profile'), '', t('Edit'));
		}

		$r = q("SELECT * FROM profile WHERE uid = %d",
				local_channel()
		);

		if($r) {
			foreach($r as $rr) {
				if(!($multi_profiles || $rr['is_default']))
					 continue;
				$ret['menu']['entries'][] = array(
					'photo'                => $rr['thumb'],
					'id'                   => $rr['id'],
					'alt'                  => t('Profile Image'),
					'profile_name'         => $rr['profile_name'],
					'isdefault'            => $rr['is_default'],
					'visible_to_everybody' => t('Visible to everybody'),
					'edit_visibility'      => t('Edit visibility'),
				);
			}
		}
	}

	return $ret;
}

/**
 * @brief Formats a profile for display in the sidebar.
 *
 * It is very difficult to templatise the HTML completely
 * because of all the conditional logic.
 *
 * @param array $profile
 * @param int $block
 * @param boolean $show_connect
 * @param mixed $zcard
 *
 * @return HTML string suitable for sidebar inclusion
 * Exceptions: Returns empty string if passed $profile is wrong type or not populated
 */
function profile_sidebar($profile, $block = 0, $show_connect = true, $zcard = false) {

	$observer = App::get_observer();

	$o = '';
	$location = false;
	$pdesc = true;
	$reddress = true;

	if(! perm_is_allowed($profile['uid'],((is_array($observer)) ? $observer['xchan_hash'] : ''),'view_profile')) {
		$block = true;
	}

	if((! is_array($profile)) && (! count($profile)))
		return $o;

	head_set_icon($profile['thumb']);

	if(is_sys_channel($profile['uid']))
		$show_connect = false;

	$profile['picdate'] = urlencode($profile['picdate']);

	call_hooks('profile_sidebar_enter', $profile);

	if($show_connect) {

		// This will return an empty string if we're already connected.

		$connect_url = rconnect_url($profile['uid'],get_observer_hash());
		$connect = (($connect_url) ? t('Connect') : '');
		if($connect_url)
			$connect_url = sprintf($connect_url,urlencode(channel_reddress($profile)));

		// premium channel - over-ride

		if($profile['channel_pageflags'] & PAGE_PREMIUM)
			$connect_url = z_root() . '/connect/' . $profile['channel_address'];
	}

	if((x($profile,'address') == 1)
		|| (x($profile,'locality') == 1)
		|| (x($profile,'region') == 1)
		|| (x($profile,'postal_code') == 1)
		|| (x($profile,'country_name') == 1))
		$location = t('Location:');

	$profile['homepage'] = linkify($profile['homepage'],true);

	$gender   = ((x($profile,'gender')   == 1) ? t('Gender:')   : False);
	$marital  = ((x($profile,'marital')  == 1) ? t('Status:')   : False);
	$homepage = ((x($profile,'homepage') == 1) ? t('Homepage:') : False);
	$profile['online']   = (($profile['online_status'] === 'online') ? t('Online Now') : False);

//	logger('online: ' . $profile['online']);


	if(($profile['hidewall'] && (! local_channel()) && (! remote_channel())) || $block ) {
		$location = $reddress = $pdesc = $gender = $marital = $homepage = False;
	}

	$firstname = ((strpos($profile['channel_name'],' '))
		? trim(substr($profile['channel_name'],0,strpos($profile['channel_name'],' '))) : $profile['channel_name']);
	$lastname = (($firstname === $profile['channel_name']) ? '' : trim(substr($profile['channel_name'],strlen($firstname))));

	// @fixme move this to the diaspora plugin itself

	if(plugin_is_installed('diaspora')) {
		$diaspora = array(
			'podloc'     => z_root(),
			'guid'       => $profile['channel_guid'] . str_replace('.','',App::get_hostname()),
			'pubkey'     => pemtorsa($profile['channel_pubkey']),
			'searchable' => (($block) ? 'false' : 'true'),
			'nickname'   => $profile['channel_address'],
			'fullname'   => $profile['channel_name'],
			'firstname'  => $firstname,
			'lastname'   => $lastname,
			'photo300'   => z_root() . '/photo/profile/300/' . $profile['uid'] . '.jpg',
			'photo100'   => z_root() . '/photo/profile/100/' . $profile['uid'] . '.jpg',
			'photo50'    => z_root() . '/photo/profile/50/'  . $profile['uid'] . '.jpg',
		);
	}
	else
		$diaspora = '';


	$contact_block = contact_block();

	$channel_menu = false;
	$menu = get_pconfig($profile['uid'],'system','channel_menu');
	if($menu && ! $block) {
		require_once('include/menu.php');
		$m = menu_fetch($menu,$profile['uid'],$observer['xchan_hash']);
		if($m)
			$channel_menu = menu_render($m);
	}
	$menublock = get_pconfig($profile['uid'],'system','channel_menublock');
	if ($menublock && (! $block)) {
		$comanche = new Zotlabs\Render\Comanche();
		$channel_menu .= $comanche->block($menublock);
	}

	if($zcard)
		$tpl = get_markup_template('profile_vcard_short.tpl');
	else
		$tpl = get_markup_template('profile_vcard.tpl');

	require_once('include/widgets.php');

//	if(! feature_enabled($profile['uid'],'hide_rating'))
	$z = widget_rating(array('target' => $profile['channel_hash']));

	$o .= replace_macros($tpl, array(
		'$zcard'         => $zcard,
		'$profile'       => $profile,
		'$connect'       => $connect,
		'$connect_url'   => $connect_url,
		'$location'      => $location,
		'$gender'        => $gender,
		'$pdesc'         => $pdesc,
		'$marital'       => $marital,
		'$homepage'      => $homepage,
		'$chanmenu'      => $channel_menu,
		'$diaspora'      => $diaspora,
		'$reddress'      => $reddress,
		'$rating'        => $z,
		'$contact_block' => $contact_block,
		'$editmenu'	 => profile_edit_menu($profile['uid'])
	));

	$arr = array('profile' => $profile, 'entry' => $o);

	call_hooks('profile_sidebar', $arr);

	return $o;
}


function advanced_profile(&$a) {
	require_once('include/text.php');
	if(! perm_is_allowed(App::$profile['profile_uid'],get_observer_hash(),'view_profile'))
		return '';

	if(App::$profile['fullname']) {

		$profile_fields_basic    = get_profile_fields_basic();
		$profile_fields_advanced = get_profile_fields_advanced();

		$advanced = ((feature_enabled(App::$profile['profile_uid'],'advanced_profiles')) ? true : false);
		if($advanced)
			$fields = $profile_fields_advanced;
		else
			$fields = $profile_fields_basic;

		$clean_fields = array();
		if($fields) {
			foreach($fields as $k => $v) {
				$clean_fields[] = trim($k);
			}
		}


		$tpl = get_markup_template('profile_advanced.tpl');

		$profile = array();

		$profile['fullname'] = array( t('Full Name:'), App::$profile['fullname'] ) ;

		if(App::$profile['gender']) $profile['gender'] = array( t('Gender:'),  App::$profile['gender'] );

		$ob_hash = get_observer_hash();
		if($ob_hash && perm_is_allowed(App::$profile['profile_uid'],$ob_hash,'post_like')) {
			$profile['canlike'] = true;
			$profile['likethis'] = t('Like this channel');
			$profile['profile_guid'] = App::$profile['profile_guid'];
		}

		$likers = q("select liker, xchan.*  from likes left join xchan on liker = xchan_hash where channel_id = %d and target_type = '%s' and verb = '%s'",
			intval(App::$profile['profile_uid']),
			dbesc(ACTIVITY_OBJ_PROFILE),
			dbesc(ACTIVITY_LIKE)
		);
		$profile['likers'] = array();
		$profile['like_count'] = count($likers);
		$profile['like_button_label'] = tt('Like','Likes',$profile['like_count'],'noun');
		if($likers) {
			foreach($likers as $l)
				$profile['likers'][] = array('name' => $l['xchan_name'],'photo' => zid($l['xchan_photo_s']), 'url' => zid($l['xchan_url']));
		}

		if((App::$profile['dob']) && (App::$profile['dob'] != '0000-00-00')) {

			$val = '';

			if((substr(App::$profile['dob'],5,2) === '00') || (substr(App::$profile['dob'],8,2) === '00'))
				$val = substr(App::$profile['dob'],0,4);

			$year_bd_format = t('j F, Y');
			$short_bd_format = t('j F');

			if(! $val) {
				$val = ((intval(App::$profile['dob']))
					? day_translate(datetime_convert('UTC','UTC',App::$profile['dob'] . ' 00:00 +00:00',$year_bd_format))
					: day_translate(datetime_convert('UTC','UTC','2001-' . substr(App::$profile['dob'],5) . ' 00:00 +00:00',$short_bd_format)));
			}
			$profile['birthday'] = array( t('Birthday:'), $val);
		}

		if($age = age(App::$profile['dob'],App::$profile['timezone'],''))
			$profile['age'] = array( t('Age:'), $age );

		if(App::$profile['marital'])
			$profile['marital'] = array( t('Status:'), App::$profile['marital']);

		if(App::$profile['partner'])
			$profile['marital']['partner'] = bbcode(App::$profile['partner']);

		if(strlen(App::$profile['howlong']) && App::$profile['howlong'] > NULL_DATE) {
			$profile['howlong'] = relative_date(App::$profile['howlong'], t('for %1$d %2$s'));
		}

		if(App::$profile['sexual']) $profile['sexual'] = array( t('Sexual Preference:'), App::$profile['sexual'] );

		if(App::$profile['homepage']) $profile['homepage'] = array( t('Homepage:'), linkify(App::$profile['homepage']) );

		if(App::$profile['hometown']) $profile['hometown'] = array( t('Hometown:'), linkify(App::$profile['hometown']) );

		if(App::$profile['keywords']) $profile['keywords'] = array( t('Tags:'), App::$profile['keywords']);

		if(App::$profile['politic']) $profile['politic'] = array( t('Political Views:'), App::$profile['politic']);

		if(App::$profile['religion']) $profile['religion'] = array( t('Religion:'), App::$profile['religion']);

		if($txt = prepare_text(App::$profile['about'])) $profile['about'] = array( t('About:'), $txt );

		if($txt = prepare_text(App::$profile['interest'])) $profile['interest'] = array( t('Hobbies/Interests:'), $txt);

		if($txt = prepare_text(App::$profile['likes'])) $profile['likes'] = array( t('Likes:'), $txt);

		if($txt = prepare_text(App::$profile['dislikes'])) $profile['dislikes'] = array( t('Dislikes:'), $txt);

		if($txt = prepare_text(App::$profile['contact'])) $profile['contact'] = array( t('Contact information and Social Networks:'), $txt);

		if($txt = prepare_text(App::$profile['channels'])) $profile['channels'] = array( t('My other channels:'), $txt);

		if($txt = prepare_text(App::$profile['music'])) $profile['music'] = array( t('Musical interests:'), $txt);

		if($txt = prepare_text(App::$profile['book'])) $profile['book'] = array( t('Books, literature:'), $txt);

		if($txt = prepare_text(App::$profile['tv'])) $profile['tv'] = array( t('Television:'), $txt);

		if($txt = prepare_text(App::$profile['film'])) $profile['film'] = array( t('Film/dance/culture/entertainment:'), $txt);

		if($txt = prepare_text(App::$profile['romance'])) $profile['romance'] = array( t('Love/Romance:'), $txt);

		if($txt = prepare_text(App::$profile['employment'])) $profile['employment'] = array( t('Work/employment:'), $txt);

		if($txt = prepare_text(App::$profile['education'])) $profile['education'] = array( t('School/education:'), $txt );

		if(App::$profile['extra_fields']) {
			foreach(App::$profile['extra_fields'] as $f) {
				$x = q("select * from profdef where field_name = '%s' limit 1",
					dbesc($f)
				);
				if($x && $txt = prepare_text(App::$profile[$f]))
					$profile[$f] = array( $x[0]['field_desc'] . ':',$txt);
			}
			$profile['extra_fields'] = App::$profile['extra_fields'];
		}

		$things = get_things(App::$profile['profile_guid'],App::$profile['profile_uid']);


//		logger('mod_profile: things: ' . print_r($things,true), LOGGER_DATA);

		return replace_macros($tpl, array(
			'$title' => t('Profile'),
			'$canlike' => (($profile['canlike'])? true : false),
			'$likethis' => t('Like this thing'),
			'$profile' => $profile,
			'$fields' => $clean_fields,
			'$editmenu' => profile_edit_menu(App::$profile['profile_uid']),
			'$things' => $things
		));
	}

	return '';
}


function get_my_url() {
	if(x($_SESSION, 'zrl_override'))
		return $_SESSION['zrl_override'];
	if(x($_SESSION, 'my_url'))
		return $_SESSION['my_url'];

	return false;
}

function get_my_address() {
	if(x($_SESSION, 'zid_override'))
		return $_SESSION['zid_override'];
	if(x($_SESSION, 'my_address'))
		return $_SESSION['my_address'];

	return false;
}

/**
 * @brief
 *
 * If somebody arrives at our site using a zid, add their xchan to our DB if we don't have it already.
 * And if they aren't already authenticated here, attempt reverse magic auth.
 *
 *
 * @hooks 'zid_init'
 *      string 'zid' - their zid
 *      string 'url' - the destination url
 */
function zid_init() {
	$tmp_str = get_my_address();
	if(validate_email($tmp_str)) {
		Zotlabs\Daemon\Master::Summon(array('Gprobe',bin2hex($tmp_str)));
		$arr = array('zid' => $tmp_str, 'url' => App::$cmd);
		call_hooks('zid_init',$arr);
		if(! local_channel()) {
			$r = q("select * from hubloc where hubloc_addr = '%s' order by hubloc_connected desc limit 1",
				dbesc($tmp_str)
			);
			if($r && remote_channel() && remote_channel() === $r[0]['hubloc_hash'])
				return;
			logger('zid_init: not authenticated. Invoking reverse magic-auth for ' . $tmp_str);
			// try to avoid recursion - but send them home to do a proper magic auth
			$query = App::$query_string;
			$query = str_replace(array('?zid=','&zid='),array('?rzid=','&rzid='),$query);
			$dest = '/' . urlencode($query);
			if($r && ($r[0]['hubloc_url'] != z_root()) && (! strstr($dest,'/magic')) && (! strstr($dest,'/rmagic'))) {
				goaway($r[0]['hubloc_url'] . '/magic' . '?f=&rev=1&dest=' . z_root() . $dest);
			}
			else
				logger('zid_init: no hubloc found.');
		}
	}
}

/**
 * @brief
 *
 * If somebody arrives at our site using a zat, authenticate them
 *
 */

function zat_init() {
	if(local_channel() || remote_channel())
		return;

	$r = q("select * from atoken where atoken_token = '%s' limit 1",
		dbesc($_REQUEST['zat'])
	);
	if($r) {
		$xchan = atoken_xchan($r[0]);
		atoken_login($xchan);
	}

}




// Used from within PCSS themes to set theme parameters. If there's a
// puid request variable, that is the "page owner" and normally their theme
// settings take precedence; unless a local user sets the "always_my_theme"
// system pconfig, which means they don't want to see anybody else's theme
// settings except their own while on this site.

function get_theme_uid() {
	$uid = (($_REQUEST['puid']) ? intval($_REQUEST['puid']) : 0);
	if(local_channel()) {
		if((get_pconfig(local_channel(),'system','always_my_theme')) || (! $uid))
			return local_channel();
	}
	if(! $uid) {
		$x = get_sys_channel();
		if($x)
			return $x['channel_id'];
	}

	return $uid;
}

/**
* @brief Retrieves the path of the default_profile_photo for this system
* with the specified size.
*
* @param int $size
*  one of (300, 80, 48)
* @returns string
*/
function get_default_profile_photo($size = 300) {
	$scheme = get_config('system','default_profile_photo');
	if(! $scheme)
		$scheme = 'rainbow_man';

	return 'images/default_profile_photos/' . $scheme . '/' . $size . '.png';
}

/**
 * @brief Test whether a given identity is NOT a member of the Hubzilla.
 *
 * @param string $s;
 *    xchan_hash of the identity in question
 * @returns boolean true or false
 */
function is_foreigner($s) {
	return((strpbrk($s, '.:@')) ? true : false);
}

/**
 * @brief Test whether a given identity is a member of the Hubzilla.
 *
 * @param string $s;
 *    xchan_hash of the identity in question
 * @returns boolean true or false
 */
function is_member($s) {
	return((is_foreigner($s)) ? false : true);
}

function get_online_status($nick) {

	$ret = array('result' => false);

	if(observer_prohibited())
		return $ret;

	$r = q("select channel_id, channel_hash from channel where channel_address = '%s' limit 1",
		dbesc(argv(1))
	);
	if($r) {
		$hide = get_pconfig($r[0]['channel_id'],'system','hide_online_status');
		if($hide)
			return $ret;
		$x = q("select cp_status from chatpresence where cp_xchan = '%s' and cp_room = 0 limit 1",
			dbesc($r[0]['channel_hash'])
		);
		if($x)
			$ret['result'] = $x[0]['cp_status'];
	}

	return $ret;
}


function remote_online_status($webbie) {

	$result = false;
	$r = q("select * from hubloc where hubloc_addr = '%s' limit 1",
		dbesc($webbie)
	);
	if(! $r)
		return $result;

	$url = $r[0]['hubloc_url'] . '/online/' . substr($webbie,0,strpos($webbie,'@'));

	$x = z_fetch_url($url);
	if($x['success']) {
		$j = json_decode($x['body'],true);
		if($j)
			$result = (($j['result']) ? $j['result'] : false);
	}

	return $result;
}


/**
 * @brief
 *
 * @return string
 */

function identity_selector() {
	if(local_channel()) {
		$r = q("select channel.*, xchan.* from channel left join xchan on channel.channel_hash = xchan.xchan_hash where channel.channel_account_id = %d and channel_removed = 0 order by channel_name ",
			intval(get_account_id())
		);
		if($r && count($r) > 1) {
			//$account = App::get_account();
			$o = replace_macros(get_markup_template('channel_id_select.tpl'), array(
				'$channels' => $r,
				'$selected' => local_channel()
			));
			return $o;
		}
	}

	return '';
}


function is_public_profile() {
	if(! local_channel())
		return false;
	if(intval(get_config('system','block_public')))
		return false;
	$channel = App::get_channel();
	if($channel) {
		$perm = \Zotlabs\Access\PermissionLimits::Get($channel['channel_id'],'view_profile');
		if($perm == PERMS_PUBLIC)
			return true;
	}
	return false;
}

function get_profile_fields_basic($filter = 0) {

	$profile_fields_basic = (($filter == 0) ? get_config('system','profile_fields_basic') : null);
	if(! $profile_fields_basic)
		$profile_fields_basic = array('fullname','pdesc','chandesc','gender','dob','dob_tz','address','locality','region','postal_code','country_name','marital','sexual','homepage','hometown','keywords','about','contact');

	$x = array();
	if($profile_fields_basic)
		foreach($profile_fields_basic as $f)
			$x[$f] = 1;

	return $x;
}


function get_profile_fields_advanced($filter = 0) {
	$basic = get_profile_fields_basic($filter);
	$profile_fields_advanced = (($filter == 0) ? get_config('system','profile_fields_advanced') : null);
	if(! $profile_fields_advanced)
		$profile_fields_advanced = array('partner','howlong','politic','religion','likes','dislikes','interest','channels','music','book','film','tv','romance','employment','education');

	$x = array();
	if($basic)
		foreach($basic as $f => $v)
			$x[$f] = $v;

	if($profile_fields_advanced)
		foreach($profile_fields_advanced as $f)
			$x[$f] = 1;

	return $x;
}

/**
 * @brief Clear notifyflags for a channel.
 *
 * Most likely during bulk import of content or other activity that is likely
 * to generate huge amounts of undesired notifications.
 *
 * @param int $channel_id
 *    The channel to disable notifications for
 * @returns int
 *    Current notification flag value. Send this to notifications_on() to restore the channel settings when finished
 *    with the activity requiring notifications_off();
 */
function notifications_off($channel_id) {
	$r = q("select channel_notifyflags from channel where channel_id = %d limit 1",
		intval($channel_id)
	);
	q("update channel set channel_notifyflags = 0 where channel_id = %d",
		intval($channel_id)
	);

	return intval($r[0]['channel_notifyflags']);
}


function notifications_on($channel_id, $value) {
	$x = q("update channel set channel_notifyflags = %d where channel_id = %d",
		intval($value),
		intval($channel_id)
	);

	return $x;
}


function get_channel_default_perms($uid) {

	$ret = [];

	$r = q("select abook_xchan from abook where abook_channel = %d and abook_self = 1 limit 1",
		intval($uid)
	);
	if($r) {
		$x = load_abconfig($uid,$r[0]['abook_xchan'],'my_perms');
		if($x) {
			foreach($x as $xv) {
				if(intval($xv['v'])) {
					$ret[] = $xv['k'];
				}
			}
		}
	}

	return $ret;
}


function profiles_build_sync($channel_id) {
	$r = q("select * from profile where uid = %d",
		intval($channel_id)
	);
	if($r) {
		build_sync_packet($channel_id,array('profile' => $r));
	}
}


function auto_channel_create($account_id) {

	if(! $account_id)
		return false;

	$arr = array();
	$arr['account_id'] = $account_id;
	$arr['name'] = get_aconfig($account_id,'register','channel_name');
	$arr['nickname'] = legal_webbie(get_aconfig($account_id,'register','channel_address'));
	$arr['permissions_role'] = get_aconfig($account_id,'register','permissions_role');

	del_aconfig($account_id,'register','channel_name');
	del_aconfig($account_id,'register','channel_address');
	del_aconfig($account_id,'register','permissions_role');

	if((! $arr['name']) || (! $arr['nickname'])) {
		$x = q("select * from account where account_id = %d limit 1",
			intval($account_id)
		);
		if($x) {
			if(! $arr['name'])
				$arr['name'] = substr($x[0]['account_email'],0,strpos($x[0]['account_email'],'@'));
			if(! $arr['nickname'])
				$arr['nickname'] = legal_webbie(substr($x[0]['account_email'],0,strpos($x[0]['account_email'],'@')));
		}
	}
	if(! $arr['permissions_role'])
		$arr['permissions_role'] = 'social';

	if(validate_channelname($arr['name']))
		return false;
	if($arr['nickname'] === 'sys')
		$arr['nickname'] = $arr['nickname'] . mt_rand(1000,9999);

	$arr['nickname'] = check_webbie(array($arr['nickname'], $arr['nickname'] . mt_rand(1000,9999)));

	return create_identity($arr);
}

function get_cover_photo($channel_id,$format = 'bbcode', $res = PHOTO_RES_COVER_1200) {

	$r = q("select height, width, resource_id, mimetype from photo where uid = %d and imgscale = %d and photo_usage = %d",
		intval($channel_id),
		intval($res),
		intval(PHOTO_COVER)
	);
	if(! $r)
		return false;

	$output = false;

	$url = z_root() . '/photo/' . $r[0]['resource_id'] . '-' . $res ;

	switch($format) {
		case 'bbcode':
			$output = '[zrl=' . $r[0]['width'] . 'x' . $r[0]['height'] . ']' . $url . '[/zrl]';
			break;
		case 'html':
 			$output = '<img class="zrl" width="' . $r[0]['width'] . '" height="' . $r[0]['height'] . '" src="' . $url . '" alt="' . t('cover photo') . '" />';
			break;
		case 'array':
		default:
			$output = array(
				'width' => $r[0]['width'],
				'height' => $r[0]['height'],
				'type' => $r[0]['mimetype'],
				'url' => $url
			);
			break;
	}

	return $output;
}

/**
 * @brief
 *
 * @param array $channel
 * @param string $observer_hash
 * @param array $args
 * @return string
 */
function get_zcard($channel, $observer_hash = '', $args = array()) {

	logger('get_zcard');

	$maxwidth = (($args['width']) ? intval($args['width']) : 0);
	$maxheight = (($args['height']) ? intval($args['height']) : 0);

	if(($maxwidth > 1200) || ($maxwidth < 1))
		$maxwidth = 1200;

	if($maxwidth <= 425) {
		$width = 425;
		$size = 'hz_small';
		$cover_size = PHOTO_RES_COVER_425;
		$pphoto = array('mimetype' => $channel['xchan_photo_mimetype'], 'width' => 80 , 'height' => 80, 'href' => $channel['xchan_photo_m']);
	} elseif($maxwidth <= 900) {
		$width = 900;
		$size = 'hz_medium';
		$cover_size = PHOTO_RES_COVER_850;
		$pphoto = array('mimetype' => $channel['xchan_photo_mimetype'], 'width' => 160 , 'height' => 160, 'href' => $channel['xchan_photo_l']);
	} elseif($maxwidth <= 1200) {
		$width = 1200;
		$size = 'hz_large';
		$cover_size = PHOTO_RES_COVER_1200;
		$pphoto = array('mimetype' => $channel['xchan_photo_mimetype'], 'width' => 300 , 'height' => 300, 'href' => $channel['xchan_photo_l']);
	}

//	$scale = (float) $maxwidth / $width;
//	$translate = intval(($scale / 1.0) * 100);

	$channel['channel_addr'] = channel_reddress($channel);
	$zcard = array('chan' => $channel);

	$r = q("select height, width, resource_id, imgscale, mimetype from photo where uid = %d and imgscale = %d and photo_usage = %d",
		intval($channel['channel_id']),
		intval($cover_size),
		intval(PHOTO_COVER)
	);

	if($r) {
		$cover = $r[0];
		$cover['href'] = z_root() . '/photo/' . $r[0]['resource_id'] . '-' . $r[0]['imgscale'];
	} else {
		$cover = $pphoto;
	}

	$o .= replace_macros(get_markup_template('zcard.tpl'), array(
		'$maxwidth' => $maxwidth,
		'$scale' => $scale,
		'$translate' => $translate,
		'$size' => $size,
		'$cover' => $cover,
		'$pphoto' => $pphoto,
		'$zcard' => $zcard
	));

	return $o;
}


function get_zcard_embed($channel, $observer_hash = '', $args = array()) {

	logger('get_zcard_embed');

	$maxwidth = (($args['width']) ? intval($args['width']) : 0);
	$maxheight = (($args['height']) ? intval($args['height']) : 0);

	if(($maxwidth > 1200) || ($maxwidth < 1))
		$maxwidth = 1200;

	if($maxwidth <= 425) {
		$width = 425;
		$size = 'hz_small';
		$cover_size = PHOTO_RES_COVER_425;
		$pphoto = array('mimetype' => $channel['xchan_photo_mimetype'],  'width' => 80 , 'height' => 80, 'href' => $channel['xchan_photo_m']);
	}
	elseif($maxwidth <= 900) {
		$width = 900;
		$size = 'hz_medium';
		$cover_size = PHOTO_RES_COVER_850;
		$pphoto = array('mimetype' => $channel['xchan_photo_mimetype'],  'width' => 160 , 'height' => 160, 'href' => $channel['xchan_photo_l']);
	}
	elseif($maxwidth <= 1200) {
		$width = 1200;
		$size = 'hz_large';
		$cover_size = PHOTO_RES_COVER_1200;
		$pphoto = array('mimetype' => $channel['xchan_photo_mimetype'],  'width' => 300 , 'height' => 300, 'href' => $channel['xchan_photo_l']);
	}

	$channel['channel_addr'] = channel_reddress($channel);
	$zcard = array('chan' => $channel);

	$r = q("select height, width, resource_id, imgscale, mimetype from photo where uid = %d and imgscale = %d and photo_usage = %d",
		intval($channel['channel_id']),
		intval($cover_size),
		intval(PHOTO_COVER)
	);

	if($r) {
		$cover = $r[0];
		$cover['href'] = z_root() . '/photo/' . $r[0]['resource_id'] . '-' . $r[0]['imgscale'];
	} else {
		$cover = $pphoto;
	}

	$o .= replace_macros(get_markup_template('zcard_embed.tpl'),array(
		'$maxwidth' => $maxwidth,
		'$scale' => $scale,
		'$translate' => $translate,
		'$size' => $size,
		'$cover' => $cover,
		'$pphoto' => $pphoto,
		'$zcard' => $zcard
	));

	return $o;
}

/**
 * @brief
 *
 * @param string $nick
 * @return mixed
 */
function channelx_by_nick($nick) {
	$r = q("SELECT * FROM channel left join xchan on channel_hash = xchan_hash WHERE channel_address = '%s'  and channel_removed = 0 LIMIT 1",
		dbesc($nick)
	);

	return(($r) ? $r[0] : false);
}

/**
 * @brief
 *
 * @param string $hash
 * @return mixed
 */
function channelx_by_hash($hash) {
	$r = q("SELECT * FROM channel left join xchan on channel_hash = xchan_hash WHERE channel_hash = '%s' and channel_removed = 0 LIMIT 1",
		dbesc($hash)
	);

	return(($r) ? $r[0] : false);
}

/**
 * @brief
 *
 * @param int $id
 * @return mixed
 */
function channelx_by_n($id) {
	$r = q("SELECT * FROM channel left join xchan on channel_hash = xchan_hash WHERE channel_id = %d and channel_removed = 0 LIMIT 1",
		dbesc($id)
	);

	return(($r) ? $r[0] : false);
}

/**
 * @brief
 *
 * @param string $channel
 * @return string
 */
function channel_reddress($channel) {
	if(! ($channel && array_key_exists('channel_address', $channel)))
		return '';

	return strtolower($channel['channel_address'] . '@' . App::get_hostname());
}


function channel_manual_conv_update($channel_id) {

	$x = get_pconfig($channel_id, 'system','manual_conversation_update');
	if($x === false)
		$x = get_config('system','manual_conversation_update');

	return intval($x);

}