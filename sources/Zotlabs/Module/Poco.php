<?php
namespace Zotlabs\Module;

require_once('include/socgraph.php');


class Poco extends \Zotlabs\Web\Controller {

	function init() {
		poco($a,false);
	}
	
}
