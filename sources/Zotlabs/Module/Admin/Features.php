<?php

namespace Zotlabs\Module\Admin;



class Features {

	
	function post() {
	
		check_form_security_token_redirectOnErr('/admin/features', 'admin_manage_features');
	
		logger('postvars: ' . print_r($_POST,true));
	
		$arr = array();
		$features = get_features(false);
	
		foreach($features as $fname => $fdata) {
			foreach(array_slice($fdata,1) as $f) {
				$feature = $f[0];
	
				if(array_key_exists('feature_' . $feature,$_POST))
					$val = intval($_POST['feature_' . $feature]);
				else
					$val = 0;
				set_config('feature',$feature,$val);
	
				if(array_key_exists('featurelock_' . $feature,$_POST))
					set_config('feature_lock',$feature,$val);
				else
					del_config('feature_lock',$feature);
			}
		}
	
		goaway(z_root() . '/admin/features' );
	
	}
	
	function get() {
		
		if((argc() > 1) && (argv(1) === 'features')) {
			$arr = array();
			$features = get_features(false);
	
			foreach($features as $fname => $fdata) {
				$arr[$fname] = array();
				$arr[$fname][0] = $fdata[0];
				foreach(array_slice($fdata,1) as $f) {
	
					$set = get_config('feature',$f[0]);
					if($set === false)
						$set = $f[3];
					$arr[$fname][1][] = array(
						array('feature_' .$f[0],$f[1],$set,$f[2],array(t('Off'),t('On'))),
						array('featurelock_' .$f[0],sprintf( t('Lock feature %s'),$f[1]),(($f[4] !== false) ? 1 : 0),'',array(t('Off'),t('On')))
					);
				}
			}
			
			$tpl = get_markup_template("admin_settings_features.tpl");
			$o .= replace_macros($tpl, array(
				'$form_security_token' => get_form_security_token("admin_manage_features"),
				'$title'	=> t('Manage Additional Features'),
				'$features' => $arr,
				'$submit'   => t('Submit'),
			));
	
			return $o;
		}
	}
	

}