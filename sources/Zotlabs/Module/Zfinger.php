<?php
namespace Zotlabs\Module;


class Zfinger extends \Zotlabs\Web\Controller {

	function init() {
	
		require_once('include/zot.php');
		require_once('include/crypto.php');
	
	
		$x = zotinfo($_REQUEST);
		json_return_and_die($x);
	
	}
	
}
