<?php

namespace Zotlabs\Module\Admin;



class Dbsync {
	



	function get() {
		$o = '';
	
		if(argc() > 3 && intval(argv(3)) && argv(2) === 'mark') {
			set_config('database', 'update_r' . intval(argv(3)), 'success');
			if(intval(get_config('system','db_version')) <= intval(argv(3)))
				set_config('system','db_version',intval(argv(3)) + 1);
			info( t('Update has been marked successful') . EOL);
			goaway(z_root() . '/admin/dbsync');
		}
	
		if(argc() > 2 && intval(argv(2))) {
			require_once('install/update.php');
			$func = 'update_r' . intval(argv(2));
			if(function_exists($func)) {
				$retval = $func();
				if($retval === UPDATE_FAILED) {
					$o .= sprintf( t('Executing %s failed. Check system logs.'), $func); 
				}
				elseif($retval === UPDATE_SUCCESS) {
					$o .= sprintf( t('Update %s was successfully applied.'), $func);
					set_config('database',$func, 'success');
				}
				else
					$o .= sprintf( t('Update %s did not return a status. Unknown if it succeeded.'), $func);
			}
			else
				$o .= sprintf( t('Update function %s could not be found.'), $func);
	
			return $o;
		}
	
		$failed = array();
		$r = q("select * from config where cat = 'database' ");
		if(count($r)) {
			foreach($r as $rr) {
				$upd = intval(substr($rr['k'],8));
				if($rr['v'] === 'success')
					continue;
				$failed[] = $upd;
			}
		}
		if(! count($failed))
			return '<div class="generic-content-wrapper-styled"><h3>' . t('No failed updates.') . '</h3></div>';
	
		$o = replace_macros(get_markup_template('failed_updates.tpl'),array(
			'$base' => z_root(),
			'$banner' => t('Failed Updates'),
			'$desc' => '',
			'$mark' => t('Mark success (if update was manually applied)'),
			'$apply' => t('Attempt to execute this update step automatically'),
			'$failed' => $failed
		));
	
		return $o;
	}
}