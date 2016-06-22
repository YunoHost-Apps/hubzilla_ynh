<?php
namespace Zotlabs\Module;


class Well_known extends \Zotlabs\Web\Controller {

	function init(){
	
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
					\App::$argc -= 1;
					array_shift(\App::$argv);
					\App::$argv[0] = 'zfinger';
					$module = new \Zotlabs\Module\Zfinger();
					$module->init();
					break;
	
				case 'webfinger':
					\App::$argc -= 1;
					array_shift(\App::$argv);
					\App::$argv[0] = 'wfinger';
					$module = new \Zotlabs\Module\Wfinger();
					$module->init();
					break;
	
				case 'host-meta':
					\App::$argc -= 1;
					array_shift(\App::$argv);
					\App::$argv[0] = 'hostxrd';
					$module = new \Zotlabs\Module\Hostxrd();
					$module->init();
					break;
	
				default:
					if(file_exists(\App::$cmd)) {
						echo file_get_contents(\App::$cmd); 
						killme();
					}
					elseif(file_exists(\App::$cmd . '.php'))
						require_once(\App::$cmd . '.php');
					break;
	
			}
		}
	
		http_status_exit(404);
	}
}
