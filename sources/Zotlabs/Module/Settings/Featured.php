<?php

namespace Zotlabs\Module\Settings;


class Featured {
		
	function post() {
		check_form_security_token_redirectOnErr('/settings/featured', 'settings_featured');
	
		call_hooks('feature_settings_post', $_POST);
	
		build_sync_packet();
		return;
	}

	function get() {
		$settings_addons = "";
	
		$o = '';
			
		$r = q("SELECT * FROM hook WHERE hook = 'feature_settings' ");
		if(! $r)
			$settings_addons = t('No feature settings configured');
	
		call_hooks('feature_settings', $settings_addons);
					
		$tpl = get_markup_template("settings_addons.tpl");
		$o .= replace_macros($tpl, array(
			'$form_security_token' => get_form_security_token("settings_featured"),
			'$title'	=> t('Feature/Addon Settings'),
			'$settings_addons' => $settings_addons
		));
		return $o;
	}
	
}