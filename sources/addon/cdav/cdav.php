<?php

/**
 * Name: CalDAV,CardDAV server
 * Description: CalDAV and CardDAV sync server (experimental, unsupported)
 * Version: 1.0
 * Author: Mike Macgirvin <mike@macgirvin.com>
 * 
 */

function cdav_install() {

	if(ACTIVE_DBTYPE === DBTYPE_POSTGRES) {
		$type='postgres';
	}
	else {
		$type = 'mysql';
	}

	$str = file_get_contents('addon/cdav/' . $type . '.sql');
	$arr = explode(';',$str);

	$errors = '';

	foreach($arr as $a) {
		if(strlen(trim($a))) {
			$r = q(trim($a));
			if(! $r) {
				$errors .=  t('Errors encountered creating database table: ') . $a . EOL;
			}
		}
	}
	if($errors) {
		notice(t('Errors encountered creating database tables.') . EOL);
	}
}


function cdav_uninstall() {
	// Currently we do nothing here as it could destroy a lot of data, when
	// often you only want to reset the plugin state. 
	// We need a way to specify that you really really want to destroy
	// everything before we let you do it.
}

function cdav_load() {
	Zotlabs\Extend\Hook::register('well_known', 'addon/cdav/cdav.php', 'cdav_well_known');
	Zotlabs\Extend\Hook::register('feature_settings', 'addon/cdav/cdav.php', 'cdav_feature_settings');
	Zotlabs\Extend\Hook::register('feature_settings_post', 'addon/cdav/cdav.php','cdav_feature_settings_post');

}


function cdav_unload() {
	Zotlabs\Extend\Hook::unregister_by_file('addon/cdav/cdav.php');
}


function cdav_well_known(&$x) {
	if(argv(1) === 'caldav' || argv(1) === 'carddav') {
		goaway(z_root() . '/cdav');
	}
}




function cdav_feature_settings_post(&$b) {

 	if($_POST['cdav-submit']) {

		$channel = \App::get_channel();

		set_pconfig(local_channel(),'cdav','enabled',intval($_POST['cdav_enabled']));
		if(intval($_POST['cdav_enabled'])) {
			$r = q("select * from principals where uri = '%s' limit 1",
				dbesc('principals/' . $channel['channel_address'])
			);
			if($r) {
				$r = q("update principals set email = '%s', displayname = '%s' where uri = '%s' ",
					dbesc($channel['xchan_addr']),
					dbesc($channel['channel_name']),
					dbesc('principals/' . $channel['channel_address'])
				);
			}
			else {
				$r = q("insert into principals ( uri, email, displayname ) values('%s','%s','%s') ",
					dbesc('principals/' . $channel['channel_address']),
					dbesc($channel['xchan_addr']),
					dbesc($channel['channel_name'])
				);
			}
		}
		else {
			// figure out how to safely disable
		}

		info( t('CalDAV/CardDAV Settings saved.') . EOL);
	}
}


function cdav_feature_settings(&$b) {

	$channel = App::get_channel();
	$enabled = get_pconfig(local_channel(),'cdav','enabled');
		
	$sc .= '<div class="settings-block">';
	$sc .= '<div id="cdav-wrapper">';

	$sc .= '<div class="section-content-warning-wrapper">' . t('<strong>WARNING:</strong> Please note that this plugin is in early alpha state and highly experimental. You will likely loose your data at some point!') . '</div>';

	$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
		'$field'    => array('cdav_enabled', t('Enable CalDAV/CardDAV Server for this channel'), $enabled, '', array(t('No'),t('Yes'))),
	));

	$sc .= '<div class="descriptive-text">' . sprintf( t('Your CalDAV resources are located at %s '), 
		z_root() . '/cdav/calendars/' . $channel['channel_address']) . '</div>';

	$sc .= '<div class="descriptive-text">' . sprintf( t('Your CardDAV resources are located at %s '), 
		z_root() . '/cdav/addressbooks/' . $channel['channel_address']) . '</div>';

	$sc .= '</div>';

	$b .= replace_macros(get_markup_template('generic_addon_settings.tpl'), array(
		'$addon'    => array('cdav', t('CalDAV/CardDAV Settings'), '', t('Submit')),
		'$content'  => $sc
	));
}

function cdav_module() {}

function cdav_init(&$a) {

	if(DBA::$dba && DBA::$dba->connected)
		$pdovars = DBA::$dba->pdo_get();
	else
		killme();

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

	/**
	 * This server combines both CardDAV and CalDAV functionality into a single
	 * server. It is assumed that the server runs at the root of a HTTP domain (be
	 * that a domainname-based vhost or a specific TCP port.
	 *
	 * This example also assumes that you're using SQLite and the database has
	 * already been setup (along with the database tables).
	 *
	 * You may choose to use MySQL instead, just change the PDO connection
	 * statement.
	 */

	/**
	 * UTC or GMT is easy to work with, and usually recommended for any
	 * application.
	 */
	date_default_timezone_set('UTC');

	/**
	 * Make sure this setting is turned on and reflect the root url for your WebDAV
	 * server.
	 *
	 * This can be for example the root / or a complete path to your server script.
	 */

	$baseUri = '/cdav';

	/**
	 * Database
	 *
	 */

	$pdo = new \PDO($pdovars[0],$pdovars[1],$pdovars[2]);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	/**
	 * Mapping PHP errors to exceptions.
	 *
	 * While this is not strictly needed, it makes a lot of sense to do so. If an
	 * E_NOTICE or anything appears in your code, this allows SabreDAV to intercept
	 * the issue and send a proper response back to the client (HTTP/1.1 500).
	 */

	function exception_error_handler($errno, $errstr, $errfile, $errline) {
		throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
	}

	set_error_handler("exception_error_handler");

	// Autoloader
	require_once 'vendor/autoload.php';

	/**
	 * The backends. Yes we do really need all of them.
	 *
	 * This allows any developer to subclass just any of them and hook into their
	 * own backend systems.
	 */

	$auth = new \Zotlabs\Storage\BasicAuth();
	$auth->setRealm(ucfirst(\Zotlabs\Lib\System::get_platform_name()) . 'CalDAV/CardDAV');

	//$authBackend      = new \Sabre\DAV\Auth\Backend\PDO($pdo);
	$principalBackend = new \Sabre\DAVACL\PrincipalBackend\PDO($pdo);
	$carddavBackend   = new \Sabre\CardDAV\Backend\PDO($pdo);
	$caldavBackend    = new \Sabre\CalDAV\Backend\PDO($pdo);

	/**
	 * The directory tree
	 *
	 * Basically this is an array which contains the 'top-level' directories in the
	 * WebDAV server.
	 */

	$nodes = [
		// /principals
		new \Sabre\CalDAV\Principal\Collection($principalBackend),
		// /calendars
		new \Sabre\CalDAV\CalendarRoot($principalBackend, $caldavBackend),
		// /addressbook
		new \Sabre\CardDAV\AddressBookRoot($principalBackend, $carddavBackend),
	];

	// The object tree needs in turn to be passed to the server class

	$server = new \Sabre\DAV\Server($nodes);

	if(isset($baseUri))
		$server->setBaseUri($baseUri);

	// Plugins
	$server->addPlugin(new \Sabre\DAV\Auth\Plugin($auth));

//		$browser = new \Zotlabs\Storage\Browser($auth);
//		$auth->setBrowserPlugin($browser);
	
//		$server->addPlugin($browser);


	$server->addPlugin(new \Sabre\DAV\Browser\Plugin());


	$server->addPlugin(new \Sabre\CalDAV\Plugin());
	$server->addPlugin(new \Sabre\CardDAV\Plugin());
	$server->addPlugin(new \Sabre\DAVACL\Plugin());
	$server->addPlugin(new \Sabre\DAV\Sync\Plugin());

	// And off we go!
	$server->exec();

	killme();
}
