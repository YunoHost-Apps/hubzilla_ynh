<?php
/**
 * Name: Piwik Analytics
 * Description: Piwik Analytics Plugin for Hubzilla
 * Version: 1.2
 * Author: Tobias Diekershoff <https://f.diekershoff.de/profile/tobias>
 * Author: Klaus Weidenbach
 * Maintainer: none
 */

/*   Piwik Analytics Plugin for red#
 *
 *   Author: Tobias Diekershoff
 *           tobias.diekershoff@gmx.net
 *
 *   License: 3-clause BSD license
 *
 *   Configuration:
 *     Add the following lines to your .htconfig.php file or use the
 *     CLI config utility:
 *
 *     $ ./util/config piwik baseurl "www.example.com/piwik/"
 *
 *     App::$config['piwik']['baseurl'] = 'www.example.com/piwik/';
 *     App::$config['piwik']['siteid'] = '1';
 *     App::$config['piwik']['optout'] = true;  // set to false to disable
 *     App::$config['piwik']['async'] = false;  // set to true to enable
 *     App::$config['piwik']['trackjserror'] = false;  // set to true to enable
 *
 *     Change the siteid to the ID that the Piwik tracker for your Friendica
 *     installation has. Alter the baseurl to fit your needs, don't care
 *     about http/https but beware to put the trailing / at the end of your
 *     setting.
 */

function piwik_load() {
	register_hook('page_end', 'addon/piwik/piwik.php', 'piwik_analytics');

	logger("installed piwik plugin");
}

function piwik_unload() {
	unregister_hook('page_end', 'addon/piwik/piwik.php', 'piwik_analytics');

	logger("uninstalled piwik plugin");
}

function piwik_analytics($a,&$b) {

	/*
	 *   styling of every HTML block added by this plugin is done in the
	 *   associated CSS file. We just have to tell Hubzilla to get it
	 *   into the page header.
	 */
	App::$page['htmlhead'] .= '<link rel="stylesheet"  type="text/css" href="' . z_root() . '/addon/piwik/piwik.css' . '" media="all" />';

	/*
	 *   Get the configuration variables from the .htconfig file.
	 */
	$baseurl = get_config('piwik','baseurl');
	$siteid  = get_config('piwik','siteid');
	$optout  = get_config('piwik','optout');
	$async   = get_config('piwik','async');
	$trackjserror = get_config('piwik','trackjserror');

	/*
	 *   Add the Piwik tracking code for the site.
	 *   If async is set to true use asynchronous tracking
	 */
	if ($async) {
		App::$page['htmlhead'] .= "<!-- Piwik --> <script type=\"text/javascript\">\r\nvar _paq = _paq || [];\r\n(function(){ var u=((\"https:\" == document.location.protocol) ? \"https://".$baseurl."\" : \"http://".$baseurl."\");\r\n_paq.push(['setSiteId', ".$siteid."]);\r\n_paq.push(['setTrackerUrl', u+'piwik.php']);\r\n_paq.push(['trackPageView']);\r\n_paq.push(['enableLinkTracking']);\r\n";
		if ($trackjserror) App::$page['htmlhead'] .= "_paq.push(['enableJSErrorTracking']);\r\n";
		App::$page['htmlhead'] .= "var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0]; g.type='text/javascript';\r\ng.defer=true; g.async=true; g.src=u+'piwik.js';\r\ns.parentNode.insertBefore(g,s); })();\r\n </script>\r\n<!-- End Piwik Code -->\r\n";
		$b .= "<div id='piwik-code-block'> <!-- Piwik -->\r\n<noscript><p><img src=\"//".$baseurl."piwik.php?idsite=".$siteid."\" style=\"border:0\" alt=\"\" /></p></noscript>\r\n <!-- End Piwik Tracking Tag --> </div>";
	} else {
		$b .= "<div id='piwik-code-block'> <!-- Piwik -->\r\n <script type=\"text/javascript\">\r\n var pkBaseURL = ((\"https:\" == document.location.protocol) ? \"https://".$baseurl."\" : \"http://".$baseurl."\");\r\n document.write(unescape(\"%3Cscript src='\" + pkBaseURL + \"piwik.js' type='text/javascript'%3E%3C/script%3E\"));\r\n </script>\r\n<script type=\"text/javascript\">\r\n try {\r\n var piwikTracker = Piwik.getTracker(pkBaseURL + \"piwik.php\", ".$siteid.");\r\n piwikTracker.trackPageView();\r\n piwikTracker.enableLinkTracking();\r\n }\r\n catch( err ) {}\r\n </script>\r\n<noscript><p><img src=\"//".$baseurl."piwik.php?idsite=".$siteid."\" style=\"border:0\" alt=\"\" /></p></noscript>\r\n <!-- End Piwik Tracking Tag --> </div>";
	}

	/*
	 *   If the optout variable is set to true then display the notice
	 *   otherwise just include the above code into the page.
	 */
	if ($optout) {
		$b .= "<div id='piwik-optout-link'>";
		$b .= t("This website is tracked using the <a href='http://www.piwik.org'>Piwik</a> analytics tool.");
		$b .= " ";
		$the_url =  "http://".$baseurl ."index.php?module=CoreAdminHome&action=optOut";
		$b .= sprintf(t("If you do not want that your visits are logged this way you <a href='%s'>can set a cookie to prevent Piwik from tracking further visits of the site</a> (opt-out)."), $the_url);
		$b .= "</div>";
	}
}
function piwik_plugin_admin (&$a, &$o) {
	$t = get_markup_template( "admin.tpl", "addon/piwik/" );
	$o = replace_macros( $t, array(
		'$submit' => t('Submit'),
		'$baseurl' => array('baseurl', t('Piwik Base URL'), get_config('piwik','baseurl' ), t('Absolute path to your Piwik installation. (without protocol (http/s), with trailing slash)')),
		'$siteid' => array('siteid', t('Site ID'), get_config('piwik','siteid' ), ''),
		'$optout' => array('optout', t('Show opt-out cookie link?'), get_config('piwik','optout' ), ''),
		'$async' => array('async', t('Asynchronous tracking'), get_config('piwik','async' ), ''),
		'$trackjserror' => array('trackjserror', t('Enable frontend JavaScript error tracking'), get_config('piwik','trackjserror' ), t('This feature requires Piwik >= 2.2.0'))
	));
}


function piwik_plugin_admin_post (&$a) {
	$url = ((x($_POST, 'baseurl')) ? notags(trim($_POST['baseurl'])) : '');
	$id = ((x($_POST, 'siteid')) ? trim($_POST['siteid']) : '');
	$optout = ((x($_POST, 'optout')) ? trim($_POST['optout']) : '');
	$async = ((x($_POST, 'async')) ? trim($_POST['async']) : '');
	$trackjserror = ((x($_POST, 'trackjserror')) ? trim($_POST['trackjserror']) : '');
	set_config('piwik', 'baseurl', $url);
	set_config('piwik', 'siteid', $id);
	set_config('piwik', 'optout', $optout);
	set_config('piwik', 'async', $async);
	set_config('piwik', 'trackjserror', $trackjserror);
	info( t('Settings updated.'). EOL);
}
