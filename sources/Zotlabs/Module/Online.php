<?php
namespace Zotlabs\Module; /** @file */


class Online extends \Zotlabs\Web\Controller {

	function init() {
	
		$ret = array('result' => false);
		if(argc() != 2)
			json_return_and_die($ret);
	
		$ret = get_online_status(argv(1));
		json_return_and_die($ret);
	} 
	
}
