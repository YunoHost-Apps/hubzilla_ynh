<?php
/**
 * Name: pump.io Post Connector
 * Description: Post to pump.io
 * Version: 0.1
 * Author: Michael Vogel <http://pirati.ca/profile/heluecht>
 * Maintainer: none
 */

require_once('library/oauth/http.php');

require_once('library/oauth/oauth_client.php');
require_once('include/permissions.php');

define('PUMPIO_DEFAULT_POLL_INTERVAL', 5); // given in minutes

function pumpio_load() {
    register_hook('post_local',           'addon/pumpio/pumpio.php', 'pumpio_post_local');
    register_hook('notifier_normal',      'addon/pumpio/pumpio.php', 'pumpio_send');
    register_hook('jot_networks',         'addon/pumpio/pumpio.php', 'pumpio_jot_nets');
    register_hook('feature_settings',      'addon/pumpio/pumpio.php', 'pumpio_settings');
    register_hook('feature_settings_post', 'addon/pumpio/pumpio.php', 'pumpio_settings_post');
//    register_hook('cron', 'addon/pumpio/pumpio.php', 'pumpio_cron');

}
function pumpio_unload() {
    unregister_hook('post_local',       'addon/pumpio/pumpio.php', 'pumpio_post_local');
    unregister_hook('notifier_normal',  'addon/pumpio/pumpio.php', 'pumpio_send');
    unregister_hook('jot_networks',     'addon/pumpio/pumpio.php', 'pumpio_jot_nets');
    unregister_hook('feature_settings',      'addon/pumpio/pumpio.php', 'pumpio_settings');
    unregister_hook('feature_settings_post', 'addon/pumpio/pumpio.php', 'pumpio_settings_post');
//    unregister_hook('cron', 'addon/pumpio/pumpio.php', 'pumpio_cron');
}

function pumpio_module() {}

function pumpio_content(&$a) {

	if(! local_channel()) {
		notice( t('Permission denied.') . EOL);
		return '';
	}

	if(argc() > 1) {
		switch (argv(1)) {
			case 'connect':
				$o = pumpio_connect($a);
				break;
			default:
				$o = print_r(App::$argv, true);
				break;
		}
	}
	else {
		$o = pumpio_connect($a);
	}
	return $o;
}

function pumpio_registerclient($a, $host) {

	$url = 'https://' . $host . '/api/client/register';

	$params = array();

	$application_name  = get_config('pumpio', 'application_name');

	if (! $application_name)
		$application_name = App::get_hostname();

	$params['type']             = 'client_associate';
	$params['contacts']         = get_config('system','admin_email');
	$params['application_type'] = 'native';
	$params['application_name'] = $application_name;
	$params['logo_uri']         = z_root() . '/images/rhash-32.png';
	$params['redirect_uris']    = z_root() . '/pumpio/connect';


	$res = z_post_url($url,$params);
	if($res['success']) {
		logger('pumpio: registerclient: ' . $res['body'], LOGGER_DATA);
		$values = json_decode($res['body'],true);
		$pumpio = array();
		$pumpio["client_id"] = $values['client_id'];
		$pumpio["client_secret"] = $values['client_secret'];
			
		//print_r($values);
		return($values);
	}
	return(false);
}

function pumpio_connect($a) {

	// Define the needed keys

	$consumer_key    = get_pconfig(local_channel(), 'pumpio','consumer_key');
	$consumer_secret = get_pconfig(local_channel(), 'pumpio','consumer_secret');
	$hostname        = get_pconfig(local_channel(), 'pumpio','host');

	if ((($consumer_key == "") || ($consumer_secret == "")) && ($hostname != "")) {
		$clientdata = pumpio_registerclient($a, $hostname);
		set_pconfig(local_channel(), 'pumpio','consumer_key',    $clientdata['client_id']);
		set_pconfig(local_channel(), 'pumpio','consumer_secret', $clientdata['client_secret']);

		$consumer_key     = get_pconfig(local_channel(), 'pumpio','consumer_key');
		$consumer_secret  = get_pconfig(local_channel(), 'pumpio','consumer_secret');
	}

	if (($consumer_key == "") || ($consumer_secret == ""))
		return;

	// The callback URL is the script that gets called after the user authenticates with pumpio
	$callback_url = z_root() . '/pumpio/connect';

	// Let's begin.  First we need a Request Token.  The request token is required to send the user
	// to pumpio's login page.

	// Create a new instance of the TumblrOAuth library.  For this step, all we need to give the library is our
	// Consumer Key and Consumer Secret
	$client = new oauth_client_class;
	$client->debug = 1;
	$client->server = '';
	$client->oauth_version = '1.0a';
	$client->request_token_url = 'https://'.$hostname.'/oauth/request_token';
	$client->dialog_url = 'https://'.$hostname.'/oauth/authorize';
	$client->access_token_url = 'https://'.$hostname.'/oauth/access_token';
	$client->url_parameters = false;
	$client->authorization_header = true;
	$client->redirect_uri = $callback_url;
	$client->client_id = $consumer_key;
	$client->client_secret = $consumer_secret;

	if (($success = $client->Initialize())) {
		if (($success = $client->Process())) {
			if (strlen($client->access_token)) {
				set_pconfig(local_channel(), "pumpio", "oauth_token", $client->access_token);
				set_pconfig(local_channel(), "pumpio", "oauth_token_secret", $client->access_token_secret);
			}
		}
		$success = $client->Finalize($success);
	}

	if($client->exit)
		$o = 'Could not connect to pumpio. Refresh the page or try again later.';

	if($success) {
		$o .= t('You are now authenticated to pumpio.');
		$o .= '<br /><a href="' . z_root() . '/settings/featured">' . t('return to the featured settings page') . '</a>';
	}

	return($o);
}

function pumpio_jot_nets(&$a,&$b) {
	if((! local_channel()) || (! perm_is_allowed(local_channel(),'','view_stream')))
		return;

	$pumpio_post = get_pconfig(local_channel(),'pumpio','post');
	if(intval($pumpio_post) == 1) {
		$pumpio_defpost = get_pconfig(local_channel(),'pumpio','post_by_default');
		$selected = ((intval($pumpio_defpost) == 1) ? ' checked="checked" ' : '');
		$b .= '<div class="profile-jot-net"><input type="checkbox" name="pumpio_enable"' . $selected . ' value="1" /> <img src="addon/pumpio/pumpio.png" /> ' . t('Post to Pump.io') . '</div>';

	}
}


function pumpio_settings(&$a,&$s) {

	if(! local_channel())
		return;

	/* Add our stylesheet to the page so we can make our settings look nice */

	//App::$page['htmlhead'] .= '<link rel="stylesheet"  type="text/css" href="' . z_root() . '/addon/pumpio/pumpio.css' . '" media="all" />' . "\r\n";

	/* Get the current state of our config variables */

	$enabled = get_pconfig(local_channel(),'pumpio','post');
	$checked = (($enabled) ? 1 : false);

	$def_enabled = get_pconfig(local_channel(),'pumpio','post_by_default');
	$def_checked = (($def_enabled) ? 1 : false);

	$public_enabled = get_pconfig(local_channel(),'pumpio','public');
	$public_checked = (($public_enabled) ? 1 : false);

	$mirror_enabled = get_pconfig(local_channel(),'pumpio','mirror');
	$mirror_checked = (($mirror_enabled) ? 1 : false);

	$servername = get_pconfig(local_channel(), "pumpio", "host");
	$username = get_pconfig(local_channel(), "pumpio", "user");

	/* Add some HTML to the existing form */

	$sc .= replace_macros(get_markup_template('field_input.tpl'), array(
		'$field'	=> array('pumpio_host', t('Pump.io servername'), $servername, t('Without "http://" or "https://"'))
	));

	$sc .= replace_macros(get_markup_template('field_input.tpl'), array(
		'$field'	=> array('pumpio_user', t('Pump.io username'), $username, t('Without the servername'))
	));


	if (($username != '') AND ($servername != '')) {

		$oauth_token = get_pconfig(local_channel(), "pumpio", "oauth_token");
		$oauth_token_secret = get_pconfig(local_channel(), "pumpio", "oauth_token_secret");

		if (($oauth_token == "") OR ($oauth_token_secret == "")) {
			$sc .= '<div class="section-content-danger-wrapper">';
			$sc .= '<strong>' . t("You are not authenticated to pumpio") . '</strong>';
			$sc .= '</div>';
			$sc .= '<a href="'.z_root().'/pumpio/connect" class="btn btn-primary btn-xs">'.t("(Re-)Authenticate your pump.io connection").'</a>';
		}

		$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
			'$field'	=> array('pumpio', t('Enable pump.io Post Plugin'), $checked, '', array(t('No'),t('Yes'))),
		));

		$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
			'$field'	=> array('pumpio_bydefault', t('Post to pump.io by default'), $def_checked, '', array(t('No'),t('Yes'))),
		));

		$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
			'$field'	=> array('pumpio_public', t('Should posts be public'), $public_checked, '', array(t('No'),t('Yes'))),
		));

		$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
			'$field'	=> array('pumpio_mirror', t('Mirror all public posts'), $mirror_checked, '', array(t('No'),t('Yes'))),
		));

	}

	$s .= replace_macros(get_markup_template('generic_addon_settings.tpl'), array(
		'$addon' 	=> array('pumpio', '<img src="addon/pumpio/pumpio.png" style="width:auto; height:1em; margin:-3px 5px 0px 0px;">' . t('Pump.io Post Settings'), '', t('Submit')),
		'$content'	=> $sc
	));

}


function pumpio_settings_post(&$a,&$b) {

	if(x($_POST,'pumpio-submit')) {
		// filtering the username if it is filled wrong
		$user = $_POST['pumpio_user'];
		if (strstr($user, "@")) {
			$pos = strpos($user, "@");
			if ($pos > 0)
				$user = substr($user, 0, $pos);
		}

		// Filtering the hostname if someone is entering it with "http"
		$host = $_POST['pumpio_host'];
		$host = trim($host);
		$host = str_replace(array("https://", "http://"), array("", ""), $host);

		set_pconfig(local_channel(),'pumpio','post',intval($_POST['pumpio']));
		set_pconfig(local_channel(),'pumpio','host',$host);
		set_pconfig(local_channel(),'pumpio','user',$user);
		set_pconfig(local_channel(),'pumpio','public',$_POST['pumpio_public']);
		set_pconfig(local_channel(),'pumpio','mirror',$_POST['pumpio_mirror']);
		set_pconfig(local_channel(),'pumpio','post_by_default',intval($_POST['pumpio_bydefault']));
				info( t('PumpIO Settings saved.') . EOL);


	}

}

function pumpio_post_local(&$a,&$b) {

	// This can probably be changed to allow editing by pointing to a different API endpoint

	if($b['edit'])
		return;

	if((! local_channel()) || (local_channel() != $b['uid']))
		return;

	if($b['item_private'] || ($b['parent_mid'] != $b['mid']))
		return;

	$pumpio_post   = intval(get_pconfig(local_channel(),'pumpio','post'));

	$pumpio_enable = (($pumpio_post && x($_REQUEST,'pumpio_enable')) ? intval($_REQUEST['pumpio_enable']) : 0);

	if($_REQUEST['api_source'] && intval(get_pconfig(local_channel(),'pumpio','post_by_default')))
		$pumpio_enable = 1;

	if(! $pumpio_enable)
		return;

	if(strlen($b['postopts']))
		$b['postopts'] .= ',';

	$b['postopts'] .= 'pumpio';
}




function pumpio_send(&$a,&$b) {


	if((! is_item_normal($b)) || $b['item_private'] || ($b['created'] !== $b['edited']))
		return;

	if(! perm_is_allowed($b['uid'],'','view_stream'))
		return;

	if(! strstr($b['postopts'],'pumpio'))
		return;

	if($b['parent'] != $b['id'])
		return;

	// if post comes from pump.io don't send it back
	if($b['app'] == "pump.io")
		return;

	$oauth_token = get_pconfig($b['uid'], "pumpio", "oauth_token");
	$oauth_token_secret = get_pconfig($b['uid'], "pumpio", "oauth_token_secret");
	$consumer_key = get_pconfig($b['uid'], "pumpio","consumer_key");
	$consumer_secret = get_pconfig($b['uid'], "pumpio","consumer_secret");

	$host = get_pconfig($b['uid'], "pumpio", "host");
	$user = get_pconfig($b['uid'], "pumpio", "user");
	$public = get_pconfig($b['uid'], "pumpio", "public");

	if($oauth_token && $oauth_token_secret) {

		require_once('include/bbcode.php');

		$title = trim($b['title']);

		if ($title != '')
			$title = "<h4>".$title."</h4>";

		$params = array();

		$params["verb"] = "post";

		$params["object"] = array(
					'objectType' => "note",
					'content' => $title . bbcode($b['body'], false, false));

		if ($public)
			$params["to"] = array(Array(
						"objectType" => "collection",
						"id" => "http://activityschema.org/collection/public"));

		$client = new oauth_client_class;
		$client->oauth_version = '1.0a';
		$client->url_parameters = false;
		$client->authorization_header = true;
		$client->access_token = $oauth_token;
		$client->access_token_secret = $oauth_token_secret;
		$client->client_id = $consumer_key;
		$client->client_secret = $consumer_secret;

		$success = $client->CallAPI(
					'https://'.$host.'/api/user/'.$user.'/feed',
					'POST', $params, array('FailOnAccessError'=>true, 'RequestContentType'=>'application/json'), $user);

		if($success)
			logger('pumpio_send: success');
		else
			logger('pumpio_send: general error: ' . print_r($user,true));

	}
}


/*
 * This may not work with zot

function pumpio_cron($a,$b) {
		$last = get_config('pumpio','last_poll');

		$poll_interval = intval(get_config('pumpio','poll_interval'));
		if(! $poll_interval)
				$poll_interval = PUMPIO_DEFAULT_POLL_INTERVAL;

		if($last) {
				$next = $last + ($poll_interval * 60);
				if($next > time()) {
						logger('pumpio: poll intervall not reached');
						return;
				}
		}
		logger('pumpio: cron_start');

		$r = q("SELECT * FROM `pconfig` WHERE `cat` = 'pumpio' AND `k` = 'mirror' AND `v` = '1' ORDER BY RAND() ");
		if(count($r)) {
				foreach($r as $rr) {
						logger('pumpio: fetching for user '.$rr['uid']);
						pumpio_fetchtimeline($a, $rr['uid']);
				}
		}

		logger('pumpio: cron_end');

		set_config('pumpio','last_poll', time());
}

function pumpio_fetchtimeline($a, $uid) {
	$ckey     = get_pconfig($uid, 'pumpio', 'consumer_key');
	$csecret  = get_pconfig($uid, 'pumpio', 'consumer_secret');
	$otoken   = get_pconfig($uid, 'pumpio', 'oauth_token');
	$osecret  = get_pconfig($uid, 'pumpio', 'oauth_token_secret');
	$lastdate = get_pconfig($uid, 'pumpio', 'lastdate');
	$hostname = get_pconfig($uid, 'pumpio','host');
	$username = get_pconfig($uid, "pumpio", "user");

	$application_name  = get_config('pumpio', 'application_name');

	if ($application_name == "")
		$application_name = App::get_hostname();

	$first_time = ($lastdate == "");

	$client = new oauth_client_class;
	$client->oauth_version = '1.0a';
	$client->authorization_header = true;
	$client->url_parameters = false;

	$client->client_id = $ckey;
	$client->client_secret = $csecret;
	$client->access_token = $otoken;
	$client->access_token_secret = $osecret;

	$url = 'https://'.$hostname.'/api/user/'.$username.'/feed/major';

	logger('pumpio: fetching for user '.$uid.' '.$url.' C:'.$client->client_id.' CS:'.$client->client_secret.' T:'.$client->access_token.' TS:'.$client->access_token_secret);

	$success = $client->CallAPI($url, 'GET', array(), array('FailOnAccessError'=>true), $user);

	if (!$success) {
		logger('pumpio: error fetching posts for user '.$uid." ".print_r($user, true));
		return;
	}

	$posts = array_reverse($user->items);

	$initiallastdate = $lastdate;
	$lastdate = '';

	if (count($posts)) {
		foreach ($posts as $post) {
			if ($post->generator->published <= $initiallastdate)
				continue;

			if ($lastdate < $post->generator->published)
				$lastdate = $post->generator->published;

			if ($first_time)
				continue;

			$receiptians = array();
			if (@is_array($post->cc))
				$receiptians = array_merge($receiptians, $post->cc);

			if (@is_array($post->to))
				$receiptians = array_merge($receiptians, $post->to);

			$public = false;
			foreach ($receiptians AS $receiver)
				if (is_string($receiver->objectType))
					if ($receiver->id == "http://activityschema.org/collection/public")
						$public = true;

			if ($public AND !strstr($post->generator->displayName, $application_name)) {
				require_once('include/html2bbcode.php');

				$_REQUEST["type"] = "wall";
				$_REQUEST["api_source"] = true;
				$_REQUEST["profile_uid"] = $uid;
				$_REQUEST["source"] = "pump.io";

				if ($post->object->displayName != "")
					$_REQUEST["title"] = html2bbcode($post->object->displayName);

				$_REQUEST["body"] = html2bbcode($post->object->content);

				if ($post->object->fullImage->url != "")
					$_REQUEST["body"] = "[url=".$post->object->fullImage->url."][img]".$post->object->image->url."[/img][/url]\n".$_REQUEST["body"];

				logger('pumpio: posting for user '.$uid);

				$mod = new Zotlabs\Module\Item();
				$mod->post();

				logger('pumpio: posting done - user '.$uid);
			}
		}
	}

	//$lastdate = '2013-05-16T20:22:12Z';

	if ($lastdate != 0)
		set_pconfig($uid,'pumpio','lastdate', $lastdate);
}

*/
