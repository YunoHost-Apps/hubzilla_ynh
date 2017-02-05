<?php


namespace Zotlabs\Access;

use Zotlabs\Lib as Zlib;

class PermissionRoles {

	static public function version() {
		return 1;
	}

	static function role_perms($role) {

		$ret = array();

		$ret['role'] = $role;

		switch($role) {
			case 'social':
				$ret['perms_auto'] = false;
				$ret['default_collection'] = false;
				$ret['directory_publish'] = true;
				$ret['online'] = true;
				$ret['perms_connect'] = [ 
					'view_stream', 'view_profile', 'view_contacts', 'view_storage',
					'view_pages', 'send_stream', 'post_wall', 'post_comments', 
					'post_mail', 'chat', 'post_like', 'republish' ];

				$ret['limits'] = PermissionLimits::Std_Limits();
				break;

			case 'social_restricted':
				$ret['perms_auto'] = false;
				$ret['default_collection'] = true;
				$ret['directory_publish'] = true;
				$ret['online'] = true;
				$ret['perms_connect'] = [ 
					'view_stream', 'view_profile', 'view_contacts', 'view_storage',
					'view_pages', 'send_stream', 'post_wall', 'post_comments', 
					'post_mail', 'chat', 'post_like' ];

				$ret['limits'] = PermissionLimits::Std_Limits();

				break;

			case 'social_private':
				$ret['perms_auto'] = false;
				$ret['default_collection'] = true;
				$ret['directory_publish'] = false;
				$ret['online'] = false;
				$ret['perms_connect'] = [ 
					'view_stream', 'view_profile', 'view_contacts', 'view_storage',
					'view_pages', 'send_stream', 'post_wall', 'post_comments', 
					'post_mail', 'post_like' ];
				$ret['limits'] = PermissionLimits::Std_Limits();
				$ret['limits']['view_contacts'] = PERMS_SPECIFIC;
				$ret['limits']['view_storage'] = PERMS_SPECIFIC;

				break;

			case 'forum':
				$ret['perms_auto'] = true;
				$ret['default_collection'] = false;
				$ret['directory_publish'] = true;
				$ret['online'] = false;
				$ret['perms_connect'] = [ 
					'view_stream', 'view_profile', 'view_contacts', 'view_storage',
					'view_pages', 'post_wall', 'post_comments', 'tag_deliver',
					'post_mail', 'post_like' , 'republish', 'chat' ];

				$ret['limits'] = PermissionLimits::Std_Limits();
				break;

			case 'forum_restricted':
				$ret['perms_auto'] = false;
				$ret['default_collection'] = true;
				$ret['directory_publish'] = true;
				$ret['online'] = false;
				$ret['perms_connect'] = [ 
					'view_stream', 'view_profile', 'view_contacts', 'view_storage',
					'view_pages', 'post_wall', 'post_comments', 'tag_deliver',
					'post_mail', 'post_like' , 'chat' ];

				$ret['limits'] = PermissionLimits::Std_Limits();

				break;

			case 'forum_private':
				$ret['perms_auto'] = false;
				$ret['default_collection'] = true;
				$ret['directory_publish'] = false;
				$ret['online'] = false;

				$ret['perms_connect'] = [ 
					'view_stream', 'view_profile', 'view_contacts', 'view_storage',
					'view_pages', 'post_wall', 'post_comments',
					'post_mail', 'post_like' , 'chat' ];

				$ret['limits'] = PermissionLimits::Std_Limits();
				$ret['limits']['view_profile']  = PERMS_SPECIFIC;
				$ret['limits']['view_contacts'] = PERMS_SPECIFIC;
				$ret['limits']['view_storage']  = PERMS_SPECIFIC;
				$ret['limits']['view_pages']    = PERMS_SPECIFIC;

				break;

			case 'feed':
				$ret['perms_auto'] = true;
				$ret['default_collection'] = false;
				$ret['directory_publish'] = true;
				$ret['online'] = false;

				$ret['perms_connect'] = [ 
					'view_stream', 'view_profile', 'view_contacts', 'view_storage',
					'view_pages', 'send_stream', 'post_wall', 'post_comments', 
					'post_mail', 'post_like' , 'republish' ];
	
				$ret['limits'] = PermissionLimits::Std_Limits();

				break;

			case 'feed_restricted':
				$ret['perms_auto'] = false;
				$ret['default_collection'] = true;
				$ret['directory_publish'] = false;
				$ret['online'] = false;
				$ret['perms_connect'] = [ 
					'view_stream', 'view_profile', 'view_contacts', 'view_storage',
					'view_pages', 'send_stream', 'post_wall', 'post_comments', 
					'post_mail', 'post_like' , 'republish' ];

				$ret['limits'] = PermissionLimits::Std_Limits();

				break;

			case 'soapbox':
				$ret['perms_auto'] = true;
				$ret['default_collection'] = false;
				$ret['directory_publish'] = true;
				$ret['online'] = false;

				$ret['perms_connect'] = [ 
					'view_stream', 'view_profile', 'view_contacts', 'view_storage',
					'view_pages', 'post_like' , 'republish' ];

				$ret['limits'] = PermissionLimits::Std_Limits();

				break;

			case 'repository':
				$ret['perms_auto'] = true;
				$ret['default_collection'] = false;
				$ret['directory_publish'] = true;
				$ret['online'] = false;

				$ret['perms_connect'] = [ 
					'view_stream', 'view_profile', 'view_contacts', 'view_storage',
					'view_pages', 'write_storage', 'write_pages', 'post_wall', 'post_comments', 'tag_deliver',
					'post_mail', 'post_like' , 'republish', 'chat' ];

				$ret['limits'] = PermissionLimits::Std_Limits();
				break;

			case 'custom':
			default:
				break;
		}

		$x = get_config('system','role_perms');
		// let system settings over-ride any or all 
		if($x && is_array($x) && array_key_exists($role,$x))
			$ret = array_merge($ret,$x[$role]);

		call_hooks('get_role_perms',$ret);

		return $ret;
	}

	static public function new_custom_perms($uid,$perm,$abooks) {

		// set permissionlimits for this permission here, for example:

		// if($perm === 'mynewperm')
		//     \Zotlabs\Access\PermissionLimits::Set($uid,$perm,1);


		// set autoperms here if applicable
		// choices are to set to 0, 1, or the value of an existing perm

		if(get_pconfig($uid,'system','autoperms')) {

			$c = channelx_by_n($uid);
			$value = 0;

			// if($perm === 'mynewperm')
			//	 $value = get_abconfig($uid,$c['channel_hash'],'autoperms','someexistingperm'));

			if($c) {
				set_abconfig($uid,$c['channel_hash'],'autoperms',$perm,$value);
			}


		}

		// now set something for all existing connections.

		if($abooks) {
			foreach($abooks as $ab) {
				switch($perm) {
					// case 'mynewperm':
					// choices are to set to 1, set to 0, or clone an existing perm
					// set_abconfig($uid,$ab['abook_xchan'],'my_perms',$perm,
					//		get_abconfig($uid,$ab['abook_xchan'],'my_perms','someexistingperm'));
					
					default:
						break;
				}
			}
		}
	}


	static public function roles() {
	    $roles = [
			t('Social Networking') => [
				'social' => t('Social - Mostly Public'), 
				'social_restricted' => t('Social - Restricted'), 
				'social_private' => t('Social - Private')
			],

			t('Community Forum') => [
				'forum' => t('Forum - Mostly Public'), 
				'forum_restricted' => t('Forum - Restricted'), 
				'forum_private' => t('Forum - Private')
			],

			t('Feed Republish') => [
				'feed' => t('Feed - Mostly Public'), 
				'feed_restricted' => t('Feed - Restricted')
			],

			t('Special Purpose') => [
				'soapbox' => t('Special - Celebrity/Soapbox'), 
				'repository' => t('Special - Group Repository')
			],

			t('Other') => [
				'custom' => t('Custom/Expert Mode')
			]
    
		];

    	return $roles;
	}



}