<?php
/**
 * @file include/auth.php
 * @brief Functions and inline functionality for authentication.
 *
 * This file provides some functions for authentication handling and inline
 * functionality. Look for auth parameters or re-validate an existing session
 * also handles logout.
 * Also provides a function for OpenID identiy matching.
 */

require_once('include/api_auth.php');
require_once('include/security.php');


/**
 * @brief Verify login credentials.
 *
 * If system <i>authlog</i> is set a log entry will be added for failed login
 * attempts.
 *
 * @param string $email
 *  The email address to verify.
 * @param string $pass
 *  The provided password to verify.
 * @return array|null
 *  Returns account record on success, null on failure.
 */
function account_verify_password($email, $pass) {

	$email_verify = get_config('system', 'verify_email');
	$register_policy = get_config('system', 'register_policy');

	// Currently we only verify email address if there is an open registration policy.
	// This isn't because of any policy - it's because the workflow gets too complicated if 
	// you have to verify the email and then go through the account approval workflow before
	// letting them login.

	if(($email_verify) && ($register_policy == REGISTER_OPEN) && ($record['account_flags'] & ACCOUNT_UNVERIFIED))
		return null;

	$r = q("select * from account where account_email = '%s'",
		dbesc($email)
	);
	if(! ($r && count($r)))
		return null;

	foreach($r as $record) {
		if(($record['account_flags'] == ACCOUNT_OK)
			&& (hash('whirlpool', $record['account_salt'] . $pass) === $record['account_password'])) {
			logger('password verified for ' . $email);
			return $record;
		}
	}
	$error = 'password failed for ' . $email;
	logger($error);

	if($record['account_flags'] & ACCOUNT_UNVERIFIED)
		logger('Account is unverified. account_flags = ' . $record['account_flags']);
	if($record['account_flags'] & ACCOUNT_BLOCKED)
		logger('Account is blocked. account_flags = ' . $record['account_flags']);
	if($record['account_flags'] & ACCOUNT_EXPIRED)
		logger('Account is expired. account_flags = ' . $record['account_flags']);
	if($record['account_flags'] & ACCOUNT_REMOVED)
		logger('Account is removed. account_flags = ' . $record['account_flags']);
	if($record['account_flags'] & ACCOUNT_PENDING)
		logger('Account is pending. account_flags = ' . $record['account_flags']);

	log_failed_login($error);

	return null;
}

/**
 * @brief Log failed logins to a separate auth log.
 *
 * Can be used to reduce overhead for server side intrusion prevention, like
 * parse the authlog file with something like fail2ban, OSSEC, etc.
 *
 * @param string $errormsg
 *  Error message to display for failed login.
 */
function log_failed_login($errormsg) {
	$authlog = get_config('system', 'authlog');
	if ($authlog)
		@file_put_contents($authlog, datetime_convert() . ':' . session_id() . ' ' . $errormsg . PHP_EOL, FILE_APPEND);
}

/**
 * Inline - not a function
 * look for auth parameters or re-validate an existing session
 * also handles logout
 */

if((isset($_SESSION)) && (x($_SESSION, 'authenticated')) &&
	((! (x($_POST, 'auth-params'))) || ($_POST['auth-params'] !== 'login'))) {

	// process a logout request

	if(((x($_POST, 'auth-params')) && ($_POST['auth-params'] === 'logout')) || (App::$module === 'logout')) {
		// process logout request
		$args = array('channel_id' => local_channel());
		call_hooks('logging_out', $args);
		App::$session->nuke();
		info( t('Logged out.') . EOL);
		goaway(z_root());
	}

	// re-validate a visitor, optionally invoke "su" if permitted to do so

	if(x($_SESSION, 'visitor_id') && (! x($_SESSION, 'uid'))) {
		// if our authenticated guest is allowed to take control of the admin channel, make it so.
		$admins = get_config('system', 'remote_admin');
		if($admins && is_array($admins) && in_array($_SESSION['visitor_id'], $admins)) {
			$x = q("select * from account where account_email = '%s' and account_email != '' and ( account_flags & %d )>0 limit 1",
				dbesc(get_config('system', 'admin_email')),
				intval(ACCOUNT_ROLE_ADMIN)
			);
			if($x) {
				App::$session->new_cookie(60 * 60 * 24); // one day
				$_SESSION['last_login_date'] = datetime_convert();
				unset($_SESSION['visitor_id']); // no longer a visitor
				authenticate_success($x[0], true, true);
			}
		}

		$r = q("select * from xchan left join hubloc on xchan_hash = hubloc_hash where xchan_hash = '%s' limit 1",
			dbesc($_SESSION['visitor_id'])
		);
		if($r) {
			App::set_observer($r[0]);
		}
		else {
			unset($_SESSION['visitor_id']);
			unset($_SESSION['authenticated']);
		}
		App::set_groups(init_groups_visitor($_SESSION['visitor_id']));
	}

	// already logged in user returning

	if(x($_SESSION, 'uid') || x($_SESSION, 'account_id')) {

		App::$session->return_check();

		$r = q("select * from account where account_id = %d limit 1",
			intval($_SESSION['account_id'])
		);

		if(($r) && (($r[0]['account_flags'] == ACCOUNT_OK) || ($r[0]['account_flags'] == ACCOUNT_UNVERIFIED))) {
			App::$account = $r[0];
			$login_refresh = false;
			if(! x($_SESSION,'last_login_date')) {
				$_SESSION['last_login_date'] = datetime_convert('UTC','UTC');
			}
			if(strcmp(datetime_convert('UTC','UTC','now - 12 hours'), $_SESSION['last_login_date']) > 0 ) {
				$_SESSION['last_login_date'] = datetime_convert();
				App::$session->extend_cookie();
				$login_refresh = true;
			}
			authenticate_success($r[0], false, false, false, $login_refresh);
		}
		else {
			$_SESSION['account_id'] = 0;
			App::$session->nuke();
			goaway(z_root());
		}
	} // end logged in user returning
}
else {

	if(isset($_SESSION)) {
		App::$session->nuke();
	}

	// handle a fresh login request

	if((x($_POST, 'password')) && strlen($_POST['password']))
		$encrypted = hash('whirlpool', trim($_POST['password']));

	if((x($_POST, 'auth-params')) && $_POST['auth-params'] === 'login') {

		$record = null;

		$addon_auth = array(
			'username' => trim($_POST['username']), 
			'password' => trim($_POST['password']),
			'authenticated' => 0,
			'user_record' => null
		);

		/**
		 *
		 * A plugin indicates successful login by setting 'authenticated' to non-zero value and returning a user record
		 * Plugins should never set 'authenticated' except to indicate success - as hooks may be chained
		 * and later plugins should not interfere with an earlier one that succeeded.
		 *
		 */

		call_hooks('authenticate', $addon_auth);

		if(($addon_auth['authenticated']) && (count($addon_auth['user_record']))) {
			$record = $addon_auth['user_record'];
		}
		else {
			$record = App::$account = account_verify_password($_POST['username'], $_POST['password']);

			if(App::$account) {
				$_SESSION['account_id'] = App::$account['account_id'];
			}
			else {
				notice( t('Failed authentication') . EOL);
			}

			logger('authenticate: ' . print_r(App::$account, true), LOGGER_ALL);
		}

		if((! $record) || (! count($record))) {
			$error = 'authenticate: failed login attempt: ' . notags(trim($_POST['username'])) . ' from IP ' . $_SERVER['REMOTE_ADDR'];
			logger($error); 
			// Also log failed logins to a separate auth log to reduce overhead for server side intrusion prevention
			$authlog = get_config('system', 'authlog');
			if ($authlog)
				@file_put_contents($authlog, datetime_convert() . ':' . session_id() . ' ' . $error . "\n", FILE_APPEND);

			notice( t('Login failed.') . EOL );
			goaway(z_root() . '/login');
		}

		// If the user specified to remember the authentication, then change the cookie
		// to expire after one year (the default is when the browser is closed).
		// If the user did not specify to remember, change the cookie to expire when the
		// browser is closed. The reason this is necessary is because if the user
		// specifies to remember, then logs out and logs back in without specifying to
		// remember, the old "remember" cookie may remain and prevent the session from
		// expiring when the browser is closed.
		//
		// It seems like I should be able to test for the old cookie, but for some reason when
		// I read the lifetime value from session_get_cookie_params(), I always get '0'
		// (i.e. expire when the browser is closed), even when there's a time expiration
		// on the cookie

		if($_POST['remember_me']) {
			$_SESSION['remember_me'] = 1;
			App::$session->new_cookie(31449600); // one year
		}
		else {
			$_SESSION['remember_me'] = 0;
			App::$session->new_cookie(0); // 0 means delete on browser exit
		}

		// if we haven't failed up this point, log them in.

		$_SESSION['last_login_date'] = datetime_convert();
		authenticate_success($record, true, true);
	}
}


/**
 * @brief Returns the channel_id for a given openid_identity.
 *
 * Queries the values from pconfig configuration for the given openid_identity
 * and returns the corresponding channel_id.
 *
 * @fixme How do we prevent that an OpenID identity is used more than once?
 * 
 * @param string $authid
 *  The given openid_identity
 * @return int|bool
 *  Return channel_id from pconfig or false.
 */
function match_openid($authid) {
	// Query the uid/channel_id from pconfig for a given value.
	$r = q("SELECT uid FROM pconfig WHERE cat = 'system' AND k = 'openid' AND v = '%s' LIMIT 1",
		dbesc($authid)
	);
	if($r)
		return $r[0]['uid'];
	return false;
}
