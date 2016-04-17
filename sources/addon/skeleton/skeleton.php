<?php
/**
 * Name: Skeleton
 * Description: A skeleton for plugins, you can copy/paste
 * Version: 0.1
 * Depends: Core
 * Recommends: None
 * Category: Example
 * Author: ken restivo <ken@restivo.org>
 * Maintainer: ken restivo <ken@restivo.org>
 */


function skeleton_load(){
	register_hook('construct_page', 'addon/skeleton/skeleton.php', 'skeleton_construct_page');
	register_hook('feature_settings', 'addon/skeleton/skeleton.php', 'skeleton_settings');
	register_hook('feature_settings_post', 'addon/skeleton/skeleton.php', 'skeleton_settings_post');

}


function skeleton_unload(){
	unregister_hook('construct_page', 'addon/skeleton/skeleton.php', 'skeleton_construct_page');
	unregister_hook('feature_settings', 'addon/skeleton/skeleton.php', 'skeleton_settings');
	unregister_hook('feature_settings_post', 'addon/skeleton/skeleton.php', 'skeleton_settings_post');
}



function skeleton_construct_page(&$a, &$b){
	if(! local_channel())
		return;

	$some_setting = get_pconfig(local_channel(), 'skeleton','some_setting');

	// Whatever you put in settings, will show up on the left nav of your pages.
	$b['layout']['region_aside'] .= '<div>' . htmlentities($some_setting) .  '</div>';

}



function skeleton_settings_post($a,$s) {
	if(! local_channel())
		return;

	set_pconfig( local_channel(), 'skeleton', 'some_setting', $_POST['some_setting'] );

}

function skeleton_settings(&$a,&$s) {
	$id = local_channel();
	if (! $id)
		return;

	$some_setting = get_pconfig( $id, 'skeleton', 'some_setting');

	$sc = replace_macros(get_markup_template('field_input.tpl'), array(
				     '$field'	=> array('some_setting', t('Some setting'), 
							 $some_setting, 
							 t('A setting'))));
	$s .= replace_macros(get_markup_template('generic_addon_settings.tpl'), array(
				     '$addon' 	=> array('skeleton',
							 t('Skeleton Settings'), '', 
							 t('Submit')),
				     '$content'	=> $sc));

}




