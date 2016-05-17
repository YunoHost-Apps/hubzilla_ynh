<?php
namespace Zotlabs\Module;



class Randprof extends \Zotlabs\Web\Controller {

	function init() {
		require_once('include/Contact.php');
		$x = random_profile();
		if($x)
			goaway(chanlink_url($x));
	
		/** FIXME this doesn't work at the moment as a fallback */
		goaway(z_root() . '/profile');
	}
	
}
