<?php

/**
 * Name: Hubzilla-to-Friendica Connector (rtof)
 * Description: Relay public postings to a connected Friendica account
 * Version: 1.0
 * Maintainer: none
 */
 
/*
 *   Red to Friendica 
 */

require_once('include/permissions.php');

function rtof_load() {
	//  we need some hooks, for the configuration and for sending tweets
	register_hook('feature_settings', 'addon/rtof/rtof.php', 'rtof_settings'); 
	register_hook('feature_settings_post', 'addon/rtof/rtof.php', 'rtof_settings_post');
	register_hook('notifier_normal', 'addon/rtof/rtof.php', 'rtof_post_hook');
	register_hook('post_local', 'addon/rtof/rtof.php', 'rtof_post_local');
	register_hook('jot_networks',    'addon/rtof/rtof.php', 'rtof_jot_nets');
	logger("loaded rtof");
}


function rtof_unload() {
	unregister_hook('feature_settings', 'addon/rtof/rtof.php', 'rtof_settings'); 
	unregister_hook('feature_settings_post', 'addon/rtof/rtof.php', 'rtof_settings_post');
	unregister_hook('notifier_normal', 'addon/rtof/rtof.php', 'rtof_post_hook');
	unregister_hook('post_local', 'addon/rtof/rtof.php', 'rtof_post_local');
	unregister_hook('jot_networks',    'addon/rtof/rtof.php', 'rtof_jot_nets');

}

function rtof_jot_nets(&$a,&$b) {
    if((! local_channel()) || (! perm_is_allowed(local_channel(),'','view_stream')))
        return;

	$rtof_post = get_pconfig(local_channel(),'rtof','post');
	if(intval($rtof_post) == 1) {
		$rtof_defpost = get_pconfig(local_channel(),'rtof','post_by_default');
		$selected = ((intval($rtof_defpost) == 1) ? ' checked="checked" ' : '');
		$b .= '<div class="profile-jot-net"><input type="checkbox" name="rtof_enable"' . $selected . ' value="1" /> ' 
			. '<img src="addon/rtof/friendica.png" title="' . t('Post to Friendica') . '" />' . '</div>';

	}
}

function rtof_settings_post ($a,$post) {
	if(! local_channel())
		return;
	// don't check rtof settings if rtof submit button is not clicked
	if (! x($_POST,'rtof-submit'))
		return;
	
	set_pconfig(local_channel(), 'rtof', 'baseapi',         trim($_POST['rtof_baseapi']));
	set_pconfig(local_channel(), 'rtof', 'username',        trim($_POST['rtof_username']));
	set_pconfig(local_channel(), 'rtof', 'password',        z_obscure(trim($_POST['rtof_password'])));
	set_pconfig(local_channel(), 'rtof', 'post',            intval($_POST['rtof_enable']));
	set_pconfig(local_channel(), 'rtof', 'post_by_default', intval($_POST['rtof_default']));
        info( t('rtof Settings saved.') . EOL);

}

function rtof_settings(&$a,&$s) {
	if(! local_channel())
		return;
	//head_add_css('/addon/rtof/rtof.css');

	$api     = get_pconfig(local_channel(), 'rtof', 'baseapi');
	$username    = get_pconfig(local_channel(), 'rtof', 'username' );
	$password = z_unobscure(get_pconfig(local_channel(), 'rtof', 'password' ));
	$enabled = get_pconfig(local_channel(), 'rtof', 'post');
	$checked = (($enabled) ? 1 : false);
	$defenabled = get_pconfig(local_channel(),'rtof','post_by_default');
	$defchecked = (($defenabled) ? 1 : false);


	$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
		'$field'	=> array('rtof_enable', t('Allow posting to Friendica'), $checked, '', array(t('No'),t('Yes'))),
	));

	$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
		'$field'	=> array('rtof_default', t('Send public postings to Friendica by default'), $defchecked, '', array(t('No'),t('Yes'))),
	));

	$sc .= replace_macros(get_markup_template('field_input.tpl'), array(
		'$field'	=> array('rtof_baseapi', t('Friendica API Path'), $api, t('https://{sitename}/api'))
	));

	$sc .= replace_macros(get_markup_template('field_input.tpl'), array(
		'$field'	=> array('rtof_username', t('Friendica login name'), $username, t('Email'))
	));

	$sc .= replace_macros(get_markup_template('field_password.tpl'), array(
		'$field'	=> array('rtof_password', t('Friendica password'), $password, '')
	));

	$s .= replace_macros(get_markup_template('generic_addon_settings.tpl'), array(
		'$addon' 	=> array('rtof', '<img src="addon/rtof/friendica.png" style="width:auto; height:1em; margin:-3px 5px 0px 0px;">' . t('Hubzilla to Friendica Post Settings'), '', t('Submit')),
		'$content'	=> $sc
	));
}


function rtof_post_local(&$a,&$b) {
	if($b['created'] != $b['edited'])
		return;

	if(! perm_is_allowed($b['uid'],'','view_stream'))
		return;

	if((local_channel()) && (local_channel() == $b['uid']) && (! $b['item_private'])) {

		$rtof_post = get_pconfig(local_channel(),'rtof','post');
		$rtof_enable = (($rtof_post && x($_REQUEST,'rtof_enable')) ? intval($_REQUEST['rtof_enable']) : 0);

		// if API is used, default to the chosen settings
		if($_REQUEST['api_source'] && intval(get_pconfig(local_channel(),'rtof','post_by_default')))
			$rtof_enable = 1;

       if(! $rtof_enable)
            return;

       if(strlen($b['postopts']))
           $b['postopts'] .= ',';
       $b['postopts'] .= 'rtof';
    }
}


function rtof_post_hook(&$a,&$b) {

	/**
	 * Post to Friendica
	 */

	// for now, just top level posts.

	if($b['mid'] != $b['parent_mid'])
		return;

	if((! is_item_normal($b)) || $b['item_private'] || ($b['created'] !== $b['edited']))
		return;


	if(! perm_is_allowed($b['uid'],'','view_stream'))
		return;


	if(! strstr($b['postopts'],'rtof'))
		return;

	logger('Hubzilla-to-Friendica post invoked');

	load_pconfig($b['uid'], 'rtof');

	
	$api      = get_pconfig($b['uid'], 'rtof', 'baseapi');
	if(substr($api,-1,1) != '/')
		$api .= '/';
	$username = get_pconfig($b['uid'], 'rtof', 'username');
	$password = z_unobscure(get_pconfig($b['uid'], 'rtof', 'password'));

	$msg = $b['body'];

	$postdata = array('status' => $b['body'], 'title' => $b['title'], 'message_id' => $b['mid'], 'source' => 'Hubzilla');

	if(strlen($b['body'])) {
		$ret = z_post_url($api . 'statuses/update', $postdata, 0, array('http_auth' => $username . ':' . $password, 'novalidate' => 1));
		if($ret['success'])
			logger('rtof: returns: ' . print_r($ret['body'],true));
		else
			logger('rtof: z_post_url failed: ' . print_r($ret['debug'],true));
	}
}

