<?php


/**
 * Name: GNU-Social Protocol
 * Description: GNU-Social Protocol (Experimental, Not-finished, Unsupported)
 * Version: 1.0
 * Author: Mike Macgirvin
 * Maintainer: none
 * Requires: pubsubhubbub
 */


require_once('include/crypto.php');
require_once('include/items.php');
require_once('include/bb2diaspora.php');
require_once('include/contact_selectors.php');
require_once('include/queue_fn.php');
require_once('include/salmon.php');


function gnusoc_load() {
	register_hook('module_loaded', 'addon/gnusoc/gnusoc.php', 'gnusoc_load_module');
	register_hook('webfinger', 'addon/gnusoc/gnusoc.php', 'gnusoc_webfinger');
	register_hook('personal_xrd', 'addon/gnusoc/gnusoc.php', 'gnusoc_personal_xrd');
	register_hook('follow_allow', 'addon/gnusoc/gnusoc.php', 'gnusoc_follow_allow');
	register_hook('feature_settings_post', 'addon/gnusoc/gnusoc.php', 'gnusoc_feature_settings_post');
	register_hook('feature_settings', 'addon/gnusoc/gnusoc.php', 'gnusoc_feature_settings');


//	register_hook('notifier_hub', 'addon/gnusoc/gnusoc.php', 'gnusoc_process_outbound');
//	register_hook('permissions_create', 'addon/gnusoc/gnusoc.php', 'gnusoc_permissions_create');
//	register_hook('permissions_update', 'addon/gnusoc/gnusoc.php', 'gnusoc_permissions_update');

}

function gnusoc_unload() {
	unregister_hook('module_loaded', 'addon/gnusoc/gnusoc.php', 'gnusoc_load_module');
	unregister_hook('webfinger', 'addon/gnusoc/gnusoc.php', 'gnusoc_webfinger');
	unregister_hook('personal_xrd', 'addon/gnusoc/gnusoc.php', 'gnusoc_personal_xrd');
	unregister_hook('follow_allow', 'addon/gnusoc/gnusoc.php', 'gnusoc_follow_allow');
	unregister_hook('feature_settings_post', 'addon/gnusoc/gnusoc.php', 'gnusoc_feature_settings_post');
	unregister_hook('feature_settings', 'addon/gnusoc/gnusoc.php', 'gnusoc_feature_settings');

}

// @fixme - subscribe to hub(s) on follow


function gnusoc_load_module(&$a, &$b) {
	if($b['module'] === 'salmon') {
		require_once('addon/gnusoc/salmon.php');
		$b['installed'] = true;
	}
}



function gnusoc_webfinger(&$a,&$b) {
	$b['result']['links'][] = array('rel' => 'salmon', 'href' => z_root() . '/salmon/' . $b['channel']['channel_address']);
}

function gnusoc_personal_xrd(&$a,&$b) {
	$b['xml'] = str_replace('</XRD>',
		'<Link rel="salmon" href="' . z_root() . '/salmon/' . $b['user']['channel_address'] . '" />' . "\r\n" . '</XRD>', $b['xml']);

}


function gnusoc_follow_allow(&$a, &$b) {

	if($b['xchan']['xchan_network'] !== 'gnusoc')
		return;

	$allowed = get_pconfig($b['channel_id'],'system','gnusoc_allowed');
	if($allowed === false)
		$allowed = 1;
	$b['allowed'] = $allowed;
	$b['singleton'] = 1;  // this network does not support channel clones
}




function gnusoc_feature_settings_post(&$a,&$b) {

	if($_POST['gnusoc-submit']) {
		set_pconfig(local_channel(),'system','gnusoc_allowed',intval($_POST['gnusoc_allowed']));
		info( t('GNU-Social Protocol Settings updated.') . EOL);
	}
}


function gnusoc_feature_settings(&$a,&$s) {
	$gnusoc_allowed = get_pconfig(local_channel(),'system','gnusoc_allowed');
	if($gnusoc_allowed === false)
		$gnus_allowed = get_config('gnusoc','allowed');	

	$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
		'$field'	=> array('gnusoc_allowed', t('Enable the (experimental) GNU-Social protocol for this channel'), $gnusoc_allowed, '', $yes_no),
	));

	$s .= replace_macros(get_markup_template('generic_addon_settings.tpl'), array(
		'$addon' 	=> array('gnusoc', '<img src="addon/gnusoc/gnusoc-32.png" style="width:auto; height:1em; margin:-3px 5px 0px 0px;">' . t('GNU-Social Protocol Settings'), '', t('Submit')),
		'$content'	=> $sc
	));

	return;

}

