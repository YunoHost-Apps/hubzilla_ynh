<?php /** @file */

function nav(&$a) {

	/**
	 *
	 * Build page header and site navigation bars
	 *
	 */

	if(!(x(App::$page,'nav')))
		App::$page['nav'] = '';

	$base = z_root();
    App::$page['htmlhead'] .= <<< EOT

<script>$(document).ready(function() {
	$("#nav-search-text").search_autocomplete('$base/acl');
});

</script>
EOT;



	if(local_channel()) {
		$channel = App::get_channel();
		$observer = App::get_observer();
		$prof = q("select id from profile where uid = %d and is_default = 1",
			intval($channel['channel_id'])
		);

		$chans = q("select channel_name, channel_id from channel where channel_account_id = %d and channel_removed = 0 order by channel_name ",
			intval(get_account_id())
		);
	}
	elseif(remote_channel())
		$observer = App::get_observer();
	

	$myident = (($channel) ? $channel['xchan_addr'] : '');
		
	$sitelocation = (($myident) ? $myident : App::get_hostname());



	/**
	 *
	 * Provide a banner/logo/whatever
	 *
	 */

	$banner = get_config('system','banner');

	if($banner === false) 
		$banner = get_config('system','sitename');

	App::$page['header'] .= replace_macros(get_markup_template('hdr.tpl'), array(
        '$baseurl' => z_root(),
		'$sitelocation' => $sitelocation,
		'$banner' =>  $banner
	));

	$server_role = get_config('system','server_role');
	$basic = (($server_role === 'basic') ? true : false);
	$techlevel = get_account_techlevel();

	// nav links: array of array('href', 'text', 'extra css classes', 'title')
	$nav = Array();

	/**
	 * Display login or logout
	 */	

	$nav['usermenu']=array();
	$userinfo = null;
	$nav['loginmenu']=array();

	if(local_channel()) {


		if($chans && count($chans) > 1 && feature_enabled(local_channel(),'nav_channel_select') && (! $basic))
			$nav['channels'] = $chans;

		$nav['logout'] = Array('logout',t('Logout'), "", t('End this session'),'logout_nav_btn');
		
		// user menu
		$nav['usermenu'][] = Array('channel/' . $channel['channel_address'], t('Home'), "", t('Your posts and conversations'),'channel_nav_btn');
		$nav['usermenu'][] = Array('profile/' . $channel['channel_address'], t('View Profile'), "", t('Your profile page'),'profile_nav_btn');
		if(feature_enabled(local_channel(),'multi_profiles') && (! $basic))
			$nav['usermenu'][]   = Array('profiles', t('Edit Profiles'),"", t('Manage/Edit profiles'),'profiles_nav_btn');
		else
			$nav['usermenu'][]   = Array('profiles/' . $prof[0]['id'], t('Edit Profile'),"", t('Edit your profile'),'profiles_nav_btn');

		$nav['usermenu'][] = Array('photos/' . $channel['channel_address'], t('Photos'), "", t('Your photos'),'photos_nav_btn');
		$nav['usermenu'][] = Array('cloud/' . $channel['channel_address'],t('Files'),"",t('Your files'),'cloud_nav_btn');

		if((! $basic) && feature_enabled(local_channel(),'ajaxchat'))
			$nav['usermenu'][] = Array('chat/' . $channel['channel_address'], t('Chat'),"",t('Your chatrooms'),'chat_nav_btn');


		require_once('include/menu.php');
		$has_bookmarks = menu_list_count(local_channel(),'',MENU_BOOKMARK) + menu_list_count(local_channel(),'',MENU_SYSTEM|MENU_BOOKMARK);
		if(($has_bookmarks) && (! $basic)) {
			$nav['usermenu'][] = Array('bookmarks', t('Bookmarks'), "", t('Your bookmarks'),'bookmarks_nav_btn');
		}

		if(feature_enabled($channel['channel_id'],'webpages') && (! $basic))
			$nav['usermenu'][] = Array('webpages/' . $channel['channel_address'],t('Webpages'),"",t('Your webpages'),'webpages_nav_btn');
		if(feature_enabled($channel['channel_id'],'wiki') && (! $basic))
			$nav['usermenu'][] = Array('wiki/' . $channel['channel_address'],t('Wikis'),"",t('Your wikis'),'wiki_nav_btn');
	}
	else {
		if(! get_account_id())  {
			$nav['loginmenu'][] = Array('login',t('Login'),'',t('Sign in'),'login_nav_btn');
		}
		else
			$nav['alogout'] = Array('logout',t('Logout'), "", t('End this session'),'logout_nav_btn');


	}

	if($observer) {
			$userinfo = array(
			'icon' => $observer['xchan_photo_m'],
			'name' => $observer['xchan_addr'],
		);
	}

	elseif(! $_SESSION['authenticated']) {
		$nav['loginmenu'][] = Array('rmagic',t('Remote authentication'),'',t('Click to authenticate to your home hub'),'rmagic_nav_btn');
	}

	$homelink = get_my_url();
	if(! $homelink) {
		$observer = App::get_observer();
		$homelink = (($observer) ? $observer['xchan_url'] : '');
	}

	if(! local_channel()) {
		$nav['rusermenu'] = array(
			$homelink,
			t('Get me home'),
			'logout',
			t('Log me out of this site')
		);
	}

	if(((get_config('system','register_policy') == REGISTER_OPEN) || (get_config('system','register_policy') == REGISTER_APPROVE)) && (! $_SESSION['authenticated']))
		$nav['register'] = array('register',t('Register'), "", t('Create an account'),'register_nav_btn');

	if(! get_config('system','hide_help')) {
		$help_url = z_root() . '/help?f=&cmd=' . App::$cmd;
		$context_help = '';
		$enable_context_help = ((intval(get_config('system','enable_context_help')) === 1 || get_config('system','enable_context_help') === false) ? true : false);
		if($enable_context_help === true) {
			require_once('include/help.php');
			$context_help = load_context_help();
			//point directly to /help if $context_help is empty - this can be removed once we have context help for all modules
			$enable_context_help = (($context_help) ? true : false);
		}
		$nav['help'] = array($help_url, t('Help'), "", t('Help and documentation'), 'help_nav_btn', $context_help, $enable_context_help);
	}

	if(! $basic)
		$nav['apps'] = array('apps', t('Apps'), "", t('Applications, utilities, links, games'),'apps_nav_btn');

	$nav['search'] = array('search', t('Search'), "", t('Search site @name, #tag, ?docs, content'));

	$nav['directory'] = array('directory', t('Directory'), "", t('Channel Directory'),'directory_nav_btn'); 


	/**
	 *
	 * The following nav links are only show to logged in users
	 *
	 */

	if(local_channel()) {

	
		$nav['network'] = array('network', t('Grid'), "", t('Your grid'),'network_nav_btn');
		$nav['network']['mark'] = array('', t('Mark all grid notifications seen'), '','');

		$nav['home'] = array('channel/' . $channel['channel_address'], t('Channel Home'), "", t('Channel home'),'home_nav_btn');
		$nav['home']['mark'] = array('', t('Mark all channel notifications seen'), '','');


		$nav['intros'] = array('connections/ifpending',	t('Connections'), "", t('Connections'),'connections_nav_btn');


		$nav['notifications'] = array('notifications/system',	t('Notices'), "", t('Notifications'),'notifications_nav_btn');
		$nav['notifications']['all']=array('notifications/system', t('See all notifications'), "", "");
		$nav['notifications']['mark'] = array('', t('Mark all system notifications seen'), '','');

		$nav['messages'] = array('mail/combined', t('Mail'), "", t('Private mail'),'mail_nav_btn');
		$nav['messages']['all']=array('mail/combined', t('See all private messages'), "", "");
		$nav['messages']['mark'] = array('', t('Mark all private messages seen'), '','');
		$nav['messages']['inbox'] = array('mail/inbox', t('Inbox'), "", t('Inbox'));
		$nav['messages']['outbox']= array('mail/outbox', t('Outbox'), "", t('Outbox'));
		$nav['messages']['new'] = array('mail/new', t('New Message'), "", t('New Message'));


		$nav['all_events'] = array('events', t('Events'), "", t('Event Calendar'),'events_nav_btn');
		$nav['all_events']['all']=array('events', t('See all events'), "", "");
		$nav['all_events']['mark'] = array('', t('Mark all events seen'), '','');

		if(! $basic)		
			$nav['manage'] = array('manage', t('Channel Manager'), "", t('Manage Your Channels'),'manage_nav_btn');

		$nav['settings'] = array('settings', t('Settings'),"", t('Account/Channel Settings'),'settings_nav_btn');

	}

	/**
	 * Admin page
	 */
	 if (is_site_admin()){
		 $nav['admin'] = array('admin/', t('Admin'), "", t('Site Setup and Configuration'),'admin_nav_btn');
	 }


	/**
	 *
	 * Provide a banner/logo/whatever
	 *
	 */

	$banner = get_config('system','banner');

	if($banner === false) 
		$banner = get_config('system','sitename');

	$x = array('nav' => $nav, 'usermenu' => $userinfo );
	call_hooks('nav', $x);

// Not sure the best place to put this on the page. So I'm implementing it but leaving it 
// turned off until somebody discovers this and figures out a good location for it. 
$powered_by = '';

//	$powered_by = '<strong>red<img class="smiley" src="' . z_root() . '/images/rm-16.png" alt="r#" />matrix</strong>';

	$tpl = get_markup_template('nav.tpl');

	App::$page['nav'] .= replace_macros($tpl, array(
		'$baseurl' => z_root(),
		'$sitelocation' => $sitelocation,
		'$nav' => $x['nav'],
		'$banner' =>  $banner,
		'$emptynotifications' => t('Loading...'),
		'$userinfo' => $x['usermenu'],
		'$localuser' => local_channel(),
		'$sel' => 	App::$nav_sel,
		'$powered_by' => $powered_by,
		'$help' => t('@name, #tag, ?doc, content'),
		'$pleasewait' => t('Please wait...')
	));


	if(x($_SESSION, 'reload_avatar') && $observer) {
		// The avatar has been changed on the server but the browser doesn't know that, 
		// force the browser to reload the image from the server instead of its cache.
		$tpl = get_markup_template('force_image_reload.tpl');

		App::$page['nav'] .= replace_macros($tpl, array(
			'$imgUrl' => $observer['xchan_photo_m']
		));
		unset($_SESSION['reload_avatar']);
	}


	call_hooks('page_header', App::$page['nav']);
}

/*
 * Set a menu item in navbar as selected
 * 
 */
function nav_set_selected($item){
    App::$nav_sel = array(
		'community' 	=> null,
		'network' 		=> null,
		'home'			=> null,
		'profiles'		=> null,
		'intros'        => null,
		'notifications'	=> null,
		'messages'		=> null,
		'directory'	    => null,
		'settings'		=> null,
		'contacts'		=> null,
		'manage'        => null,
		'register'      => null,
	);
	App::$nav_sel[$item] = 'active';
}
