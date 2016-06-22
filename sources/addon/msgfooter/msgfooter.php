<?php
/**
 * Name: Msg Footer
 * Description: Provide legal or other text at the bottom of posts
 * Version: 1.0
 * Author: Mike Macgirvin
 * Maintainer: none
 */


function msgfooter_load() {

	/**
	 * 
	 * Our demo plugin will attach in three places.
	 * The first is just prior to storing a local post.
	 *
	 */

	register_hook('post_local', 'addon/msgfooter/msgfooter.php', 'msgfooter_post_hook');
	logger("loaded msgfooter");
}


function msgfooter_unload() {

	/**
	 *
	 * unload unregisters any hooks created with register_hook
	 * during load. It may also delete configuration settings
	 * and any other cleanup.
	 *
	 */

	unregister_hook('post_local',    'addon/msgfooter/msgfooter.php', 'msgfooter_post_hook');

	logger("removed msgfooter");
}


function msgfooter_plugin_admin(&$a,&$o) {

	$t = get_markup_template("admin.tpl", "addon/msgfooter/");

	$o = replace_macros($t, array(
		'$submit' => t('Save Settings'),
		'$msgfooter_text' => array('msgfooter_text', t('text to include in all outgoing posts from this site'), get_config('msgfooter', 'msgfooter_text'), '')
	));
}

function msgfooter_plugin_admin_post(&$a){
	$msgfooter_text = ((x($_POST,'msgfooter_text')) ?       trim($_POST['msgfooter_text']) : '');
	set_config('msgfooter','msgfooter_text',$msgfooter_text);
	info( t('Settings updated.'). EOL );
}


function msgfooter_post_hook($a, &$item) {

	/**
	 *
	 * An item was posted on the local system.
	 * We are going to look for specific items:
	 *      - A status post by a profile owner
	 *      - The profile owner must have allowed our plugin
	 *
	 */

	logger('msgfooter invoked');

	if(! local_channel())   /* non-zero if this is a logged in user of this system */
		return;

	if(local_channel() != $item['uid'])    /* Does this person own the post? */
		return;

	if($item['item_type'])
		return;

	if($item['parent'])   /* If the item has a parent, this is a comment or something else, not a status post. */
		return;

	/* Retrieve our config setting */

	$footer = get_config('msgfooter', 'msgfooter_text');

	if(! $footer)
		return;


	$item['body'] .= '[footer]' . $footer . '[/footer]';

	return;
}




