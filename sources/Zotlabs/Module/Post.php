<?php
namespace Zotlabs\Module;

/**
 * @file mod/post.php
 *
 * @brief Zot endpoint.
 *
 */

require_once('include/zot.php');


class Post extends \Zotlabs\Web\Controller {

	function init() {
	
		if (array_key_exists('auth', $_REQUEST)) {
			$x = new \Zotlabs\Zot\Auth($_REQUEST);
			exit;
		}
	
	}
	
	
		function post() {
	
		$z = new \Zotlabs\Zot\Receiver($_REQUEST['data'],get_config('system','prvkey'), new \Zotlabs\Zot\ZotHandler());
		
		// notreached;
	
		exit;
	
	}
	
}
