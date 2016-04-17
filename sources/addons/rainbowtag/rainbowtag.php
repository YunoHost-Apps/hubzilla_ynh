<?php


/**
 * Name: Rainbowtag
 * Description: Add some colour to tag clouds
 * Version: 1.0
 * Author: Mike Macgirvin
 * Maintainer: Mike Macgirvin <mike@macgirvin.com>
 */



function rainbowtag_load() {
	register_hook('construct_page', 'addon/rainbowtag/rainbowtag.php', 'rainbowtag_construct_page');
	register_hook('feature_settings', 'addon/rainbowtag/rainbowtag.php', 'rainbowtag_addon_settings');
	register_hook('feature_settings_post', 'addon/rainbowtag/rainbowtag.php', 'rainbowtag_addon_settings_post');

}

function rainbowtag_unload() {
	unregister_hook('construct_page', 'addon/rainbowtag/rainbowtag.php', 'rainbowtag_construct_page');
	unregister_hook('feature_settings', 'addon/rainbowtag/rainbowtag.php', 'rainbowtag_addon_settings');
	unregister_hook('feature_settings_post', 'addon/rainbowtag/rainbowtag.php', 'rainbowtag_addon_settings_post');

}



function rainbowtag_construct_page(&$a,&$b) {

	if(! App::$profile_uid)
		return;
	if(! intval(get_pconfig(App::$profile_uid,'rainbowtag','enable')))
		return;

	$c = get_pconfig(App::$profile_uid,'rainbowtag','colors');
	$color1 = ((is_array($c) && $c[0]) ? $c[0] : 'DarkGray');
	$color2 = ((is_array($c) && $c[1]) ? $c[1] : 'LawnGreen');
	$color3 = ((is_array($c) && $c[2]) ? $c[2] : 'DarkOrange');
	$color4 = ((is_array($c) && $c[3]) ? $c[3] : 'Red');
	$color5 = ((is_array($c) && $c[4]) ? $c[4] : 'Gold');
	$color6 = ((is_array($c) && $c[5]) ? $c[5] : 'Teal');
	$color7 = ((is_array($c) && $c[6]) ? $c[6] : 'DarkMagenta');
	$color8 = ((is_array($c) && $c[7]) ? $c[7] : 'DarkGoldenRod');
	$color9 = ((is_array($c) && $c[8]) ? $c[8] : 'DarkBlue');
	$color10 = ((is_array($c) && $c[9]) ? $c[9] : 'DeepPink');

		

	$o = '<style>';
	$o .= '.tag1  { color: ' . $color1  . ' !important; }' . "\r\n";
	$o .= '.tag2  { color: ' . $color2  . ' !important; }' . "\r\n";
	$o .= '.tag3  { color: ' . $color3  . ' !important; }' . "\r\n";
	$o .= '.tag4  { color: ' . $color4  . ' !important; }' . "\r\n";
	$o .= '.tag5  { color: ' . $color5  . ' !important; }' . "\r\n";
	$o .= '.tag6  { color: ' . $color6  . ' !important; }' . "\r\n";
	$o .= '.tag7  { color: ' . $color7  . ' !important; }' . "\r\n";
	$o .= '.tag8  { color: ' . $color8  . ' !important; }' . "\r\n";
	$o .= '.tag9  { color: ' . $color9  . ' !important; }' . "\r\n";
	$o .= '.tag10 { color: ' . $color10 . ' !important; }' . "\r\n";
	$o .= '</style>';

	App::$page['htmlhead'] .= $o;

}

function rainbowtag_addon_settings(&$a,&$s) {


	if(! local_channel())
		return;

	/* Add our stylesheet to the page so we can make our settings look nice */

	//head_add_css('/addon/rainbowtag/rainbowtag.css');

	$enable_checked = (intval(get_pconfig(local_channel(),'rainbowtag','enable')) ? 1 : false);

	$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
		'$field'	=> array('rainbowtag-enable', t('Enable Rainbowtag'), $enable_checked, '', array(t('No'),t('Yes'))),
	));

	$s .= replace_macros(get_markup_template('generic_addon_settings.tpl'), array(
		'$addon' 	=> array('rainbowtag', t('Rainbowtag Settings'), '', t('Submit')),
		'$content'	=> $sc
	));

	return;

}

function rainbowtag_addon_settings_post(&$a,&$b) {

	if(! local_channel())
		return;

	if($_POST['rainbowtag-submit']) {
		$enable = ((x($_POST,'rainbowtag-enable')) ? intval($_POST['rainbowtag-enable']) : 0);
		set_pconfig(local_channel(),'rainbowtag','enable', $enable);
		info( t('Rainbowtag Settings saved.') . EOL);
	}
}
