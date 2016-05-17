<?php
namespace Zotlabs\Module;


class Hostxrd extends \Zotlabs\Web\Controller {

	function init() {
		header('Access-Control-Allow-Origin: *');
		header("Content-type: application/xrd+xml");
		logger('hostxrd',LOGGER_DEBUG);
	
		$tpl = get_markup_template('xrd_host.tpl');
		$x = replace_macros(get_markup_template('xrd_host.tpl'), array(
			'$zhost' => \App::get_hostname(),
			'$zroot' => z_root()
		));
		$arr = array('xrd' => $x);
		call_hooks('hostxrd',$arr);
	
		echo $arr['xrd'];
		killme();
	}
	
}
