<?php


namespace Zotlabs\Access;

use Zotlabs\Lib as Zlib;

class Permissions {

	/**
	 * Extensible permissions.
	 * To add new permissions, add to the list of $perms below, with a simple description.
	 *
	 * Also visit PermissionRoles.php and add to the $ret['perms_connect'] property for any role
	 * if this permission should be granted to new connections.
	 *
	 * Next look at PermissionRoles::new_custom_perms() and provide a handler for updating custom 
	 * permission roles. You will want to set a default PermissionLimit for each channel and also
	 * provide a sane default for any existing connections. You may or may not wish to provide a
	 * default auto permission. If in doubt, leave this alone as custom permissions by definition
	 * are the responsbility of the channel owner to manage. You just don't want to create any 
	 * suprises or break things so you have an opportunity to provide sane settings. 
	 *
	 * Update the version here and in PermissionRoles
	 *
	 *
	 * Permissions with 'view' in the name are considered read permissions. Anything
	 * else requires authentication. Read permission limits are PERMS_PUBLIC and anything else
	 * is given PERMS_SPECIFIC.
	 *
	 * PermissionLimits::Std_limits() retrieves the standard limits. A permission role
	 * MAY alter an individual setting after retrieving the Std_limits if you require
	 * something different for a specific permission within the given role.  
	 *
	 */

	static public function version() {
		// This must match the version in PermissionRoles.php before permission updates can run.
		return 1;
	}


	static public function Perms($filter = '') {

		$perms = [
			'view_stream'   => t('Can view my channel stream and posts'),
			'send_stream'   => t('Can send me their channel stream and posts'),
			'view_profile'  => t('Can view my default channel profile'),
			'view_contacts' => t('Can view my connections'),
			'view_storage'  => t('Can view my file storage and photos'),
			'write_storage' => t('Can upload/modify my file storage and photos'),
			'view_pages'    => t('Can view my channel webpages'),
			'write_pages'   => t('Can create/edit my channel webpages'),
			'post_wall'     => t('Can post on my channel (wall) page'),
			'post_comments' => t('Can comment on or like my posts'),
			'post_mail'     => t('Can send me private mail messages'),
			'post_like'     => t('Can like/dislike profiles and profile things'),
			'tag_deliver'   => t('Can forward to all my channel connections via @+ mentions in posts'),
			'chat'          => t('Can chat with me'),
			'republish'     => t('Can source my public posts in derived channels'),
			'delegate'      => t('Can administer my channel')
		];

		$x = array('permissions' => $perms, 'filter' => $filter);
		call_hooks('permissions_list',$x);
		return($x['permissions']);

	}

	static public function BlockedAnonPerms() {

		// Perms from the above list that are blocked from anonymous observers.
		// e.g. you must be authenticated.

		$res = array();
		$perms = PermissionLimits::Std_limits();
		foreach($perms as $perm => $limit) {
			if($limit != PERMS_PUBLIC) {
				$res[] = $perm;
			}
		}

		$x = array('permissions' => $res);
		call_hooks('write_perms',$x);
		return($x['permissions']);

	}

	// converts [ 0 => 'view_stream', ... ]
	// to [ 'view_stream' => 1 ]
	// for any permissions in $arr;
	// Undeclared permissions are set to 0

	static public function FilledPerms($arr) {
		$everything = self::Perms();
		$ret = [];
		foreach($everything as $k => $v) {
			if(in_array($k,$arr))
				$ret[$k] = 1;
			else
				$ret[$k] = 0;
		}
		return $ret;

	}

	static public function FilledAutoperms($channel_id) {
		if(! intval(get_pconfig($channel_id,'system','autoperms')))
			return false;

		$arr = [];
		$r = q("select * from pconfig where uid = %d and cat = 'autoperms'",
			intval($channel_id)
		);
		if($r) {
			foreach($r as $rr) {
				$arr[$rr['k']] = $arr[$rr['v']];
			}
		}
		return $arr;
	}

	static public function PermsCompare($p1,$p2) {
		foreach($p1 as $k => $v) {
			if(! array_key_exists($k,$p2))
				return false;
			if($p1[$k] != $p2[$k])
				return false;
		}
		return true;
	}
}