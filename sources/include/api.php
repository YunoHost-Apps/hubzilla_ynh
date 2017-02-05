<?php /** @file */

require_once("include/bbcode.php");
require_once("include/datetime.php");
require_once("include/conversation.php");
require_once("include/oauth.php");
require_once("include/html2plain.php");
require_once('include/security.php');
require_once('include/photos.php');
require_once('include/items.php');
require_once('include/attach.php');
require_once('include/api_auth.php');
require_once('include/api_zot.php');

	/*
	 *
	 * Hubzilla API. 
	 *
	 */


	$API = array();

	$called_api = Null;

	// All commands which require authentication accept a "channel" parameter
	// which is the left hand side of the channel address/nickname.
	// If provided, the desired channel is selected before carrying out the command.
	// If not provided, the default channel associated with the account is used.   
	// If channel selection fails, the API command requiring login will fail. 

	function api_user() {
		$aid = get_account_id();
		$channel = App::get_channel();
		
		if(($aid) && (x($_REQUEST,'channel'))) {

			// Only change channel if it is different than the current channel

			if($channel && x($channel,'channel_address') && $channel['channel_address'] != $_REQUEST['channel']) {
				$c = q("select channel_id from channel where channel_address = '%s' and channel_account_id = %d limit 1",
					dbesc($_REQUEST['channel']),
					intval($aid)
				);
				if((! $c) || (! change_channel($c[0]['channel_id'])))
					return false;
			}
		}			
		if ($_SESSION['allow_api'])
			return local_channel();
		return false;
	}


	function api_date($str){
		//Wed May 23 06:01:13 +0000 2007
		return datetime_convert('UTC', 'UTC', $str, 'D M d H:i:s +0000 Y' );
	}


	function api_register_func($path, $func, $auth = false) {
		\Zotlabs\Lib\Api_router::register($path,$func,$auth);
	}

	
	/**************************
	 *  MAIN API ENTRY POINT  *
	 **************************/

	function api_call(){

		$p    = App::$cmd;
		$type = null;

		if(strrpos($p,'.')) {
			$type = substr($p,strrpos($p,'.')+1);
			if(strpos($type,'/') === false) {
				$p = substr($p,0,strrpos($p,'.'));
				// recalculate App argc,argv since we just extracted the type from it
				App::$argv = explode('/',$p);
				App::$argc = count(App::$argv);
			}
		}

		if((! $type) || (! in_array($type, [ 'json', 'xml', 'rss', 'as', 'atom' ])))
			$type = 'json';

		$info = \Zotlabs\Lib\Api_router::find($p);

		if(in_array($type, [ 'rss', 'atom', 'as' ])) {
			// These types no longer supported.
			$info = false;
		}

		logger('API info: ' . $p . ' type: ' . $type . ' ' . print_r($info,true), LOGGER_DEBUG,LOG_INFO);

		if($info) {

			if ($info['auth'] === true && api_user() === false) {
					api_login($a);
			}

			load_contact_links(api_user());

			$channel = App::get_channel();

			logger('API call for ' . $channel['channel_name'] . ': ' . App::$query_string);
			logger('API parameters: ' . print_r($_REQUEST,true));

			$r = call_user_func($info['func'],$type);

			if($r === false) 
				return;

			switch($type) {
				case 'xml':
					header ('Content-Type: text/xml');
					return $r; 
					break;
				case 'json':
					header ('Content-Type: application/json');
					// Lookup JSONP to understand these lines. They provide cross-domain AJAX ability.
					if ($_GET['callback'])
						$r = $_GET['callback'] . '(' . $r . ')' ;
					return $r; 
					break;
			}

		}
	

		header('HTTP/1.1 404 Not Found');
		logger('API call not implemented: ' . App::$query_string . ' - ' . print_r($_REQUEST,true));
		$r = '<status><error>not implemented</error></status>';
		switch($type){
			case 'xml':
				header ('Content-Type: text/xml');
				return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $r;
				break;
			case "json":
				header ('Content-Type: application/json');
			    return json_encode(array('error' => 'not implemented'));
				break;
			case "rss":
				header ('Content-Type: application/rss+xml');
				return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $r;
				break;
			case "atom":
				header ('Content-Type: application/atom+xml');
				return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $r;
				break;
		}
	}

	/**
	 *  load api $templatename for $type and replace $data array
	 */

	function api_apply_template($templatename, $type, $data){

		switch($type){
			case 'xml':
				if($data) {
					foreach($data as $k => $v)
						$ret = arrtoxml(str_replace('$','',$k),$v);
				}
				break;
			case 'json':
			default:
				if($data) {
					foreach($data as $rv) {
						$ret = json_encode($rv);
					}
				}
				break;
		}

		return $ret;
	}
	



	function api_client_register($type) {

		$ret = array();
		$key = random_string(16);
		$secret = random_string(16);
		$name = trim(escape_tags($_REQUEST['application_name']));
		if(! $name)
			json_return_and_die($ret);
		if(is_array($_REQUEST['redirect_uris']))
			$redirect = trim($_REQUEST['redirect_uris'][0]);
		else
			$redirect = trim($_REQUEST['redirect_uris']);
		$icon = trim($_REQUEST['logo_uri']);
		$r = q("INSERT INTO clients (client_id, pw, clname, redirect_uri, icon, uid)
			VALUES ('%s','%s','%s','%s','%s',%d)",
			dbesc($key),
			dbesc($secret),
			dbesc($name),
			dbesc($redirect),
			dbesc($icon),
			intval(0)
		);

		$ret['client_id'] = $key;
		$ret['client_secret'] = $secret;
		$ret['expires_at'] = 0;
		json_return_and_die($ret);
	}



	function api_oauth_request_token( $type){
		try{
			$oauth = new ZotOAuth1();
			$req = OAuth1Request::from_request();
			logger('Req: ' . var_export($req,true),LOGGER_DATA);
			$r = $oauth->fetch_request_token($req);
		}catch(Exception $e){
			logger('oauth_exception: ' . print_r($e->getMessage(),true));
			echo 'error=' . OAuth1Util::urlencode_rfc3986($e->getMessage()); 
			killme();
		}
		echo $r;
		killme();	
	}

	function api_oauth_access_token( $type){
		try{
			$oauth = new ZotOAuth1();
			$req   = OAuth1Request::from_request();
			$r     = $oauth->fetch_access_token($req);
		}
		catch(Exception $e) {
			echo 'error=' . OAuth1Util::urlencode_rfc3986($e->getMessage()); 
			killme();
		}
		echo $r;
		killme();			
	}


