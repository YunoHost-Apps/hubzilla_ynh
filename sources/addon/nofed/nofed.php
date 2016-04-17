<?php

/**
 * Name: No Federation (nofed)
 * Description: Prevent posting from being federated to anybody. It will exist only on your channel page. 
 * Version: 1.0
 * Maintainer: none
 */
 
/*
 *   NoFed
 */

require_once('include/permissions.php');

function nofed_load() {
	register_hook('feature_settings', 'addon/nofed/nofed.php', 'nofed_settings'); 
	register_hook('feature_settings_post', 'addon/nofed/nofed.php', 'nofed_settings_post');
	register_hook('post_local', 'addon/nofed/nofed.php', 'nofed_post_local');
	register_hook('jot_networks',    'addon/nofed/nofed.php', 'nofed_jot_nets');
	logger("loaded nofed");
}


function nofed_unload() {
	unregister_hook('feature_settings', 'addon/nofed/nofed.php', 'nofed_settings'); 
	unregister_hook('feature_settings_post', 'addon/nofed/nofed.php', 'nofed_settings_post');
	unregister_hook('post_local', 'addon/nofed/nofed.php', 'nofed_post_local');
	unregister_hook('jot_networks',    'addon/nofed/nofed.php', 'nofed_jot_nets');

}

function nofed_jot_nets(&$a,&$b) {
    if(! local_channel()) 
        return;

	$nofed_post = get_pconfig(local_channel(),'nofed','post');
	if(intval($nofed_post) == 1) {
		$nofed_defpost = get_pconfig(local_channel(),'nofed','post_by_default');
		$selected = ((intval($nofed_defpost) == 1) ? ' checked="checked" ' : '');
		$b .= '<div class="profile-jot-net"><input type="checkbox" name="nofed_enable"' . $selected . ' value="1" /> ' 
			. '<img style="height: 32px; width: 32px;" src="images/blank.png" /> ' . t('Federate') . '</div>';
	}
}

function nofed_settings_post ($a,$post) {
	if(! local_channel())
		return;

	// don't check nofed settings if nofed submit button is not clicked
	if (! x($_POST,'nofed-submit'))
		return;

	set_pconfig(local_channel(), 'nofed', 'post',            intval($_POST['nofed_enable']));
	set_pconfig(local_channel(), 'nofed', 'post_by_default', intval($_POST['nofed_default']));
        info( t('nofed Settings saved.') . EOL);

}

function nofed_settings(&$a,&$s) {
	if(! local_channel())
		return;

	//head_add_css('/addon/nofed/nofed.css');

	$enabled = get_pconfig(local_channel(), 'nofed', 'post');
	$checked = (($enabled) ? 1 : false);
	$defenabled = get_pconfig(local_channel(),'nofed','post_by_default');
	$defchecked = (($defenabled) ? 1 : false);

	$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
		'$field'	=> array('nofed_enable', t('Allow Federation Toggle'), $checked, '', array(t('No'),t('Yes'))),
	));

	$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
		'$field'	=> array('nofed_default', t('Federate posts by default'), $defchecked, '', array(t('No'),t('Yes'))),
	));

	$s .= replace_macros(get_markup_template('generic_addon_settings.tpl'), array(
		'$addon' 	=> array('nofed', '<img src="images/blank.png" style="width:1em; height:1em; margin:-3px 5px 0px 0px;">' . t('NoFed Settings'), '', t('Submit')),
		'$content'	=> $sc
	));

}


function nofed_post_local(&$a,&$b) {
	if($b['created'] != $b['edited'])
		return;

	if($b['mid'] !== $b['parent_mid'])
		return;

	if((local_channel()) && (local_channel() == $b['uid'])) {

		if($b['allow_cid'] || $b['allow_gid'] || $b['deny_cid'] || $b['deny_gid'])
			return;

		$nofed_post = get_pconfig(local_channel(),'nofed','post');
		if(! $nofed_post)
			return;

		$nofed_enable = (($nofed_post && x($_REQUEST,'nofed_enable')) ? intval($_REQUEST['nofed_enable']) : 0);

		// if API is used, default to the chosen settings
		if($_REQUEST['api_source'] && intval(get_pconfig(local_channel(),'nofed','post_by_default')))
			$nofed_enable = 1;

       if($nofed_enable)
            return;

       if(strlen($b['postopts']))
           $b['postopts'] .= ',';
       $b['postopts'] .= 'nodeliver';
    }
}
