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
 * If system.authlog is set a log entry will be added for failed login
 * attempts.
 *
 * @param string $login
 *  The login to verify (channel address, account email or guest login token).
 * @param string $pass
 *  The provided password to verify.
 * @return array|null
 *  Returns account record on success, null on failure.
 *  The return array is dependent on the login mechanism.
 *    $ret['account'] will be set if either an email or channel address validation was successful (local login).
 *    $ret['channel'] will be set if a channel address validation was successful.
 *    $ret['xchan'] will be set if a guest access token validation was successful. 
 *   Keys will exist for invalid return arrays but will be set to null. 
 *   This function does not perform a login. It merely validates systems passwords and tokens.  
 *
 */

function account_verify_password($login, $pass) {

	$ret = [ 'account' => null, 'channel' => null, 'xchan' => null ];

	$email_verify = get_config('system', 'verify_email');
	$register_policy = get_config('system', 'register_policy');

	if(! $login)
		return null;

	$account = null;
	$channel = null;
	$xchan   = null;

	if(! strpos($login,'@')) {
		$channel = channelx_by_nick($login);
		if(! $channel) {
			$x = q("select * from atoken where atoken_name = '%s' and atoken_token = '%s' limit 1",
				dbesc($login),
				dbesc($pass)
			);
			if($x) {
				$ret['xchan'] = atoken_xchan($x[0]);
				atoken_create_xchan($ret['xchan']);
				return $ret;
			}
		}
	}
	if($channel) {
		$where = " where account_id = " . intval($channel['channel_account_id']) . " ";
	}
	else {
		$where = " where account_email = '" . dbesc($login) . "' ";
	}

	$a = q("select * from account $where");
	if(! $a) {
		return null;
	}

	$account = $a[0];

	// Currently we only verify email address if there is an open registration policy.
	// This isn't because of any policy - it's because the workflow gets too complicated if 
	// you have to verify the email and then go through the account approval workflow before
	// letting them login.

	if(($email_verify) && ($register_policy == REGISTER_OPEN) && ($account['account_flags'] & ACCOUNT_UNVERIFIED)) {
		logger('email verification required for ' . $login);
		return null;
	}

	if(($account['account_flags'] == ACCOUNT_OK) 
		&& (hash('whirlpool',$account['account_salt'] . $pass) === $account['account_password'])) {
		logger('password verified for ' . $login);
		$ret['account'] = $account;
		if($channel)
			$ret['channel'] = $channel;
		return $ret;
	}

	$error = 'password failed for ' . $login;
	logger($error);

	if($account['account_flags'] & ACCOUNT_UNVERIFIED)
		logger('Account is unverified. account_flags = ' . $account['account_flags']);
	if($account['account_flags'] & ACCOUNT_BLOCKED)
		logger('Account is blocked. account_flags = ' . $account['account_flags']);
	if($account['account_flags'] & ACCOUNT_EXPIRED)
		logger('Account is expired. account_flags = ' . $account['account_flags']);
	if($account['account_flags'] & ACCOUNT_REMOVED)
		logger('Account is removed. account_flags = ' . $account['account_flags']);
	if($account['account_flags'] & ACCOUNT_PENDING)
		logger('Account is pending. account_flags = ' . $account['account_flags']);

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
				authenticate_success($x[0], null, true, true);
			}
		}
		if(array_key_exists('atoken',$_SESSION)) {
			$y = q("select * from atoken where atoken_id = %d limit 1",
				intval($_SESSION['atoken'])
			);
			if($y)
				$r = array(atoken_xchan($y[0]));
		}
		else {
			$r = q("select * from xchan left join hubloc on xchan_hash = hubloc_hash where xchan_hash = '%s' limit 1",
				dbesc($_SESSION['visitor_id'])
			);
		}
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
			$ch = (($_SESSION['uid']) ? channelx_by_n($_SESSION['uid']) : null);
			authenticate_success($r[0], null, $ch, false, false, $login_refresh);
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

		$atoken  = null;
		$account = null;

		if(($addon_auth['authenticated']) && (count($addon_auth['user_record']))) {
			$account = $addon_auth['user_record'];
		}
		else {
			$verify = account_verify_password($_POST['username'], $_POST['password']);
			if($verify) {
				$atoken  = $verify['xchan'];
				$channel = $verify['channel'];
				$account = App::$account = $verify['account'];
			}

			if(App::$account) {
				$_SESSION['account_id'] = App::$account['account_id'];
			}
			elseif($atoken) {
				atoken_login($atoken);
			}
			else {
				notice( t('Failed authentication') . EOL);
			}
		}

		if(! ($account || $atoken)) {
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
		if(! $atoken)
			authenticate_success($account,$channel,true, true);
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
