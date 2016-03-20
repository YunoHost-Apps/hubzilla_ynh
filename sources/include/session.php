<?php

/**
 * @file include/session.php
 *
 * @brief This file includes session related functions.
 *
 * Session management functions. These provide database storage of PHP
 * session info.
 */

$session_exists = 0;
$session_expire = 180000;


/**
 * @brief Resets the current session.
 *
 * @return void
 */

function nuke_session() {
	new_cookie(0); // 0 means delete on browser exit

	unset($_SESSION['authenticated']);
	unset($_SESSION['account_id']);
	unset($_SESSION['uid']);
	unset($_SESSION['visitor_id']);
	unset($_SESSION['administrator']);
	unset($_SESSION['cid']);
	unset($_SESSION['theme']);
	unset($_SESSION['mobile_theme']);
	unset($_SESSION['show_mobile']);
	unset($_SESSION['page_flags']);
	unset($_SESSION['delegate']);
	unset($_SESSION['delegate_channel']);
	unset($_SESSION['my_url']);
	unset($_SESSION['my_address']);
	unset($_SESSION['addr']);
	unset($_SESSION['return_url']);
	unset($_SESSION['remote_service_class']);
	unset($_SESSION['remote_hub']);
}



function new_cookie($time) {
	$old_sid = session_id();

	// ??? This shouldn't have any effect if called after session_start()
	// We probably need to set the session expiration and change the PHPSESSID cookie.

	session_set_cookie_params($time);
	session_regenerate_id(false);

	q("UPDATE session SET sid = '%s' WHERE sid = '%s'",
			dbesc(session_id()),
			dbesc($old_sid)
	);

	if (x($_COOKIE, 'jsAvailable')) {
		if ($time) {
			$expires = time() + $time;
		} else {
			$expires = 0;
		}
		setcookie('jsAvailable', $_COOKIE['jsAvailable'], $expires);
	}
}


function ref_session_open ($s, $n) {
	return true;
}


function ref_session_read ($id) {
	global $session_exists;
	if(x($id))
		$r = q("SELECT `data` FROM `session` WHERE `sid`= '%s'", dbesc($id));

	if(count($r)) {
		$session_exists = true;
		return $r[0]['data'];
	}

	return '';
}


function ref_session_write ($id, $data) {
	global $session_exists, $session_expire;

	if(! $id || ! $data) {
		return false;
	}

	$expire = time() + $session_expire;
	$default_expire = time() + 300;

	if($session_exists) {
		q("UPDATE `session`
				SET `data` = '%s', `expire` = '%s' WHERE `sid` = '%s'",
				dbesc($data),
				dbesc($expire),
				dbesc($id)
		);
	} else {
		q("INSERT INTO `session` (sid, expire, data) values ('%s', '%s', '%s')",
				//SET `sid` = '%s', `expire` = '%s', `data` = '%s'",
				dbesc($id),
				dbesc($default_expire),
				dbesc($data)
		);
	}

	return true;
}


function ref_session_close() {
	return true;
}


function ref_session_destroy ($id) {
	q("DELETE FROM `session` WHERE `sid` = '%s'", dbesc($id));
	return true;
}


function ref_session_gc($expire) {
	q("DELETE FROM session WHERE expire < %d", dbesc(time()));
	return true;
}

$gc_probability = 50;

ini_set('session.gc_probability', $gc_probability);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);

/*
 * Set our session storage functions.
 */

session_set_save_handler(
		'ref_session_open',
		'ref_session_close',
		'ref_session_read',
		'ref_session_write',
		'ref_session_destroy',
		'ref_session_gc'
);


   // Force cookies to be secure (https only) if this site is SSL enabled. Must be done before session_start().

    if(intval(get_app()->config['system']['ssl_cookie_protection'])) {
        $arr = session_get_cookie_params();
        session_set_cookie_params(
            ((isset($arr['lifetime']))  ? $arr['lifetime'] : 0),
            ((isset($arr['path']))      ? $arr['path']     : '/'),
            ((isset($arr['domain']))    ? $arr['domain']   : get_app()->get_hostname()),
            ((isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') ? true : false),
            ((isset($arr['httponly']))  ? $arr['httponly'] : true));
    }