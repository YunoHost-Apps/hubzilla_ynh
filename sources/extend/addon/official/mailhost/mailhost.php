<?php

/**
 * Name: mailhost
 * Description: Select one server to send email notifications when you have multiple clones
 * Version: 1.0
 * Author: Mike Macgirvin <mike@zothub.com>
 * Maintainer: Mike Macgirvin <mike@macgirvin.com>
 */

function mailhost_install() {
	register_hook('feature_settings', 'addon/mailhost/mailhost.php', 'mailhost_addon_settings');
	register_hook('feature_settings_post', 'addon/mailhost/mailhost.php', 'mailhost_addon_settings_post');
}


function mailhost_uninstall() {
	unregister_hook('feature_settings', 'addon/mailhost/mailhost.php', 'mailhost_addon_settings');
	unregister_hook('feature_settings_post', 'addon/mailhost/mailhost.php', 'mailhost_addon_settings_post');
}

function mailhost_addon_settings(&$a,&$s) {

	if(! local_channel())
		return;

	/* Add our stylesheet to the page so we can make our settings look nice */

	//head_add_css('/addon/mailhost/mailhost.css');

	$mailhost = get_pconfig(local_channel(),'system','email_notify_host');
	if(! $mailhost)
		$mailhost = App::get_hostname();

	$sc .= replace_macros(get_markup_template('field_input.tpl'), array(
		'$field'	=> array('mailhost-mailhost', t('Email notification hub'), $mailhost, t('Hostname'))
	));

	$s .= replace_macros(get_markup_template('generic_addon_settings.tpl'), array(
		'$addon' 	=> array('mailhost',t('Mailhost Settings'), '', t('Submit')),
		'$content'	=> $sc
	));

	return;
}

function mailhost_addon_settings_post(&$a,&$b) {

	if(! local_channel())
		return;

	if($_POST['mailhost-submit']) {
		set_pconfig(local_channel(),'system','email_notify_host',trim($_POST['mailhost-mailhost']));
		info( t('MAILHOST Settings saved.') . EOL);
	}
}

