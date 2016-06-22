<?php
namespace Zotlabs\Module;


class Lang extends \Zotlabs\Web\Controller {

	function get() {
		return lang_selector();
	}
	
	
}
