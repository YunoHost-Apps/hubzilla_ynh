<?php
namespace Zotlabs\Module;


class Regver extends \Zotlabs\Web\Controller {

	function get() {
	
		global $lang;
	
		$_SESSION['return_url'] = \App::$cmd;
	
		if(argc() != 3)
			killme();
	
		$cmd  = argv(1);
		$hash = argv(2);
	
		if($cmd === 'deny') {
			if (! account_deny($hash)) killme();
		}
	
		if($cmd === 'allow') {
			if (! account_approve($hash)) killme();
		}
	}
	
}
