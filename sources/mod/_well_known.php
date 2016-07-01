<?php

function _well_known_init(&$a){

	if(argc() > 1) {

		$arr = array('server' => $_SERVER, 'request' => $_REQUEST);
		call_hooks('well_known', $arr);


		if(! check_siteallowed($_SERVER['REMOTE_ADDR'])) {
			logger('well_known: site not allowed. ' . $_SERVER['REMOTE_ADDR']);
			killme();
		}

		// from php.net re: REMOTE_HOST:
		//     Note: Your web server must be configured to create this variable. For example in Apache 
		// you'll need HostnameLookups On inside httpd.conf for it to exist. See also gethostbyaddr(). 

		if(get_config('system','siteallowed_remote_host') && (! check_siteallowed($_SERVER['REMOTE_HOST']))) {
			logger('well_known: site not allowed. ' . $_SERVER['REMOTE_HOST']);
			killme();
		}


		switch(argv(1)) {
			case 'zot-info':
				App::$argc -= 1;
				array_shift(App::$argv);
				App::$argv[0] = 'zfinger';
				require_once('mod/zfinger.php');
				zfinger_init($a);
				break;

			case 'webfinger':
				App::$argc -= 1;
				array_shift(App::$argv);
				App::$argv[0] = 'wfinger';
				require_once('mod/wfinger.php');
				wfinger_init($a);
				break;

			case 'host-meta':
				App::$argc -= 1;
				array_shift(App::$argv);
				App::$argv[0] = 'hostxrd';
				require_once('mod/hostxrd.php');
				hostxrd_init($a);
				break;

			default:
				if(file_exists(App::$cmd)) {
					echo file_get_contents(App::$cmd); 
					killme();
				}
				elseif(file_exists(App::$cmd . '.php'))
					require_once(App::$cmd . '.php');
				break;

		}
	}

	http_status_exit(404);
}