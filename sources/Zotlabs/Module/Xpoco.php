<?php
namespace Zotlabs\Module;

require_once('include/socgraph.php');


class Xpoco extends \Zotlabs\Web\Controller {

	function init() {
		poco($a,true);
	}
	
}
