<?php

require_once('library/openid/openid.php');

	/**
	 *
	 * Name: Openid
	 * Description: Openid (traditional) client and server
	 * Version: 1.0
	 * Author: Mike Macgirvin
	 *
	 */


function openid_load() {
	Zotlabs\Extend\Hook::register('module_loaded','addon/openid/openid.php','openid_module_loaded');
	Zotlabs\Extend\Hook::register('reverse_magic_auth','addon/openid/openid.php','openid_reverse_magic_auth');
}

function openid_unload() {
	Zotlabs\Extend\Hook::unregister_by_file('addon/openid/openid.php');
}


function openid_module_loaded(&$x) {
	if($x['module'] === 'openid') {
		require_once('addon/openid/Mod_Openid.php');
		$x['controller'] = new \Zotlabs\Module\Openid();
		$x['installed'] = true;
	}
	if($x['module'] === 'id') {
		require_once('addon/openid/Mod_Id.php');
		$x['controller'] = new \Zotlabs\Module\Id();
		$x['installed'] = true;
	}
}

function openid_reverse_magic_auth($x) {

	try {
		$openid = new \LightOpenID(z_root());
		$openid->identity = $x['address'];
		$openid->returnUrl = z_root() . '/openid';
		$openid->required =  [ 'namePerson/friendly', 'namePerson' ];
		$openid->optional = [ 'namePerson/first', 'media/image/aspect11', 'media/image/default' ];
		goaway($openid->authUrl());
	}
	catch (\Exception $e) {
		notice( t('We encountered a problem while logging in with the OpenID you provided. Please check the correct spelling of the ID.').'<br /><br >'. t('The error message was:').' '.$e->getMessage());
	}

}