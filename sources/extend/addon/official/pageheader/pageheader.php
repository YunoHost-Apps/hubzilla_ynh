<?php


/**
 * Name: Page Header
 * Description: Inserts a page header
 * Version: 1.1
 * Author: Keith Fernie <http://friendika.me4.it/profile/keith>
 *         Hauke Altmann <https://snarl.de/profile/tugelblend>
 * 
 */

function pageheader_load() {
    Zotlabs\Extend\Hook::register('page_content_top', 'addon/pageheader/pageheader.php', array('\\Pageheader','pageheader_fetch'));
	Zotlabs\Extend\Hook::register('feature_settings', 'addon/pageheader/pageheader.php', array('\\Pageheader','pageheader_addon_settings'));
	Zotlabs\Extend\Hook::register('feature_settings_post', 'addon/pageheader/pageheader.php',array('\\Pageheader','pageheader_addon_settings_post'));

}


function pageheader_unload() {
    Zotlabs\Extend\Hook::unregister('page_content_top', 'addon/pageheader/pageheader.php', array('\\Pageheader','pageheader_fetch'));
	Zotlabs\Extend\Hook::unregister('feature_settings', 'addon/pageheader/pageheader.php', array('\\Pageheader','pageheader_addon_settings'));
	Zotlabs\Extend\Hook::unregister('feature_settings_post', 'addon/pageheader/pageheader.php',array('\\Pageheader','pageheader_addon_settings_post'));

}


class Pageheader {

	static public function pageheader_addon_settings(&$s) {


		if(! is_site_admin())
			return;

		$words = get_config('pageheader','text');
		if(! $words)
			$words = '';

	    $sc .= '<div class="settings-block">';
    	$sc .= '<div id="pageheader-wrapper">';
	    $sc .= '<label id="pageheader-label" for="pageheader-words">' . t('Message to display on every page on this server') . ' </label>';
    	$sc .= '<textarea class="form-control form-group" id="pageheader-words" type="text" name="pageheader-words">' . $words . '</textarea>';
    	$sc .= '</div><div class="clear"></div>';

		$s .= replace_macros(get_markup_template('generic_addon_settings.tpl'), array(
			'$addon' 	=> array('pageheader', t('Pageheader Settings'), '', t('Submit')),
			'$content'	=> $sc
		));


		return;

	}

	static public function pageheader_addon_settings_post(&$b) {

		if(! is_site_admin())
			return;

		if($_POST['pageheader-submit']) {
			set_config('pageheader','text',trim(strip_tags($_POST['pageheader-words'])));
			info( t('pageheader Settings saved.') . EOL);
		}
	}

	static public function pageheader_fetch(&$b) {
	
		if(file_exists('pageheader.html')){
			$s = file_get_contents('pageheader.html');
		} else {
			$s = get_config('pageheader', 'text');
			App::$page['htmlhead'] .= '<link rel="stylesheet" type="text/css" href="' . z_root() . '/addon/pageheader/pageheader.css' . '" media="all" />' . "\r\n";
		}

		if($s)
			$b .= '<div class="pageheader">' . $s . '</div>';
	}
}