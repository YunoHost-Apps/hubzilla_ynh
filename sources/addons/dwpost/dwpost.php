<?php

/**
 * Name: Dreamwidth Post feature
 * Description: Post to dreamwidth
 * Version: 1.0
 * Author: Tony Baldwin <https://red.free-haven.org/channel/tony>
 * Author: Michael Johnston
 * Author: Cat Gray <https://free-haven.org/profile/catness>
 * Maintainer: none
 */

require_once('include/permissions.php');

function dwpost_load() {
    register_hook('post_local',           'addon/dwpost/dwpost.php', 'dwpost_post_local');
    register_hook('notifier_normal',      'addon/dwpost/dwpost.php', 'dwpost_send');
    register_hook('jot_networks',         'addon/dwpost/dwpost.php', 'dwpost_jot_nets');
    register_hook('feature_settings',      'addon/dwpost/dwpost.php', 'dwpost_settings');
    register_hook('feature_settings_post', 'addon/dwpost/dwpost.php', 'dwpost_settings_post');

}
function dwpost_unload() {
    unregister_hook('post_local',       'addon/dwpost/dwpost.php', 'dwpost_post_local');
    unregister_hook('notifier_normal',  'addon/dwpost/dwpost.php', 'dwpost_send');
    unregister_hook('jot_networks',     'addon/dwpost/dwpost.php', 'dwpost_jot_nets');
    unregister_hook('feature_settings',      'addon/dwpost/dwpost.php', 'dwpost_settings');
    unregister_hook('feature_settings_post', 'addon/dwpost/dwpost.php', 'dwpost_settings_post');

}


function dwpost_jot_nets(&$a,&$b) {
    if((! local_channel()) || (! perm_is_allowed(local_channel(),'','view_stream')))
        return;

    $dw_post = get_pconfig(local_channel(),'dwpost','post');
    if(intval($dw_post) == 1) {
        $dw_defpost = get_pconfig(local_channel(),'dwpost','post_by_default');
        $selected = ((intval($dw_defpost) == 1) ? ' checked="checked" ' : '');
        $b .= '<div class="profile-jot-net"><input type="checkbox" name="dwpost_enable" ' . $selected . ' value="1" /> '
            . t('Post to Dreamwidth') . '</div>';
    }
}


function dwpost_settings(&$a,&$s) {

	if(! local_channel())
		return;

	/* Add our stylesheet to the page so we can make our settings look nice */

	//App::$page['htmlhead'] .= '<link rel="stylesheet"  type="text/css" href="' . z_root() . '/addon/dwpost/dwpost.css' . '" media="all" />' . "\r\n";

	/* Get the current state of our config variables */

	$enabled = get_pconfig(local_channel(),'dwpost','post');

	$checked = (($enabled) ? 1 : false);

	$def_enabled = get_pconfig(local_channel(),'dwpost','post_by_default');

	$def_checked = (($def_enabled) ? 1 : false);

	$dw_username = get_pconfig(local_channel(), 'dwpost', 'dw_username');
	$dw_password = z_unobscure(get_pconfig(local_channel(), 'dwpost', 'dw_password'));


	/* Add some HTML to the existing form */

	$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
		'$field'	=> array('dwpost', t('Enable Dreamwidth Post Plugin'), $checked, '', array(t('No'),t('Yes'))),
	));

	$sc .= replace_macros(get_markup_template('field_input.tpl'), array(
		'$field'	=> array('dw_username', t('Dreamwidth username'), $dw_username, '')
	));

	$sc .= replace_macros(get_markup_template('field_password.tpl'), array(
		'$field'	=> array('dw_password', t('Dreamwidth password'), $dw_password, '')
	));

	$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
		'$field'	=> array('dw_bydefault', t('Post to Dreamwidth by default'), $def_checked, '', array(t('No'),t('Yes'))),
	));

	$s .= replace_macros(get_markup_template('generic_addon_settings.tpl'), array(
		'$addon' 	=> array('dwpost',t('Dreamwidth Post Settings'), '', t('Submit')),
		'$content'	=> $sc
	));

}


function dwpost_settings_post(&$a,&$b) {

	if(x($_POST,'dwpost-submit')) {

		set_pconfig(local_channel(),'dwpost','post',intval($_POST['dwpost']));
		set_pconfig(local_channel(),'dwpost','post_by_default',intval($_POST['dw_bydefault']));
		set_pconfig(local_channel(),'dwpost','dw_username',trim($_POST['dw_username']));
		set_pconfig(local_channel(),'dwpost','dw_password',z_obscure(trim($_POST['dw_password'])));

	}

}

function dwpost_post_local(&$a,&$b) {

	// This can probably be changed to allow editing by pointing to a different API endpoint

	if($b['edit'])
		return;

	if((! local_channel()) || (local_channel() != $b['uid']))
		return;

	if($b['item_private'] || $b['parent'])
		return;

	logger('Dreamwidth xpost invoked');

    $dw_post   = intval(get_pconfig(local_channel(),'dwpost','post'));

	$dw_enable = (($dw_post && x($_REQUEST,'dwpost_enable')) ? intval($_REQUEST['dwpost_enable']) : 0);

	if($_REQUEST['api_source'] && intval(get_pconfig(local_channel(),'dwpost','post_by_default')))
		$dw_enable = 1;

    if(! $dw_enable)
       return;

    if(strlen($b['postopts']))
       $b['postopts'] .= ',';
     $b['postopts'] .= 'dwpost';
}




function dwpost_send(&$a,&$b) {

    if((! is_item_normal($b)) || $b['item_private'] || ($b['created'] !== $b['edited']))
        return;

	if(! perm_is_allowed($b['uid'],'','view_stream'))
		return;

    if(! strstr($b['postopts'],'dwpost'))
        return;

    if($b['parent'] != $b['id'])
        return;

	// dreamwidth post in the LJ user's timezone. 
	// Hopefully the person's Friendica account
	// will be set to the same thing.

	$tz = 'UTC';

	$x = q("select channel_timezone from channel where channel_id = %d limit 1",
		intval($b['uid'])
	);
	if($x && strlen($x[0]['channel_timezone']))
		$tz = $x[0]['channel_timezone'];	

	$dw_username = get_pconfig($b['uid'],'dwpost','dw_username');
	$dw_password = z_unobscure(get_pconfig($b['uid'],'dwpost','dw_password'));
	$dw_blog = 'http://www.dreamwidth.org/interface/xmlrpc';

	if($dw_username && $dw_password && $dw_blog) {

		require_once('include/bbcode.php');
		require_once('include/datetime.php');

		$title = $b['title'];
		$post = bbcode($b['body']);
		$post = xmlify($post);
		$tags = dwpost_get_tags($b['tag']);

		$date = datetime_convert('UTC',$tz,$b['created'],'Y-m-d H:i:s');
		$year = intval(substr($date,0,4));
		$mon  = intval(substr($date,5,2));
		$day  = intval(substr($date,8,2));
		$hour = intval(substr($date,11,2));
		$min  = intval(substr($date,14,2));

		$xml = <<< EOT
<?xml version="1.0" encoding="utf-8"?>
<methodCall><methodName>LJ.XMLRPC.postevent</methodName>
<params><param>
<value><struct>
<member><name>year</name><value><int>$year</int></value></member>
<member><name>mon</name><value><int>$mon</int></value></member>
<member><name>day</name><value><int>$day</int></value></member>
<member><name>hour</name><value><int>$hour</int></value></member>
<member><name>min</name><value><int>$min</int></value></member>
<member><name>event</name><value><string>$post</string></value></member>
<member><name>username</name><value><string>$dw_username</string></value></member>
<member><name>password</name><value><string>$dw_password</string></value></member>
<member><name>subject</name><value><string>$title</string></value></member>
<member><name>lineendings</name><value><string>unix</string></value></member>
<member><name>ver</name><value><int>1</int></value></member>
<member><name>props</name>
<value><struct>
<member><name>useragent</name><value><string>Friendica</string></value></member>
<member><name>taglist</name><value><string>$tags</string></value></member>
</struct></value></member>
</struct></value>
</param></params>
</methodCall>

EOT;

		logger('dwpost: data: ' . $xml, LOGGER_DATA);

		if($dw_blog !== 'test')
			$x = z_post_url($dw_blog,$xml,array('headers' => array("Content-Type: text/xml")));
		logger('posted to dreamwidth: ' . print_r($x,true), LOGGER_DEBUG);

	}
}

function dwpost_get_tags($post)
{
	preg_match_all("/\]([^\[#]+)\[/",$post,$matches);
	$tags = implode(', ',$matches[1]);
	return $tags;
}
