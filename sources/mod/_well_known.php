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
				$a->argc -= 1;
				array_shift($a->argv);
				$a->argv[0] = 'zfinger';
				require_once('mod/zfinger.php');
				zfinger_init($a);
				break;

			case 'webfinger':
				$a->argc -= 1;
				array_shift($a->argv);
				$a->argv[0] = 'wfinger';
				require_once('mod/wfinger.php');
				wfinger_init($a);
				break;

			case 'host-meta':
				$a->argc -= 1;
				array_shift($a->argv);
				$a->argv[0] = 'hostxrd';
				require_once('mod/hostxrd.php');
				hostxrd_init($a);
				break;

			default:
				// look in $WEBROOT/well_known for the requested file in case it is 
				// something a site requires and for which we do not have a module

				// @fixme - we may need to determine the content-type and stick it in the header
				// for now this can be done with a php script masquerading as the requested file

				$wk_file = str_replace('.well-known','well_known',$a->cmd);
				if(file_exists($wk_file)) {
					echo file_get_contents($wk_file); 
					killme();
				}
				elseif(file_exists($wk_file . '.php'))
					require_once($wk_file . '.php');
				break;

		}
	}

	http_status_exit(404);
}