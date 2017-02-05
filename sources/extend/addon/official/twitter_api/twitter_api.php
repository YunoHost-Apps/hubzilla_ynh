<?php

/**
 * Name: Twitter API
 * Description: Implements a reasonable and useful subset the Twitter V1 and V1.1 API
 * Version: 1.0
 * Maintainer: none
 */

/*
Not implemented by now:
statuses/retweets_of_me
friendships/create
friendships/destroy
friendships/exists
friendships/show
account/update_location
account/update_profile_background_image
account/update_profile_image
blocks/create
blocks/destroy

Not implemented in status.net:
statuses/retweeted_to_me
statuses/retweeted_by_me
direct_messages/destroy
account/end_session
account/update_delivery_device
notifications/follow
notifications/leave
blocks/exists
blocks/blocking
lists
*/

require_once('include/api.php');


function twitter_api_load() {
	Zotlabs\Extend\Hook::register('api_register','addon/twitter_api/twitter_api.php','twitter_api_register');
}

function twitter_api_unload() {
	Zotlabs\Extend\Hook::unregister_by_file('addon/twitter_api/twitter_api.php');
}


function twitter_api_register($x) {

        api_register_func('api/account/verify_credentials','api_account_verify_credentials', true);
        api_register_func('api/1.1/account/verify_credentials','api_account_verify_credentials', true);
        api_register_func('api/account/logout','api_account_logout', false);
        api_register_func('api/1.1/account/logout','api_account_logout', false);
        api_register_func('api/statuses/mediap','api_statuses_mediap', true);
        api_register_func('api/1.1/statuses/mediap','api_statuses_mediap', true);
        api_register_func('api/statuses/update_with_media','api_statuses_update', true);
        api_register_func('api/statuses/update','api_statuses_update', true);
        api_register_func('api/1.1/statuses/update_with_media','api_statuses_update', true);
        api_register_func('api/1.1/statuses/update','api_statuses_update', true);
        api_register_func('api/users/show','api_users_show');
        api_register_func('api/1.1/users/show','api_users_show');
        api_register_func('api/statuses/home_timeline','api_statuses_home_timeline', true);
        api_register_func('api/statuses/friends_timeline','api_statuses_home_timeline', true);
        api_register_func('api/1.1/statuses/home_timeline','api_statuses_home_timeline', true);
        api_register_func('api/1.1/statuses/friends_timeline','api_statuses_home_timeline', true);
        api_register_func('api/statuses/public_timeline','api_statuses_public_timeline', true);
        api_register_func('api/1.1/statuses/public_timeline','api_statuses_public_timeline', true);
        api_register_func('api/statuses/show','api_statuses_show', true);
        api_register_func('api/1.1/statuses/show','api_statuses_show', true);
        api_register_func('api/statuses/retweet','api_statuses_repeat', true);
        api_register_func('api/1.1/statuses/retweet','api_statuses_repeat', true);
        api_register_func('api/statuses/destroy','api_statuses_destroy', true);
        api_register_func('api/1.1/statuses/destroy','api_statuses_destroy', true);
        api_register_func('api/statuses/mentions','api_statuses_mentions', true);
        api_register_func('api/statuses/replies','api_statuses_mentions', true);
        api_register_func('api/1.1/statuses/mentions','api_statuses_mentions', true);
        api_register_func('api/1.1/statuses/replies','api_statuses_mentions', true);
        api_register_func('api/statuses/user_timeline','api_statuses_user_timeline', true);
        api_register_func('api/1.1/statuses/user_timeline','api_statuses_user_timeline', true);
        api_register_func('api/favorites/create', 'api_favorites_create_destroy', true);
        api_register_func('api/favorites/destroy', 'api_favorites_create_destroy', true);
        api_register_func('api/1.1/favorites/create', 'api_favorites_create_destroy', true);
        api_register_func('api/1.1/favorites/destroy', 'api_favorites_create_destroy', true);
        api_register_func('api/favorites','api_favorites', true);
        api_register_func('api/1.1/favorites','api_favorites', true);
        api_register_func('api/account/rate_limit_status','api_account_rate_limit_status',true);
        api_register_func('api/1.1/account/rate_limit_status','api_account_rate_limit_status',true);
        api_register_func('api/help/test','api_help_test',false);
        api_register_func('api/1.1/help/test','api_help_test',false);
        api_register_func('api/statuses/friends','api_statuses_friends',true);
        api_register_func('api/statuses/followers','api_statuses_followers',true);
        api_register_func('api/1.1/statuses/friends','api_statuses_friends',true);
        api_register_func('api/1.1/statuses/followers','api_statuses_followers',true);
        api_register_func('api/statusnet/config','api_statusnet_config',false);
        api_register_func('api/friendica/config','api_statusnet_config',false);
        api_register_func('api/red/config','api_statusnet_config',false);
        api_register_func('api/z/1.0/config','api_statusnet_config',false);
        api_register_func('api/statusnet/version','api_statusnet_version',false);
        api_register_func('api/friendica/version','api_friendica_version',false);
        api_register_func('api/red/version','api_friendica_version',false);
        api_register_func('api/z/1.0/version','api_friendica_version',false);
        api_register_func('api/friends/ids','api_friends_ids',true);
        api_register_func('api/followers/ids','api_followers_ids',true);
        api_register_func('api/1.1/friends/ids','api_friends_ids',true);
        api_register_func('api/1.1/followers/ids','api_followers_ids',true);
        api_register_func('api/direct_messages/new','api_direct_messages_new',true);
        api_register_func('api/1.1/direct_messages/new','api_direct_messages_new',true);
        api_register_func('api/direct_messages/conversation','api_direct_messages_conversation',true);
        api_register_func('api/direct_messages/all','api_direct_messages_all',true);
        api_register_func('api/direct_messages/sent','api_direct_messages_sentbox',true);
        api_register_func('api/direct_messages','api_direct_messages_inbox',true);
        api_register_func('api/1.1/direct_messages/conversation','api_direct_messages_conversation',true);
        api_register_func('api/1.1/direct_messages/all','api_direct_messages_all',true);
        api_register_func('api/1.1/direct_messages/sent','api_direct_messages_sentbox',true);
        api_register_func('api/1.1/direct_messages','api_direct_messages_inbox',true);

        api_register_func('api/1.1/oauth/request_token', 'api_oauth_request_token', false);
        api_register_func('api/1.1/oauth/access_token', 'api_oauth_access_token', false);

}

/**
 * Returns user info array.
 */

function api_get_user($contact_id = null, $contact_xchan = null){

	$user = null;
	$extra_query = '';


	if(! is_null($contact_xchan)) {
		$user = local_channel();
		$extra_query = " and abook_xchan = '" . dbesc($contact_xchan) . "' ";
	}
	else {
		if(! is_null($contact_id)){
			$user = $contact_id;
			$extra_query = " AND abook_id = %d ";
		}
		
		if(is_null($user) && x($_GET, 'user_id')) {
			$user = intval($_GET['user_id']);	
			$extra_query = " AND abook_id = %d ";
		}
		if(is_null($user) && x($_GET, 'screen_name')) {
			$user = dbesc($_GET['screen_name']);	
			$extra_query = " AND xchan_addr like '%s@%%' ";
			if(api_user() !== false)
				$extra_query .= " AND abook_channel = " . intval(api_user());
		}
	}
		
	if (! $user) {
		if (api_user() === false) {
			api_login($a); 
			return false;
		} else {
			$user = local_channel();
			$extra_query = " AND abook_channel = %d AND abook_self = 1 ";
		}
			
	}
		
	logger('api_user: ' . $extra_query . ', user: ' . $user, LOGGER_DATA, LOG_INFO);

	// user info		

	$uinfo = q("SELECT * from abook left join xchan on abook_xchan = xchan_hash 
		WHERE true
		$extra_query",
		$user
	);

	if (! $uinfo) {
		return false;
	}

	$following = false;
		
	if(intval($uinfo[0]['abook_self'])) {
		$usr = q("select * from channel where channel_id = %d limit 1",
			intval(api_user())
		);
		$profile = q("select * from profile where uid = %d and is_default = 1 limit 1",
			intval(api_user())
		);

		$item_normal = item_normal();

		// count public wall messages
		$r = q("SELECT COUNT(id) as total FROM item
			WHERE uid = %d
			AND item_wall = 1 $item_normal 
			AND allow_cid = '' AND allow_gid = '' AND deny_cid = '' AND deny_gid = ''
			AND item_private = 0 ",
			intval($usr[0]['channel_id'])
		);
		if($r) {
			$countitms = $r[0]['total'];
			$following = true;
		}
	}
	else {
		$r = q("SELECT COUNT(id) as total FROM item
			WHERE author_xchan = '%s'
			AND allow_cid = '' AND allow_gid = '' AND deny_cid = '' AND deny_gid = ''
			AND item_private = 0 ",
			intval($uinfo[0]['xchan_hash'])
		);
		if($r) {
			$countitms = $r[0]['total'];
		}		
		$following = ((get_abconfig($uinfo[0]['abook_channel'],$uinfo[0]['abook_xchan'],'my_perms','view_stream')) ? true : false );
	}

	// count friends
	if($usr) {
		$r = q("SELECT COUNT(abook_id) as total FROM abook
			WHERE abook_channel = %d AND abook_self = 0 ",
			intval($usr[0]['channel_id'])
		);
		if($r) {
			$countfriends = $r[0]['total'];
			$countfollowers = $r[0]['total'];
		}
	}

	$r = q("SELECT count(id) as total FROM item where item_starred = 1 and uid = %d " . item_normal(),
		intval($uinfo[0]['channel_id'])
	);
	if($r)
		$starred = $r[0]['total'];
	
	if(! intval($uinfo[0]['abook_self'])) {
		$countfriends = 0;
		$countfollowers = 0;
		$starred = 0;
	}

	$ret = array(
		'id' => intval($uinfo[0]['abook_id']),
		'self' => (intval($uinfo[0]['abook_self']) ? 1 : 0),
		'uid' => intval($uinfo[0]['abook_channel']),
		'guid' => $uinfo[0]['xchan_hash'],
		'name' => (($uinfo[0]['xchan_name']) ? $uinfo[0]['xchan_name'] : substr($uinfo[0]['xchan_addr'],0,strpos($uinfo[0]['xchan_addr'],'@'))),
		'screen_name' => substr($uinfo[0]['xchan_addr'],0,strpos($uinfo[0]['xchan_addr'],'@')),
		'location' => ($usr) ? $usr[0]['channel_location'] : '',
		'profile_image_url' => $uinfo[0]['xchan_photo_l'],
		'url' => $uinfo[0]['xchan_url'],
		'contact_url' => z_root() . '/connections/'.$uinfo[0]['abook_id'],
		'protected' => false,	
		'friends_count' => intval($countfriends),
		'created_at' => api_date($uinfo[0]['abook_created']),
		'utc_offset' => '+00:00',
		'time_zone' => 'UTC', //$uinfo[0]['timezone'],
		'geo_enabled' => false,
		'statuses_count' => intval($countitms), //#XXX: fix me 
		'lang' => App::$language,
		'description' => (($profile) ? $profile[0]['pdesc'] : ''),
		'followers_count' => intval($countfollowers),
		'favourites_count' => intval($starred),
		'contributors_enabled' => false,
		'follow_request_sent' => true,
		'profile_background_color' => 'cfe8f6',
		'profile_text_color' => '000000',
		'profile_link_color' => 'FF8500',
		'profile_sidebar_fill_color' =>'AD0066',
		'profile_sidebar_border_color' => 'AD0066',
		'profile_background_image_url' => '',
		'profile_background_tile' => false,
		'profile_use_background_image' => false,
		'notifications' => false,
		'following' => $following,
		'verified' => true // #XXX: fix me
	);

	$x = api_get_status($uinfo[0]['xchan_hash']);
	if($x)
		$ret['status'] = $x;

//		logger('api_get_user: ' . print_r($ret,true));

	return $ret;
		
}



function api_item_get_user( $item) {

	// The author is our direct contact, in a conversation with us.

	if($item['author']['abook_id']) {
		return api_get_user($item['author']['abook_id']);
	}	
		
	// We don't know this person directly.
		
	$nick = substr($item['author']['xchan_addr'],0,strpos($item['author']['xchan_addr'],'@'));
	$name = $item['author']['xchan_name'];

	// Generating a random ID
	if (! $nick)
		$nick = mt_rand(2000000, 2100000);

	$ret = array(
		'id' => $nick,
		'name' => $name,
		'screen_name' => $nick,
		'location' => '', //$uinfo[0]['default-location'],
		'description' => '',
		'profile_image_url' => $item['author']['xchan_photo_m'],
		'url' => $item['author']['xchan_url'],
		'protected' => false,
		'followers_count' => 0,
		'friends_count' => 0,
		'created_at' => '',
		'favourites_count' => 0,
		'utc_offset' => 0, // #XXX: fix me
		'time_zone' => '', //$uinfo[0]['timezone'],
		'statuses_count' => 0,
		'following' => false,
		'statusnet_blocking' => false,
		'notifications' => false,
		'uid' => 0,
		'contact_url' => 0,
		'geo_enabled' => false,
		'lang' => 'en', // #XXX: fix me
		'contributors_enabled' => false,
		'follow_request_sent' => false,
		'profile_background_color' => 'cfe8f6',
		'profile_text_color' => '000000',
		'profile_link_color' => 'FF8500',
		'profile_sidebar_fill_color' =>'AD0066',
		'profile_sidebar_border_color' => 'AD0066',
		'profile_background_image_url' => '',
		'profile_background_tile' => false,
		'profile_use_background_image' => false,
		'verified' => true, // #XXX: fix me
		'followers' => '' // #XXX: fix me
	);

	return $ret; 
}


	
/**
 * Returns an HTTP 200 OK response code and a representation of the requesting user if authentication was successful; 
 * returns a 401 status code and an error message if not. 
 * http://developer.twitter.com/doc/get/account/verify_credentials
 */
function api_account_verify_credentials($type){
	if(api_user()===false) 
		return false;
	$user_info = api_get_user();
	return api_apply_template('user', $type, array('user' => $user_info));
}


function api_account_logout( $type){
	require_once('include/auth.php');
	App::$session->nuke();
	return api_apply_template('user', $type, array('user' => null));
}
	 	

/**
 * get data from $_REQUEST ( e.g. $_POST or $_GET )
 */

function requestdata($k) {
	if(array_key_exists($k,$_REQUEST))
		return $_REQUEST[$k];
	return null;
}



function api_statuses_mediap( $type) {
	if (api_user() === false) {
		logger('api_statuses_update: no user');
		return false;
	}
	$user_info = api_get_user();

//		logger('status_with_media: ' . print_r($_REQUEST,true), LOGGER_DEBUG);

	$_REQUEST['type'] = 'wall';
	$_REQUEST['profile_uid'] = api_user();
	$_REQUEST['api_source'] = true;
				
	$txt = requestdata('status');

	require_once('library/HTMLPurifier.auto.php');
	require_once('include/html2bbcode.php');

	if((strpos($txt,'<') !== false) || (strpos($txt,'>') !== false)) {
		$txt = html2bb_video($txt);
		$config = HTMLPurifier_Config::createDefault();
		$config->set('Cache.DefinitionImpl', null);
		$purifier = new HTMLPurifier($config);
		$txt = $purifier->purify($txt);
	}
	$txt = html2bbcode($txt);
		
	App::$argv[1] = $user_info['screen_name'];
		
	$_REQUEST['silent']='1'; //tell wall_upload function to return img info instead of echo
	$_FILES['userfile'] = $_FILES['media'];

	$mod = new Zotlabs\Module\Wall_attach();
	$mod->post();


	$_REQUEST['body']= $txt . "\n\n" . $posted;

	$mod = new Zotlabs\Module\Item();
	$mod->post();

	// this should output the last post (the one we just posted).
	return api_status_show($type);
}


function api_statuses_update( $type) {
	if (api_user() === false) {
		logger('api_statuses_update: no user');
		return false;
	}

	logger('api_statuses_update: REQUEST ' . print_r($_REQUEST,true));
	logger('api_statuses_update: FILES ' . print_r($_FILES,true));


	// set this so that the item_post() function is quiet and doesn't redirect or emit json

	$_REQUEST['api_source'] = true;


	$user_info = api_get_user();

	// convert $_POST array items to the form we use for web posts.

	// logger('api_post: ' . print_r($_POST,true));

	if(requestdata('htmlstatus')) {
		require_once('library/HTMLPurifier.auto.php');
		require_once('include/html2bbcode.php');

		$txt = requestdata('htmlstatus');

		if((strpos($txt,'<') !== false) || (strpos($txt,'>') !== false)) {

			$txt = html2bb_video($txt);

			$config = HTMLPurifier_Config::createDefault();
			$config->set('Cache.DefinitionImpl', null);


			$purifier = new HTMLPurifier($config);
			$txt = $purifier->purify($txt);
		}

		$_REQUEST['body'] = html2bbcode($txt);

	}
	else
		$_REQUEST['body'] = requestdata('status');

	$parent = requestdata('in_reply_to_status_id');

	if(ctype_digit($parent))
		$_REQUEST['parent'] = $parent;
	else
		$_REQUEST['parent_mid'] = $parent;

	if($_REQUEST['namespace'] && $parent) {
		$x = q("select iid from iconfig where cat = 'system' and k = '%s' and v = '%s' limit 1",
			dbesc($_REQUEST['namespace']),
			dbesc($parent)
		);
		if($x) {
			$_REQUEST['parent'] = $x[0]['iid'];
		}
	}

	if(requestdata('lat') && requestdata('long'))
		$_REQUEST['coord'] = sprintf("%s %s",requestdata('lat'),requestdata('long'));

	$_REQUEST['profile_uid'] = api_user();

	if($parent)
		$_REQUEST['type'] = 'net-comment';
	else {
		$_REQUEST['type'] = 'wall';
		
		if(x($_FILES,'media')) {
			if(is_array($_FILES['media']['name'])) {
				$num_uploads = count($_FILES['media']['name']);
				for($x = 0; $x < $num_uploads; $x ++) {
					$_FILES['userfile'] = array();
					$_FILES['userfile']['name'] = $_FILES['media']['name'][$x];
					$_FILES['userfile']['type'] = $_FILES['media']['type'][$x];
					$_FILES['userfile']['tmp_name'] = $_FILES['media']['tmp_name'][$x];
					$_FILES['userfile']['error'] = $_FILES['media']['error'][$x];
					$_FILES['userfile']['size'] = $_FILES['media']['size'][$x];

					// upload each image if we have any
					$_REQUEST['silent']='1'; //tell wall_upload function to return img info instead of echo
					$mod = new Zotlabs\Module\Wall_attach();
					App::$data['api_info'] = $user_info;
					$media = $mod->post();

					if(strlen($media)>0)
						$_REQUEST['body'] .= "\n\n" . $media;
				}
			}
			else {
				// AndStatus doesn't present media as an array
				$_FILES['userfile'] = $_FILES['media'];
				// upload each image if we have any
				$_REQUEST['silent']='1'; //tell wall_upload function to return img info instead of echo
				$mod = new Zotlabs\Module\Wall_attach();
				App::$data['api_info'] = $user_info;
				$media = $mod->post();

				if(strlen($media)>0)
					$_REQUEST['body'] .= "\n\n" . $media;
			}
		}
	}

	// call out normal post function

	$mod = new Zotlabs\Module\Item();
	$mod->post();	

	// this should output the last post (the one we just posted).
	return api_status_show($type);
}


function api_get_status($xchan_hash) {
	require_once('include/security.php');

	$item_normal = item_normal();

	$lastwall = q("SELECT * from item where
		item_private = 0 $item_normal
		and author_xchan = '%s'
		and allow_cid = '' and allow_gid = '' and deny_cid = '' and deny_gid = ''
		and verb = '%s'
		order by created desc limit 1",
		dbesc($xchan_hash),
		dbesc(ACTIVITY_POST)
	);

	if($lastwall) {
		$lastwall = $lastwall[0];
			
		$in_reply_to_status_id = '';
		$in_reply_to_user_id = '';
		$in_reply_to_screen_name = '';

		if($lastwall['author_xchan'] != $lastwall['owner_xchan']) {
			$w = q("select * from abook left join xchan on abook_xchan = xchan_hash where
				xchan_hash = '%s' limit 1",
				dbesc($lastwall['owner_xchan'])
			);
			if($w) {
				$in_reply_to_user_id = $w[0]['abook_id'];
				$in_reply_to_screen_name = substr($w[0]['xchan_addr'],0,strpos($w[0]['xchan_addr'],'@'));
			}
		}
			
		if ($lastwall['parent']!=$lastwall['id']) {
			$in_reply_to_status_id=$lastwall['thr_parent'];
			if(! $in_reply_to_user_id) {
				$in_reply_to_user_id = $user_info['id'];
				$in_reply_to_screen_name = $user_info['screen_name'];
			}
		}
		unobscure($lastwall);  
		$status_info = array(
			'text' => html2plain(prepare_text($lastwall['body'],$lastwall['mimetype']), 0),
			'truncated' => false,
			'created_at' => api_date($lastwall['created']),
			'in_reply_to_status_id' => $in_reply_to_status_id,
			'source' => (($lastwall['app']) ? $lastwall['app'] : 'web'),
			'id' => ($lastwall['id']),
			'in_reply_to_user_id' => $in_reply_to_user_id,
			'in_reply_to_screen_name' => $in_reply_to_screen_name,
			'geo' => '',
			'favorited' => false,
			'coordinates' => $lastwall['coord'],
			'place' => $lastwall['location'],
			'contributors' => ''					
		);

	}
	
	return $status_info;
}

function api_status_show( $type){
	$user_info = api_get_user();

	// get last public message

	require_once('include/security.php');
	$item_normal = item_normal();

	$lastwall = q("SELECT * from item where
		item_private = 0 $item_normal
		and author_xchan = '%s'
		and allow_cid = '' and allow_gid = '' and deny_cid = '' and deny_gid = ''
		and verb = '%s'
		order by created desc limit 1",
		dbesc($user_info['guid']),
		dbesc(ACTIVITY_POST)
	);

	if($lastwall){
		$lastwall = $lastwall[0];
			
		$in_reply_to_status_id = '';
		$in_reply_to_user_id = '';
		$in_reply_to_screen_name = '';

		if($lastwall['author_xchan'] != $lastwall['owner_xchan']) {
			$w = q("select * from abook left join xchan on abook_xchan = xchan_hash where
				xchan_hash = '%s' limit 1",
				dbesc($lastwall['owner_xchan'])
			);
			if($w) {
				$in_reply_to_user_id = $w[0]['abook_id'];
				$in_reply_to_screen_name = substr($w[0]['xchan_addr'],0,strpos($w[0]['xchan_addr'],'@'));
			}
		}
			
		if ($lastwall['parent']!=$lastwall['id']) {
			$in_reply_to_status_id=$lastwall['thr_parent'];
			if(! $in_reply_to_user_id) {
				$in_reply_to_user_id = $user_info['id'];
				$in_reply_to_screen_name = $user_info['screen_name'];
			}
		}
		unobscure($lastwall);  
		$status_info = array(
			'text' => html2plain(prepare_text($lastwall['body'],$lastwall['mimetype']), 0),
			'truncated' => false,
			'created_at' => api_date($lastwall['created']),
			'in_reply_to_status_id' => $in_reply_to_status_id,
			'source' => (($lastwall['app']) ? $lastwall['app'] : 'web'),
			'id' => ($lastwall['id']),
			'in_reply_to_user_id' => $in_reply_to_user_id,
			'in_reply_to_screen_name' => $in_reply_to_screen_name,
			'geo' => '',
			'favorited' => false,
			'coordinates' => $lastwall['coord'],
			'place' => $lastwall['location'],
			'contributors' => ''					
		);
		$status_info['user'] = $user_info;
		if(array_key_exists('status',$status_info['user']))
			unset($status_info['user']['status']);
	}

	return  api_apply_template('status', $type, array('$status' => $status_info));
		
}

		
/**
 * Returns extended information of a given user, specified by ID or screen name as per the required id parameter.
 * The author's most recent status will be returned inline.
 * http://developer.twitter.com/doc/get/users/show
 */

// FIXME - this is essentially the same as api_status_show except for the template formatting at the end. Consolidate.
 

function api_users_show( $type){
	$user_info = api_get_user();

	require_once('include/security.php');
	$item_normal = item_normal();

	$lastwall = q("SELECT * from item where 1
		and item_private != 0 $item_normal
		and author_xchan = '%s'
		and allow_cid = '' and allow_gid = '' and deny_cid = '' and deny_gid = ''
		and verb = '%s'
		order by created desc limit 1",
		dbesc($user_info['guid']),
		dbesc(ACTIVITY_POST)
	);

	if($lastwall){
		$lastwall = $lastwall[0];
			
		$in_reply_to_status_id = '';
		$in_reply_to_user_id = '';
		$in_reply_to_screen_name = '';

		if($lastwall['author_xchan'] != $lastwall['owner_xchan']) {
			$w = q("select * from abook left join xchan on abook_xchan = xchan_hash where
				xchan_hash = '%s' limit 1",
				dbesc($lastwall['owner_xchan'])
			);
			if($w) {
				$in_reply_to_user_id = $w[0]['abook_id'];
				$in_reply_to_screen_name = substr($w[0]['xchan_addr'],0,strpos($w[0]['xchan_addr'],'@'));
			}
		}
			
		if ($lastwall['parent']!=$lastwall['id']) {
			$in_reply_to_status_id=$lastwall['thr_parent'];
			if(! $in_reply_to_user_id) {
				$in_reply_to_user_id = $user_info['id'];
				$in_reply_to_screen_name = $user_info['screen_name'];
			}
		}  
		unobscure($lastwall);
		$user_info['status'] = array(
			'text' => html2plain(prepare_text($lastwall['body'],$lastwall['mimetype']), 0),
			'truncated' => false,
			'created_at' => api_date($lastwall['created']),
			'in_reply_to_status_id' => $in_reply_to_status_id,
			'source' => (($lastwall['app']) ? $lastwall['app'] : 'web'),
			'id' => (($w) ? $w[0]['abook_id'] : $user_info['id']),
			'in_reply_to_user_id' => $in_reply_to_user_id,
			'in_reply_to_screen_name' => $in_reply_to_screen_name,
			'geo' => '',
			'favorited' => false,
			'coordinates' => $lastwall['coord'],
			'place' => $lastwall['location'],
			'contributors' => ''					
		);

	}
	return  api_apply_template('user', $type, array('$user' => $user_info));

}


/**
 *
 * http://developer.twitter.com/doc/get/statuses/home_timeline
 *
 * TODO: Optional parameters
 * TODO: Add reply info
 */

function api_statuses_home_timeline( $type){
	if (api_user() === false) 
		return false;

	$user_info = api_get_user();
	// get last network messages


	// params
	$count           = (x($_REQUEST,'count') ? $_REQUEST['count'] : 20);
	$page            = (x($_REQUEST,'page') ? $_REQUEST['page']-1 : 0);
	if($page < 0) 
		$page = 0;
	$since_id        = (x($_REQUEST,'since_id') ? $_REQUEST['since_id'] : 0);
	$max_id          = (x($_REQUEST,'max_id') ? $_REQUEST['max_id'] : 0);
	$exclude_replies = (x($_REQUEST,'exclude_replies') ? 1 : 0);

	$start = $page * $count;

	//$include_entities = (x($_REQUEST,'include_entities')?$_REQUEST['include_entities']:false);

	$sql_extra = '';
	if ($max_id > 0)
		$sql_extra .= ' AND item.id <= ' . intval($max_id);
	if ($exclude_replies > 0)
		$sql_extra .= ' AND item.parent = item.id';
	if (api_user() != $user_info['uid']) {
		$observer = App::get_observer();
		require_once('include/permissions.php');
		if(! perm_is_allowed($user_info['uid'],(($observer) ? $observer['xchan_hash'] : ''),'view_stream'))
			return '';
		$sql_extra .= ' and item_private = 0 ';
	}

	$item_normal = item_normal();

	$r = q("SELECT * from item WHERE uid = %d $item_normal
		$sql_extra
		AND id > %d
		ORDER BY received DESC LIMIT %d ,%d ",
		intval($user_info['uid']),
		intval($since_id),
		intval($start),	
		intval($count)
	);

	xchan_query($r,true);

	$ret = api_format_items($r,$user_info);

	// We aren't going to try to figure out at the item, group, and page
	// level which items you've seen and which you haven't. If you're looking
	// at the network timeline just mark everything seen. 
	
	if (api_user() == $user_info['uid']) {
		$r = q("UPDATE item SET item_unseen = 0 WHERE item_unseen = 1 and uid = %d",
			intval($user_info['uid'])
		);
	}

	$data = array('$statuses' => $ret);
	return  api_apply_template('timeline', $type, $data);
}


function api_statuses_public_timeline( $type){
	if(api_user() === false)
		return false;

	$user_info = api_get_user();

	$sys = get_sys_channel();

	// params
	$count = (x($_REQUEST,'count') ? $_REQUEST['count']   : 20);
	$page  = (x($_REQUEST,'page')  ? $_REQUEST['page']-1  :  0);
	if($page < 0)
		$page=0;
	$since_id = (x($_REQUEST,'since_id') ? $_REQUEST['since_id'] : 0);
	$max_id = (x($_REQUEST,'max_id') ? $_REQUEST['max_id'] : 0);

	$start = $page * $count;

	//$include_entities = (x($_REQUEST,'include_entities')?$_REQUEST['include_entities']:false);

	if ($max_id > 0)
		$sql_extra = 'AND item.id <= '.intval($max_id);
	require_once('include/security.php');
	$item_normal = item_normal();

	$r = q("select * from item where allow_cid = ''  and allow_gid = ''
		and deny_cid  = ''  and deny_gid  = ''
		and item_private = 0
		$item_normal
		and uid = " . $sys['channel_id'] . "
		$sql_extra
		AND id > %d group by mid
		order by received desc LIMIT %d OFFSET %d ",
		intval($since_id),
		intval($count),
		intval($start)
	);

	xchan_query($r,true);

	$ret = api_format_items($r,$user_info);


	$data = array('statuses' => $ret);

	return  api_apply_template('timeline', $type, $data);
}



function api_statuses_show($type){
	if(api_user() === false) 
		return false;

	$user_info = api_get_user();

	// params
	$id = intval(argv(3));
	if(! $id)
		$id = $_REQUEST['id'];

	logger('API: api_statuses_show: '.$id);

	//$include_entities = (x($_REQUEST,'include_entities') ? $_REQUEST['include_entities'] : false);
	$conversation = (x($_REQUEST,'conversation') ? 1 : 0);

	$sql_extra = '';
	if ($conversation)
		$sql_extra .= " AND item.parent = %d  ORDER BY received ASC ";
	else
		$sql_extra .= " AND item.id = %d";

	$item_normal = item_normal();
	$r = q("select * from item where uid = %d $item_normal $sql_extra",
		intval(api_user()),
		intval($id)
	);

	xchan_query($r,true);

	$ret = api_format_items($r,$user_info);


	if ($conversation) {
		$data = array('statuses' => $ret);
		return api_apply_template('timeline', $type, $data);
	}
	else {
		$data = array('status' => $ret[0]);
		return  api_apply_template('status', $type, $data);
	}
}


/**
 * 
 */

function api_statuses_repeat( $type){
	if(api_user() === false) 
		return false;

	$user_info = api_get_user();

	// params
	$id = intval(argv(3));

	logger('API: api_statuses_repeat: ' . $id);

	//$include_entities = (x($_REQUEST,'include_entities') ? $_REQUEST['include_entities'] : false);

	$observer = App::get_observer();

	$item_normal = item_normal();

	$r = q("SELECT * from item where and id = %d $item_normal limit 1",
		intval($id)
	);

	if(perm_is_allowed($r[0]['uid'],$observer['xchan_hash'],'view_stream')) {
		if ($r[0]['body'] != '') {
			$_REQUEST['body'] = html_entity_decode('&#x2672; ', ENT_QUOTES, 'UTF-8') . '[zrl=' . $r[0]['reply_url'] . ']' . $r[0]['reply_author'] . '[/zrl] ' . "\n" . $r[0]['body'];
			$_REQUEST['profile_uid'] = api_user();
			$_REQUEST['type'] = 'wall';
			$_REQUEST['api_source'] = true;
			$mod = new Zotlabs\Module\Item();
			$mod->post();
		}
	}
	else
		return false;

	if ($type == 'xml')
		$ok = 'true';
	else
		$ok = 'ok';

	return api_apply_template('test', $type, array('$ok' => $ok));
}


/**
 * 
 */

function api_statuses_destroy( $type){
	if(api_user() === false)
		return false;

	$user_info = api_get_user();

	// params
	$id = intval(argv(3));
	if($id) {
		// first prove that we own the item

		$r = q("select * from item where id = %d and uid = %d limit 1",
			intval($id),
			intval($user_info['uid'])
		);
		if(! $r)
			return false;
	}
	else {
		if($_REQUEST['namespace'] && $_REQUEST['remote_id']) {
			$r = q("select * from iconfig left join item on iconfig.iid = item.id 
				where cat = 'system' and k = '%s' and v = '%s' and item.uid = %d limit 1",
				dbesc($_REQUEST['namespace']),
				dbesc($_REQUEST['remote_id']),
				intval($user_info['uid'])
			);
			if(! $r)
				return false;
			$id = $r[0]['iid'];
		}
		if($_REQUEST['namespace'] && $_REQUEST['comment_id']) {
			$r = q("select * from iconfig left join item on item.id = iconfig.iid where cat = 'system' and k = '%s' and v = '%s' and uid = %d and item.id != item.parent limit 1",
				dbesc($_REQUEST['namespace']),
				dbesc($_REQUEST['comment_id']),
				intval($user_info['uid'])
			);
			if(! $r)
				return false;
			$id = $r[0]['iid'];
		}
	}
	if(! $id)
		return false;

	logger('API: api_statuses_destroy: '.$id);
	require_once('include/items.php');
	drop_item($id, false);


	if ($type == 'xml')
		$ok = 'true';
	else
		$ok = 'ok';

	return api_apply_template('test', $type, array('$ok' => $ok));
}


/**
 * 
 * http://developer.twitter.com/doc/get/statuses/mentions
 * 
 */


function api_statuses_mentions( $type){
	if(api_user() === false)
		return false;
				
	$user_info = api_get_user();
	// get last network messages


	// params
	$count = (x($_REQUEST,'count') ? $_REQUEST['count']  : 20);
	$page  = (x($_REQUEST,'page')  ? $_REQUEST['page']-1 :  0);
	if($page < 0) 
		$page=0;
	$since_id = (x($_REQUEST,'since_id') ? $_REQUEST['since_id'] : 0);
	$max_id = (x($_REQUEST,'max_id') ? $_REQUEST['max_id'] : 0);

	$start = $page * $count;

	//$include_entities = (x($_REQUEST,'include_entities')?$_REQUEST['include_entities']:false);

	$sql_extra .= " AND item_mentionsme = 1 ";
	if($max_id > 0)
		$sql_extra .= " AND item.id <= " . intval($max_id) . " ";

	require_once('include/security.php');
	$item_normal = item_normal();

	$r = q("select * from item where uid = " . intval(api_user()) . "
		$item_normal $sql_extra
		AND id > %d group by mid
		order by received desc LIMIT %d OFFSET %d ",
		intval($since_id),
		intval($count),
		intval($start)
	);

	xchan_query($r,true);

	$ret = api_format_items($r,$user_info);

	$data = array('statuses' => $ret);
	return  api_apply_template('timeline', $type, $data);
}


function api_statuses_user_timeline( $type){
	if(api_user() === false) 
		return false;
		
	$user_info = api_get_user();

	// get last network messages

	logger('api_statuses_user_timeline: api_user: '. api_user() .
		   "\nuser_info: ".print_r($user_info, true) .
		   "\n_REQUEST:  ".print_r($_REQUEST, true),
		   LOGGER_DEBUG);

	// params
	$count = (x($_REQUEST,'count') ? $_REQUEST['count'] : 20);
	$page = (x($_REQUEST,'page') ? $_REQUEST['page']-1 : 0);
	if($page < 0) 
		$page = 0;
	$since_id = (x($_REQUEST,'since_id') ? $_REQUEST['since_id'] : 0);
	$exclude_replies = (x($_REQUEST,'exclude_replies') ? 1 :0);
		
	$start = $page * $count;

	$sql_extra = '';

	//FIXME - this isn't yet implemented
	if($exclude_replies > 0)  $sql_extra .= ' AND item.parent = item.id';

	$arr = [
		'uid'      => api_user(),
		'since_id' => $since_id,
		'start'    => $start,
		'records'  => $count
	];
	
	if ($user_info['self'] === 1)
		$arr['wall'] = 1;
	else
		$arr['cid'] = $user_info['id'];


	$r = items_fetch($arr,App::get_channel(),get_observer_hash());
		
	$ret = api_format_items($r,$user_info);

	$data = array('statuses' => $ret);
	return(api_apply_template('timeline', $type, $data));
}


/**
 * Star/unstar an item
 * param: id : id of the item
 *
 * api v1 : https://web.archive.org/web/20131019055350/https://dev.twitter.com/docs/api/1/post/favorites/create/%3Aid
 */

function api_favorites_create_destroy($type){

	if(api_user() === false) 
		return false;

	$action = str_replace('.' . $type,'',argv(2));
	if (argc() > 3) {
		$itemid = intval(argv(3));
	}
	else {
		$itemid = intval($_REQUEST['id']);
	}

	$item = q("SELECT * FROM item WHERE id = %d AND uid = %d",
		intval($itemid), 
		intval(api_user())
	);

	if(! $item)
		return false;

	switch($action){
		case 'create':
			$flags = $item[0]['item_starred'] = 1;
			break;
		case 'destroy':
			$flags = $item[0]['item_starred'] = 0;
			break;
		default:
			return false;
	}

	$r = q("UPDATE item SET item_starred = %d where id = %d and uid = %d",
		intval($flags),
		intval($itemid),
		intval(api_user())
	);
	if(! $r)
		return false;

	$item = q("SELECT * FROM item WHERE id = %d AND uid = %d",
		intval($itemid), 
		intval(api_user())
	);

	xchan_query($item,true);

	$user_info = api_get_user();
	$rets = api_format_items($item,$user_info);
	$ret = $rets[0];

	$data = array('status' => $ret);

	return api_apply_template('status', $type, $data);
}


function api_favorites( $type){
	if(api_user() === false) 
		return false;

	$user_info = api_get_user();

	// params
	$count	         = (x($_REQUEST,'count') ? $_REQUEST['count'] : 20);
	$page            = (x($_REQUEST,'page')  ? $_REQUEST['page']-1 : 0);
	if($page < 0) 
		$page = 0;
	$since_id        = (x($_REQUEST,'since_id') ? $_REQUEST['since_id'] : 0);
	$max_id          = (x($_REQUEST,'max_id') ? $_REQUEST['max_id'] : 0);
	$exclude_replies = (x($_REQUEST,'exclude_replies') ? 1 :0);

	$start = $page * $count;

	//$include_entities = (x($_REQUEST,'include_entities') ? $_REQUEST['include_entities'] : false);

	$sql_extra = '';
	if($max_id > 0)
		$sql_extra .= ' AND item.id <= ' . intval($max_id);
	if($exclude_replies > 0)
		$sql_extra .= ' AND item.parent = item.id';

	if(api_user() != $user_info['uid']) {
		$observer = App::get_observer();
		require_once('include/permissions.php');
		if(! perm_is_allowed($user_info['uid'],(($observer) ? $observer['xchan_hash'] : ''),'view_stream'))
			return '';
		$sql_extra .= ' and item_private = 0 ';
	}

	$item_normal = item_normal();

	$r = q("SELECT * from item WHERE uid = %d $item_normal
		and item_starred = 1 $sql_extra
		AND id > %d
		ORDER BY received DESC LIMIT %d ,%d ",
		intval($user_info['uid']),
		intval($since_id),
		intval($start),	
		intval($count)
	);

	xchan_query($r,true);

	$ret = api_format_items($r,$user_info);

	$data = array('statuses' => $ret);
	return(api_apply_template('timeline', $type, $data));

}


function api_format_message($item, $recipient, $sender) {
	// standard meta information
	$ret = array(
		'id'                    => $item['id'],
		'created_at'            => api_date($item['created']),
		'sender_id'             => $sender['id'] ,
		'sender_screen_name'    => $sender['screen_name'],
		'sender'                => $sender,
		'recipient_id'          => $recipient['id'],
		'recipient_screen_name' => $recipient['screen_name'],
		'recipient'             => $recipient,
	);
	unobscure_mail($item);
	//don't send title to regular StatusNET requests to avoid confusing these apps
	if(x($_GET, 'getText')) {
		$ret['title'] = $item['title'] ;
		if($_GET['getText'] === 'html') {
			$ret['text'] = prepare_text($item['body'],$item['mimetype']);
		}
		elseif($_GET['getText'] === 'plain') {
			$ret['text'] = html2plain(prepare_text($item['body'],$item['mimetype']), 0);
		}
	}
	else {
		$ret['text'] = $item['title'] . "\n" . html2plain(prepare_text($item['body'],$item['mimetype']),0);
	}
	if(isset($_GET['getUserObjects']) && $_GET['getUserObjects'] == 'false') {
		unset($ret['sender']);
		unset($ret['recipient']);
	}

	return $ret;
}

function api_format_items($r,$user_info) {

	//logger('api_format_items: ' . print_r($r,true));

	//logger('api_format_items: ' . print_r($user_info,true));

	$ret = array();

	$x = array('items' => $r,'api_user' => api_user(),'user_info' => $user_info);
	call_hooks('api_format_items',$x);
	$r = $x['items'];

	if(! $r)
		return $ret;

	foreach($r as $item) {

		localize_item($item);

		$status_user = (($item['author_xchan']==$user_info['guid'])?$user_info: api_item_get_user($item));
		if(array_key_exists('status',$status_user))
			unset($status_user['status']);

		if($item['parent'] != $item['id']) {
			$r = q("select id from item where parent= %d and id < %d order by id desc limit 1",
				intval($item['parent']), 
				intval($item['id'])
			);
			if($r)
				$in_reply_to_status_id = $r[0]['id'];
			else
				$in_reply_to_status_id = $item['parent'];

			xchan_query($r,true);

			$in_reply_to_screen_name = $r[0]['author']['xchan_name'];
			$in_reply_to_user_id = $r[0]['author']['abook_id'];

		}
		else {
			$in_reply_to_screen_name = '';
			$in_reply_to_user_id = 0;
			$in_reply_to_status_id = 0;
		}
		unobscure($item);
		// Workaround for ostatus messages where the title is identically to the body
		$statusbody = trim(html2plain(prepare_text($item['body'],$item['mimetype']), 0));
		$statustitle = trim($item['title']);

		if(($statustitle != '') and (strpos($statusbody, $statustitle) !== false))
			$statustext = trim($statusbody);
		else
			$statustext = trim($statustitle . "\n\n" . $statusbody);


		$status = array(
			'text'		                => $statustext,
			'truncated'                 => false,
			'created_at'                => api_date($item['created']),
			'in_reply_to_status_id'     => $in_reply_to_status_id,
			'source'                    => (($item['app']) ? $item['app'] : 'web'),
			'id'		                => intval($item['id']),
			'in_reply_to_user_id'       => $in_reply_to_user_id,
			'in_reply_to_screen_name'   => $in_reply_to_screen_name,
			'geo'                       => '',
			'favorited'                 => (intval($item['item_starred']) ? true : false),
			'user'                      =>  $status_user ,
			'statusnet_html'		    => trim(prepare_text($item['body'],$item['mimetype'])),

			'statusnet_conversation_id'	=> $item['parent'],
		);

		// Seesmic doesn't like the following content
		if($_SERVER['HTTP_USER_AGENT'] != 'Seesmic') {
			$status2 = array(
				'updated'      => api_date($item['edited']),
				'published'    => api_date($item['created']),
				'message_id'   => $item['mid'],
				'url'		   => $item['plink'],
				'coordinates'  => $item['coord'],
				'place'        => $item['location'],
				'contributors' => '',
				'annotations'  => '',
				'entities'     => '',
				'objecttype'   => (($item['obj_type']) ? $item['obj_type'] : ACTIVITY_OBJ_NOTE),
				'verb'         => (($item['verb']) ? $item['verb'] : ACTIVITY_POST),
				'self'         => z_root().'/api/statuses/show/'.$item['id'].'.'.$type,
				'edit'         => z_root().'/api/statuses/show/'.$item['id'].'.'.$type,
			);

			$status = array_merge($status, $status2);
		}

		$ret[] = $status;
	};
	return $ret;
}


function api_account_rate_limit_status($type) {

	$hash = array(
		  'reset_time_in_seconds' => strtotime('now + 1 hour'),
		  'remaining_hits' => (string) 150,
		  'hourly_limit' => (string) 150,
		  'reset_time' => datetime_convert('UTC','UTC','now + 1 hour',ATOM_TIME),
	);
	if ($type == 'xml')
		$hash['resettime_in_seconds'] = $hash['reset_time_in_seconds'];

	return api_apply_template('ratelimit', $type, array('$hash' => $hash));

}


function api_help_test($type) {

	if($type == 'xml')
		$ok = 'true';
	else
		$ok = 'ok';

	return api_apply_template('test', $type, array('ok' => $ok));

}


/**
 *  https://dev.twitter.com/docs/api/1/get/statuses/friends 
 *  This function is deprecated by Twitter
 *  returns: json, xml 
 *
 */

function api_statuses_f( $type, $qtype) {
	if(api_user() === false) 
		return false;
	$user_info = api_get_user();
		
		
	// friends and followers only for self
	if ($user_info['self'] == 0){
		return false;
	}
		
	if(x($_GET,'cursor') && $_GET['cursor']=='undefined'){
		/* this is to stop Hotot to load friends multiple times
		*  I'm not sure if I'm missing return something or
		*  is a bug in hotot. Workaround, meantime
		*/
			
		/*$ret=Array();
		return array('users' => $ret);*/
		return false;
	}
		

	if($qtype == 'friends') {
		$r = q("select abook_id from abook left join abconfig on abook_xchan = xchan and abook_channel = chan 
			where chan = %d and abook_self = 0 and abook_pending = 0 and cat = 'my_perms' and k = 'view_stream' and v = '1' ",
			intval(api_user())
		);
	}

	if($qtype == 'followers') {
		$r = q("select abook_id from abook left join abconfig on abook_xchan = xchan and abook_channel = chan 
			where chan = %d and abook_self = 0 and abook_pending = 0 and cat = 'their_perms' and k = 'view_stream' and v = '1' ",
			intval(api_user())
		);
	}

	$ret = array();

	if($r) {
		foreach($r as $cid) {
			$ret[] = api_get_user($cid['abook_id']);
		}
	}

	return array('users' => $ret);

}

function api_statuses_friends($type){
	$data = api_statuses_f($type,'friends');
	if($data === false)
		return false;
	return(api_apply_template('friends', $type, $data));
}

function api_statuses_followers($type){
	$data = api_statuses_f($type,'followers');
	if($data === false)
		return false;
	return(api_apply_template('friends', $type, $data));
}


function api_statusnet_config($type) {

	$name      = get_config('system','sitename');
	$server    = App::get_hostname();
	$logo      = z_root() . '/images/hz-64.png';
	$email     = get_config('system','admin_email');
	$closed    = ((get_config('system','register_policy') == REGISTER_CLOSED) ? true : false);
	$private   = ((get_config('system','block_public')) ? true : false);
	$textlimit = ((get_config('system','max_import_size')) ? get_config('system','max_import_size') : 200000);
	if(get_config('system','api_import_size'))
		$texlimit = get_config('system','api_import_size');

	$m = parse_url(z_root());

	$ssl = (($m['scheme'] === 'https') ? true : false);
	$sslserver = (($ssl) ? str_replace('http:','https:',z_root()) : '');

	$config = [
		'site' => [ 
			'name'           => $name,
			'server'         => $server, 
			'theme'          => 'default', 
			'path'           => '',
			'logo'           => $logo, 
			'fancy'          => true, 
			'language'       => 'en', 
			'email'          => $email, 
			'broughtby'      => '',
			'broughtbyurl'   => '', 
			'timezone'       => 'UTC', 
			'closed'         => $closed, 
			'inviteonly'     => false,
			'private'        => $private, 
			'textlimit'      => $textlimit, 
			'sslserver'      => $sslserver, 
			'ssl'            => $ssl,
			'shorturllength' => 30,
    
    		'platform' => [
				'PLATFORM_NAME' => Zotlabs\Lib\System::get_platform_name(),
				'STD_VERSION' => Zotlabs\Lib\System::get_project_version(),
				'ZOT_REVISION' => ZOT_REVISION,
				'DB_UPDATE_VERSION' => Zotlabs\Lib\System::get_update_version()
			]
		]
	];  

	return api_apply_template('config', $type, array('config' => $config));

}

function api_statusnet_version($type) {

	// liar

	if($type === 'xml') {
		header('Content-type: application/xml');
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n" . '<version>0.9.7</version>' . "\r\n";
		killme();
	}
	elseif($type === 'json') {
		header('Content-type: application/json');
		echo '"0.9.7"';
		killme();
	}
}


function api_friendica_version($type) {

	if($type === 'xml') {
		header('Content-type: application/xml');
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n" . '<version>' . Zotlabs\Lib\System::get_project_version() . '</version>' . "\r\n";
		killme();
	}
	elseif($type === 'json') {
		header('Content-type: application/json');
		echo '"' . Zotlabs\Lib\System::get_project_version() . '"';
		killme();
	}
}

function api_ff_ids($type,$qtype) {

	if(! api_user())
		return false;

	if($qtype == 'friends') {
		$r = q("select abook_id from abook left join abconfig on abook_xchan = xchan and abook_channel = chan 
			where chan = %d and abook_self = 0 and abook_pending = 0 and cat = 'my_perms' and k = 'view_stream' and v = '1' ",
			intval(api_user())
		);
	}

	if($qtype == 'followers') {
		$r = q("select abook_id from abook left join abconfig on abook_xchan = xchan and abook_channel = chan 
			where chan = %d and abook_self = 0 and abook_pending = 0 and cat = 'their_perms' and k = 'view_stream' and v = '1' ",
			intval(api_user())
		);
	}

	if($r) {
		if($type === 'xml') {
			header('Content-type: application/xml');
			echo '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n" . '<ids>' . "\r\n";
			foreach($r as $rv)
				echo '<id>' . $rv['abook_id'] . '</id>' . "\r\n";
			echo '</ids>' . "\r\n";
			killme();
		}
		elseif($type === 'json') {
			$ret = array();
			header('Content-type: application/json');
			foreach($r as $rv) {
				$ret[] = $rv['abook_id'];
			}
			echo json_encode($ret);
			killme();
		}
	}
}

function api_friends_ids($type) {
	api_ff_ids($type,'friends');
}

function api_followers_ids($type) {
	api_ff_ids($type,'followers');
}

function api_direct_messages_new( $type) {
	if(api_user() === false)
		return false;
		
	if(!x($_POST, 'text') || !x($_POST,'screen_name'))
		return;

	$sender = api_get_user();
		
	require_once('include/message.php');

	// in a decentralised world the screen name is ambiguous

	$r = q("SELECT abook_id FROM abook left join xchan on abook_xchan = xchan_hash 
		WHERE abook_channel = %d and xchan_addr like '%s'",
		intval(api_user()),
		dbesc($_POST['screen_name'] . '@%')
	);

	$recipient = api_get_user($r[0]['abook_id']);			
	$replyto   = '';
	$sub       = '';
		
	if(array_key_exists('replyto',$_REQUEST) && $_REQUEST['replyto']) {
		$r = q('SELECT parent_mid, title FROM mail WHERE uid=%d AND id=%d',
			intval(api_user()),
			intval($_REQUEST['replyto'])
		);
		if($r) {
			$replyto = $r[0]['parent_mid'];
			$sub     = $r[0]['title'];
		}
	}
	else {
		if(x($_REQUEST,'title')) {
			$sub = $_REQUEST['title'];
		}
		else {
			$sub = ((strlen($_POST['text']) > 10) ? substr($_POST['text'],0,10) . '...' : $_POST['text']);
		}
	}

	$id = send_message(api_user(),$recipient['guid'], $_POST['text'], $sub, $replyto);

	if($id > (-1)) {
		$r = q("SELECT * FROM mail WHERE id = %d", 
			intval($id)
		);
		if(! $r)
			return false;

		$ret = api_format_message($r[0], $recipient, $sender);		
	} 
	else {
		$ret = [ 'error' => $id ];	
	}
		
	$data = [ 'messages' => $ret ];
	return(api_apply_template('direct_messages', $type, $data));
				
}

function api_direct_messages_box( $type, $box) {
	if(api_user() === false) 
		return false;
		
	$user_info = api_get_user();
		
	// params
	$count = (x($_GET,'count') ? $_GET['count'] : 20);
	$page  = (x($_REQUEST,'page') ? $_REQUEST['page'] - 1 : 0);
	if($page < 0) 
		$page = 0;
		
	$start   = $page * $count;
	$channel = App::get_channel();		

	$profile_url = z_root() . '/channel/' . $channel['channel_address'];
	if($box === 'sentbox') {
		$sql_extra = "from_xchan = '" . dbesc( $channel['channel_hash'] ) . "'";
	}
	elseif($box === 'conversation') {
		$sql_extra = "parent_mid = '" . dbesc($_GET['uri'])  . "'";
	}
	elseif($box === 'all') {
		$sql_extra = 'true';
	}
	elseif($box === 'inbox') {
		$sql_extra = "from_xchan != '" . dbesc($channel['channel_hash']) . "'";
	}
		
	$r = q("SELECT * FROM mail WHERE channel_id = %d AND $sql_extra ORDER BY created DESC LIMIT %d OFFSET %d",
		intval(api_user()),
		intval($count), 
		intval($start)
	);
		
	$ret = array();
	if($r) {
		foreach($r as $item) {
			if($item['from_xchan'] === $channel['channel_hash']) {
				$sender = $user_info;
				$recipient = api_get_user( null, $item['to_xchan']);
			}
			else {
				$sender = api_get_user( null, $item['from_xchan']);
				$recipient = $user_info;
			}
	
			$ret[] = api_format_message($item, $recipient, $sender);
		}
	}
		
	$data = array('messages' => $ret);
	return(api_apply_template('direct_messages', $type, $data));
		
}

function api_direct_messages_sentbox($type){
	return api_direct_messages_box($type, 'sentbox');
}

function api_direct_messages_inbox($type){
	return api_direct_messages_box($type, 'inbox');
}

function api_direct_messages_all($type){
	return api_direct_messages_box($type, 'all');
}

function api_direct_messages_conversation($type){
	return api_direct_messages_box($type, 'conversation');
}



