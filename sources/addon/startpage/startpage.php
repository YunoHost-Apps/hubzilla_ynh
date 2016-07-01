<?php
/**
 * Name: Start Page
 * Description: Set a preferred page to load on login from home page
 * Version: 1.0
 * Author: Mike Macgirvin <http://macgirvin.com/profile/mike>
 * Maintainer: Mike Macgirvin <mike@macgirvin.com> 
 */


function startpage_load() {
//	register_hook('home_init', 'addon/startpage/startpage.php', 'startpage_home_init');
	register_hook('feature_settings', 'addon/startpage/startpage.php', 'startpage_settings');
	register_hook('feature_settings_post', 'addon/startpage/startpage.php', 'startpage_settings_post');
}


function startpage_unload() {
	unregister_hook('home_init', 'addon/startpage/startpage.php', 'startpage_home_init');
	unregister_hook('feature_settings', 'addon/startpage/startpage.php', 'startpage_settings');
	unregister_hook('feature_settings_post', 'addon/startpage/startpage.php', 'startpage_settings_post');
}



function startpage_home_init($a, $b) {

	return;
	if(! local_channel())
		return;

	$channel = App::get_channel();
	$page = $channel['channel_startpage'];
	if(! $page)
		$page = get_pconfig(local_channel(),'system','startpage');

	if(strlen($page)) {		
		$slash = ((strpos($page,'/') === 0) ? true : false);
// If we goaway to a z_root for the channel page, all clones will be redirected to the primary hub, so...
		if(stristr($page,'channel'))
			goaway ('$page');

		if(stristr($page,'://'))
			goaway(z_root() . '/' . $page);
		goaway(z_root() . (($slash) ? '' : '/') . $page);
	}
	return;
}

/**
 *
 * Callback from the settings post function.
 * $post contains the $_POST array.
 * We will make sure we've got a valid user account
 * and if so set our configuration setting for this person.
 *
 */

function startpage_settings_post($a,$post) {
	if(! local_channel())
		return;
	$channel = App::get_channel();

	if($_POST['startpage-submit']) {
		$page = strip_tags(trim($_POST['startpage']));
		$page = trim($page,'/');

		if($page == 'channel')
			$page = 'channel/' . $channel['channel_address'];
		elseif($page == '')
			$page = '';
		else
			if(strpos($page,'http') !== 0)
				$page = $page;

		$r = q("update channel set channel_startpage = '%s' where channel_id = %d",
			dbesc($page),
			intval(local_channel())
		);
		set_pconfig(local_channel(),'system','startpage',$page);

	}
}


/**
 *
 * Called from the Plugin Setting form. 
 * Add our own settings info to the page.
 *
 */



function startpage_settings(&$a,&$s) {

	if(! local_channel())
		return;

	/* Add our stylesheet to the page so we can make our settings look nice */

	//head_add_css('/addon/startpage/startpage.css');

	/* Get the current state of our config variable */

	$page = get_pconfig(local_channel(),'system','startpage');

	$sc .= replace_macros(get_markup_template('field_input.tpl'), array(
		'$field'	=> array('startpage', t('Page to load after login'), $page, t('Examples: &quot;apps&quot;, &quot;network?f=&gid=37&quot; (privacy collection), &quot;channel&quot; or &quot;notifications/system&quot; (leave blank for default network page (grid).'))
	));

	$s .= replace_macros(get_markup_template('generic_addon_settings.tpl'), array(
		'$addon' 	=> array('startpage', t('Startpage Settings'), '', t('Submit')),
		'$content'	=> $sc
	));

	return;
    
}
