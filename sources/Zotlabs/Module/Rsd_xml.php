<?php
namespace Zotlabs\Module;

class Rsd_xml extends \Zotlabs\Web\Controller {

	function init() {
		header ("Content-Type: text/xml");
		echo replace_macros(get_markup_template('rsd.tpl'),array(
			'$project' => \Zotlabs\Lib\System::get_platform_name(),
			'$baseurl' => z_root(),
			'$apipath' => z_root() . '/api/'
		));
		killme();
	}

}

