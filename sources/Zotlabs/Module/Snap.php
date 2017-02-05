<?php

namespace Zotlabs\Module;

/**
 * @brief Initialize Hubzilla's cloud (SabreDAV).
 *
 * Module for accessing the DAV storage area from a DAV client.
 */

use \Sabre\DAV as SDAV;
use \Zotlabs\Storage;

// composer autoloader for SabreDAV
require_once('vendor/autoload.php');


/**
 * @brief Fires up the SabreDAV server.
 *
 * @param App &$a
 */

class Snap extends \Zotlabs\Web\Controller {

	function init() {
	
		// workaround for HTTP-auth in CGI mode
		if (x($_SERVER, 'REDIRECT_REMOTE_USER')) {
 			$userpass = base64_decode(substr($_SERVER["REDIRECT_REMOTE_USER"], 6)) ;
			if(strlen($userpass)) {
			 	list($name, $password) = explode(':', $userpass);
				$_SERVER['PHP_AUTH_USER'] = $name;
				$_SERVER['PHP_AUTH_PW'] = $password;
			}
		}

		if (x($_SERVER, 'HTTP_AUTHORIZATION')) {
			$userpass = base64_decode(substr($_SERVER["HTTP_AUTHORIZATION"], 6)) ;
			if(strlen($userpass)) {
				list($name, $password) = explode(':', $userpass);
				$_SERVER['PHP_AUTH_USER'] = $name;
				$_SERVER['PHP_AUTH_PW'] = $password;
			}
		}
	
		if (! is_dir('store'))
			os_mkdir('store', STORAGE_DEFAULT_PERMISSIONS, false);
	
		$which = null;
		if (argc() > 1)
			$which = argv(1);
	
		$profile = 0;
	
		if($which)
			profile_load( $which, $profile);
		else
			killme();	

		if($_SERVER['PHP_AUTH_USER'] && $_SERVER['PHP_AUTH_USER'] !== $which)
			killme();

		if(local_channel()) {
			$c = \App::get_channel();
			if($c && $c['channel_address'] !== $which)
				killme();
		}

		if(! in_array(strtolower($_SERVER['REQUEST_METHOD']),['propfind','get','head']))
			killme(); 

		$auth = new \Zotlabs\Storage\BasicAuth();
		$auth->setRealm(ucfirst(\Zotlabs\Lib\System::get_platform_name()) . 'WebDAV');

		$rootDirectory = new SDAV\FS\Directory("store");

		// The server object is responsible for making sense out of the WebDAV protocol
		$server = new SDAV\Server($rootDirectory);

		$authPlugin = new \Sabre\DAV\Auth\Plugin($auth);
		$server->addPlugin($authPlugin);

		// If your server is not on your webroot, make sure the following line has the
		// correct information
		$server->setBaseUri('/snap');

		// The lock manager is reponsible for making sure users don't overwrite
		// each others changes.
		$lockBackend = new SDAV\Locks\Backend\File('store/[data]/locks');
		$lockPlugin = new SDAV\Locks\Plugin($lockBackend);
		$server->addPlugin($lockPlugin);

		// This ensures that we get a pretty index in the browser, but it is
		// optional.

//		$server->addPlugin(new SDAV\Browser\Plugin());

		// All we need to do now, is to fire up the server
		$server->exec();
		killme();

	}
	
}
