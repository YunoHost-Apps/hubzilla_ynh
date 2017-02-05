<?php
namespace Zotlabs\Module;

require_once('include/api.php');

class Api extends \Zotlabs\Web\Controller {


	function init() {
		zot_api_init();

		api_register_func('api/client/register', 'api_client_register', false);
		api_register_func('api/oauth/request_token', 'api_oauth_request_token', false);
		api_register_func('api/oauth/access_token', 'api_oauth_access_token', false);

		$args = [];
		call_hooks('api_register',$args);

		return;
	}

	function post() {
		if(! local_channel()) {
			notice( t('Permission denied.') . EOL);
			return;
		}
	
	}
	
	function get() {

		if(\App::$cmd === 'api/oauth/authorize'){
	
			/* 
			 * api/oauth/authorize interact with the user. return a standard page
			 */
			
			\App::$page['template'] = 'minimal';
					
			// get consumer/client from request token
			try {
				$request = OAuth1Request::from_request();
			}
			catch(\Exception $e) {
				echo "<pre>"; var_dump($e); killme();
			}
			
			
			if(x($_POST,'oauth_yes')){
			
				$app = $this->oauth_get_client($request);
				if (is_null($app)) 
					return "Invalid request. Unknown token.";

				$consumer = new OAuth1Consumer($app['client_id'], $app['pw'], $app['redirect_uri']);
	
				$verifier = md5($app['secret'] . local_channel());
				set_config('oauth', $verifier, local_channel());
				
				
				if($consumer->callback_url != null) {
					$params = $request->get_parameters();
					$glue = '?';
					if(strstr($consumer->callback_url,$glue))
						$glue = '?';
					goaway($consumer->callback_url . $glue . "oauth_token=" . OAuth1Util::urlencode_rfc3986($params['oauth_token']) . "&oauth_verifier=" . OAuth1Util::urlencode_rfc3986($verifier));
					killme();
				}
							
				$tpl = get_markup_template("oauth_authorize_done.tpl");
				$o = replace_macros($tpl, array(
					'$title' => t('Authorize application connection'),
					'$info' => t('Return to your app and insert this Security Code:'),
					'$code' => $verifier,
				));
			
				return $o;
			}
			
			
			if(! local_channel()) {
				//TODO: we need login form to redirect to this page
				notice( t('Please login to continue.') . EOL );
				return login(false,'api-login',$request->get_parameters());
			}
			
			$app = $this->oauth_get_client($request);
			if (is_null($app))
				return "Invalid request. Unknown token.";
						
			$tpl = get_markup_template('oauth_authorize.tpl');
			$o = replace_macros($tpl, array(
				'$title'     => t('Authorize application connection'),
				'$app'       => $app,
				'$authorize' => t('Do you want to authorize this application to access your posts and contacts, and/or create new posts for you?'),
				'$yes'	     => t('Yes'),
				'$no'	     => t('No'),
			));
			
			//echo "<pre>"; var_dump($app); killme();
			
			return $o;
		}
		
		echo api_call();
		killme();
	}

	function oauth_get_client($request){

		$params = $request->get_parameters();
		$token  = $params['oauth_token'];
	
		$r = q("SELECT clients.* FROM clients, tokens WHERE clients.client_id = tokens.client_id 
			AND tokens.id = '%s' AND tokens.auth_scope = 'request' ",
			dbesc($token)
		);
		if($r)
			return $r[0];

		return null;
	
	}
	
}
