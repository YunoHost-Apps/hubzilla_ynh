<?php

/**
 * Name: Hubzilla Crosspost Connector (redred)
 * Description: Relay public postings to another Redmatrix/Hubzilla channel
 * Version: 1.0
 * Maintainer: none
 */
 
/*
 *   Hubzilla to Hubzilla
 */

require_once('include/permissions.php');

function redred_load() {
	//  we need some hooks, for the configuration and for sending tweets
	register_hook('feature_settings', 'addon/redred/redred.php', 'redred_settings'); 
	register_hook('feature_settings_post', 'addon/redred/redred.php', 'redred_settings_post');
	register_hook('notifier_normal', 'addon/redred/redred.php', 'redred_post_hook');
	register_hook('post_local', 'addon/redred/redred.php', 'redred_post_local');
	register_hook('jot_networks',    'addon/redred/redred.php', 'redred_jot_nets');
	logger("loaded redred");
}


function redred_unload() {
	unregister_hook('feature_settings', 'addon/redred/redred.php', 'redred_settings'); 
	unregister_hook('feature_settings_post', 'addon/redred/redred.php', 'redred_settings_post');
	unregister_hook('notifier_normal', 'addon/redred/redred.php', 'redred_post_hook');
	unregister_hook('post_local', 'addon/redred/redred.php', 'redred_post_local');
	unregister_hook('jot_networks',    'addon/redred/redred.php', 'redred_jot_nets');

}

function redred_jot_nets(&$a,&$b) {
    if((! local_channel()) || (! perm_is_allowed(local_channel(),'','view_stream')))
        return;

	$redred_post = get_pconfig(local_channel(),'redred','post');
	if(intval($redred_post) == 1) {
		$redred_defpost = get_pconfig(local_channel(),'redred','post_by_default');
		$selected = ((intval($redred_defpost) == 1) ? ' checked="checked" ' : '');
		$b .= '<div class="profile-jot-net"><input type="checkbox" name="redred_enable"' . $selected . ' value="1" /> ' 
			. '<img src="images/rm-32.png" /> ' . t('Post to Red') . '</div>';
	}
}

function redred_settings_post ($a,$post) {
	if(! local_channel())
		return;
	// don't check redred settings if redred submit button is not clicked
	if (! x($_POST,'redred-submit'))
		return;

	$channel = App::get_channel();
	// Don't let somebody post to their self channel. Since we aren't passing message-id this would be very very bad.

	if(! trim($_POST['redred_channel'])) {
		notice( t('Channel is required.') . EOL);
		return;
	}

	if($channel['channel_address'] === trim($_POST['redred_channel'])) {
		notice( t('Invalid channel.') . EOL);
		return;
	}

 	
	set_pconfig(local_channel(), 'redred', 'baseapi',         trim($_POST['redred_baseapi']));
	set_pconfig(local_channel(), 'redred', 'username',        trim($_POST['redred_username']));
	set_pconfig(local_channel(), 'redred', 'password',        z_obscure(trim($_POST['redred_password'])));
	set_pconfig(local_channel(), 'redred', 'channel',         trim($_POST['redred_channel']));
	set_pconfig(local_channel(), 'redred', 'post',            intval($_POST['redred_enable']));
	set_pconfig(local_channel(), 'redred', 'post_by_default', intval($_POST['redred_default']));
        info( t('redred Settings saved.') . EOL);

}

function redred_settings(&$a,&$s) {
	if(! local_channel())
		return;
	//head_add_css('/addon/redred/redred.css');

	$api     = get_pconfig(local_channel(), 'redred', 'baseapi');
	$username    = get_pconfig(local_channel(), 'redred', 'username' );
	$password = z_unobscure(get_pconfig(local_channel(), 'redred', 'password' ));
	$channel = get_pconfig(local_channel(), 'redred', 'channel' );
	$enabled = get_pconfig(local_channel(), 'redred', 'post');
	$checked = (($enabled) ? 1 : false);
	$defenabled = get_pconfig(local_channel(),'redred','post_by_default');
	$defchecked = (($defenabled) ? 1 : false);

	$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
		'$field'	=> array('redred_enable', t('Allow posting to another Hubzilla Channel'), $checked, '', array(t('No'),t('Yes'))),
	));

	$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
		'$field'	=> array('redred_default', t('Send public postings to Hubzilla channel by default'), $defchecked, '', array(t('No'),t('Yes'))),
	));

	$sc .= replace_macros(get_markup_template('field_input.tpl'), array(
		'$field'	=> array('redred_baseapi', t('Hubzilla API Path'), $api, t('https://{sitename}/api'))
	));

	$sc .= replace_macros(get_markup_template('field_input.tpl'), array(
		'$field'	=> array('redred_username', t('Hubzilla login name'), $username, t('Email'))
	));

	$sc .= replace_macros(get_markup_template('field_input.tpl'), array(
		'$field'	=> array('redred_channel', t('Hubzilla channel name'), $channel, t('Nickname'))
	));

	$sc .= replace_macros(get_markup_template('field_password.tpl'), array(
		'$field'	=> array('redred_password', t('Hubzilla password'), $password, '')
	));

	$s .= replace_macros(get_markup_template('generic_addon_settings.tpl'), array(
		'$addon' 	=> array('redred', '<img src="images/hz-32.png" style="width:auto; height:1em; margin:-3px 5px 0px 0px;">' . t('Hubzilla Crosspost Settings'), '', t('Submit')),
		'$content'	=> $sc
	));

}


function redred_post_local(&$a,&$b) {
	if($b['created'] != $b['edited'])
		return;

	if(! perm_is_allowed($b['uid'],'','view_stream'))
		return;

	if((local_channel()) && (local_channel() == $b['uid']) && (! $b['item_private'])) {

		$redred_post = get_pconfig(local_channel(),'redred','post');
		$redred_enable = (($redred_post && x($_REQUEST,'redred_enable')) ? intval($_REQUEST['redred_enable']) : 0);

		// if API is used, default to the chosen settings
		if($_REQUEST['api_source'] && intval(get_pconfig(local_channel(),'redred','post_by_default')))
			$redred_enable = 1;

       if(! $redred_enable)
            return;

       if(strlen($b['postopts']))
           $b['postopts'] .= ',';
       $b['postopts'] .= 'redred';
    }
}


function redred_post_hook(&$a,&$b) {

	/**
	 * Post to Red
	 */

	// for now, just top level posts.

	if($b['mid'] != $b['parent_mid'])
		return;

	if((! is_item_normal($b)) || $b['item_private'] || ($b['created'] !== $b['edited']))
		return;


	if(! perm_is_allowed($b['uid'],'','view_stream'))
		return;


	if(! strstr($b['postopts'],'redred'))
		return;

	logger('Red-to-Red post invoked');

	load_pconfig($b['uid'], 'redred');

	
	$api      = get_pconfig($b['uid'], 'redred', 'baseapi');
	if(substr($api,-1,1) != '/')
		$api .= '/';
	$username = get_pconfig($b['uid'], 'redred', 'username');
	$password = z_unobscure(get_pconfig($b['uid'], 'redred', 'password'));
	$channel  = get_pconfig($b['uid'], 'redred', 'channel');

	$msg = $b['body'];

	$postdata = array('status' => $b['body'], 'title' => $b['title'], 'channel' => $channel);

	if(strlen($b['body'])) {
		$ret = z_post_url($api . 'statuses/update', $postdata, 0, array('http_auth' => $username . ':' . $password));
		if($ret['success'])
			logger('redred: returns: ' . print_r($ret['body'],true));
		else
			logger('redred: z_post_url failed: ' . print_r($ret['debug'],true));
	}
}

