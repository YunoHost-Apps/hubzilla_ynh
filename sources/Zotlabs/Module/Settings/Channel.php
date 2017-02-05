<?php

namespace Zotlabs\Module\Settings;


class Channel {


	function post() {

		$channel = \App::get_channel();

		check_form_security_token_redirectOnErr('/settings', 'settings');
		
		call_hooks('settings_post', $_POST);
	
		$set_perms = '';
	
		$role = ((x($_POST,'permissions_role')) ? notags(trim($_POST['permissions_role'])) : '');
		$oldrole = get_pconfig(local_channel(),'system','permissions_role');
	
		if(($role != $oldrole) || ($role === 'custom')) {
	
			if($role === 'custom') {
				$hide_presence    = (((x($_POST,'hide_presence')) && (intval($_POST['hide_presence']) == 1)) ? 1: 0);
				$publish          = (((x($_POST,'profile_in_directory')) && (intval($_POST['profile_in_directory']) == 1)) ? 1: 0);
				$def_group        = ((x($_POST,'group-selection')) ? notags(trim($_POST['group-selection'])) : '');
				$r = q("update channel set channel_default_group = '%s' where channel_id = %d",
					dbesc($def_group),
					intval(local_channel())
				);	
	
				$global_perms = \Zotlabs\Access\Permissions::Perms();
	
				foreach($global_perms as $k => $v) {
					\Zotlabs\Access\PermissionLimits::Set(local_channel(),$k,intval($_POST[$k]));
				}
				$acl = new \Zotlabs\Access\AccessList($channel);
				$acl->set_from_array($_POST);
				$x = $acl->get();
	
				$r = q("update channel set channel_allow_cid = '%s', channel_allow_gid = '%s', 
					channel_deny_cid = '%s', channel_deny_gid = '%s' where channel_id = %d",
					dbesc($x['allow_cid']),
					dbesc($x['allow_gid']),
					dbesc($x['deny_cid']),
					dbesc($x['deny_gid']),
					intval(local_channel())
				);
			}
			else {
			   	$role_permissions = \Zotlabs\Access\PermissionRoles::role_perms($_POST['permissions_role']);
				if(! $role_permissions) {
					notice('Permissions category could not be found.');
					return;
				}
				$hide_presence    = 1 - (intval($role_permissions['online']));
				if($role_permissions['default_collection']) {
					$r = q("select hash from groups where uid = %d and gname = '%s' limit 1",
						intval(local_channel()),
						dbesc( t('Friends') )
					);
					if(! $r) {
						require_once('include/group.php');
						group_add(local_channel(), t('Friends'));
						group_add_member(local_channel(),t('Friends'),$channel['channel_hash']);
						$r = q("select hash from groups where uid = %d and gname = '%s' limit 1",
							intval(local_channel()),
							dbesc( t('Friends') )
						);
					}
					if($r) {
						q("update channel set channel_default_group = '%s', channel_allow_gid = '%s', channel_allow_cid = '', channel_deny_gid = '', channel_deny_cid = '' where channel_id = %d",
							dbesc($r[0]['hash']),
							dbesc('<' . $r[0]['hash'] . '>'),
							intval(local_channel())
						);
					}
					else {
						notice( sprintf('Default privacy group \'%s\' not found. Please create and re-submit permission change.', t('Friends')) . EOL);
						return;
					}
				}
				// no default collection
				else {
					q("update channel set channel_default_group = '', channel_allow_gid = '', channel_allow_cid = '', channel_deny_gid = '', 
						channel_deny_cid = '' where channel_id = %d",
							intval(local_channel())
					);
				}
	
				$x = \Zotlabs\Access\Permissions::FilledPerms($role_permissions['perms_connect']);
				foreach($x as $k => $v) {
					set_abconfig(local_channel(),$channel['channel_hash'],'my_perms',$k, $v);
					if($role_permissions['perms_auto']) {
						set_pconfig(local_channel(),'autoperms',$k,$v);
					}
					else {
						del_pconfig(local_channel(),'autoperms',$k);
					}
				}	

				if($role_permissions['limits']) {
					foreach($role_permissions['limits'] as $k => $v) {
						\Zotlabs\Access\PermissionLimits::Set(local_channel(),$k,$v);
					}
				}
				if(array_key_exists('directory_publish',$role_permissions)) {
					$publish = intval($role_permissions['directory_publish']);
				}
			}
	
			set_pconfig(local_channel(),'system','hide_online_status',$hide_presence);
			set_pconfig(local_channel(),'system','permissions_role',$role);
		}
	
		$username         = ((x($_POST,'username'))   ? notags(trim($_POST['username']))     : '');
		$timezone         = ((x($_POST,'timezone_select'))   ? notags(trim($_POST['timezone_select']))     : '');
		$defloc           = ((x($_POST,'defloc'))     ? notags(trim($_POST['defloc']))       : '');
		$openid           = ((x($_POST,'openid_url')) ? notags(trim($_POST['openid_url']))   : '');
		$maxreq           = ((x($_POST,'maxreq'))     ? intval($_POST['maxreq'])             : 0);
		$expire           = ((x($_POST,'expire'))     ? intval($_POST['expire'])             : 0);
		$evdays           = ((x($_POST,'evdays'))     ? intval($_POST['evdays'])             : 3);
		$photo_path       = ((x($_POST,'photo_path')) ? escape_tags(trim($_POST['photo_path'])) : '');
		$attach_path      = ((x($_POST,'attach_path')) ? escape_tags(trim($_POST['attach_path'])) : '');
	
		$channel_menu     = ((x($_POST['channel_menu'])) ? htmlspecialchars_decode(trim($_POST['channel_menu']),ENT_QUOTES) : '');
	
		$expire_items     = ((x($_POST,'expire_items')) ? intval($_POST['expire_items'])	 : 0);
		$expire_starred   = ((x($_POST,'expire_starred')) ? intval($_POST['expire_starred']) : 0);
		$expire_photos    = ((x($_POST,'expire_photos'))? intval($_POST['expire_photos'])	 : 0);
		$expire_network_only    = ((x($_POST,'expire_network_only'))? intval($_POST['expire_network_only'])	 : 0);
	
		$allow_location   = (((x($_POST,'allow_location')) && (intval($_POST['allow_location']) == 1)) ? 1: 0);
	
		$blocktags        = (((x($_POST,'blocktags')) && (intval($_POST['blocktags']) == 1)) ? 0: 1); // this setting is inverted!
		$unkmail          = (((x($_POST,'unkmail')) && (intval($_POST['unkmail']) == 1)) ? 1: 0);
		$cntunkmail       = ((x($_POST,'cntunkmail')) ? intval($_POST['cntunkmail']) : 0);
		$suggestme        = ((x($_POST,'suggestme')) ? intval($_POST['suggestme'])  : 0);  
	
		$post_newfriend   = (($_POST['post_newfriend'] == 1) ? 1: 0);
		$post_joingroup   = (($_POST['post_joingroup'] == 1) ? 1: 0);
		$post_profilechange   = (($_POST['post_profilechange'] == 1) ? 1: 0);
		$adult            = (($_POST['adult'] == 1) ? 1 : 0);
	
		$cal_first_day   = (((x($_POST,'first_day')) && (intval($_POST['first_day']) == 1)) ? 1: 0);
	
		$pageflags = $channel['channel_pageflags'];
		$existing_adult = (($pageflags & PAGE_ADULT) ? 1 : 0);
		if($adult != $existing_adult)
			$pageflags = ($pageflags ^ PAGE_ADULT);
	
	
		$notify = 0;
	
		if(x($_POST,'notify1'))
			$notify += intval($_POST['notify1']);
		if(x($_POST,'notify2'))
			$notify += intval($_POST['notify2']);
		if(x($_POST,'notify3'))
			$notify += intval($_POST['notify3']);
		if(x($_POST,'notify4'))
			$notify += intval($_POST['notify4']);
		if(x($_POST,'notify5'))
			$notify += intval($_POST['notify5']);
		if(x($_POST,'notify6'))
			$notify += intval($_POST['notify6']);
		if(x($_POST,'notify7'))
			$notify += intval($_POST['notify7']);
		if(x($_POST,'notify8'))
			$notify += intval($_POST['notify8']);
	
	
		$vnotify = 0;
	
		if(x($_POST,'vnotify1'))
			$vnotify += intval($_POST['vnotify1']);
		if(x($_POST,'vnotify2'))
			$vnotify += intval($_POST['vnotify2']);
		if(x($_POST,'vnotify3'))
			$vnotify += intval($_POST['vnotify3']);
		if(x($_POST,'vnotify4'))
			$vnotify += intval($_POST['vnotify4']);
		if(x($_POST,'vnotify5'))
			$vnotify += intval($_POST['vnotify5']);
		if(x($_POST,'vnotify6'))
			$vnotify += intval($_POST['vnotify6']);
		if(x($_POST,'vnotify7'))
			$vnotify += intval($_POST['vnotify7']);
		if(x($_POST,'vnotify8'))
			$vnotify += intval($_POST['vnotify8']);
		if(x($_POST,'vnotify9'))
			$vnotify += intval($_POST['vnotify9']);
		if(x($_POST,'vnotify10'))
			$vnotify += intval($_POST['vnotify10']);
		if(x($_POST,'vnotify11'))
			$vnotify += intval($_POST['vnotify11']);
	
		$always_show_in_notices = x($_POST,'always_show_in_notices') ? 1 : 0;
		
		$err = '';
	
		$name_change = false;
	
		if($username != $channel['channel_name']) {
			$name_change = true;
			require_once('include/channel.php');
			$err = validate_channelname($username);
			if($err) {
				notice($err);
				return;
			}
		}
	
		if($timezone != $channel['channel_timezone']) {
			if(strlen($timezone))
				date_default_timezone_set($timezone);
		}
	
		set_pconfig(local_channel(),'system','use_browser_location',$allow_location);
		set_pconfig(local_channel(),'system','suggestme', $suggestme);
		set_pconfig(local_channel(),'system','post_newfriend', $post_newfriend);
		set_pconfig(local_channel(),'system','post_joingroup', $post_joingroup);
		set_pconfig(local_channel(),'system','post_profilechange', $post_profilechange);
		set_pconfig(local_channel(),'system','blocktags',$blocktags);
		set_pconfig(local_channel(),'system','channel_menu',$channel_menu);
		set_pconfig(local_channel(),'system','vnotify',$vnotify);
		set_pconfig(local_channel(),'system','always_show_in_notices',$always_show_in_notices);
		set_pconfig(local_channel(),'system','evdays',$evdays);
		set_pconfig(local_channel(),'system','photo_path',$photo_path);
		set_pconfig(local_channel(),'system','attach_path',$attach_path);
		set_pconfig(local_channel(),'system','cal_first_day',$cal_first_day);
	
		$r = q("update channel set channel_name = '%s', channel_pageflags = %d, channel_timezone = '%s', channel_location = '%s', channel_notifyflags = %d, channel_max_anon_mail = %d, channel_max_friend_req = %d, channel_expire_days = %d $set_perms where channel_id = %d",
			dbesc($username),
			intval($pageflags),
			dbesc($timezone),
			dbesc($defloc),
			intval($notify),
			intval($unkmail),
			intval($maxreq),
			intval($expire),
			intval(local_channel())
		);   
		if($r)
			info( t('Settings updated.') . EOL);
	
		if(! is_null($publish)) {
			$r = q("UPDATE profile SET publish = %d WHERE is_default = 1 AND uid = %d",
				intval($publish),
				intval(local_channel())
			);
		}
	
		if($name_change) {
			$r = q("update xchan set xchan_name = '%s', xchan_name_date = '%s' where xchan_hash = '%s'",
				dbesc($username),
				dbesc(datetime_convert()),
				dbesc($channel['channel_hash'])
			);
			$r = q("update profile set fullname = '%s' where uid = %d and is_default = 1",
				dbesc($username),
				intval($channel['channel_id'])
			);
		}
	
		\Zotlabs\Daemon\Master::Summon(array('Directory',local_channel()));
	
		build_sync_packet();
	
	
		if($email_changed && \App::$config['system']['register_policy'] == REGISTER_VERIFY) {
	
			// FIXME - set to un-verified, blocked and redirect to logout
			// Why? Are we verifying people or email addresses?
	
		}
	
		goaway(z_root() . '/settings' );
		return; // NOTREACHED
	}
			
	function get() {
	
		require_once('include/acl_selectors.php');
		require_once('include/permissions.php');


		$yes_no = array(t('No'),t('Yes'));
	
	
		$p = q("SELECT * FROM profile WHERE is_default = 1 AND uid = %d LIMIT 1",
			intval(local_channel())
		);
		if(count($p))
			$profile = $p[0];
	
		load_pconfig(local_channel(),'expire');
	
		$channel = \App::get_channel();
	
		$global_perms = \Zotlabs\Access\Permissions::Perms();

		$permiss = array();
	
		$perm_opts = array(
			array( t('Nobody except yourself'), 0),
			array( t('Only those you specifically allow'), PERMS_SPECIFIC), 
			array( t('Approved connections'), PERMS_CONTACTS),
			array( t('Any connections'), PERMS_PENDING),
			array( t('Anybody on this website'), PERMS_SITE),
			array( t('Anybody in this network'), PERMS_NETWORK),
			array( t('Anybody authenticated'), PERMS_AUTHED),
			array( t('Anybody on the internet'), PERMS_PUBLIC)
		);
	
		$limits = \Zotlabs\Access\PermissionLimits::Get(local_channel());
	
		foreach($global_perms as $k => $perm) {
			$options = array();
			foreach($perm_opts as $opt) {
				if((! strstr($k,'view')) && $opt[1] == PERMS_PUBLIC)
					continue;
				$options[$opt[1]] = $opt[0];
			}
			$permiss[] = array($k,$perm,$limits[$k],'',$options);			
		}
	
	
		//		logger('permiss: ' . print_r($permiss,true));
	
	
	
		$username   = $channel['channel_name'];
		$nickname   = $channel['channel_address'];
		$timezone   = $channel['channel_timezone'];
		$notify     = $channel['channel_notifyflags'];
		$defloc     = $channel['channel_location'];
	
		$maxreq     = $channel['channel_max_friend_req'];
		$expire     = $channel['channel_expire_days'];
		$adult_flag = intval($channel['channel_pageflags'] & PAGE_ADULT);
		$sys_expire = get_config('system','default_expire_days');
	
//		$unkmail    = \App::$user['unkmail'];
//		$cntunkmail = \App::$user['cntunkmail'];
	
		$hide_presence = intval(get_pconfig(local_channel(), 'system','hide_online_status'));
	
	
		$expire_items = get_pconfig(local_channel(), 'expire','items');
		$expire_items = (($expire_items===false)? '1' : $expire_items); // default if not set: 1
		
		$expire_notes = get_pconfig(local_channel(), 'expire','notes');
		$expire_notes = (($expire_notes===false)? '1' : $expire_notes); // default if not set: 1
	
		$expire_starred = get_pconfig(local_channel(), 'expire','starred');
		$expire_starred = (($expire_starred===false)? '1' : $expire_starred); // default if not set: 1
		
		$expire_photos = get_pconfig(local_channel(), 'expire','photos');
		$expire_photos = (($expire_photos===false)? '0' : $expire_photos); // default if not set: 0
	
		$expire_network_only = get_pconfig(local_channel(), 'expire','network_only');
		$expire_network_only = (($expire_network_only===false)? '0' : $expire_network_only); // default if not set: 0
	
	
		$suggestme = get_pconfig(local_channel(), 'system','suggestme');
		$suggestme = (($suggestme===false)? '0': $suggestme); // default if not set: 0
	
		$post_newfriend = get_pconfig(local_channel(), 'system','post_newfriend');
		$post_newfriend = (($post_newfriend===false)? '0': $post_newfriend); // default if not set: 0
	
		$post_joingroup = get_pconfig(local_channel(), 'system','post_joingroup');
		$post_joingroup = (($post_joingroup===false)? '0': $post_joingroup); // default if not set: 0
	
		$post_profilechange = get_pconfig(local_channel(), 'system','post_profilechange');
		$post_profilechange = (($post_profilechange===false)? '0': $post_profilechange); // default if not set: 0
	
		$blocktags  = get_pconfig(local_channel(),'system','blocktags');
		$blocktags = (($blocktags===false) ? '0' : $blocktags);
		
		$timezone = date_default_timezone_get();
	
		$opt_tpl = get_markup_template("field_checkbox.tpl");
		if(get_config('system','publish_all')) {
			$profile_in_dir = '<input type="hidden" name="profile_in_directory" value="1" />';
		}
		else {
			$profile_in_dir = replace_macros($opt_tpl,array(
				'$field' 	=> array('profile_in_directory', t('Publish your default profile in the network directory'), $profile['publish'], '', $yes_no),
			));
		}
	
		$suggestme = replace_macros($opt_tpl,array(
				'$field' 	=> array('suggestme',  t('Allow us to suggest you as a potential friend to new members?'), $suggestme, '', $yes_no),
	
		));
	
		$subdir = ((strlen(\App::get_path())) ? '<br />' . t('or') . ' ' . z_root() . '/channel/' . $nickname : '');
	
		$tpl_addr = get_markup_template("settings_nick_set.tpl");
	
		$prof_addr = replace_macros($tpl_addr,array(
			'$desc' => t('Your channel address is'),
			'$nickname' => $nickname,
			'$subdir' => $subdir,
			'$davdesc' => t('Your files/photos are accessible via WebDAV at'),
			'$davpath' => ((get_account_techlevel() > 3) ? z_root() . '/dav/' . $nickname : ''),
			'$basepath' => \App::get_hostname()
		));

	
		$stpl = get_markup_template('settings.tpl');
	
		$acl = new \Zotlabs\Access\AccessList($channel);
		$perm_defaults = $acl->get();
	
		require_once('include/group.php');
		$group_select = mini_group_select(local_channel(),$channel['channel_default_group']);
	
		require_once('include/menu.php');
		$m1 = menu_list(local_channel());
		$menu = false;
		if($m1) {
			$menu = array();
			$current = get_pconfig(local_channel(),'system','channel_menu');
			$menu[] = array('name' => '', 'selected' => ((! $current) ? true : false));
			foreach($m1 as $m) {
				$menu[] = array('name' => htmlspecialchars($m['menu_name'],ENT_COMPAT,'UTF-8'), 'selected' => (($m['menu_name'] === $current) ? ' selected="selected" ' : false));
			}
		}
	
		$evdays = get_pconfig(local_channel(),'system','evdays');
		if(! $evdays)
			$evdays = 3;
	
		$permissions_role = get_pconfig(local_channel(),'system','permissions_role');
		if(! $permissions_role)
			$permissions_role = 'custom';
	
		$permissions_set = (($permissions_role != 'custom') ? true : false);

		$perm_roles = \Zotlabs\Access\PermissionRoles::roles();
		if((get_account_techlevel() < 4) && $permissions_role !== 'custom')
			unset($perm_roles[t('Other')]);

		$vnotify = get_pconfig(local_channel(),'system','vnotify');
		$always_show_in_notices = get_pconfig(local_channel(),'system','always_show_in_notices');
		if($vnotify === false)
			$vnotify = (-1);
	
		$o .= replace_macros($stpl,array(
			'$ptitle' 	=> t('Channel Settings'),
	
			'$submit' 	=> t('Submit'),
			'$baseurl' => z_root(),
			'$uid' => local_channel(),
			'$form_security_token' => get_form_security_token("settings"),
			'$nickname_block' => $prof_addr,
			'$h_basic' 	=> t('Basic Settings'),
			'$username' => array('username',  t('Full Name:'), $username,''),
			'$email' 	=> array('email', t('Email Address:'), $email, ''),
			'$timezone' => array('timezone_select' , t('Your Timezone:'), $timezone, '', get_timezones()),
			'$defloc'	=> array('defloc', t('Default Post Location:'), $defloc, t('Geographical location to display on your posts')),
			'$allowloc' => array('allow_location', t('Use Browser Location:'), ((get_pconfig(local_channel(),'system','use_browser_location')) ? 1 : ''), '', $yes_no),
			
			'$adult'    => array('adult', t('Adult Content'), $adult_flag, t('This channel frequently or regularly publishes adult content. (Please tag any adult material and/or nudity with #NSFW)'), $yes_no),
	
			'$h_prv' 	=> t('Security and Privacy Settings'),
			'$permissions_set' => $permissions_set,
			'$server_role' => \Zotlabs\Lib\System::get_server_role(),
			'$perms_set_msg' => t('Your permissions are already configured. Click to view/adjust'),
	
			'$hide_presence' => array('hide_presence', t('Hide my online presence'),$hide_presence, t('Prevents displaying in your profile that you are online'), $yes_no),
	
			'$lbl_pmacro' => t('Simple Privacy Settings:'),
			'$pmacro3'    => t('Very Public - <em>extremely permissive (should be used with caution)</em>'),
			'$pmacro2'    => t('Typical - <em>default public, privacy when desired (similar to social network permissions but with improved privacy)</em>'),
			'$pmacro1'    => t('Private - <em>default private, never open or public</em>'),
			'$pmacro0'    => t('Blocked - <em>default blocked to/from everybody</em>'),
			'$permiss_arr' => $permiss,
			'$blocktags' => array('blocktags',t('Allow others to tag your posts'), 1-$blocktags, t('Often used by the community to retro-actively flag inappropriate content'), $yes_no),
	
			'$lbl_p2macro' => t('Channel Permission Limits'),
	
			'$expire' => array('expire',t('Expire other channel content after this many days'),$expire, t('0 or blank to use the website limit.') . ' ' . ((intval($sys_expire)) ? sprintf( t('This website expires after %d days.'),intval($sys_expire)) : t('This website does not expire imported content.')) . ' ' . t('The website limit takes precedence if lower than your limit.')),
			'$maxreq' 	=> array('maxreq', t('Maximum Friend Requests/Day:'), intval($channel['channel_max_friend_req']) , t('May reduce spam activity')),
			'$permissions' => t('Default Access Control List (ACL)'),
			'$permdesc' => t("\x28click to open/close\x29"),
			'$aclselect' => populate_acl($perm_defaults, false, \Zotlabs\Lib\PermissionDescription::fromDescription(t('Use my default audience setting for the type of object published'))),
			'$allow_cid' => acl2json($perm_defaults['allow_cid']),
			'$allow_gid' => acl2json($perm_defaults['allow_gid']),
			'$deny_cid' => acl2json($perm_defaults['deny_cid']),
			'$deny_gid' => acl2json($perm_defaults['deny_gid']),
			'$suggestme' => $suggestme,
			'$group_select' => $group_select,
			'$role' => array('permissions_role' , t('Channel permissions category:'), $permissions_role, '', $perm_roles),
	
			'$profile_in_dir' => $profile_in_dir,
			'$hide_friends' => $hide_friends,
			'$hide_wall' => $hide_wall,
			'$unkmail' => $unkmail,		
			'$cntunkmail' 	=> array('cntunkmail', t('Maximum private messages per day from unknown people:'), intval($channel['channel_max_anon_mail']) ,t("Useful to reduce spamming")),
			
			
			'$h_not' 	=> t('Notification Settings'),
			'$activity_options' => t('By default post a status message when:'),
			'$post_newfriend' => array('post_newfriend',  t('accepting a friend request'), $post_newfriend, '', $yes_no),
			'$post_joingroup' => array('post_joingroup',  t('joining a forum/community'), $post_joingroup, '', $yes_no),
			'$post_profilechange' => array('post_profilechange',  t('making an <em>interesting</em> profile change'), $post_profilechange, '', $yes_no),
			'$lbl_not' 	=> t('Send a notification email when:'),
			'$notify1'	=> array('notify1', t('You receive a connection request'), ($notify & NOTIFY_INTRO), NOTIFY_INTRO, '', $yes_no),
			'$notify2'	=> array('notify2', t('Your connections are confirmed'), ($notify & NOTIFY_CONFIRM), NOTIFY_CONFIRM, '', $yes_no),
			'$notify3'	=> array('notify3', t('Someone writes on your profile wall'), ($notify & NOTIFY_WALL), NOTIFY_WALL, '', $yes_no),
			'$notify4'	=> array('notify4', t('Someone writes a followup comment'), ($notify & NOTIFY_COMMENT), NOTIFY_COMMENT, '', $yes_no),
			'$notify5'	=> array('notify5', t('You receive a private message'), ($notify & NOTIFY_MAIL), NOTIFY_MAIL, '', $yes_no),
			'$notify6'  => array('notify6', t('You receive a friend suggestion'), ($notify & NOTIFY_SUGGEST), NOTIFY_SUGGEST, '', $yes_no),
			'$notify7'  => array('notify7', t('You are tagged in a post'), ($notify & NOTIFY_TAGSELF), NOTIFY_TAGSELF, '', $yes_no),
			'$notify8'  => array('notify8', t('You are poked/prodded/etc. in a post'), ($notify & NOTIFY_POKE), NOTIFY_POKE, '', $yes_no),
			
			'$notify9'  => array('notify9', t('Someone likes your post/comment'), ($notify & NOTIFY_LIKE), NOTIFY_LIKE, '', $yes_no),
			
	
			'$lbl_vnot' 	=> t('Show visual notifications including:'),
	
			'$vnotify1'	=> array('vnotify1', t('Unseen grid activity'), ($vnotify & VNOTIFY_NETWORK), VNOTIFY_NETWORK, '', $yes_no),
			'$vnotify2'	=> array('vnotify2', t('Unseen channel activity'), ($vnotify & VNOTIFY_CHANNEL), VNOTIFY_CHANNEL, '', $yes_no),
			'$vnotify3'	=> array('vnotify3', t('Unseen private messages'), ($vnotify & VNOTIFY_MAIL), VNOTIFY_MAIL, t('Recommended'), $yes_no),
			'$vnotify4'	=> array('vnotify4', t('Upcoming events'), ($vnotify & VNOTIFY_EVENT), VNOTIFY_EVENT, '', $yes_no),
			'$vnotify5'	=> array('vnotify5', t('Events today'), ($vnotify & VNOTIFY_EVENTTODAY), VNOTIFY_EVENTTODAY, '', $yes_no),
			'$vnotify6'  => array('vnotify6', t('Upcoming birthdays'), ($vnotify & VNOTIFY_BIRTHDAY), VNOTIFY_BIRTHDAY, t('Not available in all themes'), $yes_no),
			'$vnotify7'  => array('vnotify7', t('System (personal) notifications'), ($vnotify & VNOTIFY_SYSTEM), VNOTIFY_SYSTEM, '', $yes_no),
			'$vnotify8'  => array('vnotify8', t('System info messages'), ($vnotify & VNOTIFY_INFO), VNOTIFY_INFO, t('Recommended'), $yes_no),
			'$vnotify9'  => array('vnotify9', t('System critical alerts'), ($vnotify & VNOTIFY_ALERT), VNOTIFY_ALERT, t('Recommended'), $yes_no),
			'$vnotify10'  => array('vnotify10', t('New connections'), ($vnotify & VNOTIFY_INTRO), VNOTIFY_INTRO, t('Recommended'), $yes_no),
			'$vnotify11'  => array('vnotify11', t('System Registrations'), ($vnotify & VNOTIFY_REGISTER), VNOTIFY_REGISTER, '', $yes_no),
			'$always_show_in_notices'  => array('always_show_in_notices', t('Also show new wall posts, private messages and connections under Notices'), $always_show_in_notices, 1, '', $yes_no),
	
			'$evdays' => array('evdays', t('Notify me of events this many days in advance'), $evdays, t('Must be greater than 0')),			
	
			'$h_advn' => t('Advanced Account/Page Type Settings'),
			'$h_descadvn' => t('Change the behaviour of this account for special situations'),
			'$pagetype' => $pagetype,
			'$lbl_misc' => t('Miscellaneous Settings'),
			'$photo_path' => array('photo_path', t('Default photo upload folder'), get_pconfig(local_channel(),'system','photo_path'), t('%Y - current year, %m -  current month')),
			'$attach_path' => array('attach_path', t('Default file upload folder'), get_pconfig(local_channel(),'system','attach_path'), t('%Y - current year, %m -  current month')),
			'$menus' => $menu,			
			'$menu_desc' => t('Personal menu to display in your channel pages'),
			'$removeme' => t('Remove Channel'),
			'$removechannel' => t('Remove this channel.'),
			'$firefoxshare' => t('Firefox Share $Projectname provider'),
			'$cal_first_day' => array('first_day', t('Start calendar week on monday'), ((get_pconfig(local_channel(),'system','cal_first_day')) ? 1 : ''), '', $yes_no),
		));
	
		call_hooks('settings_form',$o);
	
		//$o .= '</form>' . "\r\n";
	
		return $o;
	}
}
