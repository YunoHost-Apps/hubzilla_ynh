<?php /** @file */

/** 
 * OAuth server
 * Based on oauth2-php <http://code.google.com/p/oauth2-php/>
 * 
 */

define('REQUEST_TOKEN_DURATION', 300);
define('ACCESS_TOKEN_DURATION', 31536000);

require_once("library/OAuth1.php");

//require_once("library/oauth2-php/lib/OAuth2.inc");

class ZotOAuth1DataStore extends OAuth1DataStore {

	function gen_token(){
		return md5(base64_encode(pack('N6', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), uniqid())));
	}
	
	function lookup_consumer($consumer_key) {
		logger('consumer_key: ' . $consumer_key, LOGGER_DEBUG);

		$r = q("SELECT client_id, pw, redirect_uri FROM clients WHERE client_id = '%s'",
			dbesc($consumer_key)
		);

		if($r) {
			App::set_oauth_key($consumer_key);
			return new OAuth1Consumer($r[0]['client_id'],$r[0]['pw'],$r[0]['redirect_uri']);
		}
		return null;
	}

	function lookup_token($consumer, $token_type, $token) {

		logger(__function__.":".$consumer.", ". $token_type.", ".$token, LOGGER_DEBUG);

		$r = q("SELECT id, secret, auth_scope, expires, uid  FROM tokens WHERE client_id = '%s' AND auth_scope = '%s' AND id = '%s'",
			dbesc($consumer->key),
			dbesc($token_type),
			dbesc($token)
		);

		if (count($r)){
			$ot=new OAuth1Token($r[0]['id'],$r[0]['secret']);
			$ot->scope=$r[0]['auth_scope'];
			$ot->expires = $r[0]['expires'];
			$ot->uid = $r[0]['uid'];
			return $ot;
		}
		return null;
	}

	function lookup_nonce($consumer, $token, $nonce, $timestamp) {

		$r = q("SELECT id, secret FROM tokens WHERE client_id = '%s' AND id = '%s' AND expires = %d",
			dbesc($consumer->key),
			dbesc($nonce),
			intval($timestamp)
		);

		if (count($r))
			return new OAuth1Token($r[0]['id'],$r[0]['secret']);
		return null;
	}

	function new_request_token($consumer, $callback = null) {

		logger(__function__.":".$consumer.", ". $callback, LOGGER_DEBUG);

		$key = $this->gen_token();
		$sec = $this->gen_token();
		
		if ($consumer->key){
			$k = $consumer->key;
		} else {
			$k = $consumer;
		}

		$r = q("INSERT INTO tokens (id, secret, client_id, auth_scope, expires) VALUES ('%s','%s','%s','%s', %d)",
				dbesc($key),
				dbesc($sec),
				dbesc($k),
				'request',
				time()+intval(REQUEST_TOKEN_DURATION));

		if(! $r)
			return null;
		return new OAuth1Token($key,$sec);
	}

	function new_access_token($token, $consumer, $verifier = null) {

		logger(__function__.":".$token.", ". $consumer.", ". $verifier, LOGGER_DEBUG);
    
		// return a new access token attached to this consumer
		// for the user associated with this token if the request token
		// is authorized
		// should also invalidate the request token
	
		$ret=Null;
	
		// get user for this verifier
		$uverifier = get_config("oauth", $verifier);
		logger(__function__.":".$verifier.",".$uverifier, LOGGER_DEBUG);
		if (is_null($verifier) || ($uverifier!==false)) {
		
			$key = $this->gen_token();
			$sec = $this->gen_token();

			$r = q("INSERT INTO tokens (id, secret, client_id, auth_scope, expires, uid) VALUES ('%s','%s','%s','%s', %d, %d)",
				dbesc($key),
				dbesc($sec),
				dbesc($consumer->key),
				'access',
				time()+intval(ACCESS_TOKEN_DURATION),
				intval($uverifier));

			if ($r)
				$ret = new OAuth1Token($key,$sec);		
		}
		
		
		q("DELETE FROM tokens WHERE id='%s'", $token->key);
	
	
		if (!is_null($ret) && $uverifier!==false) {
			del_config("oauth", $verifier);
	
			//	$apps = get_pconfig($uverifier, "oauth", "apps");
			//	if ($apps===false) $apps=array();
			//  $apps[] = $consumer->key;
			// set_pconfig($uverifier, "oauth", "apps", $apps);
		}
		return $ret;
	}
}

class ZotOAuth1 extends OAuth1Server {

	function __construct() {
		parent::__construct(new ZotOAuth1DataStore());
		$this->add_signature_method(new OAuth1SignatureMethod_PLAINTEXT());
		$this->add_signature_method(new OAuth1SignatureMethod_HMAC_SHA1());
	}
	
	function loginUser($uid){

		logger("ZotOAuth1::loginUser $uid");

		$r = q("SELECT * FROM channel WHERE channel_id = %d LIMIT 1",
			intval($uid)
		);
		if(count($r)){
			$record = $r[0];
		} else {
			logger('ZotOAuth1::loginUser failure: ' . print_r($_SERVER,true), LOGGER_DEBUG);
			header('HTTP/1.0 401 Unauthorized');
			echo('This api requires login');
			killme();
		}

		$_SESSION['uid'] = $record['channel_id'];
		$_SESSION['addr'] = $_SERVER['REMOTE_ADDR'];

		$x = q("select * from account where account_id = %d limit 1",
			intval($record['channel_account_id'])
		);
		if($x) {
			require_once('include/security.php');
			authenticate_success($x[0],null,true,false,true,true);
			$_SESSION['allow_api'] = true;
		}
	}
	
}

/*
 *

 not yet used

class FKOAuth2 extends OAuth2 {

	private function db_secret($client_secret){
		return hash('whirlpool',$client_secret);
	}

	public function addClient($client_id, $client_secret, $redirect_uri) {
		$client_secret = $this->db_secret($client_secret);
		$r = q("INSERT INTO clients (client_id, pw, redirect_uri) VALUES ('%s', '%s', '%s')",
			dbesc($client_id),
			dbesc($client_secret),
			dbesc($redirect_uri)
		);
		  
		return $r;
	}

	protected function checkClientCredentials($client_id, $client_secret = NULL) {
		$client_secret = $this->db_secret($client_secret);
		
		$r = q("SELECT pw FROM clients WHERE client_id = '%s'",
			dbesc($client_id));

		if ($client_secret === NULL)
			return $result !== FALSE;

		return $result["client_secret"] == $client_secret;
	}

	protected function getRedirectUri($client_id) {
		$r = q("SELECT redirect_uri FROM clients WHERE client_id = '%s'",
				dbesc($client_id));
		if ($r === FALSE)
			return FALSE;

		return isset($r[0]["redirect_uri"]) && $r[0]["redirect_uri"] ? $r[0]["redirect_uri"] : NULL;
	}

	protected function getAccessToken($oauth_token) {
		$r = q("SELECT client_id, expires, scope FROM tokens WHERE id = '%s'",
				dbesc($oauth_token));
	
		if (count($r))
			return $r[0];
		return null;
	}


	
	protected function setAccessToken($oauth_token, $client_id, $expires, $scope = NULL) {
		$r = q("INSERT INTO tokens (id, client_id, expires, scope) VALUES ('%s', '%s', %d, '%s')",
				dbesc($oauth_token),
				dbesc($client_id),
				intval($expires),
				dbesc($scope));
				
		return $r;
	}

	protected function getSupportedGrantTypes() {
		return array(
		  OAUTH2_GRANT_TYPE_AUTH_CODE,
		);
	}


	protected function getAuthCode($code) {
		$r = q("SELECT id, client_id, redirect_uri, expires, auth_scope FROM auth_codes WHERE id = '%s'",
				dbesc($code));
		
		if (count($r))
			return $r[0];
		return null;
	}

	protected function setAuthCode($code, $client_id, $redirect_uri, $expires, $scope = NULL) {
		$r = q("INSERT INTO auth_codes 
					(id, client_id, redirect_uri, expires, auth_scope) VALUES 
					('%s', '%s', '%s', %d, '%s')",
				dbesc($code),
				dbesc($client_id),
				dbesc($redirect_uri),
				intval($expires),
				dbesc($scope));
		return $r;	  
	}	
	
}
*/
