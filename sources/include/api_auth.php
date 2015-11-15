<?php /** @file */

require_once("oauth.php");


/**
 * Simple HTTP Login
 */

function api_login(&$a){
	// login with oauth
	try {
		$oauth = new FKOAuth1();
		$req = OAuthRequest::from_request();

		list($consumer,$token) = $oauth->verify_request($req);

		if (!is_null($token)){
			$oauth->loginUser($token->uid);

			$a->set_oauth_key($consumer->key);

			call_hooks('logged_in', $a->user);
			return;
		}
		echo __file__.__line__.__function__."<pre>"; 
//			var_dump($consumer, $token); 
		die();
	}
	catch(Exception $e) {
		logger(__file__.__line__.__function__."\n".$e);
	}

		
	// workaround for HTTP-auth in CGI mode
	if(x($_SERVER,'REDIRECT_REMOTE_USER')) {
		$userpass = base64_decode(substr($_SERVER["REDIRECT_REMOTE_USER"],6)) ;
		if(strlen($userpass)) {
			list($name, $password) = explode(':', $userpass);
			$_SERVER['PHP_AUTH_USER'] = $name;
			$_SERVER['PHP_AUTH_PW'] = $password;
		}
	}

	if(x($_SERVER,'HTTP_AUTHORIZATION')) {
		$userpass = base64_decode(substr($_SERVER["HTTP_AUTHORIZATION"],6)) ;
		if(strlen($userpass)) {
			list($name, $password) = explode(':', $userpass);
			$_SERVER['PHP_AUTH_USER'] = $name;
			$_SERVER['PHP_AUTH_PW'] = $password;
		}
	}


	if (!isset($_SERVER['PHP_AUTH_USER'])) {
		logger('API_login: ' . print_r($_SERVER,true), LOGGER_DEBUG);
		header('WWW-Authenticate: Basic realm="Red"');
		header('HTTP/1.0 401 Unauthorized');
		die('This api requires login');
	}
		
	// process normal login request
	require_once('include/auth.php');
	$channel_login = 0;
	$record = account_verify_password($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW']);
	if(! $record) {
	        $r = q("select * from channel where channel_address = '%s' limit 1",
		       dbesc($_SERVER['PHP_AUTH_USER'])
			);
        	if ($r) {
			$x = q("select * from account where account_id = %d limit 1",
			       intval($r[0]['channel_account_id'])
				);
			if ($x) {
				$record = account_verify_password($x[0]['account_email'],$_SERVER['PHP_AUTH_PW']);
				if($record)
					$channel_login = $r[0]['channel_id'];
			}
		}
		if(! $record) {	
			logger('API_login failure: ' . print_r($_SERVER,true), LOGGER_DEBUG);
			header('WWW-Authenticate: Basic realm="Red"');
			header('HTTP/1.0 401 Unauthorized');
			die('This api requires login');
		}
	}

	require_once('include/security.php');
	authenticate_success($record);

	if($channel_login)
		change_channel($channel_login);

	$_SESSION['allow_api'] = true;
}
