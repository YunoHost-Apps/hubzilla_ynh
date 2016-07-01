<?php
namespace Zotlabs\Module;


class Siteinfo_json extends \Zotlabs\Web\Controller {

	function init() {
	
		$data = get_site_info();
		json_return_and_die($data);
	
	}
	
}
