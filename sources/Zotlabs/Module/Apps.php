<?php
namespace Zotlabs\Module;

require_once('include/apps.php');


class Apps extends \Zotlabs\Web\Controller {

	function get() {
	
		if(argc() == 2 && argv(1) == 'edit')
			$mode = 'edit';
		else
			$mode = 'list';
	
		$_SESSION['return_url'] = \App::$cmd;
	
		$apps = array();
	
	
		if(local_channel()) {
			import_system_apps();
			$syslist = array();
			$list = app_list(local_channel(), false, $_GET['cat']);
			if($list) {
				foreach($list as $x) {
					$syslist[] = app_encode($x);
				}
			}
			translate_system_apps($syslist);
		}
		else
			$syslist = get_system_apps(true);

		usort($syslist,'app_name_compare');
	
	//	logger('apps: ' . print_r($syslist,true));
	
		foreach($syslist as $app) {
			$apps[] = app_render($app,$mode);
		}
	
		return replace_macros(get_markup_template('myapps.tpl'), array(
			'$sitename' => get_config('system','sitename'),
			'$cat' => ((array_key_exists('cat',$_GET) && $_GET['cat']) ? ' - ' . escape_tags($_GET['cat']) : ''),
			'$title' => t('Apps'),
			'$apps' => $apps,
		));
	
	}
	
}
