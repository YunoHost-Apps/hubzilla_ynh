<?php
namespace Zotlabs\Module;
/**
 * @file Zotlabs/Module/Setup.php
 *
 * @brief Controller for the initial setup/installation.
 *
 * @todo This setup module could need some love and improvements.
 */


/**
 * @brief Initialisation for the setup module.
 *
 */
class Setup extends \Zotlabs\Web\Controller {

	private static $install_wizard_pass = 1;

	/**
	 * {@inheritDoc}
	 * @see \\Zotlabs\\Web\\Controller::init()
	 */
	function init() {
		// Ensure that if somebody hasn't read the install documentation and doesn't have all
		// the required modules or has a totally borked shared hosting provider and they can't
		// figure out what the hell is going on - that we at least spit out an error message which
		// we can inquire about when they write to tell us that our software doesn't work.

		// The worst thing we can do at this point is throw a white screen of death and rely on
		// them knowing about servers and php modules and logfiles enough so that we can guess
		// at the source of the problem. As ugly as it may be, we need to throw a technically worded
		// PHP error message in their face. Once installation is complete application errors will
		// throw a white screen because these error messages divulge information which can
		// potentially be useful to hackers.

		error_reporting(E_ERROR | E_WARNING | E_PARSE );
		ini_set('log_errors', '0');
		ini_set('display_errors', '1');

		// $baseurl/setup/testrwrite to test if rewite in .htaccess is working
		if (argc() == 2 && argv(1) == "testrewrite") {
			echo 'ok';
			killme();
		}

		if (x($_POST, 'pass')) {
			$this->install_wizard_pass = intval($_POST['pass']);
		} else {
			$this->install_wizard_pass = 1;
		}
	}

	/**
	 * @brief Handle the actions of the different setup steps.
	 *
	 */
	function post() {

		switch($this->install_wizard_pass) {
			case 1:
			case 2:
				return;
				// implied break;
			case 3:
				$urlpath = \App::get_path();
				$dbhost = trim($_POST['dbhost']);
				$dbport = intval(trim($_POST['dbport']));
				$dbuser = trim($_POST['dbuser']);
				$dbpass = trim($_POST['dbpass']);
				$dbdata = trim($_POST['dbdata']);
				$dbtype = intval(trim($_POST['dbtype']));
				$phpath = trim($_POST['phpath']);
				$adminmail = trim($_POST['adminmail']);
				$siteurl = trim($_POST['siteurl']);
				$server_role = trim($_POST['server_role']);
				if(! $server_role)
					$server_role = 'standard';

				// $siteurl should not have a trailing slash

				$siteurl = rtrim($siteurl,'/');

				require_once('include/dba/dba_driver.php');

				$db = \DBA::dba_factory($dbhost, $dbport, $dbuser, $dbpass, $dbdata, $dbtype, true);

				if(! \DBA::$dba->connected) {
					echo 'Database Connect failed: ' . \DBA::$dba->error;
					killme();
				}
				return;
				// implied break;
			case 4:
				$urlpath = \App::get_path();
				$dbhost = trim($_POST['dbhost']);
				$dbport = intval(trim($_POST['dbport']));
				$dbuser = trim($_POST['dbuser']);
				$dbpass = trim($_POST['dbpass']);
				$dbdata = trim($_POST['dbdata']);
				$dbtype = intval(trim($_POST['dbtype']));
				$phpath = trim($_POST['phpath']);
				$timezone = trim($_POST['timezone']);
				$adminmail = trim($_POST['adminmail']);
				$siteurl = trim($_POST['siteurl']);
				$server_role = trim($_POST['server_role']);
				if(! $server_role)
					$server_role = 'standard';

				if($siteurl != z_root()) {
					$test = z_fetch_url($siteurl."/setup/testrewrite");
					if((! $test['success']) || ($test['body'] != 'ok'))  {
						\App::$data['url_fail'] = true;
						\App::$data['url_error'] = $test['error'];
						return;
					}
				}

				if(! \DBA::$dba->connected) {
					// connect to db
					$db = \DBA::dba_factory($dbhost, $dbport, $dbuser, $dbpass, $dbdata, $dbtype, true);
				}

				if(! \DBA::$dba->connected) {
					echo 'CRITICAL: DB not connected.';
					killme();
				}

				$tpl = get_intltext_template('htconfig.tpl');
				$txt = replace_macros($tpl,array(
					'$dbhost'      => $dbhost,
					'$dbport'      => $dbport,
					'$dbuser'      => $dbuser,
					'$dbpass'      => $dbpass,
					'$dbdata'      => $dbdata,
					'$dbtype'      => $dbtype,
					'$server_role' => $server_role,
					'$timezone'    => $timezone,
					'$siteurl'     => $siteurl,
					'$site_id'     => random_string(),
					'$phpath'      => $phpath,
					'$adminmail' => $adminmail
				));

				$result = file_put_contents('.htconfig.php', $txt);
				if(! $result) {
					\App::$data['txt'] = $txt;
				}

				$errors = $this->load_database($db);

				if($errors)
					\App::$data['db_failed'] = $errors;
				else
					\App::$data['db_installed'] = true;

				return;
				// implied break;
			default:
				break;
		}
	}

	/**
	 * @brief Get output for the setup page.
	 *
	 * Depending on the state we are currently in it returns different content.
	 *
	 * @return string parsed HTML output
	 */

	function get() {

		$o = '';
		$wizard_status = '';
		$install_title = t('$Projectname Server - Setup');

		if(x(\App::$data, 'db_conn_failed')) {
			$this->install_wizard_pass = 2;
			$wizard_status =  t('Could not connect to database.');
		}
		if(x(\App::$data, 'url_fail')) {
			$this->install_wizard_pass = 3;
			$wizard_status =  t('Could not connect to specified site URL. Possible SSL certificate or DNS issue.');
			if(\App::$data['url_error'])
				$wizard_status .= ' ' . \App::$data['url_error'];
		}

		if(x(\App::$data, 'db_create_failed')) {
			$this->install_wizard_pass = 2;
			$wizard_status =  t('Could not create table.');
		}
		$db_return_text = '';
		if(x(\App::$data, 'db_installed')) {
			$txt = '<p style="font-size: 130%;">';
			$txt .= t('Your site database has been installed.') . EOL;
			$db_return_text .= $txt;
		}
		if(x(\App::$data, 'db_failed')) {
			$txt = t('You may need to import the file "install/schema_xxx.sql" manually using a database client.') . EOL;
			$txt .= t('Please see the file "install/INSTALL.txt".') . EOL ."<hr>" ;
			$txt .= "<pre>".\App::$data['db_failed'] . "</pre>". EOL ;
			$db_return_text .= $txt;
		}
		if(\DBA::$dba && \DBA::$dba->connected) {
			$r = q("SELECT COUNT(*) as total FROM account");
			if($r && count($r) && $r[0]['total']) {
				$tpl = get_markup_template('install.tpl');
				return replace_macros($tpl, array(
					'$title' => $install_title,
					'$pass' => '',
					'$status' => t('Permission denied.'),
					'$text' => '',
				));
			}
		}

		if(x(\App::$data, 'txt') && strlen(\App::$data['txt'])) {
			$db_return_text .= $this->manual_config($a);
		}

		if ($db_return_text != "") {
			$tpl = get_markup_template('install.tpl');
			return replace_macros($tpl, array(
				'$title' => $install_title,
				'$pass' => '',
				'$text' => $db_return_text . $this->what_next(),
			));
		}

		switch ($this->install_wizard_pass){
			case 1: { // System check

				$checks = array();

				$this->check_funcs($checks);

				$this->check_htconfig($checks);

				$this->check_store($checks);

				$this->check_smarty3($checks);

				$this->check_keys($checks);

				if (x($_POST, 'phpath'))
					$phpath = notags(trim($_POST['phpath']));

				$this->check_php($phpath, $checks);

				$this->check_phpconfig($checks);

				$this->check_htaccess($checks);

				$checkspassed = array_reduce($checks, "self::check_passed", true);

				$tpl = get_markup_template('install_checks.tpl');
				$o .= replace_macros($tpl, array(
					'$title' => $install_title,
					'$pass' => t('System check'),
					'$checks' => $checks,
					'$passed' => $checkspassed,
					'$see_install' => t('Please see the file "install/INSTALL.txt".'),
					'$next' => t('Next'),
					'$reload' => t('Check again'),
					'$phpath' => $phpath,
					'$baseurl' => z_root(),
				));
				return $o;
			}; break;

			case 2: { // Database config

				$dbhost = ((x($_POST,'dbhost')) ? trim($_POST['dbhost']) : '127.0.0.1');
				$dbuser = trim($_POST['dbuser']);
				$dbport = intval(trim($_POST['dbport']));
				$dbpass = trim($_POST['dbpass']);
				$dbdata = trim($_POST['dbdata']);
				$dbtype = intval(trim($_POST['dbtype']));
				$phpath = trim($_POST['phpath']);
				$adminmail = trim($_POST['adminmail']);
				$siteurl = trim($_POST['siteurl']);

				$tpl = get_markup_template('install_db.tpl');
				$o .= replace_macros($tpl, array(
					'$title' => $install_title,
					'$pass' => t('Database connection'),
					'$info_01' => t('In order to install $Projectname we need to know how to connect to your database.'),
					'$info_02' => t('Please contact your hosting provider or site administrator if you have questions about these settings.'),
					'$info_03' => t('The database you specify below should already exist. If it does not, please create it before continuing.'),

					'$status' => $wizard_status,

					'$dbhost' => array('dbhost', t('Database Server Name'), $dbhost, t('Default is 127.0.0.1')),
					'$dbport' => array('dbport', t('Database Port'), $dbport, t('Communication port number - use 0 for default')),
					'$dbuser' => array('dbuser', t('Database Login Name'), $dbuser, ''),
					'$dbpass' => array('dbpass', t('Database Login Password'), $dbpass, ''),
					'$dbdata' => array('dbdata', t('Database Name'), $dbdata, ''),
					'$dbtype' => array('dbtype', t('Database Type'), $dbtype, '', array( 0=>'MySQL', 1=>'PostgreSQL' )),

					'$adminmail' => array('adminmail', t('Site administrator email address'), $adminmail, t('Your account email address must match this in order to use the web admin panel.')),
					'$siteurl' => array('siteurl', t('Website URL'), z_root(), t('Please use SSL (https) URL if available.')),
					'$lbl_10' => t('Please select a default timezone for your website'),

					'$baseurl' => z_root(),

					'$phpath' => $phpath,

					'$submit' => t('Submit'),
				));
				return $o;
			}; break;
			case 3: { // Site settings
				require_once('include/datetime.php');
				$dbhost = ((x($_POST,'dbhost')) ? trim($_POST['dbhost']) : '127.0.0.1');
				$dbport = intval(trim($_POST['dbuser']));
				$dbuser = trim($_POST['dbuser']);
				$dbpass = trim($_POST['dbpass']);
				$dbdata = trim($_POST['dbdata']);
				$dbtype = intval(trim($_POST['dbtype']));
				$phpath = trim($_POST['phpath']);

				$adminmail = trim($_POST['adminmail']);
				$siteurl = trim($_POST['siteurl']);
				$timezone = ((x($_POST,'timezone')) ? ($_POST['timezone']) : 'America/Los_Angeles');

				$server_roles = [
					'basic'    => t('Basic/Minimal Social Networking'),
					'standard' => t('Standard Configuration (default)'),
					'pro'      => t('Professional')
				];

				$tpl = get_markup_template('install_settings.tpl');
				$o .= replace_macros($tpl, array(
					'$title' => $install_title,
					'$pass' => t('Site settings'),
					'$status' => $wizard_status,

					'$dbhost' => $dbhost,
					'$dbport' => $dbport,
					'$dbuser' => $dbuser,
					'$dbpass' => $dbpass,
					'$dbdata' => $dbdata,
					'$phpath' => $phpath,
					'$dbtype' => $dbtype,

					'$adminmail' => array('adminmail', t('Site administrator email address'), $adminmail, t('Your account email address must match this in order to use the web admin panel.')),

					'$siteurl' => array('siteurl', t('Website URL'), z_root(), t('Please use SSL (https) URL if available.')),

					'$server_role' => array('server_role', t("Server Configuration/Role"), 'standard','',$server_roles),

					'$timezone' => array('timezone', t('Please select a default timezone for your website'), $timezone, '', get_timezones()),

					'$baseurl' => z_root(),

					'$submit' => t('Submit'),
				));
				return $o;
			}; break;
		}
	}

	/**
	 * @brief Add a check result to the array for output.
	 *
	 * @param[in,out] array &$checks array passed to template
	 * @param string $title a title for the check
	 * @param boolean $status
	 * @param boolean $required
	 * @param string $help optional help string
	 */
	function check_add(&$checks, $title, $status, $required, $help = '') {
		$checks[] = array(
			'title'    => $title,
			'status'   => $status,
			'required' => $required,
			'help'     => $help
		);
	}

	/**
	 * @brief Checks the PHP environment.
	 *
	 * @param[in,out] string &$phpath
	 * @param[out] array &$checks
	 */
	function check_php(&$phpath, &$checks) {
		$help = '';

		if(version_compare(PHP_VERSION, '5.5') < 0) {
			$help .= t('PHP version 5.5 or greater is required.');
			$this->check_add($checks, t('PHP version'), false, false, $help);
		}

		if (strlen($phpath)) {
			$passed = file_exists($phpath);
		}
		elseif(function_exists('shell_exec')) {
			if(is_windows())
				$phpath = trim(shell_exec('where php'));
			else
				$phpath = trim(shell_exec('which php'));

			$passed = strlen($phpath);
		}

		if(!$passed) {
			$help .= t('Could not find a command line version of PHP in the web server PATH.'). EOL;
			$help .= t('If you don\'t have a command line version of PHP installed on server, you will not be able to run background polling via cron.') . EOL;
			$help .= EOL . EOL ;
			$tpl = get_markup_template('field_input.tpl');
			$help .= replace_macros($tpl, array(
				'$field' => array('phpath', t('PHP executable path'), $phpath, t('Enter full path to php executable. You can leave this blank to continue the installation.')),
			));
			$phpath = '';
		}

		$this->check_add($checks, t('Command line PHP').($passed?" (<tt>$phpath</tt>)":""), $passed, false, $help);

		if($passed) {
			$str = autoname(8);
			$cmd = "$phpath install/testargs.php $str";
			$help = '';

			if(function_exists('shell_exec'))
				$result = trim(shell_exec($cmd));
			else
				$help .= t('Unable to check command line PHP, as shell_exec() is disabled. This is required.') . EOL;
			$passed2 = (($result == $str) ? true : false);
			if(!$passed2) {
				$help .= t('The command line version of PHP on your system does not have "register_argc_argv" enabled.'). EOL;
				$help .= t('This is required for message delivery to work.');
			}

			$this->check_add($checks, t('PHP register_argc_argv'), $passed, true, $help);
		}
	}

	/**
	 * @brief Some PHP configuration checks.
	 *
	 * @todo Change how we display such informational text. Add more description
	 *       how to change them.
	 *
	 * @param[out] array &$checks
	 */
	function check_phpconfig(&$checks) {
		require_once 'include/environment.php';

		$help = '';

		$result = getPhpiniUploadLimits();
		$help = sprintf(t('Your max allowed total upload size is set to %s. Maximum size of one file to upload is set to %s. You are allowed to upload up to %d files at once.'),
				userReadableSize($result['post_max_size']),
				userReadableSize($result['max_upload_filesize']),
				$result['max_file_uploads']
				);
		$help .= '<br>' . t('You can adjust these settings in the server php.ini file.');

		$this->check_add($checks, t('PHP upload limits'), true, false, $help);
	}

	/**
	 * @brief Check if the openssl implementation can generate keys.
	 *
	 * @param[out] array $checks
	 */
	function check_keys(&$checks) {
		$help = '';
		$res = false;

		if (function_exists('openssl_pkey_new')) {
			$res = openssl_pkey_new(array(
					'digest_alg' => 'sha1',
					'private_key_bits' => 4096,
					'encrypt_key' => false)
			);
		}

		// Get private key

		if (! $res) {
			$help .= t('Error: the "openssl_pkey_new" function on this system is not able to generate encryption keys'). EOL;
			$help .= t('If running under Windows, please see "http://www.php.net/manual/en/openssl.installation.php".');
		}

		$this->check_add($checks, t('Generate encryption keys'), $res, true, $help);
	}

	/**
	 * @brief Check for some PHP functions and modules.
	 *
	 * @param[in,out] array &$checks
	 */
	function check_funcs(&$checks) {
		$ck_funcs = array();

		// add check metadata, the real check is done bit later and return values set
		$this->check_add($ck_funcs, t('libCurl PHP module'), true, true);
		$this->check_add($ck_funcs, t('GD graphics PHP module'), true, true);
		$this->check_add($ck_funcs, t('OpenSSL PHP module'), true, true);
		$this->check_add($ck_funcs, t('PDO database PHP module'), true, true);
		$this->check_add($ck_funcs, t('mb_string PHP module'), true, true);
		$this->check_add($ck_funcs, t('xml PHP module'), true, true);

		if(function_exists('apache_get_modules')){
			if (! in_array('mod_rewrite', apache_get_modules())) {
				$this->check_add($ck_funcs, t('Apache mod_rewrite module'), false, true, t('Error: Apache webserver mod-rewrite module is required but not installed.'));
			} else {
				$this->check_add($ck_funcs, t('Apache mod_rewrite module'), true, true);
			}
		}
		if((! function_exists('exec')) || strstr(ini_get('disable_functions'),'exec')) {
			$this->check_add($ck_funcs, t('exec'), false, true, t('Error: exec is required but is either not installed or has been disabled in php.ini'));
		}
		else {
			$this->check_add($ck_funcs, t('exec'), true, true);
		}
		if((! function_exists('shell_exec')) || strstr(ini_get('disable_functions'),'shell_exec')) {
			$this->check_add($ck_funcs, t('shell_exec'), false, true, t('Error: shell_exec is required but is either not installed or has been disabled in php.ini'));
		}
		else {
			$this->check_add($ck_funcs, t('shell_exec'), true, true);
		}

		if(! function_exists('curl_init')) {
			$ck_funcs[0]['status'] = false;
			$ck_funcs[0]['help'] = t('Error: libCURL PHP module required but not installed.');
		}
		if(! function_exists('imagecreatefromjpeg')) {
			$ck_funcs[1]['status'] = false;
			$ck_funcs[1]['help'] = t('Error: GD graphics PHP module with JPEG support required but not installed.');
		}
		if(! function_exists('openssl_public_encrypt')) {
			$ck_funcs[2]['status'] = false;
			$ck_funcs[2]['help'] = t('Error: openssl PHP module required but not installed.');
		}
		if(! class_exists('PDO')) {
			$ck_funcs[3]['status'] = false;
			$ck_funcs[3]['help'] = t('Error: PDO database PHP module required but not installed.');
		}
		if(! function_exists('mb_strlen')) {
			$ck_funcs[4]['status'] = false;
			$ck_funcs[4]['help'] = t('Error: mb_string PHP module required but not installed.');
		}
		if(! extension_loaded('xml')) {
			$ck_funcs[6]['status'] = false;
			$ck_funcs[6]['help'] = t('Error: xml PHP module required for DAV but not installed.');
		}

		$checks = array_merge($checks, $ck_funcs);
	}

	/**
	 * @brief Check for .htconfig requirements.
	 *
	 * @param[out] array &$checks
	 */
	function check_htconfig(&$checks) {
		$status = true;
		$help = '';

		if( (file_exists('.htconfig.php') && !is_writable('.htconfig.php')) ||
			(!file_exists('.htconfig.php') && !is_writable('.')) ) {
				$status = false;
				$help = t('The web installer needs to be able to create a file called ".htconfig.php" in the top folder of your web server and it is unable to do so.') .EOL;
				$help .= t('This is most often a permission setting, as the web server may not be able to write files in your folder - even if you can.').EOL;
				$help .= t('At the end of this procedure, we will give you a text to save in a file named .htconfig.php in your Red top folder.').EOL;
				$help .= t('You can alternatively skip this procedure and perform a manual installation. Please see the file "install/INSTALL.txt" for instructions.').EOL;
		}

		$this->check_add($checks, t('.htconfig.php is writable'), $status, false, $help);
	}

	/**
	 * @brief Checks for our templating engine Smarty3 requirements.
	 *
	 * @param[out] array &$checks
	 */
	function check_smarty3(&$checks) {
		$status = true;
		$help = '';

		if(! is_writable(TEMPLATE_BUILD_PATH) ) {
			$status = false;
			$help = t('This software uses the Smarty3 template engine to render its web views. Smarty3 compiles templates to PHP to speed up rendering.') .EOL;
			$help .= sprintf( t('In order to store these compiled templates, the web server needs to have write access to the directory %s under the top level web folder.'), TEMPLATE_BUILD_PATH) . EOL;
			$help .= t('Please ensure that the user that your web server runs as (e.g. www-data) has write access to this folder.').EOL;
			$help .= sprintf( t('Note: as a security measure, you should give the web server write access to %s only--not the template files (.tpl) that it contains.'), TEMPLATE_BUILD_PATH) . EOL;
		}

		$this->check_add($checks, sprintf( t('%s is writable'), TEMPLATE_BUILD_PATH), $status, true, $help);
	}

	/**
	 * @brief Check for store directory.
	 *
	 * @param[out] array &$checks
	 */
	function check_store(&$checks) {
		$status = true;
		$help = '';

		@os_mkdir(TEMPLATE_BUILD_PATH, STORAGE_DEFAULT_PERMISSIONS, true);

		if(! is_writable('store')) {
			$status = false;
			$help = t('This software uses the store directory to save uploaded files. The web server needs to have write access to the store directory under the top level web folder') . EOL;
			$help .= t('Please ensure that the user that your web server runs as (e.g. www-data) has write access to this folder.').EOL;
		}

		$this->check_add($checks, t('store is writable'), $status, true, $help);
	}

	/**
	 * @brief Check URL rewrite und SSL certificate.
	 *
	 * @param[out] array &$checks
	 */
	function check_htaccess(&$checks) {
		$a = get_app();
		$status = true;
		$help = '';
		$ssl_error = false;

		$url = z_root() . '/setup/testrewrite';

		if (function_exists('curl_init')){
			$test = z_fetch_url($url);
			if(! $test['success']) {
				if(strstr($url,'https://')) {
					$test = z_fetch_url($url,false,0,array('novalidate' => true));
					if($test['success']) {
						$ssl_error = true;
					}
				}
				else {
					$test = z_fetch_url(str_replace('http://','https://',$url),false,0,array('novalidate' => true));
					if($test['success']) {
						$ssl_error = true;
					}
				}

				if($ssl_error) {
					$help = t('SSL certificate cannot be validated. Fix certificate or disable https access to this site.') . EOL;
					$help .= t('If you have https access to your website or allow connections to TCP port 443 (the https: port), you MUST use a browser-valid certificate. You MUST NOT use self-signed certificates!') . EOL;
					$help .= t('This restriction is incorporated because public posts from you may for example contain references to images on your own hub.') . EOL;
					$help .= t('If your certificate is not recognized, members of other sites (who may themselves have valid certificates) will get a warning message on their own site complaining about security issues.') . EOL;
					$help .= t('This can cause usability issues elsewhere (not just on your own site) so we must insist on this requirement.') .EOL;
					$help .= t('Providers are available that issue free certificates which are browser-valid.'). EOL;

					$help .= t('If you are confident that the certificate is valid and signed by a trusted authority, check to see if you have failed to install an intermediate cert. These are not normally required by browsers, but are required for server-to-server communications.') . EOL;

					$this->check_add($checks, t('SSL certificate validation'), false, true, $help);
				}
			}

			if ((! $test['success']) || ($test['body'] != "ok")) {
				$status = false;
				$help = t('Url rewrite in .htaccess is not working. Check your server configuration.'.'Test: '.var_export($test,true));
			}

			$this->check_add($checks, t('Url rewrite is working'), $status, true, $help);
		} else {
			// cannot check modrewrite if libcurl is not installed
		}
	}

	/**
	 * @brief
	 *
	 * @param App &$a
	 * @return string with paresed HTML
	 */
	function manual_config(&$a) {
		$data = htmlspecialchars(\App::$data['txt'], ENT_COMPAT, 'UTF-8');
		$o = t('The database configuration file ".htconfig.php" could not be written. Please use the enclosed text to create a configuration file in your web server root.');
		$o .= "<textarea rows=\"24\" cols=\"80\" >$data</textarea>";

		return $o;
	}

	function load_database_rem($v, $i){
		$l = trim($i);
		if (strlen($l)>1 && ($l[0]=="-" || ($l[0]=="/" && $l[1]=="*"))){
			return $v;
		} else  {
			return $v."\n".$i;
		}
	}


	function load_database($db) {
		$str = file_get_contents(\DBA::$dba->get_install_script());
		$arr = explode(';', $str);
		$errors = false;
		foreach($arr as $a) {
			if(strlen(trim($a))) {
				$r = dbq(trim($a));
				if(! $r) {
					$errors .=  t('Errors encountered creating database tables.') . $a . EOL;
				}
			}
		}

		return $errors;
	}

	/**
	 * @brief
	 *
	 * @return string with parsed HTML
	 */
	function what_next() {
		$a = get_app();
		// install the standard theme
		set_config('system', 'allowed_themes', 'redbasic');

		// Set a lenient list of ciphers if using openssl. Other ssl engines
		// (e.g. NSS used in RedHat) require different syntax, so hopefully
		// the default curl cipher list will work for most sites. If not,
		// this can set via config. Many distros are now disabling RC4,
		// but many existing sites still use it and are unable to change it.
		// We do not use SSL for encryption, only to protect session cookies.
		// z_fetch_url() is also used to import shared links and other content
		// so in theory most any cipher could show up and we should do our best
		// to make the content available rather than tell folks that there's a
		// weird SSL error which they can't do anything about. This does not affect
		// the SSL server, but is only a client negotiation to find something workable.
		// Hence it will not make your system susceptible to POODL or other nasties.

		$x = curl_version();
		if(stristr($x['ssl_version'],'openssl'))
			set_config('system','curl_ssl_ciphers','ALL:!eNULL');

		// Create a system channel
		require_once ('include/channel.php');
		create_sys_channel();

		$baseurl = z_root();
		return
			t('<h1>What next</h1>')
			."<p>".t('IMPORTANT: You will need to [manually] setup a scheduled task for the poller.')
			.t('Please see the file "install/INSTALL.txt".')
			."</p><p>"
			.t("Go to your new hub <a href='$baseurl/register'>registration page</a> and register as new member. Remember to use the same email you have entered as administrator email. This will allow you to enter the site admin panel.")
			."</p>";
	}

	/**
	 * @brief
	 *
	 * @param unknown $v
	 * @param array $c
	 * @return array
	 */
	static private function check_passed($v, $c) {
		if ($c['required'])
			$v = $v && $c['status'];

		return $v;
	}

}
