<?php

namespace Zotlabs\Module\Settings;


class Features {

	function post() {
		check_form_security_token_redirectOnErr('/settings/features', 'settings_features');
	
		// Build list of features and check which are set
		// We will not create any settings for features that are above our techlevel

		$features = get_features();
		$all_features = array();
		foreach($features as $k => $v) {
			foreach($v as $f) 
				$all_features[] = $f[0];
		}
		foreach($all_features as $k) {
			if(x($_POST,"feature_$k"))
				set_pconfig(local_channel(),'feature',$k, 1);
			else
				set_pconfig(local_channel(),'feature',$k, 0);
		}
		build_sync_packet();
		return;
	}

	function get() {
		$arr = array();
		$features = get_features();
	
		foreach($features as $fname => $fdata) {
			$arr[$fname] = array();
			$arr[$fname][0] = $fdata[0];
			foreach(array_slice($fdata,1) as $f) {
				$arr[$fname][1][] = array('feature_' .$f[0],$f[1],((intval(feature_enabled(local_channel(),$f[0]))) ? "1" : ''),$f[2],array(t('Off'),t('On')));
			}
		}
			
		$tpl = get_markup_template("settings_features.tpl");
		$o .= replace_macros($tpl, array(
			'$form_security_token' => get_form_security_token("settings_features"),
			'$title'	=> t('Additional Features'),
			'$features' => $arr,
			'$submit'   => t('Submit'),
		));
	
		return $o;
	}

}
