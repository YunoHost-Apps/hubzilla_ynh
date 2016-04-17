<?php
/**
 * Name: NSA bait
 * Description: Make yourself a political target
 * Version: 1.0
 * Author: Mike Macgirvin <http://macgirvin.com/profile/mike>
 * Maintainer: none
 */


function nsabait_load() {

	/**
	 * 
	 * Our demo plugin will attach in three places.
	 * The first is just prior to storing a local post.
	 *
	 */

	register_hook('post_local_start', 'addon/nsabait/nsabait.php', 'nsabait_post_hook');

	/**
	 *
	 * Then we'll attach into the plugin settings page, and also the 
	 * settings post hook so that we can create and update
	 * user preferences.
	 *
	 */

	register_hook('feature_settings', 'addon/nsabait/nsabait.php', 'nsabait_settings');
	register_hook('feature_settings_post', 'addon/nsabait/nsabait.php', 'nsabait_settings_post');

	logger("loaded nsabait");
}


function nsabait_unload() {

	/**
	 *
	 * unload unregisters any hooks created with register_hook
	 * during load. It may also delete configuration settings
	 * and any other cleanup.
	 *
	 */

	unregister_hook('post_local_start',    'addon/nsabait/nsabait.php', 'nsabait_post_hook');
	unregister_hook('feature_settings', 'addon/nsabait/nsabait.php', 'nsabait_settings');
	unregister_hook('feature_settings_post', 'addon/nsabait/nsabait.php', 'nsabait_settings_post');


	logger("removed nsabait");
}



function nsabait_post_hook($a, &$req) {
	/**
	 *
	 * An item was posted on the local system.
	 * We are going to look for specific items:
	 *      - A status post by a profile owner
	 *      - The profile owner must have allowed our plugin
	 *
	 */

	logger('nsabait invoked');

	if(! local_channel())   /* non-zero if this is a logged in user of this system */
		return;

	if(local_channel() != $req['profile_uid'])    /* Does this person own the post? */
		return;

	if($req['parent'])   /* If the req has a parent, this is a comment or something else, not a status post. */
		return;

	if($req['namespace'] || $req['remote_id'] || $req['post_id'])
		return;

	/* Retrieve our personal config setting */

	$active = get_pconfig(local_channel(), 'nsabait', 'enable');

	if(! $active)
		return;

	$nsabait = file('addon/nsabait/words.txt');
	shuffle($nsabait);
	$used = array();

	$req['body'] .= "\n";

	for($x = 0; $x < 5; $x ++) {
		$y = mt_rand(0,count($nsabait));
		if((in_array(strtolower(trim($nsabait[$y])),$used)) || (! trim($nsabait[$y]))) {
			$x -= 1;
			continue;
		}
		$used[] = strtolower(trim($nsabait[$y]));

		$req['body'] .= ' #' . str_replace(' ','_',trim($nsabait[$y]));
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

function nsabait_settings_post($a,$post) {
	if(! local_channel())
		return;
	if($_POST['nsabait-submit']) {
		set_pconfig(local_channel(),'nsabait','enable',intval($_POST['nsabait']));
		info( t('Nsabait Settings updated.') . EOL);
	}
}


/**
 *
 * Called from the Plugin Setting form. 
 * Add our own settings info to the page.
 *
 */



function nsabait_settings(&$a,&$s) {

	if(! local_channel())
		return;

	/* Add our stylesheet to the page so we can make our settings look nice */

	//App::$page['htmlhead'] .= '<link rel="stylesheet"  type="text/css" href="' . z_root() . '/addon/nsabait/nsabait.css' . '" media="all" />' . "\r\n";

	/* Get the current state of our config variable */

	$enabled = get_pconfig(local_channel(),'nsabait','enable');

	$checked = (($enabled) ? 1 : false);

	/* Add some HTML to the existing form */

	$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
		'$field'	=> array('nsabait', t('Enable NSAbait Plugin'), $checked, '', array(t('No'),t('Yes'))),
	));

	$s .= replace_macros(get_markup_template('generic_addon_settings.tpl'), array(
		'$addon' 	=> array('nsabait',t('NSAbait Settings'), '', t('Submit')),
		'$content'	=> $sc
	));
}
