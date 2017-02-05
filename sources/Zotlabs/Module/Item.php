<?php

namespace Zotlabs\Module;

/**
 *
 * This is the POST destination for most all locally posted
 * text stuff. This function handles status, wall-to-wall status, 
 * local comments, and remote coments that are posted on this site 
 * (as opposed to being delivered in a feed).
 * Also processed here are posts and comments coming through the 
 * statusnet/twitter API. 
 * All of these become an "item" which is our basic unit of 
 * information.
 * Posts that originate externally or do not fall into the above 
 * posting categories go through item_store() instead of this function. 
 *
 */  

require_once('include/crypto.php');
require_once('include/items.php');
require_once('include/attach.php');
require_once('include/bbcode.php');


use \Zotlabs\Lib as Zlib;

class Item extends \Zotlabs\Web\Controller {

	function post() {
	
		// This will change. Figure out who the observer is and whether or not
		// they have permission to post here. Else ignore the post.
	
		if((! local_channel()) && (! remote_channel()) && (! x($_REQUEST,'commenter')))
			return;
	
		require_once('include/security.php');
	
		$uid = local_channel();
		$channel = null;
		$observer = null;
	
	
		/**
		 * Is this a reply to something?
		 */
	
		$parent = ((x($_REQUEST,'parent')) ? intval($_REQUEST['parent']) : 0);
		$parent_mid = ((x($_REQUEST,'parent_mid')) ? trim($_REQUEST['parent_mid']) : '');
	
		$remote_xchan = ((x($_REQUEST,'remote_xchan')) ? trim($_REQUEST['remote_xchan']) : false);
		$r = q("select * from xchan where xchan_hash = '%s' limit 1",
			dbesc($remote_xchan)
		);
		if($r)
			$remote_observer = $r[0];
		else 
			$remote_xchan = $remote_observer = false;
	
		$profile_uid = ((x($_REQUEST,'profile_uid')) ? intval($_REQUEST['profile_uid'])    : 0);
		require_once('include/channel.php');
		$sys = get_sys_channel();
		if($sys && $profile_uid && ($sys['channel_id'] == $profile_uid) && is_site_admin()) {
			$uid = intval($sys['channel_id']);
			$channel = $sys;
			$observer = $sys;
		}
	
		if(x($_REQUEST,'dropitems')) {
			require_once('include/items.php');
			$arr_drop = explode(',',$_REQUEST['dropitems']);
			drop_items($arr_drop);
			$json = array('success' => 1);
			echo json_encode($json);
			killme();
		}
	
		call_hooks('post_local_start', $_REQUEST);
	
	//	 logger('postvars ' . print_r($_REQUEST,true), LOGGER_DATA);
	
		$api_source = ((x($_REQUEST,'api_source') && $_REQUEST['api_source']) ? true : false);
	
		$consensus = intval($_REQUEST['consensus']);
		$nocomment = intval($_REQUEST['nocomment']);
	
		// 'origin' (if non-zero) indicates that this network is where the message originated,
		// for the purpose of relaying comments to other conversation members. 
		// If using the API from a device (leaf node) you must set origin to 1 (default) or leave unset.
		// If the API is used from another network with its own distribution
		// and deliveries, you may wish to set origin to 0 or false and allow the other 
		// network to relay comments.
	
		// If you are unsure, it is prudent (and important) to leave it unset.   
	
		$origin = (($api_source && array_key_exists('origin',$_REQUEST)) ? intval($_REQUEST['origin']) : 1);
	
		// To represent message-ids on other networks - this will create an iconfig record
	
		$namespace = (($api_source && array_key_exists('namespace',$_REQUEST)) ? strip_tags($_REQUEST['namespace']) : '');
		$remote_id = (($api_source && array_key_exists('remote_id',$_REQUEST)) ? strip_tags($_REQUEST['remote_id']) : '');
	
		$owner_hash = null;
	
		$message_id  = ((x($_REQUEST,'message_id') && $api_source)  ? strip_tags($_REQUEST['message_id'])       : '');
		$created     = ((x($_REQUEST,'created'))     ? datetime_convert(date_default_timezone_get(),'UTC',$_REQUEST['created']) : datetime_convert());
		$post_id     = ((x($_REQUEST,'post_id'))     ? intval($_REQUEST['post_id'])        : 0);
		$app         = ((x($_REQUEST,'source'))      ? strip_tags($_REQUEST['source'])     : '');
		$return_path = ((x($_REQUEST,'return'))      ? $_REQUEST['return']                 : '');
		$preview     = ((x($_REQUEST,'preview'))     ? intval($_REQUEST['preview'])        : 0);
		$categories  = ((x($_REQUEST,'category'))    ? escape_tags($_REQUEST['category'])  : '');
		$webpage     = ((x($_REQUEST,'webpage'))     ? intval($_REQUEST['webpage'])        : 0);
		$pagetitle   = ((x($_REQUEST,'pagetitle'))   ? escape_tags(urlencode($_REQUEST['pagetitle'])) : '');
		$layout_mid  = ((x($_REQUEST,'layout_mid'))  ? escape_tags($_REQUEST['layout_mid']): '');
		$plink       = ((x($_REQUEST,'permalink'))   ? escape_tags($_REQUEST['permalink']) : '');
		$obj_type    = ((x($_REQUEST,'obj_type'))    ? escape_tags($_REQUEST['obj_type'])  : ACTIVITY_OBJ_NOTE);
	
		// allow API to bulk load a bunch of imported items with sending out a bunch of posts. 
		$nopush      = ((x($_REQUEST,'nopush'))      ? intval($_REQUEST['nopush'])         : 0);
	
		/*
		 * Check service class limits
		 */
		if ($uid && !(x($_REQUEST,'parent')) && !(x($_REQUEST,'post_id'))) {
			$ret = $this->item_check_service_class($uid,(($_REQUEST['webpage'] == ITEM_TYPE_WEBPAGE) ? true : false));
			if (!$ret['success']) { 
				notice( t($ret['message']) . EOL) ;
				if($api_source)
					return ( [ 'success' => false, 'message' => 'service class exception' ] );	
				if(x($_REQUEST,'return')) 
					goaway(z_root() . "/" . $return_path );
				killme();
			}
		}
	
		if($pagetitle) {
			require_once('library/urlify/URLify.php');
			$pagetitle = strtolower(\URLify::transliterate($pagetitle));
		}
	
	
		$item_flags = $item_restrict = 0;
	
		$route = '';
		$parent_item = null;
		$parent_contact = null;
		$thr_parent = '';
		$parid = 0;
		$r = false;
	
		if($parent || $parent_mid) {
	
			if(! x($_REQUEST,'type'))
				$_REQUEST['type'] = 'net-comment';
	
			if($obj_type == ACTIVITY_OBJ_POST)
				$obj_type = ACTIVITY_OBJ_COMMENT;
	
			if($parent) {
				$r = q("SELECT * FROM item WHERE id = %d LIMIT 1",
					intval($parent)
				);
			}
			elseif($parent_mid && $uid) {
				// This is coming from an API source, and we are logged in
				$r = q("SELECT * FROM item WHERE mid = '%s' AND uid = %d LIMIT 1",
					dbesc($parent_mid),
					intval($uid)
				);
			}
			// if this isn't the real parent of the conversation, find it
			if($r !== false && count($r)) {
				$parid = $r[0]['parent'];
				$parent_mid = $r[0]['mid'];
				if($r[0]['id'] != $r[0]['parent']) {
					$r = q("SELECT * FROM item WHERE id = parent AND parent = %d LIMIT 1",
						intval($parid)
					);
				}
			}
	
			if(($r === false) || (! count($r))) {
				notice( t('Unable to locate original post.') . EOL);
				if($api_source)
					return ( [ 'success' => false, 'message' => 'invalid post id' ] );	
				if(x($_REQUEST,'return')) 
					goaway(z_root() . "/" . $return_path );
				killme();
			}
	
			// can_comment_on_post() needs info from the following xchan_query 
			// This may be from the discover tab which means we need to correct the effective uid

			xchan_query($r,true,(($r[0]['uid'] == local_channel()) ? 0 : local_channel()));
	
			$parent_item = $r[0];
			$parent = $r[0]['id'];
	
			// multi-level threading - preserve the info but re-parent to our single level threading
	
			$thr_parent = $parent_mid;
	
			$route = $parent_item['route'];
	
		}
	
		if(! $observer)
			$observer = \App::get_observer();
	
		if($parent) {
			logger('mod_item: item_post parent=' . $parent);
			$can_comment = false;
			if((array_key_exists('owner',$parent_item)) && intval($parent_item['owner']['abook_self']))
				$can_comment = perm_is_allowed($profile_uid,$observer['xchan_hash'],'post_comments');
			else
				$can_comment = can_comment_on_post($observer['xchan_hash'],$parent_item);
	
			if(! $can_comment) {
				notice( t('Permission denied.') . EOL) ;
				if($api_source)
					return ( [ 'success' => false, 'message' => 'permission denied' ] );	
				if(x($_REQUEST,'return')) 
					goaway(z_root() . "/" . $return_path );
				killme();
			}
		}
		else {
			if(! perm_is_allowed($profile_uid,$observer['xchan_hash'],($webpage) ? 'write_pages' : 'post_wall')) {
				notice( t('Permission denied.') . EOL) ;
				if($api_source)
					return ( [ 'success' => false, 'message' => 'permission denied' ] );	
				if(x($_REQUEST,'return')) 
					goaway(z_root() . "/" . $return_path );
				killme();
			}
		}
	
	
		// is this an edited post?
	
		$orig_post = null;
	
		if($namespace && $remote_id) {
			// It wasn't an internally generated post - see if we've got an item matching this remote service id
			$i = q("select iid from iconfig where cat = 'system' and k = '%s' and v = '%s' limit 1",
				dbesc($namespace),
				dbesc($remote_id) 
			);
			if($i)
				$post_id = $i[0]['iid'];	
		}
	
		$iconfig = null;
	
		if($post_id) {
			$i = q("SELECT * FROM item WHERE uid = %d AND id = %d LIMIT 1",
				intval($profile_uid),
				intval($post_id)
			);
			if(! count($i))
				killme();
			$orig_post = $i[0];
			$iconfig = q("select * from iconfig where iid = %d",
				intval($post_id)
			);
		}
	
	
		if(! $channel) {
			if($uid && $uid == $profile_uid) {
				$channel = \App::get_channel();
			}
			else {
				// posting as yourself but not necessarily to a channel you control
				$r = q("select * from channel left join account on channel_account_id = account_id where channel_id = %d LIMIT 1",
					intval($profile_uid)
				);
				if($r)
					$channel = $r[0];
			}
		}
	
	
		if(! $channel) {
			logger("mod_item: no channel.");
			if($api_source)
				return ( [ 'success' => false, 'message' => 'no channel' ] );	
			if(x($_REQUEST,'return')) 
				goaway(z_root() . "/" . $return_path );
			killme();
		}
	
		$owner_xchan = null;
	
		$r = q("select * from xchan where xchan_hash = '%s' limit 1",
			dbesc($channel['channel_hash'])
		);
		if($r && count($r)) {
			$owner_xchan = $r[0];
		}
		else {
			logger("mod_item: no owner.");
			if($api_source)
				return ( [ 'success' => false, 'message' => 'no owner' ] );	
			if(x($_REQUEST,'return')) 
				goaway(z_root() . "/" . $return_path );
			killme();
		}
	
		$walltowall = false;
		$walltowall_comment = false;
	
		if($remote_xchan)
			$observer = $remote_observer;
	
		if($observer) {
			logger('mod_item: post accepted from ' . $observer['xchan_name'] . ' for ' . $owner_xchan['xchan_name'], LOGGER_DEBUG);
	
			// wall-to-wall detection.
			// For top-level posts, if the author and owner are different it's a wall-to-wall
			// For comments, We need to additionally look at the parent and see if it's a wall post that originated locally.
	
			if($observer['xchan_name'] != $owner_xchan['xchan_name'])  {
				if(($parent_item) && ($parent_item['item_wall'] && $parent_item['item_origin'])) {
					$walltowall_comment = true;
					$walltowall = true;
				}
				if(! $parent) {
					$walltowall = true;		
				}
			}
		}
	
		$acl = new \Zotlabs\Access\AccessList($channel);

		$view_policy = \Zotlabs\Access\PermissionLimits::Get($channel['channel_id'],'view_stream');	
		$comment_policy = \Zotlabs\Access\PermissionLimits::Get($channel['channel_id'],'post_comments');
	
		$public_policy = ((x($_REQUEST,'public_policy')) ? escape_tags($_REQUEST['public_policy']) : map_scope($view_policy,true));
		if($webpage)
			$public_policy = '';
		if($public_policy)
			$private = 1;
	
		if($orig_post) {
			$private = 0;
			// webpages are allowed to change ACLs after the fact. Normal conversation items aren't. 
			if($webpage) {
				$acl->set_from_array($_REQUEST);
			}
			else {
				$acl->set($orig_post);
				$public_policy     = $orig_post['public_policy'];
				$private           = $orig_post['item_private'];
			}
	
			if($private || $public_policy || $acl->is_private())
				$private = 1;
	
	
			$location          = $orig_post['location'];
			$coord             = $orig_post['coord'];
			$verb              = $orig_post['verb'];
			$app               = $orig_post['app'];
			$title             = escape_tags(trim($_REQUEST['title']));
			$body              = trim($_REQUEST['body']);
			$item_flags        = $orig_post['item_flags'];
	
			$item_origin   = $orig_post['item_origin'];
			$item_unseen   = $orig_post['item_unseen'];
			$item_starred   = $orig_post['item_starred'];
			$item_uplink   = $orig_post['item_uplink'];
			$item_consensus   = $orig_post['item_consensus'];
			$item_wall   = $orig_post['item_wall'];
			$item_thread_top   = $orig_post['item_thread_top'];
			$item_notshown   = $orig_post['item_notshown'];
			$item_nsfw   = $orig_post['item_nsfw'];
			$item_relay   = $orig_post['item_relay'];
			$item_mentionsme   = $orig_post['item_mentionsme'];
			$item_nocomment   = $orig_post['item_nocomment'];
			$item_obscured   = $orig_post['item_obscured'];
			$item_verified   = $orig_post['item_verified'];
			$item_retained   = $orig_post['item_retained'];
			$item_rss   = $orig_post['item_rss'];
			$item_deleted   = $orig_post['item_deleted'];
			$item_type   = $orig_post['item_type'];
			$item_hidden   = $orig_post['item_hidden'];
			$item_unpublished   = $orig_post['item_unpublished'];
			$item_delayed   = $orig_post['item_delayed'];
			$item_pending_remove   = $orig_post['item_pending_remove'];
			$item_blocked   = $orig_post['item_blocked'];
	
	
	
			$postopts          = $orig_post['postopts'];
			$created           = $orig_post['created'];
			$mid               = $orig_post['mid'];
			$parent_mid        = $orig_post['parent_mid'];
			$plink             = $orig_post['plink'];
	
		}
		else {
			if(! $walltowall) {
				if((array_key_exists('contact_allow',$_REQUEST))
					|| (array_key_exists('group_allow',$_REQUEST))
					|| (array_key_exists('contact_deny',$_REQUEST))
					|| (array_key_exists('group_deny',$_REQUEST))) {
					$acl->set_from_array($_REQUEST);
				}
				elseif(! $api_source) {
	
					// if no ACL has been defined and we aren't using the API, the form
					// didn't send us any parameters. This means there's no ACL or it has
					// been reset to the default audience.
					// If $api_source is set and there are no ACL parameters, we default
					// to the channel permissions which were set in the ACL contructor.
	
					$acl->set(array('allow_cid' => '', 'allow_gid' => '', 'deny_cid' => '', 'deny_gid' => ''));
				}
			}
	
	
			$location          = notags(trim($_REQUEST['location']));
			$coord             = notags(trim($_REQUEST['coord']));
			$verb              = notags(trim($_REQUEST['verb']));
			$title             = escape_tags(trim($_REQUEST['title']));
			$body              = trim($_REQUEST['body']);
			$body              .= trim($_REQUEST['attachment']);
			$postopts          = '';
	
			$private = intval($acl->is_private() || ($public_policy));
	
			// If this is a comment, set the permissions from the parent.
	
			if($parent_item) {
				$private = 0;
				$acl->set($parent_item);
				$private = intval($acl->is_private() || $parent_item['item_private']);
				$public_policy     = $parent_item['public_policy'];
				$owner_hash        = $parent_item['owner_xchan'];
			}
		
			if(! strlen($body)) {
				if($preview)
					killme();
				info( t('Empty post discarded.') . EOL );
				if($api_source)
					return ( [ 'success' => false, 'message' => 'no content' ] );	
				if(x($_REQUEST,'return')) 
					goaway(z_root() . "/" . $return_path );
				killme();
			}
		}
		
	
		$expires = NULL_DATE;
	
		if(feature_enabled($profile_uid,'content_expire')) {
			if(x($_REQUEST,'expire')) {
				$expires = datetime_convert(date_default_timezone_get(),'UTC', $_REQUEST['expire']);
				if($expires <= datetime_convert())
					$expires = NULL_DATE;
			}
		}
	
		$mimetype = notags(trim($_REQUEST['mimetype']));
		if(! $mimetype)
			$mimetype = 'text/bbcode';
	
		if($preview) {
			$body = z_input_filter($profile_uid,$body,$mimetype);
		}
	
	
		// Verify ability to use html or php!!!
	
		$execflag = false;
	
		if($mimetype !== 'text/bbcode') {
			$z = q("select account_id, account_roles, channel_pageflags from account left join channel on channel_account_id = account_id where channel_id = %d limit 1",
				intval($profile_uid)
			);
			if($z && (($z[0]['account_roles'] & ACCOUNT_ROLE_ALLOWCODE) || ($z[0]['channel_pageflags'] & PAGE_ALLOWCODE))) {
				if($uid && (get_account_id() == $z[0]['account_id'])) {
					$execflag = true;
				}
				else {
					notice( t('Executable content type not permitted to this channel.') . EOL);
					if($api_source)
						return ( [ 'success' => false, 'message' => 'forbidden content type' ] );	
					if(x($_REQUEST,'return')) 
						goaway(z_root() . "/" . $return_path );
					killme();
				}
			}
		}
	
		$gacl = $acl->get();
		$str_contact_allow = $gacl['allow_cid'];
		$str_group_allow   = $gacl['allow_gid'];
		$str_contact_deny  = $gacl['deny_cid'];
		$str_group_deny    = $gacl['deny_gid'];
	
		if($mimetype === 'text/bbcode') {
	
			require_once('include/text.php');			
	
			// Markdown doesn't work correctly. Do not re-enable unless you're willing to fix it and support it.
	
			// Sample that will probably give you grief - you must preserve the linebreaks
			// and provide the correct markdown interpretation and you cannot allow unfiltered HTML
	
			// Markdown
			// ========
			//
			// **bold** abcde
			// fghijkl
			// *italic*
			// <img src="javascript:alert('hacked');" />
	
	//		if($uid && $uid == $profile_uid && feature_enabled($uid,'markdown')) {
	//			require_once('include/bb2diaspora.php');
	//			$body = escape_tags(trim($body));
	//			$body = str_replace("\n",'<br />', $body);
	//			$body = preg_replace_callback('/\[share(.*?)\]/ism','\share_shield',$body);			
	//			$body = markdown_to_bb($body,true);
	//			$body = preg_replace_callback('/\[share(.*?)\]/ism','\share_unshield',$body);
	//		}
	
			// BBCODE alert: the following functions assume bbcode input
			// and will require alternatives for alternative content-types (text/html, text/markdown, text/plain, etc.)
			// we may need virtual or template classes to implement the possible alternatives
	
			// Work around doubled linefeeds in Tinymce 3.5b2
			// First figure out if it's a status post that would've been
			// created using tinymce. Otherwise leave it alone. 
	
			$plaintext = true;
	
	//		$plaintext = ((feature_enabled($profile_uid,'richtext')) ? false : true);
	//		if((! $parent) && (! $api_source) && (! $plaintext)) {
	//			$body = fix_mce_lf($body);
	//		}
	
	
	
			// If we're sending a private top-level message with a single @-taggable channel as a recipient, @-tag it, if our pconfig is set.
	
	
			if((! $parent) && (get_pconfig($profile_uid,'system','tagifonlyrecip')) && (substr_count($str_contact_allow,'<') == 1) && ($str_group_allow == '') && ($str_contact_deny == '') && ($str_group_deny == '')) {
				$x = q("select abook_id, abconfig.v from abook left join abconfig on abook_xchan = abconfig.xchan and abook_channel = abconfig.chan and cat= 'their_perms' and abconfig.k = 'tag_deliver' and abconfig.v = 1 and abook_xchan = '%s' and abook_channel = %d limit 1",
					dbesc(str_replace(array('<','>'),array('',''),$str_contact_allow)),
					intval($profile_uid)
				);
				if($x)
					$body .= "\n\n@group+" . $x[0]['abook_id'] . "\n";
			}
	
			/**
			 * fix naked links by passing through a callback to see if this is a hubzilla site
			 * (already known to us) which will get a zrl, otherwise link with url, add bookmark tag to both.
			 * First protect any url inside certain bbcode tags so we don't double link it.
			 */
	
	
			$body = preg_replace_callback('/\[code(.*?)\[\/(code)\]/ism','\red_escape_codeblock',$body);
			$body = preg_replace_callback('/\[url(.*?)\[\/(url)\]/ism','\red_escape_codeblock',$body);
			$body = preg_replace_callback('/\[zrl(.*?)\[\/(zrl)\]/ism','\red_escape_codeblock',$body);
	

			$body = preg_replace_callback("/([^\]\='".'"'."\/]|^|\#\^)(https?\:\/\/[a-zA-Z0-9\:\/\-\?\&\;\.\=\@\_\~\#\%\$\!\+\,\(\)]+)/ism", 'nakedoembed', $body);
			$body = preg_replace_callback("/([^\]\='".'"'."\/]|^|\#\^)(https?\:\/\/[a-zA-Z0-9\:\/\-\?\&\;\.\=\@\_\~\#\%\$\!\+\,\(\)]+)/ism", '\red_zrl_callback', $body);
	
			$body = preg_replace_callback('/\[\$b64zrl(.*?)\[\/(zrl)\]/ism','\red_unescape_codeblock',$body);
			$body = preg_replace_callback('/\[\$b64url(.*?)\[\/(url)\]/ism','\red_unescape_codeblock',$body);
			$body = preg_replace_callback('/\[\$b64code(.*?)\[\/(code)\]/ism','\red_unescape_codeblock',$body);
	
	
			// fix any img tags that should be zmg
	
			$body = preg_replace_callback('/\[img(.*?)\](.*?)\[\/img\]/ism','\red_zrlify_img_callback',$body);
	
	
			$body = bb_translate_video($body);
	
			/**
			 * Fold multi-line [code] sequences
			 */
	
			$body = preg_replace('/\[\/code\]\s*\[code\]/ism',"\n",$body); 
	
			$body = scale_external_images($body,false);
	
	
			// Look for tags and linkify them
			$results = linkify_tags($a, $body, ($uid) ? $uid : $profile_uid);
	
			if($results) {
	
				// Set permissions based on tag replacements
				set_linkified_perms($results, $str_contact_allow, $str_group_allow, $profile_uid, $parent_item, $private);
	
				$post_tags = array();
				foreach($results as $result) {
					$success = $result['success'];
					if($success['replaced']) {
						$post_tags[] = array(
							'uid'   => $profile_uid, 
							'ttype' => $success['termtype'],
							'otype' => TERM_OBJ_POST,
							'term'  => $success['term'],
							'url'   => $success['url']
						); 				
					}
				}
			}
	
	
			/**
			 *
			 * When a photo was uploaded into the message using the (profile wall) ajax 
			 * uploader, The permissions are initially set to disallow anybody but the
			 * owner from seeing it. This is because the permissions may not yet have been
			 * set for the post. If it's private, the photo permissions should be set
			 * appropriately. But we didn't know the final permissions on the post until
			 * now. So now we'll look for links of uploaded photos and attachments that are in the
			 * post and set them to the same permissions as the post itself.
			 *
			 * If the post was end-to-end encrypted we can't find images and attachments in the body,
			 * use our media_str input instead which only contains these elements - but only do this
			 * when encrypted content exists because the photo/attachment may have been removed from 
			 * the post and we should keep it private. If it's encrypted we have no way of knowing
			 * so we'll set the permissions regardless and realise that the media may not be 
			 * referenced in the post. 
			 *
			 * What is preventing us from being able to upload photos into comments is dealing with
			 * the photo and attachment permissions, since we don't always know who was in the 
			 * distribution for the top level post.
			 * 
			 * We might be able to provide this functionality with a lot of fiddling:
			 * - if the top level post is public (make the photo public)
			 * - if the top level post was written by us or a wall post that belongs to us (match the top level post)
			 * - if the top level post has privacy mentions, add those to the permissions.
			 * - otherwise disallow the photo *or* make the photo public. This is the part that gets messy. 
			 */
	
			if(! $preview) {
				fix_attached_photo_permissions($profile_uid,$owner_xchan['xchan_hash'],((strpos($body,'[/crypt]')) ? $_POST['media_str'] : $body),$str_contact_allow,$str_group_allow,$str_contact_deny,$str_group_deny);
	
				fix_attached_file_permissions($channel,$observer['xchan_hash'],((strpos($body,'[/crypt]')) ? $_POST['media_str'] : $body),$str_contact_allow,$str_group_allow,$str_contact_deny,$str_group_deny);
	
			}
	
	
			$attachments = '';
			$match = false;
	
			if(preg_match_all('/(\[attachment\](.*?)\[\/attachment\])/',$body,$match)) {
				$attachments = array();
				$i = 0;
				foreach($match[2] as $mtch) {
					$attach_link = '';
					$hash = substr($mtch,0,strpos($mtch,','));
					$rev = intval(substr($mtch,strpos($mtch,',')));
					$r = attach_by_hash_nodata($hash,$rev);
					if($r['success']) {
						$attachments[] = array(
							'href'     => z_root() . '/attach/' . $r['data']['hash'],
							'length'   => $r['data']['filesize'],
							'type'     => $r['data']['filetype'],
							'title'    => urlencode($r['data']['filename']),
							'revision' => $r['data']['revision']
						);
					}
					$ext = substr($r['data']['filename'],strrpos($r['data']['filename'],'.'));
					if(strpos($r['data']['filetype'],'audio/') !== false)
						$attach_link =  '[audio]' . z_root() . '/attach/' . $r['data']['hash'] . '/' . $r['data']['revision'] . (($ext) ? $ext : '') . '[/audio]';
					elseif(strpos($r['data']['filetype'],'video/') !== false)
						$attach_link =  '[video]' . z_root() . '/attach/' . $r['data']['hash'] . '/' . $r['data']['revision'] . (($ext) ? $ext : '') . '[/video]';
					$body = str_replace($match[1][$i],$attach_link,$body);
					$i++;
				}
			}
	
		}
	
	// BBCODE end alert
	
		if(strlen($categories)) {
			$cats = explode(',',$categories);
			foreach($cats as $cat) {
				$post_tags[] = array(
					'uid'   => $profile_uid, 
					'ttype' => TERM_CATEGORY,
					'otype' => TERM_OBJ_POST,
					'term'  => trim($cat),
					'url'   => $owner_xchan['xchan_url'] . '?f=&cat=' . urlencode(trim($cat))
				); 				
			}
		}
	
		if($orig_post) {
			// preserve original tags
			$t = q("select * from term where oid = %d and otype = %d and uid = %d and ttype in ( %d, %d, %d )",
				intval($orig_post['id']),
				intval(TERM_OBJ_POST),
				intval($profile_uid),
				intval(TERM_UNKNOWN),
				intval(TERM_FILE),
				intval(TERM_COMMUNITYTAG)
			);
			if($t) {
				foreach($t as $t1) {
					$post_tags[] = array(
						'uid'   => $profile_uid, 
						'ttype' => $t1['type'],
						'otype' => TERM_OBJ_POST,
						'term'  => $t1['term'],
						'url'   => $t1['url'],
					); 				
				}
			}
		} 
	
	
		$item_unseen = ((local_channel() != $profile_uid) ? 1 : 0);
		$item_wall = (($post_type === 'wall' || $post_type === 'wall-comment') ? 1 : 0);
		$item_origin = (($origin) ? 1 : 0);
		$item_consensus = (($consensus) ? 1 : 0);
		$item_nocomment = (($nocomment) ? 1 : 0);
	
	
		// determine if this is a wall post
	
		if($parent) {
			$item_wall = $parent_item['item_wall'];
		}
		else {
			if(! $webpage) {
				$item_wall = 1;
			}
		}
	
	
		if($moderated)
			$item_blocked = ITEM_MODERATED;
	
			
		if(! strlen($verb))
			$verb = ACTIVITY_POST ;
	
		$notify_type = (($parent) ? 'comment-new' : 'wall-new' );
	
		if(! $mid) {
			$mid = (($message_id) ? $message_id : item_message_id());
		}
		if(! $parent_mid) {
			$parent_mid = $mid;
		}
	
		if($parent_item)
			$parent_mid = $parent_item['mid'];
	
		// Fallback so that we alway have a thr_parent
	
		if(!$thr_parent)
			$thr_parent = $mid;
	
		$datarray = array();
	
		$item_thread_top = ((! $parent) ? 1 : 0);
	
		if ((! $plink) && ($item_thread_top)) {
			$plink = z_root() . '/channel/' . $channel['channel_address'] . '/?f=&mid=' . $mid;
		}
		
		$datarray['aid']                 = $channel['channel_account_id'];
		$datarray['uid']                 = $profile_uid;
		$datarray['owner_xchan']         = (($owner_hash) ? $owner_hash : $owner_xchan['xchan_hash']);
		$datarray['author_xchan']        = $observer['xchan_hash'];
		$datarray['created']             = $created;
		$datarray['edited']              = (($orig_post) ? datetime_convert() : $created);
		$datarray['expires']             = $expires;
		$datarray['commented']           = (($orig_post) ? datetime_convert() : $created);
		$datarray['received']            = (($orig_post) ? datetime_convert() : $created);
		$datarray['changed']             = (($orig_post) ? datetime_convert() : $created);
		$datarray['mid']                 = $mid;
		$datarray['parent_mid']          = $parent_mid;
		$datarray['mimetype']            = $mimetype;
		$datarray['title']               = $title;
		$datarray['body']                = $body;
		$datarray['app']                 = $app;
		$datarray['location']            = $location;
		$datarray['coord']               = $coord;
		$datarray['verb']                = $verb;
		$datarray['obj_type']            = $obj_type;
		$datarray['allow_cid']           = $str_contact_allow;
		$datarray['allow_gid']           = $str_group_allow;
		$datarray['deny_cid']            = $str_contact_deny;
		$datarray['deny_gid']            = $str_group_deny;
		$datarray['attach']              = $attachments;
		$datarray['thr_parent']          = $thr_parent;
		$datarray['postopts']            = $postopts;
		$datarray['item_unseen']         = intval($item_unseen);
		$datarray['item_wall']           = intval($item_wall);
		$datarray['item_origin']         = intval($item_origin);
		$datarray['item_type']           = $webpage;
		$datarray['item_private']        = intval($private);
		$datarray['item_thread_top']     = intval($item_thread_top);
		$datarray['item_unseen']         = intval($item_unseen);
		$datarray['item_starred']        = intval($item_starred);
		$datarray['item_uplink']         = intval($item_uplink);
		$datarray['item_consensus']      = intval($item_consensus);
		$datarray['item_notshown']       = intval($item_notshown);
		$datarray['item_nsfw']           = intval($item_nsfw);
		$datarray['item_relay']          = intval($item_relay);
		$datarray['item_mentionsme']     = intval($item_mentionsme);
		$datarray['item_nocomment']      = intval($item_nocomment);
		$datarray['item_obscured']       = intval($item_obscured);
		$datarray['item_verified']       = intval($item_verified);
		$datarray['item_retained']       = intval($item_retained);
		$datarray['item_rss']            = intval($item_rss);
		$datarray['item_deleted']        = intval($item_deleted);
		$datarray['item_hidden']         = intval($item_hidden);
		$datarray['item_unpublished']    = intval($item_unpublished);
		$datarray['item_delayed']        = intval($item_delayed);
		$datarray['item_pending_remove'] = intval($item_pending_remove);
		$datarray['item_blocked']        = intval($item_blocked);	
		$datarray['layout_mid']          = $layout_mid;
		$datarray['public_policy']       = $public_policy;
		$datarray['comment_policy']      = map_scope($comment_policy); 
		$datarray['term']                = $post_tags;
		$datarray['plink']               = $plink;
		$datarray['route']               = $route;
	
		if($iconfig)
			$datarray['iconfig'] = $iconfig;
	
		// preview mode - prepare the body for display and send it via json
	
		if($preview) {
			require_once('include/conversation.php');
	
			$datarray['owner'] = $owner_xchan;
			$datarray['author'] = $observer;
			$datarray['attach'] = json_encode($datarray['attach']);
			$o = conversation($a,array($datarray),'search',false,'preview');
	//		logger('preview: ' . $o, LOGGER_DEBUG);
			echo json_encode(array('preview' => $o));
			killme();
		}
		if($orig_post)
			$datarray['edit'] = true;
	
		// suppress duplicates, *unless* you're editing an existing post. This could get picked up
		// as a duplicate if you're editing it very soon after posting it initially and you edited
		// some attribute besides the content, such as title or categories. 

		if(feature_enabled($profile_uid,'suppress_duplicates') && (! $orig_post)) {
	
			$z = q("select created from item where uid = %d and created > %s - INTERVAL %s and body = '%s' limit 1",
				intval($profile_uid),
				db_utcnow(),
				db_quoteinterval('2 MINUTE'),
				dbesc($body)
			);
	
			if($z) {
				$datarray['cancel'] = 1;
				notice( t('Duplicate post suppressed.') . EOL);
				logger('Duplicate post. Faking plugin cancel.');
			}
		}
	
		call_hooks('post_local',$datarray);
	
		if(x($datarray,'cancel')) {
			logger('mod_item: post cancelled by plugin or duplicate suppressed.');
			if($return_path)
				goaway(z_root() . "/" . $return_path);
			if($api_source)
				return ( [ 'success' => false, 'message' => 'operation cancelled' ] );	
			$json = array('cancel' => 1);
			$json['reload'] = z_root() . '/' . $_REQUEST['jsreload'];
			echo json_encode($json);
			killme();
		}
	
	
		if(mb_strlen($datarray['title']) > 255)
			$datarray['title'] = mb_substr($datarray['title'],0,255);
	
		if(array_key_exists('item_private',$datarray) && $datarray['item_private']) {
	
			$datarray['body'] = trim(z_input_filter($datarray['uid'],$datarray['body'],$datarray['mimetype']));
	
			if($uid) {
				if($channel['channel_hash'] === $datarray['author_xchan']) {
					$datarray['sig'] = base64url_encode(rsa_sign($datarray['body'],$channel['channel_prvkey']));
					$datarray['item_verified'] = 1;
				}
			}
		}
	
		if($webpage) {
			Zlib\IConfig::Set($datarray,'system', webpage_to_namespace($webpage),
				(($pagetitle) ? $pagetitle : substr($datarray['mid'],0,16)),true);
		}
		elseif($namespace) {
			Zlib\IConfig::Set($datarray,'system', $namespace,
				(($remote_id) ? $remote_id : substr($datarray['mid'],0,16)),true);
		}


		if($orig_post) {
			$datarray['id'] = $post_id;
	
			$x = item_store_update($datarray,$execflag);
			
			item_create_edit_activity($x);			

			if(! $parent) {
				$r = q("select * from item where id = %d",
					intval($post_id)
				);
				if($r) {
					xchan_query($r);
					$sync_item = fetch_post_tags($r);
					build_sync_packet($profile_uid,array('item' => array(encode_item($sync_item[0],true))));
				}
			}
			if(! $nopush)
				\Zotlabs\Daemon\Master::Summon(array('Notifier', 'edit_post', $post_id));
	

			if($api_source)
				return($x);

			if((x($_REQUEST,'return')) && strlen($return_path)) {
				logger('return: ' . $return_path);
				goaway(z_root() . "/" . $return_path );
			}
			killme();
		}
		else
			$post_id = 0;
	
		$post = item_store($datarray,$execflag);
	
		$post_id = $post['item_id'];

		$datarray = $post['item'];

		if($post_id) {
			logger('mod_item: saved item ' . $post_id);
	
			if($parent) {
	
				// only send comment notification if this is a wall-to-wall comment,
				// otherwise it will happen during delivery
	
				if(($datarray['owner_xchan'] != $datarray['author_xchan']) && (intval($parent_item['item_wall']))) {
					Zlib\Enotify::submit(array(
						'type'         => NOTIFY_COMMENT,
						'from_xchan'   => $datarray['author_xchan'],
						'to_xchan'     => $datarray['owner_xchan'],
						'item'         => $datarray,
						'link'		   => z_root() . '/display/' . $datarray['mid'],
						'verb'         => ACTIVITY_POST,
						'otype'        => 'item',
						'parent'       => $parent,
						'parent_mid'   => $parent_item['mid']
					));
				
				}
			}
			else {
				$parent = $post_id;
	
				if(($datarray['owner_xchan'] != $datarray['author_xchan']) && ($datarray['item_type'] == ITEM_TYPE_POST)) {
					Zlib\Enotify::submit(array(
						'type'         => NOTIFY_WALL,
						'from_xchan'   => $datarray['author_xchan'],
						'to_xchan'     => $datarray['owner_xchan'],
						'item'         => $datarray,
						'link'		   => z_root() . '/display/' . $datarray['mid'],
						'verb'         => ACTIVITY_POST,
						'otype'        => 'item'
					));
				}
	
				if($uid && $uid == $profile_uid && (is_item_normal($datarray))) {
					q("update channel set channel_lastpost = '%s' where channel_id = %d",
						dbesc(datetime_convert()),
						intval($uid)
					);
				}
			}
	
			// photo comments turn the corresponding item visible to the profile wall
			// This way we don't see every picture in your new photo album posted to your wall at once.
			// They will show up as people comment on them.
	
			if(intval($parent_item['item_hidden'])) {
				$r = q("UPDATE item SET item_hidden = 0 WHERE id = %d",
					intval($parent_item['id'])
				);
			}
		}
		else {
			logger('mod_item: unable to retrieve post that was just stored.');
			notice( t('System error. Post not saved.') . EOL);
			if($return_path)
				goaway(z_root() . "/" . $return_path );
			if($api_source)
				return ( [ 'success' => false, 'message' => 'system error' ] );
			killme();
		}
		
		if(($parent) && ($parent != $post_id)) {
			// Store the comment signature information in case we need to relay to Diaspora
			//$ditem = $datarray;
			//$ditem['author'] = $observer;
			//store_diaspora_comment_sig($ditem,$channel,$parent_item, $post_id, (($walltowall_comment) ? 1 : 0));
		}
		else {
			$r = q("select * from item where id = %d",
				intval($post_id)
			);
			if($r) {
				xchan_query($r);
				$sync_item = fetch_post_tags($r);
				build_sync_packet($profile_uid,array('item' => array(encode_item($sync_item[0],true))));
			}
		}
	
		$datarray['id']    = $post_id;
		$datarray['llink'] = z_root() . '/display/' . $channel['channel_address'] . '/' . $post_id;
	
		call_hooks('post_local_end', $datarray);
	
		if(! $nopush)
			\Zotlabs\Daemon\Master::Summon(array('Notifier', $notify_type, $post_id));
	
		logger('post_complete');
	
		// figure out how to return, depending on from whence we came
	
		if($api_source)
			return $post;
	
		if($return_path) {
			goaway(z_root() . "/" . $return_path);
		}
	
		$json = array('success' => 1);
		if(x($_REQUEST,'jsreload') && strlen($_REQUEST['jsreload']))
			$json['reload'] = z_root() . '/' . $_REQUEST['jsreload'];
	
		logger('post_json: ' . print_r($json,true), LOGGER_DEBUG);
	
		echo json_encode($json);
		killme();
		// NOTREACHED
	}
	
	
	function get() {
	
		if((! local_channel()) && (! remote_channel()))
			return;
	
		require_once('include/security.php');
	
		if((argc() == 3) && (argv(1) === 'drop') && intval(argv(2))) {
	
			require_once('include/items.php');
			$i = q("select id, uid, author_xchan, owner_xchan, source_xchan, item_type from item where id = %d limit 1",
				intval(argv(2))
			);
	
			if($i) {
				$can_delete = false;
				$local_delete = false;
				if(local_channel() && local_channel() == $i[0]['uid'])
					$local_delete = true;
	
				$sys = get_sys_channel();
				if(is_site_admin() && $sys['channel_id'] == $i[0]['uid'])
					$can_delete = true;
	
				$ob_hash = get_observer_hash();
				if($ob_hash && ($ob_hash === $i[0]['author_xchan'] || $ob_hash === $i[0]['owner_xchan'] || $ob_hash === $i[0]['source_xchan']))
					$can_delete = true;
	
				if(! ($can_delete || $local_delete)) {
					notice( t('Permission denied.') . EOL);
					return;
				}
	
				// if this is a different page type or it's just a local delete
				// but not by the item author or owner, do a simple deletion
	
				if(intval($i[0]['item_type']) || ($local_delete && (! $can_delete))) {
					drop_item($i[0]['id']);
				}
				else {
					// complex deletion that needs to propagate and be performed in phases
					drop_item($i[0]['id'],true,DROPITEM_PHASE1);
					$r = q("select * from item where id = %d",
						intval($i[0]['id'])
					);
					if($r) {
						xchan_query($r);
						$sync_item = fetch_post_tags($r);
						build_sync_packet($i[0]['uid'],array('item' => array(encode_item($sync_item[0],true))));
					}
					tag_deliver($i[0]['uid'],$i[0]['id']);
				}
			}
		}
	}
	
	
	
	function item_check_service_class($channel_id,$iswebpage) {
		$ret = array('success' => false, 'message' => '');
	
		if ($iswebpage) {
			$r = q("select count(i.id)  as total from item i 
				right join channel c on (i.author_xchan=c.channel_hash and i.uid=c.channel_id )  
				and i.parent=i.id and i.item_type = %d and i.item_deleted = 0 and i.uid= %d ",
				intval(ITEM_TYPE_WEBPAGE),
				intval($channel_id)
			);
		}
		else {
			$r = q("select count(id) as total from item where parent = id and item_wall = 1 and uid = %d " . item_normal(),
				intval($channel_id)
			);
		}
	
		if(! $r) {
			$ret['message'] = t('Unable to obtain post information from database.');
			return $ret;
		} 
	
		if (!$iswebpage) {
			$max = engr_units_to_bytes(service_class_fetch($channel_id,'total_items'));
			if(! service_class_allows($channel_id,'total_items',$r[0]['total'])) {
				$result['message'] .= upgrade_message() . sprintf( t('You have reached your limit of %1$.0f top level posts.'),$max);
				return $result;
			}
		}
		else {
			$max = engr_units_to_bytes(service_class_fetch($channel_id,'total_pages'));
			if(! service_class_allows($channel_id,'total_pages',$r[0]['total'])) {
				$result['message'] .= upgrade_message() . sprintf( t('You have reached your limit of %1$.0f webpages.'),$max);
				return $result;
			}	
		}
	
		$ret['success'] = true;
		return $ret;
	}
	
	
}
