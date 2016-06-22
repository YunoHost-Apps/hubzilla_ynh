<?php

namespace Zotlabs\Web;

/**
 *
 * @brief This file includes session related functions.
 *
 * Session management functions. These provide database storage of PHP
 * session info.
 */


class Session {

	private $handler = null;
	private $session_started = false;

	public function init() {

		$gc_probability = 50;

		ini_set('session.gc_probability', $gc_probability);
		ini_set('session.use_only_cookies', 1);
		ini_set('session.cookie_httponly', 1);
	
		/*
		 * Set our session storage functions.
		 */

		$handler = new \Zotlabs\Web\SessionHandler();

		$this->handler = $handler;

		$x = session_set_save_handler($handler,false);
		if(! $x)
			logger('Session save handler initialisation failed.',LOGGER_NORMAL,LOG_ERR);

		// Force cookies to be secure (https only) if this site is SSL enabled. 
		// Must be done before session_start().


		$arr = session_get_cookie_params();

		// Note when setting cookies: set the domain to false which creates a single domain
		// cookie. If you use a hostname it will create a .domain.com wildcard which will
		// have some nasty side effects if you have any other subdomains running hubzilla. 

		session_set_cookie_params(
			((isset($arr['lifetime']))   ? $arr['lifetime'] : 0),
			((isset($arr['path']))      ? $arr['path']     : '/'),
			(($arr['domain'])    ? $arr['domain']   : false),
			((isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') ? true : false),
			((isset($arr['httponly']))  ? $arr['httponly'] : true)
		);

		register_shutdown_function('session_write_close');

	}

	public function start() {
		session_start();
		$this->session_started = true;
	}

	/**
	 * @brief Resets the current session.
	 *
	 * @return void
	 */

	public function nuke() {
		$this->new_cookie(0); // 0 means delete on browser exit
		if($_SESSION && count($_SESSION)) {
			foreach($_SESSION as $k => $v) {
				unset($_SESSION[$k]);
			}
		}
	}

	public function new_cookie($xtime) {

		$newxtime = (($xtime> 0) ? (time() + $xtime) : 0);

		$old_sid = session_id();

		$arr = session_get_cookie_params();

		if($this->handler && $this->session_started) {

			session_regenerate_id(true);

			// force SessionHandler record creation with the new session_id
			// which occurs as a side effect of read()

			$this->handler->read(session_id());
		}
		else 
			logger('no session handler');

		if (x($_COOKIE, 'jsdisabled')) {
			setcookie('jsdisabled', $_COOKIE['jsdisabled'], $newxtime, '/', false,((isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') ? true : false),((isset($arr['httponly']))  ? $arr['httponly'] : true));
		}
		setcookie(session_name(),session_id(),$newxtime, '/', false,((isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') ? true : false),((isset($arr['httponly']))  ? $arr['httponly'] : true));

		$arr = array('expire' => $xtime);
		call_hooks('new_cookie', $arr);

	}

	public function extend_cookie() {

		$arr = session_get_cookie_params();

		// if there's a long-term cookie, extend it

		$xtime = (($_SESSION['remember_me']) ? (60 * 60 * 24 * 365) : 0 );

		if($xtime)
			setcookie(session_name(),session_id(),(time() + $xtime), '/', false,((isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') ? true : false),((isset($arr['httponly']))  ? $arr['httponly'] : true));
		$arr = array('expire' => $xtime);
		call_hooks('extend_cookie', $arr);

	}


	public function return_check() {

		// check a returning visitor against IP changes.
		// If the change results in being blocked from re-entry with the current cookie
		// nuke the session and logout.
		// Returning at all indicates the session is still valid.

		// first check if we're enforcing that sessions can't change IP address
		// @todo what to do with IPv6 addresses

		if($_SESSION['addr'] && $_SESSION['addr'] != $_SERVER['REMOTE_ADDR']) {
			logger('SECURITY: Session IP address changed: ' . $_SESSION['addr'] . ' != ' . $_SERVER['REMOTE_ADDR']);

			$partial1 = substr($_SESSION['addr'], 0, strrpos($_SESSION['addr'], '.')); 
			$partial2 = substr($_SERVER['REMOTE_ADDR'], 0, strrpos($_SERVER['REMOTE_ADDR'], '.')); 

			$paranoia = intval(get_pconfig($_SESSION['uid'], 'system', 'paranoia'));

			if(! $paranoia)
				$paranoia = intval(get_config('system', 'paranoia'));

			switch($paranoia) {
				case 0:
					// no IP checking
					break;
				case 2:
					// check 2 octets
					$partial1 = substr($partial1, 0, strrpos($partial1, '.'));
					$partial2 = substr($partial2, 0, strrpos($partial2, '.'));
					if($partial1 == $partial2)
						break;
				case 1:
					// check 3 octets
					if($partial1 == $partial2)
						break;
				case 3:
				default:
					// check any difference at all
					logger('Session address changed. Paranoid setting in effect, blocking session. '
					. $_SESSION['addr'] . ' != ' . $_SERVER['REMOTE_ADDR']);
					$this->nuke();
					goaway(z_root());
					break;
			}
		}
		return true;
	}

}
