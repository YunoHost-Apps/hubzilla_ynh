<?php
namespace Zotlabs\Module;


class Login extends \Zotlabs\Web\Controller {

	function get() {
		if(local_channel())
			goaway(z_root());
		if(remote_channel() && $_SESSION['atoken'])
			goaway(z_root());

		return login((\App::$config['system']['register_policy'] == REGISTER_CLOSED) ? false : true);
	}
	
}
