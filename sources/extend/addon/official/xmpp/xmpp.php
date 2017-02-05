<?php
/**
 * Name: XMPP (Jabber)
 * Description: Embedded XMPP (Jabber) client
 * Version: 0.1
 * Author: Michael Vogel <https://pirati.ca/profile/heluecht>
 */

function xmpp_load() {
	register_hook('feature_settings', 'addon/xmpp/xmpp.php', 'xmpp_plugin_settings');
	register_hook('feature_settings_post', 'addon/xmpp/xmpp.php', 'xmpp_plugin_settings_post');
	register_hook('page_end', 'addon/xmpp/xmpp.php', 'xmpp_script');
	register_hook('change_channel', 'addon/xmpp/xmpp.php', 'xmpp_login');
}

function xmpp_unload() {
	unregister_hook('feature_settings', 'addon/xmpp/xmpp.php', 'xmpp_plugin_settings');
	unregister_hook('feature_settings_post', 'addon/xmpp/xmpp.php', 'xmpp_plugin_settings_post');
	unregister_hook('page_end', 'addon/xmpp/xmpp.php', 'xmpp_script');
	unregister_hook('logged_in', 'addon/xmpp/xmpp.php', 'xmpp_login');
	unregister_hook('change_channel', 'addon/xmpp/xmpp.php', 'xmpp_login');
}

function xmpp_plugin_settings_post($a,$post) {
	if(! local_channel() || (! $_POST['xmpp-submit']))
		return;
	set_pconfig(local_channel(),'xmpp','enabled',intval($_POST['xmpp_enabled']));
	set_pconfig(local_channel(),'xmpp','individual',intval($_POST['xmpp_individual']));
	set_pconfig(local_channel(),'xmpp','bosh_proxy',$_POST['xmpp_bosh_proxy']);

	info( t('XMPP settings updated.') . EOL);
}

function xmpp_plugin_settings(&$a,&$s) {

	if(! local_channel())
		return;


	/* Get the current state of our config variable */

	$enabled = intval(get_pconfig(local_channel(),'xmpp','enabled'));
	$enabled_checked = (($enabled) ? ' checked="checked" ' : '');

	$individual = intval(get_pconfig(local_channel(),'xmpp','individual'));
	$individual_checked = (($individual) ? ' checked="checked" ' : '');

	$bosh_proxy = get_pconfig(local_channel(),"xmpp","bosh_proxy");


	$sc = '';
	$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
        '$field'    => array('xmpp_enabled', t('Enable Chat'), $enabled, '', array(t('No'),t('Yes'))),
    ));

	if(get_config("xmpp", "central_userbase")) {
		$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
			'$field'    => array('xmpp_individual', t('Individual credentials'), $individual, '')
		));
	}

	if((! get_config("xmpp", "central_userbase")) || (get_pconfig(local_channel(),"xmpp","individual"))) {
		$sc .= replace_macros(get_markup_template('field_input.tpl'), array(
			'$field'    => array('xmpp_bosh_proxy', t('Jabber BOSH server'), $bosh_proxy, '')
		));
	}

	$s .= replace_macros(get_markup_template('generic_addon_settings.tpl'), array(
        '$addon'    => array('xmpp', '<img src="addon/xmpp/xmpp.png" style="width:auto; height:1em; margin:-3px 5px 0px 0px;">' . t('XMPP Settings'), '', t('Submit')),
        '$content'  => $sc
    ));


}

function xmpp_login($a,$b) {

	if(! local_channel())
		return;

	if (! $_SESSION['allow_api']) {
		$password = substr(random_string(16));
		set_pconfig(local_channel(), "xmpp", "password", $password);
	}
}

function xmpp_plugin_admin(&$a, &$o){
	$t = get_markup_template("admin.tpl", "addon/xmpp/");

	$o = replace_macros($t, array(
		'$submit' => t('Save Settings'),
		'$bosh_proxy'       => array('bosh_proxy', t('Jabber BOSH host'),            get_config('xmpp', 'bosh_proxy'), ''),
		'$central_userbase' => array('central_userbase', t('Use central userbase'), get_config('xmpp', 'central_userbase'), t('If enabled, members will automatically login to an ejabberd server that has to be installed on this machine with synchronized credentials via the "auth_ejabberd.php" script.')),
	));
}

function xmpp_plugin_admin_post(&$a){
	$bosh_proxy       = ((x($_POST,'bosh_proxy')) ?       trim($_POST['bosh_proxy']) : '');
	$central_userbase = ((x($_POST,'central_userbase')) ? intval($_POST['central_userbase']) : false);
	set_config('xmpp','bosh_proxy',$bosh_proxy);
	set_config('xmpp','central_userbase',$central_userbase);
	info( t('Settings updated.'). EOL );
}

function xmpp_script(&$a,&$s) {
	xmpp_converse($a,$s);
}

function xmpp_converse(&$a,&$s) {
	if (!local_channel())
		return;

	if ($_GET["mode"] == "minimal")
		return;

	if (App::$is_mobile || App::$is_tablet)
		return;

	if (!get_pconfig(local_channel(),"xmpp","enabled"))
		return;

	App::$page['htmlhead'] .= '<link type="text/css" rel="stylesheet" media="screen" href="addon/xmpp/converse/css/converse.css" />'."\n";
	App::$page['htmlhead'] .= '<script src="addon/xmpp/converse/builds/converse.min.js"></script>'."\n";

	if (get_config("xmpp", "central_userbase") && !get_pconfig(local_channel(),"xmpp","individual")) {
		$bosh_proxy = get_config("xmpp", "bosh_proxy");

		$password = get_pconfig(local_channel(), "xmpp", "password");

		if ($password == "") {
			$password = substr(random_string(),0,16);
			set_pconfig(local_channel(), "xmpp", "password", $password);
		}
		$channel = App::get_channel();

		$jid = $channel["channel_address"]."@".App::get_hostname()."/converse-".substr(random_string(),0,5);;

		$auto_login = "auto_login: true,
			authentication: 'login',
			jid: '$jid',
			password: '$password',
			allow_logout: false,";
	} else {
		$bosh_proxy = get_pconfig(local_channel(), "xmpp", "bosh_proxy");

		$auto_login = "";
	}

	if ($bosh_proxy == "")
		return;

	if (in_array(argv(0), array("manage", "logout")))
		$additional_commands = "converse.user.logout();\n";
	else
		$additional_commands = "";

	$on_ready = "";

	$initialize = "converse.initialize({
					bosh_service_url: '$bosh_proxy',
					keepalive: true,
					message_carbons: false,
					forward_messages: false,
					play_sounds: true,
					sounds_path: 'addon/xmpp/converse/sounds/',
					roster_groups: false,
					show_controlbox_by_default: false,
					show_toolbar: true,
					allow_contact_removal: false,
					allow_registration: false,
					hide_offline_users: true,
					allow_chat_pending_contacts: false,
					allow_dragresize: true,
					auto_away: 0,
					auto_xa: 0,
					csi_waiting_time: 300,
					auto_reconnect: true,
					$auto_login
					xhr_user_search: false
				});\n";

	App::$page['htmlhead'] .= "<script>
					require(['converse'], function (converse) {
						$initialize
						converse.listen.on('ready', function (event) {
							$on_ready
						});
						$additional_commands
					});
				</script>";
}

