<?php

/**
 * Name: LiveJournal Post Connector
 * Description: Post to LiveJournal
 * Version: 1.0
 * Author: Tony Baldwin <https://red.free-haven.org/channel/tony>
 * Author: Michael Johnston
 * Author: Cat Gray <https://free-haven.org/profile/catness>
 * Maintainer: none
 */

require_once('include/permissions.php');

function ljpost_load() {
    register_hook('post_local',           'addon/ljpost/ljpost.php', 'ljpost_post_local');
    register_hook('notifier_normal',      'addon/ljpost/ljpost.php', 'ljpost_send');
    register_hook('jot_networks',         'addon/ljpost/ljpost.php', 'ljpost_jot_nets');
    register_hook('feature_settings',      'addon/ljpost/ljpost.php', 'ljpost_settings');
    register_hook('feature_settings_post', 'addon/ljpost/ljpost.php', 'ljpost_settings_post');

}
function ljpost_unload() {
    unregister_hook('post_local',       'addon/ljpost/ljpost.php', 'ljpost_post_local');
    unregister_hook('notifier_normal',  'addon/ljpost/ljpost.php', 'ljpost_send');
    unregister_hook('jot_networks',     'addon/ljpost/ljpost.php', 'ljpost_jot_nets');
    unregister_hook('feature_settings',      'addon/ljpost/ljpost.php', 'ljpost_settings');
    unregister_hook('feature_settings_post', 'addon/ljpost/ljpost.php', 'ljpost_settings_post');

}


function ljpost_jot_nets(&$a,&$b) {
    if((! local_channel()) || (! perm_is_allowed(local_channel(),'','view_stream')))
        return;

    $lj_post = get_pconfig(local_channel(),'ljpost','post');
    if(intval($lj_post) == 1) {
        $lj_defpost = get_pconfig(local_channel(),'ljpost','post_by_default');
        $selected = ((intval($lj_defpost) == 1) ? ' checked="checked" ' : '');
        $b .= '<div class="profile-jot-net"><input type="checkbox" name="ljpost_enable" ' . $selected . ' value="1" /> '
            . t('Post to LiveJournal') . '</div>';
    }
}


function ljpost_settings(&$a,&$s) {

	if(! local_channel())
		return;

	/* Add our stylesheet to the page so we can make our settings look nice */

	//App::$page['htmlhead'] .= '<link rel="stylesheet"  type="text/css" href="' . z_root() . '/addon/ljpost/ljpost.css' . '" media="all" />' . "\r\n";

	/* Get the current state of our config variables */

	$enabled = get_pconfig(local_channel(),'ljpost','post');

	$checked = (($enabled) ? 1 : false);

	$def_enabled = get_pconfig(local_channel(),'ljpost','post_by_default');

	$def_checked = (($def_enabled) ? 1 : false);

	$lj_username = get_pconfig(local_channel(), 'ljpost', 'lj_username');
	$lj_password = z_unobscure(get_pconfig(local_channel(), 'ljpost', 'lj_password'));

	$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
		'$field'	=> array('ljpost', t('Enable LiveJournal Post Plugin'), $checked, '', array(t('No'),t('Yes'))),
	));

	$sc .= replace_macros(get_markup_template('field_input.tpl'), array(
		'$field'	=> array('lj_username', t('LiveJournal username'), $lj_username, '')
	));

	$sc .= replace_macros(get_markup_template('field_password.tpl'), array(
		'$field'	=> array('lj_password', t('LiveJournal password'), $lj_password, '')
	));

	$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
		'$field'	=> array('lj_bydefault', t('Post to LiveJournal by default'), $def_checked, '', array(t('No'),t('Yes'))),
	));

	$s .= replace_macros(get_markup_template('generic_addon_settings.tpl'), array(
		'$addon' 	=> array('ljpost',t('LiveJournal Post Settings'), '', t('Submit')),
		'$content'	=> $sc
	));

}


function ljpost_settings_post(&$a,&$b) {

	if(x($_POST,'ljpost-submit')) {

		set_pconfig(local_channel(),'ljpost','post',intval($_POST['ljpost']));
		set_pconfig(local_channel(),'ljpost','post_by_default',intval($_POST['lj_bydefault']));
		set_pconfig(local_channel(),'ljpost','lj_username',trim($_POST['lj_username']));
		set_pconfig(local_channel(),'ljpost','lj_password',z_obscure(trim($_POST['lj_password'])));
                info( t('LiveJournal Settings saved.') . EOL);
	}

}

function ljpost_post_local(&$a,&$b) {

	// This can probably be changed to allow editing by pointing to a different API endpoint

	if($b['edit'])
		return;

	if((! local_channel()) || (local_channel() != $b['uid']))
		return;

	if($b['item_private'] || $b['parent'])
		return;

    $lj_post   = intval(get_pconfig(local_channel(),'ljpost','post'));

	$lj_enable = (($lj_post && x($_REQUEST,'ljpost_enable')) ? intval($_REQUEST['ljpost_enable']) : 0);

	if($_REQUEST['api_source'] && intval(get_pconfig(local_channel(),'ljpost','post_by_default')))
		$lj_enable = 1;

    if(! $lj_enable)
       return;

    if(strlen($b['postopts']))
       $b['postopts'] .= ',';
     $b['postopts'] .= 'ljpost';
}




function ljpost_send(&$a,&$b) {

    if((! is_item_normal($b)) || $b['item_private'] || ($b['created'] !== $b['edited']))
        return;

	if(! perm_is_allowed($b['uid'],'','view_stream'))
		return;

    if(! strstr($b['postopts'],'ljpost'))
        return;

    if($b['parent'] != $b['id'])
        return;
	logger('Livejournal xpost invoked');
	// LiveJournal post in the LJ user's timezone. 
	// Hopefully the person's Friendica account
	// will be set to the same thing.

	$tz = 'UTC';

	$x = q("select channel_timezone from channel where channel_id = %d limit 1",
		intval($b['uid'])
	);
	if($x && strlen($x[0]['channel_timezone']))
		$tz = $x[0]['channel_timezone'];	

	$lj_username = xmlify(get_pconfig($b['uid'],'ljpost','lj_username'));
	$lj_password = xmlify(z_unobscure(get_pconfig($b['uid'],'ljpost','lj_password')));
	$lj_journal = xmlify(get_pconfig($b['uid'],'ljpost','lj_journal'));
//	if(! $lj_journal)
//		$lj_journal = $lj_username;

	$lj_blog = xmlify(get_pconfig($b['uid'],'ljpost','lj_blog'));
	if(! strlen($lj_blog))
		$lj_blog = xmlify('http://www.livejournal.com/interface/xmlrpc');

	if($lj_username && $lj_password && $lj_blog) {

		require_once('include/bbcode.php');
		require_once('include/datetime.php');

		$title = xmlify($b['title']);
		$post = bbcode($b['body']);
		$post = xmlify($post);
		$tags = ljpost_get_tags($b['tag']);

		$date = datetime_convert('UTC',$tz,$b['created'],'Y-m-d H:i:s');
		$year = intval(substr($date,0,4));
		$mon  = intval(substr($date,5,2));
		$day  = intval(substr($date,8,2));
		$hour = intval(substr($date,11,2));
		$min  = intval(substr($date,14,2));

		$xml = <<< EOT
<?xml version="1.0" encoding="utf-8"?>
<methodCall>
  <methodName>LJ.XMLRPC.postevent</methodName>
  <params>
    <param><value>
        <struct>
        <member><name>username</name><value><string>$lj_username</string></value></member>
        <member><name>password</name><value><string>$lj_password</string></value></member>
        <member><name>event</name><value><string>$post</string></value></member>
        <member><name>subject</name><value><string>$title</string></value></member>
        <member><name>lineendings</name><value><string>unix</string></value></member>
        <member><name>year</name><value><int>$year</int></value></member>
        <member><name>mon</name><value><int>$mon</int></value></member>
        <member><name>day</name><value><int>$day</int></value></member>
        <member><name>hour</name><value><int>$hour</int></value></member>
        <member><name>min</name><value><int>$min</int></value></member>
		<member><name>usejournal</name><value><string>$lj_username</string></value></member>
		<member>
			<name>props</name>
			<value>
				<struct>
					<member>
						<name>useragent</name>
						<value><string>Hubzilla</string></value>
					</member>
					<member>
						<name>taglist</name>
						<value><string>$tags</string></value>
					</member>
				</struct>
			</value>
		</member>
        </struct>
    </value></param>
  </params>
</methodCall>

EOT;

		logger('ljpost: data: ' . $xml, LOGGER_DATA);

		if($lj_blog !== 'test')
			$x = z_post_url($lj_blog,$xml,array('headers' => array("Content-Type: text/xml")));
		logger('posted to livejournal: ' . print_r($x,true), LOGGER_DEBUG);

	}
}

function ljpost_get_tags($post)
{
	preg_match_all("/\]([^\[#]+)\[/",$post,$matches);
	$tags = implode(', ',$matches[1]);
	return $tags;
}
