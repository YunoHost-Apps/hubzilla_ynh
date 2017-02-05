<?php
/**
 * @file include/items.php
 */

use Zotlabs\Lib as Zlib;

require_once('include/bbcode.php');
require_once('include/oembed.php');
require_once('include/crypto.php');
require_once('include/feedutils.php');
require_once('include/photo/photo_driver.php');
require_once('include/permissions.php');

/**
 * @brief Collects recipients.
 *
 * @param array $item
 * @param[out] boolean $private_envelope
 * @return array containing the recipients
 */
function collect_recipients($item, &$private_envelope) {

	require_once('include/group.php');

	$private_envelope = ((intval($item['item_private'])) ? true : false);
	$recipients = array();

	if($item['allow_cid'] || $item['allow_gid'] || $item['deny_cid'] || $item['deny_gid']) {

		// it is private

		$allow_people = expand_acl($item['allow_cid']);

		$allow_groups = expand_groups(expand_acl($item['allow_gid']));

		$recipients = array_unique(array_merge($allow_people,$allow_groups));

		// if you specifically deny somebody but haven't allowed anybody, we'll allow everybody in your
		// address book minus the denied connections. The post is still private and can't be seen publicly
		// as that would allow the denied person to see the post by logging out.

		if((! $item['allow_cid']) && (! $item['allow_gid'])) {
			$r = q("select * from abook where abook_channel = %d and abook_self = 0 and abook_pending = 0 and abook_archived = 0 ",
				intval($item['uid'])
			);

			if($r) {
				foreach($r as $rr) {
					$recipients[] = $rr['abook_xchan'];
				}
			}
		}

		$deny_people  = expand_acl($item['deny_cid']);
		$deny_groups  = expand_groups(expand_acl($item['deny_gid']));

		$deny = array_unique(array_merge($deny_people,$deny_groups));

		// Don't deny anybody if nobody was allowed (e.g. they were all filtered out)
		// That would lead to array_diff doing the wrong thing.
		// This will result in a private post that won't be delivered to anybody.

		if($recipients && $deny)
			$recipients = array_diff($recipients,$deny);

		$private_envelope = true;
	}
	else {

		// if the post is marked private but there are no recipients and public_policy/scope = self,
		// only add the author and owner as recipients. The ACL for the post may live on the hub of
		// a different clone. We need to get the post to that hub.

		// The post may be private by virtue of not being visible to anybody on the internet,
		// but there are no envelope recipients, so set this to false. Delivery is controlled
		// by the directives in $item['public_policy'].

		$private_envelope = false;
		require_once('include/channel.php');
		//$sys = get_sys_channel();

		if(array_key_exists('public_policy',$item) && $item['public_policy'] !== 'self') {
			$r = q("select abook_xchan, xchan_network from abook left join xchan on abook_xchan = xchan_hash where abook_channel = %d and abook_self = 0 and abook_pending = 0 and abook_archived = 0 ",
				intval($item['uid'])
			);
			if($r) {

				// filter out restrictive public_policy settings from remote networks
				// which don't have this concept and will treat them as public.

				$policy = substr($item['public_policy'],0,3);
				foreach($r as $rr) {
					switch($policy) {
						case 'net':
						case 'aut':
						case 'sit':
						case 'any':
						case 'con':
							if($rr['xchan_network'] != 'zot')
								break;
						case 'pub':
						case '':
						default:
							$recipients[] = $rr['abook_xchan'];
							break;
					}
				}
			}
// we probably want to check that discovery channel delivery is allowed before uncommenting this.
//			if($policy === 'pub')
//				$recipients[] = $sys['xchan_hash'];
		}

		// Add the authors of any posts in this thread, if they are known to us.
		// This is specifically designed to forward wall-to-wall posts to the original author,
		// in case they aren't a connection but have permission to write on our wall.
		// This is important for issue tracker channels. It should be a no-op for most channels.
		// Whether or not they will accept the delivery is not determined here, but should
		// be taken into account by zot:process_delivery()

		$r = q("select author_xchan from item where parent = %d",
			intval($item['parent'])
		);
		if($r) {
			foreach($r as $rv) {
				if(! in_array($rv['author_xchan'],$recipients)) {
					$recipients[] = $rv['author_xchan'];
				}
			}
		}

	}

	// This is a somewhat expensive operation but important.
	// Don't send this item to anybody who isn't allowed to see it

	$recipients = check_list_permissions($item['uid'],$recipients,'view_stream');

	// remove any upstream recipients from our list.
	// If it is ourself we'll add it back in a second.
	// This should prevent complex delivery chains from getting overly complex by not
	// sending to anybody who is on our list of those who sent it to us.

	if($item['route']) {
		$route = explode(',',$item['route']);
		if(count($route)) {
			$route = array_unique($route);
			$recipients = array_diff($recipients,$route);
		}
	}

	// add ourself just in case we have nomadic clones that need to get a copy.

	$recipients[] = $item['author_xchan'];
	if($item['owner_xchan'] != $item['author_xchan'])
		$recipients[] = $item['owner_xchan'];

	return $recipients;
}

function comments_are_now_closed($item) {
	if($item['comments_closed'] > NULL_DATE) {
		$d = datetime_convert();
		if($d > $item['comments_closed'])
			return true;
	}

	return false;
}

function item_normal() {
	return " and item.item_hidden = 0 and item.item_type = 0 and item.item_deleted = 0
		and item.item_unpublished = 0 and item.item_delayed = 0 and item.item_pending_remove = 0
		and item.item_blocked = 0 ";
}

/**
 * @brief
 *
 * This is a compatibility function primarily for plugins, because
 * in earlier DB schemas this was a much simpler single integer compare
 *
 * @param array $item
 */
function is_item_normal($item) {

	if(intval($item['item_hidden']) || intval($item['item_type']) || intval($item['item_deleted'])
		|| intval($item['item_unpublished']) || intval($item['item_delayed']) || intval($item['item_pending_remove'])
		|| intval($item['item_blocked']))
		return false;

	return true;
}

/**
 * @brief
 *
 * This function examines the comment_policy attached to an item and decides if the current observer has
 * sufficient privileges to comment. This will normally be called on a remote site where perm_is_allowed()
 * will not be suitable because the post owner does not have a local channel_id.
 * Generally we should look at the item - in particular the author['abook_flags'] and see if ABOOK_FLAG_SELF is set.
 * If it is, you should be able to use perm_is_allowed( ... 'post_comments'), and if it isn't you need to call
 * can_comment_on_post()
 * We also check the comments_closed date/time on the item if this is set.
 *
 * @param string $observer_xchan
 * @param array $item
 * @return boolean
 */
function can_comment_on_post($observer_xchan, $item) {

//	logger('can_comment_on_post: comment_policy: ' . $item['comment_policy'], LOGGER_DEBUG);

	if(! $observer_xchan)
		return false;

	if($item['comment_policy'] === 'none')
		return false;

	if(comments_are_now_closed($item))
		return false;

	if($observer_xchan === $item['author_xchan'] || $observer_xchan === $item['owner_xchan'])
		return true;

	switch($item['comment_policy']) {
		case 'self':
			if($observer_xchan === $item['author_xchan'] || $observer_xchan === $item['owner_xchan'])
				return true;
			break;
		case 'public':
			// We don't really allow or support public comments yet, but anonymous
			// folks won't ever reach this point (as $observer_xchan will be empty).
			// This means the viewer has an xchan and we can identify them.
			return true;
			break;
		case 'any connections':
		case 'contacts':
		case 'authenticated':
		case '':
			if(array_key_exists('owner',$item) && get_abconfig($item['uid'],$item['owner']['abook_xchan'],'their_perms','post_comments')) {
					return true;
			}
			break;
		default:
			break;
	}
	if(strstr($item['comment_policy'],'network:') && strstr($item['comment_policy'],'red'))
		return true;
	if(strstr($item['comment_policy'],'network:') && strstr($item['comment_policy'],'diaspora'))
		return true;
	if(strstr($item['comment_policy'],'site:') && strstr($item['comment_policy'],App::get_hostname()))
		return true;

	return false;
}

/**
 * @brief Adds $hash to the item source route specified by $iid.
 *
 * $item['route'] contains a comma-separated list of xchans that sent the current message,
 * somewhat analogous to the * Received: header line in email. We can use this to perform
 * loop detection and to avoid sending a particular item to any "upstream" sender (they
 * already have a copy because they sent it to us).
 *
 * Modifies item in the database pointed to by $iid.
 *
 * @param integer $iid
 *    item['id'] of target item
 * @param string $hash
 *    xchan_hash of the channel that sent the item
 */
function add_source_route($iid, $hash) {
//	logger('add_source_route ' . $iid . ' ' . $hash, LOGGER_DEBUG);

	if((! $iid) || (! $hash))
		return;

	$r = q("select route from item where id = %d limit 1",
		intval($iid)
	);
	if($r) {
		$new_route = (($r[0]['route']) ? $r[0]['route'] . ',' : '') . $hash;
		q("update item set route = '%s' where id = %d",
			(dbesc($new_route)),
			intval($iid)
		);
	}
}


/**
 * @brief Post an activity.
 *
 * In its simplest form one needs only to set $arr['body'] to post a note to the logged in channel's wall.
 * Much more complex activities can be created. Permissions are checked. No filtering, tag expansion
 * or other processing is performed.
 *
 * @param array $arr
 * @returns array
 *  * \e boolean \b success true or false
 *  * \e array \b activity the resulting activity if successful
 */
function post_activity_item($arr) {

	$ret = array('success' => false);

	$is_comment = false;
	if((($arr['parent']) && $arr['parent'] != $arr['id']) || (($arr['parent_mid']) && $arr['parent_mid'] != $arr['mid']))
		$is_comment = true;

	if(! array_key_exists('item_origin',$arr))
		$arr['item_origin'] = 1;
	if(! array_key_exists('item_wall',$arr) && (! $is_comment))
		$arr['item_wall'] = 1;
	if(! array_key_exists('item_thread_top',$arr) && (! $is_comment))
		$arr['item_thread_top'] = 1;

	$channel  = App::get_channel();
	$observer = App::get_observer();

	$arr['aid'] = ((x($arr,'aid')) ? $arr['aid'] : $channel['channel_account_id']);
	$arr['uid'] = ((x($arr,'uid')) ? $arr['uid'] : $channel['channel_id']);

	if(! perm_is_allowed($arr['uid'],$observer['xchan_hash'],(($is_comment) ? 'post_comments' : 'post_wall'))) {
		$ret['message'] = t('Permission denied');
		return $ret;
	}

	$arr['public_policy'] = ((x($_REQUEST,'public_policy')) ? escape_tags($_REQUEST['public_policy']) : map_scope(\Zotlabs\Access\PermissionLimits::Get($channel['channel_id'],'view_stream'),true));
	if($arr['public_policy'])
		$arr['item_private'] = 1;

	if(! array_key_exists('mimetype',$arr))
		$arr['mimetype'] = 'text/bbcode';

	if(array_key_exists('item_private',$arr) && $arr['item_private']) {

		$arr['body'] = trim(z_input_filter($arr['uid'],$arr['body'],$arr['mimetype']));

		if($channel) {
			if($channel['channel_hash'] === $arr['author_xchan']) {
				$arr['sig'] = base64url_encode(rsa_sign($arr['body'],$channel['channel_prvkey']));
				$arr['item_verified'] = 1;
			}
		}
	}

	$arr['mid']          = ((x($arr,'mid')) ? $arr['mid'] : item_message_id());
	$arr['parent_mid']   = ((x($arr,'parent_mid')) ? $arr['parent_mid'] : $arr['mid']);
	$arr['thr_parent']   = ((x($arr,'thr_parent')) ? $arr['thr_parent'] : $arr['mid']);

	$arr['owner_xchan']  = ((x($arr,'owner_xchan'))  ? $arr['owner_xchan']  : $channel['channel_hash']);
	$arr['author_xchan'] = ((x($arr,'author_xchan')) ? $arr['author_xchan'] : $observer['xchan_hash']);

	$arr['verb']         = ((x($arr,'verb')) ? $arr['verb'] : ACTIVITY_POST);
	$arr['obj_type']     = ((x($arr,'obj_type')) ? $arr['obj_type'] : ACTIVITY_OBJ_NOTE);
	if(($is_comment) && ($arr['obj_type'] === ACTIVITY_OBJ_NOTE))
		$arr['obj_type'] = ACTIVITY_OBJ_COMMENT;

	$arr['allow_cid']    = ((x($arr,'allow_cid')) ? $arr['allow_cid'] : $channel['channel_allow_cid']);
	$arr['allow_gid']    = ((x($arr,'allow_gid')) ? $arr['allow_gid'] : $channel['channel_allow_gid']);
	$arr['deny_cid']     = ((x($arr,'deny_cid')) ? $arr['deny_cid'] : $channel['channel_deny_cid']);
	$arr['deny_gid']     = ((x($arr,'deny_gid')) ? $arr['deny_gid'] : $channel['channel_deny_gid']);

	$arr['comment_policy'] = map_scope(\Zotlabs\Access\PermissionLimits::Get($channel['channel_id'],'post_comments'));

	if ((! $arr['plink']) && (intval($arr['item_thread_top']))) {
		$arr['plink'] = z_root() . '/channel/' . $channel['channel_address'] . '/?f=&mid=' . $arr['mid'];
	}


	// for the benefit of plugins, we will behave as if this is an API call rather than a normal online post

	$_REQUEST['api_source'] = 1;

	call_hooks('post_local',$arr);

	if(x($arr,'cancel')) {
		logger('post_activity_item: post cancelled by plugin.');
		return $ret;
	}

	$post = item_store($arr);
	if($post['success'])
		$post_id = $post['item_id'];

	if($post_id) {
		$arr['id'] = $post_id;
		call_hooks('post_local_end', $arr);
		Zotlabs\Daemon\Master::Summon(array('Notifier','activity',$post_id));
		$ret['success'] = true;
		$ret['activity'] = $post['item'];
	}

	return $ret;
}


function validate_item_elements($message,$arr) {

	$result = array('success' => false);

	if(! array_key_exists('created',$arr))
		$result['message'] = 'missing created, possible author/owner lookup failure';

	if((! $arr['mid']) || (! $arr['parent_mid']))
		$result['message'] = 'missing message-id or parent message-id';

	if(array_key_exists('flags',$message) && in_array('relay',$message['flags']) && $arr['mid'] === $arr['parent_mid'])
		$result['message'] = 'relay set on top level post';

	if(! $result['message'])
		$result['success'] = true;

	return $result;

}


/**
 * @brief Limit lenght on imported system messages.
 *
 * The purpose of this function is to apply system message length limits to
 * imported messages without including any embedded photos in the length.
 *
 * @param string $body
 * @return string
 */
function limit_body_size($body) {

	$maxlen = get_max_import_size();

	// If the length of the body, including the embedded images, is smaller
	// than the maximum, then don't waste time looking for the images
	if($maxlen && (strlen($body) > $maxlen)) {

		$orig_body = $body;
		$new_body = '';
		$textlen = 0;

		$img_start = strpos($orig_body, '[img');
		$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
		$img_end = ($img_start !== false ? strpos(substr($orig_body, $img_start), '[/img]') : false);
		while(($img_st_close !== false) && ($img_end !== false)) {

			$img_st_close++; // make it point to AFTER the closing bracket
			$img_end += $img_start;
			$img_end += strlen('[/img]');

			if(! strcmp(substr($orig_body, $img_start + $img_st_close, 5), 'data:')) {
				// This is an embedded image

				if( ($textlen + $img_start) > $maxlen ) {
					if($textlen < $maxlen) {
						logger('limit_body_size: the limit happens before an embedded image', LOGGER_DEBUG);
						$new_body = $new_body . substr($orig_body, 0, $maxlen - $textlen);
						$textlen = $maxlen;
					}
				}
				else {
					$new_body = $new_body . substr($orig_body, 0, $img_start);
					$textlen += $img_start;
				}

				$new_body = $new_body . substr($orig_body, $img_start, $img_end - $img_start);
			}
			else {
				if( ($textlen + $img_end) > $maxlen ) {
					if($textlen < $maxlen) {
						$new_body = $new_body . substr($orig_body, 0, $maxlen - $textlen);
						$textlen = $maxlen;
					}
				}
				else {
					$new_body = $new_body . substr($orig_body, 0, $img_end);
					$textlen += $img_end;
				}
			}
			$orig_body = substr($orig_body, $img_end);

			if($orig_body === false) // in case the body ends on a closing image tag
				$orig_body = '';

			$img_start = strpos($orig_body, '[img');
			$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
			$img_end = ($img_start !== false ? strpos(substr($orig_body, $img_start), '[/img]') : false);
		}

		if( ($textlen + strlen($orig_body)) > $maxlen) {
			if($textlen < $maxlen) {
				$new_body = $new_body . substr($orig_body, 0, $maxlen - $textlen);
				$textlen = $maxlen;
			}
		}
		else {
			$new_body = $new_body . $orig_body;
			$textlen += strlen($orig_body);
		}

		return $new_body;
	}
	else
		return $body;
}

function title_is_body($title, $body) {

	$title = strip_tags($title);
	$title = trim($title);
	$title = str_replace(array("\n", "\r", "\t", " "), array("","","",""), $title);

	$body = strip_tags($body);
	$body = trim($body);
	$body = str_replace(array("\n", "\r", "\t", " "), array("","","",""), $body);

	if (strlen($title) < strlen($body))
		$body = substr($body, 0, strlen($title));

	if (($title != $body) and (substr($title, -3) == "...")) {
		$pos = strrpos($title, "...");
		if ($pos > 0) {
			$title = substr($title, 0, $pos);
			$body = substr($body, 0, $pos);
		}
	}

	return($title == $body);
}


function get_item_elements($x,$allow_code = false) {

	$arr = array();

	if($allow_code)
		$arr['body'] = $x['body'];
	else
		$arr['body'] = (($x['body']) ? htmlspecialchars($x['body'],ENT_COMPAT,'UTF-8',false) : '');

	$key = get_config('system','pubkey');

	$maxlen = get_max_import_size();

	if($maxlen && mb_strlen($arr['body']) > $maxlen) {
		$arr['body'] = mb_substr($arr['body'],0,$maxlen,'UTF-8');
		logger('get_item_elements: message length exceeds max_import_size: truncated');
	}

	$arr['created']      = datetime_convert('UTC','UTC',$x['created']);
	$arr['edited']       = datetime_convert('UTC','UTC',$x['edited']);

	if($arr['created'] > datetime_convert())
		$arr['created']  = datetime_convert();
	if($arr['edited'] > datetime_convert())
		$arr['edited']   = datetime_convert();

	$arr['expires']      = ((x($x,'expires') && $x['expires'])
								? datetime_convert('UTC','UTC',$x['expires'])
								: NULL_DATE);

	$arr['commented']    = ((x($x,'commented') && $x['commented'])
								? datetime_convert('UTC','UTC',$x['commented'])
								: $arr['created']);
	$arr['comments_closed']    = ((x($x,'comments_closed') && $x['comments_closed'])
								? datetime_convert('UTC','UTC',$x['comments_closed'])
								: NULL_DATE);

	$arr['title']        = (($x['title'])          ? htmlspecialchars($x['title'],          ENT_COMPAT,'UTF-8',false) : '');

	if(mb_strlen($arr['title']) > 255)
		$arr['title'] = mb_substr($arr['title'],0,255);

	$arr['app']          = (($x['app'])            ? htmlspecialchars($x['app'],            ENT_COMPAT,'UTF-8',false) : '');
	$arr['route']        = (($x['route'])          ? htmlspecialchars($x['route'],          ENT_COMPAT,'UTF-8',false) : '');
	$arr['mid']          = (($x['message_id'])     ? htmlspecialchars($x['message_id'],     ENT_COMPAT,'UTF-8',false) : '');
	$arr['parent_mid']   = (($x['message_top'])    ? htmlspecialchars($x['message_top'],    ENT_COMPAT,'UTF-8',false) : '');
	$arr['thr_parent']   = (($x['message_parent']) ? htmlspecialchars($x['message_parent'], ENT_COMPAT,'UTF-8',false) : '');

	$arr['plink']        = (($x['permalink'])      ? htmlspecialchars($x['permalink'],      ENT_COMPAT,'UTF-8',false) : '');
	$arr['location']     = (($x['location'])       ? htmlspecialchars($x['location'],       ENT_COMPAT,'UTF-8',false) : '');
	$arr['coord']        = (($x['longlat'])        ? htmlspecialchars($x['longlat'],        ENT_COMPAT,'UTF-8',false) : '');
	$arr['verb']         = (($x['verb'])           ? htmlspecialchars($x['verb'],           ENT_COMPAT,'UTF-8',false) : '');
	$arr['mimetype']     = (($x['mimetype'])       ? htmlspecialchars($x['mimetype'],       ENT_COMPAT,'UTF-8',false) : '');
	$arr['obj_type']     = (($x['object_type'])    ? htmlspecialchars($x['object_type'],    ENT_COMPAT,'UTF-8',false) : '');
	$arr['tgt_type']     = (($x['target_type'])    ? htmlspecialchars($x['target_type'],    ENT_COMPAT,'UTF-8',false) : '');

	$arr['public_policy'] = (($x['public_scope']) ? htmlspecialchars($x['public_scope'], ENT_COMPAT,'UTF-8',false) : '');
	if($arr['public_policy'] === 'public')
		$arr['public_policy'] = '';

	$arr['comment_policy'] = (($x['comment_scope']) ? htmlspecialchars($x['comment_scope'], ENT_COMPAT,'UTF-8',false) : 'contacts');

	$arr['sig']          = (($x['signature']) ? htmlspecialchars($x['signature'],  ENT_COMPAT,'UTF-8',false) : '');

	if(array_key_exists('diaspora_signature',$x) && is_array($x['diaspora_signature']))
		$x['diaspora_signature'] = json_encode($x['diaspora_signature']);

	$arr['diaspora_meta'] = (($x['diaspora_signature']) ? $x['diaspora_signature'] : '');

	$arr['obj']          = activity_sanitise($x['object']);
	$arr['target']       = activity_sanitise($x['target']);

	$arr['attach']       = activity_sanitise($x['attach']);
	$arr['term']         = decode_tags($x['tags']);
	$arr['iconfig']      = decode_item_meta($x['meta']);

	$arr['item_private'] = ((array_key_exists('flags',$x) && is_array($x['flags']) && in_array('private',$x['flags'])) ? 1 : 0);

	$arr['item_flags'] = 0;

	if(array_key_exists('flags',$x)) {

		if(in_array('consensus',$x['flags']))
			$arr['item_consensus'] = 1;

		if(in_array('deleted',$x['flags']))
			$arr['item_deleted'] = 1;

		if(in_array('notshown',$x['flags']))
			$arr['item_notshown'] = 1;

		// hidden item are no longer propagated - notshown may be a suitable alternative

		if(in_array('hidden',$x['flags']))
			$arr['item_hidden'] = 1;

	}

	// Here's the deal - the site might be down or whatever but if there's a new person you've never
	// seen before sending stuff to your stream, we MUST be able to look them up and import their data from their
	// hub and verify that they are legit - or else we're going to toss the post. We only need to do this
	// once, and after that your hub knows them. Sure some info is in the post, but it's only a transit identifier
	// and not enough info to be able to look you up from your hash - which is the only thing stored with the post.

	$xchan_hash = import_author_xchan($x['author']);
	if($xchan_hash)
		$arr['author_xchan'] = $xchan_hash;
	else
		return array();

	// save a potentially expensive lookup if author == owner
	if($arr['author_xchan'] === make_xchan_hash($x['owner']['guid'],$x['owner']['guid_sig']))
		$arr['owner_xchan'] = $arr['author_xchan'];
	else {
		$xchan_hash = import_author_xchan($x['owner']);
		if($xchan_hash)
			$arr['owner_xchan'] = $xchan_hash;
		else
			return array();
	}

	if($arr['sig']) {
		$r = q("select xchan_pubkey from xchan where xchan_hash = '%s' limit 1",
			dbesc($arr['author_xchan'])
		);
		if($r && rsa_verify($x['body'],base64url_decode($arr['sig']),$r[0]['xchan_pubkey']))
			$arr['item_verified'] = 1;
		else
			logger('get_item_elements: message verification failed.');
	}

	if(array_key_exists('revision',$x)) {

		// extended export encoding

		$arr['revision'] = $x['revision'];
		$arr['allow_cid'] = $x['allow_cid'];
		$arr['allow_gid'] = $x['allow_gid'];
		$arr['deny_cid'] = $x['deny_cid'];
		$arr['deny_gid'] = $x['deny_gid'];
		$arr['layout_mid'] = $x['layout_mid'];
		$arr['postopts'] = $x['postopts'];
		$arr['resource_id'] = $x['resource_id'];
		$arr['resource_type'] = $x['resource_type'];
		$arr['attach'] = $x['attach'];
		$arr['item_origin'] = $x['item_origin'];
		$arr['item_unseen'] = $x['item_unseen'];
		$arr['item_starred'] = $x['item_starred'];
		$arr['item_uplink'] = $x['item_uplink'];
		$arr['item_consensus'] = $x['item_consensus'];
		$arr['item_wall'] = $x['item_wall'];
		$arr['item_thread_top'] = $x['item_thread_top'];
		$arr['item_notshown'] = $x['item_notshown'];
		$arr['item_nsfw'] = $x['item_nsfw'];
		// local only		$arr['item_relay'] = $x['item_relay'];
		$arr['item_mentionsme'] = $x['item_mentionsme'];
		$arr['item_nocomment'] = $x['item_nocomment'];
		// local only $arr['item_obscured'] = $x['item_obscured'];
		// local only $arr['item_verified'] = $x['item_verified'];
		$arr['item_retained'] = $x['item_retained'];
		$arr['item_rss'] = $x['item_rss'];
		$arr['item_deleted'] = $x['item_deleted'];
		$arr['item_type'] = $x['item_type'];
		$arr['item_hidden'] = $x['item_hidden'];
		$arr['item_unpublished'] = $x['item_unpublished'];
		$arr['item_delayed'] = $x['item_delayed'];
		$arr['item_pending_remove'] = $x['item_pending_remove'];
		$arr['item_blocked'] = $x['item_blocked'];
		if(array_key_exists('item_flags',$x)) {
			if($x['item_flags'] & 0x0004)
				$arr['item_starred'] = 1;
			if($x['item_flags'] & 0x0008)
				$arr['item_uplink'] = 1;
			if($x['item_flags'] & 0x0010)
				$arr['item_consensus'] = 1;
			if($x['item_flags'] & 0x0020)
				$arr['item_wall'] = 1;
			if($x['item_flags'] & 0x0040)
				$arr['item_thread_top'] = 1;
			if($x['item_flags'] & 0x0080)
				$arr['item_notshown'] = 1;
			if($x['item_flags'] & 0x0100)
				$arr['item_nsfw'] = 1;
			if($x['item_flags'] & 0x0400)
				$arr['item_mentionsme'] = 1;
			if($x['item_flags'] & 0x0800)
				$arr['item_nocomment'] = 1;
			if($x['item_flags'] & 0x4000)
				$arr['item_retained'] = 1;
			if($x['item_flags'] & 0x8000)
				$arr['item_rss'] = 1;

		}
		if(array_key_exists('item_restrict',$x)) {
			if($x['item_restrict'] & 0x0001)
				$arr['item_hidden'] = 1;
			if($x['item_restrict'] & 0x0002)
				$arr['item_blocked'] = 1;
			if($x['item_restrict'] & 0x0010)
				$arr['item_deleted'] = 1;
			if($x['item_restrict'] & 0x0020)
				$arr['item_unpublished'] = 1;
			if($x['item_restrict'] & 0x0040)
				$arr['item_type'] = ITEM_TYPE_WEBPAGE;
			if($x['item_restrict'] & 0x0080)
				$arr['item_delayed'] = 1;
			if($x['item_restrict'] & 0x0100)
				$arr['item_type'] = ITEM_TYPE_BLOCK;
			if($x['item_restrict'] & 0x0200)
				$arr['item_type'] = ITEM_TYPE_PDL;
			if($x['item_restrict'] & 0x0400)
				$arr['item_type'] = ITEM_TYPE_BUG;
			if($x['item_restrict'] & 0x0800)
				$arr['item_pending_remove'] = 1;
			if($x['item_restrict'] & 0x1000)
				$arr['item_type'] = ITEM_TYPE_DOC;
		}
	}

	return $arr;
}


function import_author_xchan($x) {

	$arr = array('xchan' => $x, 'xchan_hash' => '');
	call_hooks('import_author_xchan',$arr);
	if($arr['xchan_hash'])
		return $arr['xchan_hash'];

	if((! array_key_exists('network', $x)) || ($x['network'] === 'zot')) {
		$y = import_author_zot($x);
	}
	if(! $y)
		$y = import_author_diaspora($x);

	if($x['network'] === 'rss') {
		$y = import_author_rss($x);
	}

	if($x['network'] === 'unknown') {
		$y = import_author_unknown($x);
	}

	return(($y) ? $y : false);
}

/**
 * @brief Imports an author from Diaspora.
 *
 * @param array $x an associative array with
 *   * \e string \b address
 * @return boolean|string false on error, otherwise xchan_hash of the new entry
 */
function import_author_diaspora($x) {
	if(! $x['address'])
		return false;

	$r = q("select * from xchan where xchan_addr = '%s' limit 1",
		dbesc($x['address'])
	);
	if($r) {
		logger('in_cache: ' . $x['address'], LOGGER_DATA);
		return $r[0]['xchan_hash'];
	}

	if(discover_by_webbie($x['address'])) {
		$r = q("select xchan_hash from xchan where xchan_addr = '%s' limit 1",
			dbesc($x['address'])
		);
		if($r)
			return $r[0]['xchan_hash'];
	}

	return false;
}

/**
 * @brief Imports an author from a RSS feed.
 *
 * @param array $x an associative array with
 *   * \e string \b url
 *   * \e string \b name
 *   * \e string \b guid
 * @return boolean|string
 */
function import_author_rss($x) {
	if(! $x['url'])
		return false;

	$r = q("select xchan_hash from xchan where xchan_network = 'rss' and xchan_url = '%s' limit 1",
		dbesc($x['url'])
	);
	if($r) {
		logger('import_author_rss: in cache' , LOGGER_DEBUG);
		return $r[0]['xchan_hash'];
	}
	$name = trim($x['name']);

	$r = q("insert into xchan ( xchan_hash, xchan_guid, xchan_url, xchan_name, xchan_network )
		values ( '%s', '%s', '%s', '%s', '%s' )",
		dbesc($x['guid']),
		dbesc($x['guid']),
		dbesc($x['url']),
		dbesc(($name) ? $name : t('(Unknown)')),
		dbesc('rss')
	);

	if($r && $x['photo']) {

		$photos = import_xchan_photo($x['photo']['src'],$x['url']);

		if($photos) {
			$r = q("update xchan set xchan_photo_date = '%s', xchan_photo_l = '%s', xchan_photo_m = '%s', xchan_photo_s = '%s', xchan_photo_mimetype = '%s' where xchan_url = '%s' and xchan_network = 'rss'",
				dbesc(datetime_convert()),
				dbesc($photos[0]),
				dbesc($photos[1]),
				dbesc($photos[2]),
				dbesc($photos[3]),
				dbesc($x['url'])
			);
			if($r)
				return $x['url'];
		}
	}

	return false;
}

function import_author_unknown($x) {

	if(! $x['url'])
		return false;

	$r = q("select xchan_hash from xchan where xchan_network = 'unknown' and xchan_url = '%s' limit 1",
		dbesc($x['url'])
	);
	if($r) {
		logger('import_author_unknown: in cache' , LOGGER_DEBUG);
		return $r[0]['xchan_hash'];
	}

	$name = trim($x['name']);

	$r = q("insert into xchan ( xchan_hash, xchan_guid, xchan_url, xchan_name, xchan_network )
		values ( '%s', '%s', '%s', '%s', '%s' )",
		dbesc($x['url']),
		dbesc($x['url']),
		dbesc($x['url']),
		dbesc(($name) ? $name : t('(Unknown)')),
		dbesc('unknown')
	);
	if($r && $x['photo']) {

		$photos = import_xchan_photo($x['photo']['src'],$x['url']);

		if($photos) {
			$r = q("update xchan set xchan_photo_date = '%s', xchan_photo_l = '%s', xchan_photo_m = '%s', xchan_photo_s = '%s', xchan_photo_mimetype = '%s' where xchan_url = '%s' and xchan_network = 'unknown'",
				dbesc(datetime_convert()),
				dbesc($photos[0]),
				dbesc($photos[1]),
				dbesc($photos[2]),
				dbesc($photos[3]),
				dbesc($x['url'])
			);
			if($r)
				return $x['url'];
		}
	}

	return false;
}

function encode_item($item,$mirror = false) {
	$x = array();
	$x['type'] = 'activity';
	$x['encoding'] = 'zot';

//	logger('encode_item: ' . print_r($item,true));

	$r = q("select channel_id from channel where channel_id = %d limit 1",
		intval($item['uid'])
	);

	if($r)
		$comment_scope = \Zotlabs\Access\PermissionLimits::Get($item['uid'],'post_comments');
	else
		$comment_scope = 0;

	$scope = $item['public_policy'];
	if(! $scope)
		$scope = 'public';

	$c_scope = map_scope($comment_scope);

	$key = get_config('system','prvkey');

	if(array_key_exists('item_obscured',$item) && intval($item['item_obscured'])) {
		if($item['title'])
			$item['title'] = crypto_unencapsulate(json_decode($item['title'],true),$key);
		if($item['body'])
			$item['body'] = crypto_unencapsulate(json_decode($item['body'],true),$key);
	}

	// If we're trying to backup an item so that it's recoverable or for export/imprt,
	// add all the attributes we need to recover it

	if($mirror) {
		$x['id'] = $item['id'];
		$x['parent'] = $item['parent'];
		$x['uid'] = $item['uid'];
		$x['allow_cid'] = $item['allow_cid'];
		$x['allow_gid'] = $item['allow_gid'];
		$x['deny_cid'] = $item['deny_cid'];
		$x['deny_gid'] = $item['deny_gid'];
		$x['revision'] = $item['revision'];
		$x['layout_mid'] = $item['layout_mid'];
		$x['postopts'] = $item['postopts'];
		$x['resource_id'] = $item['resource_id'];
		$x['resource_type'] = $item['resource_type'];
		$x['attach'] = $item['attach'];
		$x['item_origin'] = $item['item_origin'];
		$x['item_unseen'] = $item['item_unseen'];
		$x['item_starred'] = $item['item_starred'];
		$x['item_uplink'] = $item['item_uplink'];
		$x['item_consensus'] = $item['item_consensus'];
		$x['item_wall'] = $item['item_wall'];
		$x['item_thread_top'] = $item['item_thread_top'];
		$x['item_notshown'] = $item['item_notshown'];
		$x['item_nsfw'] = $item['item_nsfw'];
		$x['item_relay'] = $item['item_relay'];
		$x['item_mentionsme'] = $item['item_mentionsme'];
		$x['item_nocomment'] = $item['item_nocomment'];
		$x['item_obscured'] = $item['item_obscured'];
		$x['item_verified'] = $item['item_verified'];
		$x['item_retained'] = $item['item_retained'];
		$x['item_rss'] = $item['item_rss'];
		$x['item_deleted'] = $item['item_deleted'];
		$x['item_type'] = $item['item_type'];
		$x['item_hidden'] = $item['item_hidden'];
		$x['item_unpublished'] = $item['item_unpublished'];
		$x['item_delayed'] = $item['item_delayed'];
		$x['item_pending_remove'] = $item['item_pending_remove'];
		$x['item_blocked'] = $item['item_blocked'];
	}

	$x['message_id']      = $item['mid'];
	$x['message_top']     = $item['parent_mid'];
	$x['message_parent']  = $item['thr_parent'];
	$x['created']         = $item['created'];
	$x['edited']          = $item['edited'];
	// always send 0's over the wire
	$x['expires']         = (($item['expires'] == '0001-01-01 00:00:00') ? '0000-00-00 00:00:00' : $item['expires']);
	$x['commented']       = $item['commented'];
	$x['mimetype']        = $item['mimetype'];
	$x['title']           = $item['title'];
	$x['body']            = $item['body'];
	$x['app']             = $item['app'];
	$x['verb']            = $item['verb'];
	$x['object_type']     = $item['obj_type'];
	$x['target_type']     = $item['tgt_type'];
	$x['permalink']       = $item['plink'];
	$x['location']        = $item['location'];
	$x['longlat']         = $item['coord'];
	$x['signature']       = $item['sig'];
	$x['route']           = $item['route'];
	$x['owner']           = encode_item_xchan($item['owner']);
	$x['author']          = encode_item_xchan($item['author']);

	if($item['obj'])
		$x['object']      = json_decode($item['obj'],true);
	if($item['target'])
		$x['target']      = json_decode($item['target'],true);
	if($item['attach'])
		$x['attach']      = json_decode($item['attach'],true);
	if($y = encode_item_flags($item))
		$x['flags']       = $y;

	if($item['comments_closed'] > NULL_DATE)
		$x['comments_closed'] = $item['comments_closed'];

	$x['public_scope']    = $scope;

	if($item['item_nocomment'])
		$x['comment_scope'] = 'none';
	else
		$x['comment_scope'] = $c_scope;

	if($item['term'])
		$x['tags']        = encode_item_terms($item['term'],$mirror);

	if($item['iconfig'])
		$x['meta']        = encode_item_meta($item['iconfig'],$mirror);

	if($item['diaspora_meta']) {
		$z = json_decode($item['diaspora_meta'],true);
		if($z) {
			if(is_array($z) && array_key_exists('iv',$z))
				$x['diaspora_signature'] = crypto_unencapsulate($z,$key);
			else
				$x['diaspora_signature'] = $z;
			if(! is_array($z))
				logger('encode_item: diaspora meta is not an array: ' . print_r($z,true));
		}
	}
	logger('encode_item: ' . print_r($x,true), LOGGER_DATA);

	return $x;
}

/**
 * @brief
 *
 * @param int $scope
 * @param boolean $strip (optional) default false
 * @return string
 */
function map_scope($scope, $strip = false) {
	switch($scope) {
		case 0:
			return 'self';
		case PERMS_PUBLIC:
			if($strip)
				return '';
			return 'public';
		case PERMS_NETWORK:
			return 'network: red';
		case PERMS_AUTHED:
			return 'authenticated';
		case PERMS_SITE:
			return 'site: ' . App::get_hostname();
		case PERMS_PENDING:
			return 'any connections';
		case PERMS_CONTACTS:
		default:
			return 'contacts';
	}
}

/**
 * @brief Returns a descriptive text for a given $scope.
 *
 * @param string $scope
 * @return string translated string describing the scope
 */
function translate_scope($scope) {
	if(! $scope || $scope === 'public')
		return t('Visible to anybody on the internet.');
	if(strpos($scope,'self') === 0)
		return t('Visible to you only.');
	if(strpos($scope,'network:') === 0)
		return t('Visible to anybody in this network.');
	if(strpos($scope,'authenticated') === 0)
		return t('Visible to anybody authenticated.');
	if(strpos($scope,'site:') === 0)
		return sprintf( t('Visible to anybody on %s.'), strip_tags(substr($scope,6)));
	if(strpos($scope,'any connections') === 0)
		return t('Visible to all connections.');
	if(strpos($scope,'contacts') === 0)
		return t('Visible to approved connections.');
	if(strpos($scope,'specific') === 0)
		return t('Visible to specific connections.');
}

/**
 * @brief
 *
 * @param array $xchan
 * @return array an associative array
 */
function encode_item_xchan($xchan) {
	$ret = array();

	$ret['name']     = $xchan['xchan_name'];
	$ret['address']  = $xchan['xchan_addr'];
	$ret['url']      = $xchan['xchan_url'];
	$ret['network']  = $xchan['xchan_network'];
	$ret['photo']    = array('mimetype' => $xchan['xchan_photo_mimetype'], 'src' => $xchan['xchan_photo_m']);
	$ret['guid']     = $xchan['xchan_guid'];
	$ret['guid_sig'] = $xchan['xchan_guid_sig'];

	return $ret;
}

function encode_item_terms($terms,$mirror = false) {
	$ret = array();

	$allowed_export_terms = array( TERM_UNKNOWN, TERM_HASHTAG, TERM_MENTION, TERM_CATEGORY, TERM_BOOKMARK, TERM_COMMUNITYTAG );

	if($mirror) {
		$allowed_export_terms[] = TERM_PCATEGORY;
		$allowed_export_terms[] = TERM_FILE;
	}

	if($terms) {
		foreach($terms as $term) {
			if(in_array($term['ttype'],$allowed_export_terms))
				$ret[] = array('tag' => $term['term'], 'url' => $term['url'], 'type' => termtype($term['ttype']));
		}
	}

	return $ret;
}

function encode_item_meta($meta,$mirror = false) {
	$ret = array();

	if($meta) {
		foreach($meta as $m) {
			if($m['sharing'] || $mirror)
				$ret[] = array('family' => $m['cat'], 'key' => $m['k'], 'value' => $m['v'], 'sharing' => intval($m['sharing']));
		}
	}

	return $ret;
}

function decode_item_meta($meta) {
	$ret = array();

	if(is_array($meta) && $meta) {
		foreach($meta as $m) {
			$ret[] = array('cat' => escape_tags($m['family']),'k' => escape_tags($m['key']),'v' => $m['value'],'sharing' => $m['sharing']);
		}
	}
	return $ret;
}

/**
 * @brief
 *
 * @param int $t
 * @return string
 */
function termtype($t) {
	$types = array('unknown','hashtag','mention','category','private_category','file','search','thing','bookmark', 'hierarchy', 'communitytag');

	return(($types[$t]) ? $types[$t] : 'unknown');
}

/**
 * @brief
 *
 * @param array $t
 * @return array|string empty string or array containing associative arrays with
 *   * \e string \b term
 *   * \e string \b url
 *   * \e int \b type
 */
function decode_tags($t) {
	if($t) {
		$ret = array();
		foreach($t as $x) {
			$tag = array();
			$tag['term'] = htmlspecialchars($x['tag'], ENT_COMPAT, 'UTF-8', false);
			$tag['url']  = htmlspecialchars($x['url'], ENT_COMPAT, 'UTF-8', false);
			switch($x['type']) {
				case 'hashtag':
					$tag['ttype'] = TERM_HASHTAG;
					break;
				case 'mention':
					$tag['ttype'] = TERM_MENTION;
					break;
				case 'category':
					$tag['ttype'] = TERM_CATEGORY;
					break;
				case 'private_category':
					$tag['ttype'] = TERM_PCATEGORY;
					break;
				case 'file':
					$tag['ttype'] = TERM_FILE;
					break;
				case 'search':
					$tag['ttype'] = TERM_SEARCH;
					break;
				case 'thing':
					$tag['ttype'] = TERM_THING;
					break;
				case 'bookmark':
					$tag['ttype'] = TERM_BOOKMARK;
					break;
				case 'communitytag':
					$tag['ttype'] = TERM_COMMUNITYTAG;
					break;
				default:
				case 'unknown':
					$tag['ttype'] = TERM_UNKNOWN;
					break;
			}
			$ret[] = $tag;
		}

		return $ret;
	}

	return '';
}

/**
 * @brief Santise a potentially complex array.
 *
 * @param array $arr
 * @return array|string
 */
function activity_sanitise($arr) {
	if($arr) {
		if(is_array($arr)) {
			$ret = array();
			foreach($arr as $k => $x) {
				if(is_array($x))
					$ret[$k] = activity_sanitise($x);
				else
					$ret[$k] = htmlspecialchars($x, ENT_COMPAT, 'UTF-8', false);
			}
			return $ret;
		}
		else {
			return htmlspecialchars($arr, ENT_COMPAT, 'UTF-8', false);
		}
	}

	return '';
}

/**
 * @brief Sanitise a simple linear array.
 *
 * @param array $arr
 * @return array|string
 */
function array_sanitise($arr) {
	if($arr) {
		$ret = array();
		foreach($arr as $x) {
			$ret[] = htmlspecialchars($x, ENT_COMPAT,'UTF-8',false);
		}
		return $ret;
	}

	return '';
}

function encode_item_flags($item) {

//	most of item_flags and item_restrict are local settings which don't apply when transmitted.
//  We may need those for the case of syncing other hub locations which you are attached to.

	$ret = array();

	if(intval($item['item_deleted']))
		$ret[] = 'deleted';
	if(intval($item['item_hidden']))
		$ret[] = 'hidden';
	if(intval($item['item_notshown']))
		$ret[] = 'notshown';
	if(intval($item['item_thread_top']))
		$ret[] = 'thread_parent';
	if(intval($item['item_nsfw']))
		$ret[] = 'nsfw';
	if(intval($item['item_consensus']))
		$ret[] = 'consensus';
	if(intval($item['item_private']))
		$ret[] = 'private';

	return $ret;
}

function encode_mail($item,$extended = false) {
	$x = array();
	$x['type'] = 'mail';
	$x['encoding'] = 'zot';

	if(array_key_exists('mail_obscured',$item) && intval($item['mail_obscured'])) {
		if($item['title'])
			$item['title'] = base64url_decode(str_rot47($item['title']));
		if($item['body'])
			$item['body'] = base64url_decode(str_rot47($item['body']));
	}

	$x['message_id']     = $item['mid'];
	$x['message_parent'] = $item['parent_mid'];
	$x['created']        = $item['created'];
	$x['expires']        = $item['expires'];
	$x['diaspora_meta']  = $item['diaspora_meta'];
	$x['title']          = $item['title'];
	$x['body']           = $item['body'];
	$x['from']           = encode_item_xchan($item['from']);
	$x['to']             = encode_item_xchan($item['to']);

	if($item['attach'])
		$x['attach']     = json_decode($item['attach'],true);

	$x['flags'] = array();

	if(intval($item['mail_recalled'])) {
		$x['flags'][] = 'recalled';
		$x['title'] = '';
		$x['body']  = '';
	}

	if($extended) {
		$x['conv_guid'] = $item['conv_guid'];
		if(intval($item['mail_deleted']))
			$x['flags'][] = 'deleted';
		if(intval($item['mail_replied']))
			$x['flags'][] = 'replied';
		if(intval($item['mail_isreply']))
			$x['flags'][] = 'isreply';
		if(intval($item['mail_seen']))
			$x['flags'][] = 'seen';
	}

	return $x;
}



function get_mail_elements($x) {

	$arr = array();

	$arr['body']         = (($x['body']) ? htmlspecialchars($x['body'], ENT_COMPAT,'UTF-8',false) : '');
	$arr['title']        = (($x['title'])? htmlspecialchars($x['title'],ENT_COMPAT,'UTF-8',false) : '');

	$arr['conv_guid']    = (($x['conv_guid'])? htmlspecialchars($x['conv_guid'],ENT_COMPAT,'UTF-8',false) : '');

	$arr['created']      = datetime_convert('UTC','UTC',$x['created']);
	if((! array_key_exists('expires',$x)) || ($x['expires'] <= NULL_DATE))
		$arr['expires'] = NULL_DATE;
	else
		$arr['expires']      = datetime_convert('UTC','UTC',$x['expires']);

	$arr['mail_flags'] = 0;

	if($x['flags'] && is_array($x['flags'])) {
		if(in_array('recalled',$x['flags'])) {
			$arr['mail_recalled'] = 1;
		}
		if(in_array('replied',$x['flags'])) {
			$arr['mail_replied'] = 1;
		}
		if(in_array('isreply',$x['flags'])) {
			$arr['mail_isreply'] = 1;
		}
		if(in_array('seen',$x['flags'])) {
			$arr['mail_seen'] = 1;
		}
		if(in_array('deleted',$x['flags'])) {
			$arr['mail_deleted'] = 1;
		}
	}

	$key = get_config('system','pubkey');
	$arr['mail_obscured'] = 1;
	if($arr['body']) {
		$arr['body']  = str_rot47(base64url_encode($arr['body']));
	}

	if($arr['title']) {
		$arr['title'] = str_rot47(base64url_encode($arr['title']));
	}
	if($arr['created'] > datetime_convert())
		$arr['created']  = datetime_convert();


	$arr['mid']          = (($x['message_id'])     ? htmlspecialchars($x['message_id'],     ENT_COMPAT,'UTF-8',false) : '');
	$arr['parent_mid']   = (($x['message_parent']) ? htmlspecialchars($x['message_parent'], ENT_COMPAT,'UTF-8',false) : '');

	if($x['attach'])
		$arr['attach'] = activity_sanitise($x['attach']);

	if(($xchan_hash = import_author_xchan($x['from'])) !== false)
		$arr['from_xchan'] = $xchan_hash;
	else
		return array();

	if(($xchan_hash = import_author_xchan($x['to'])) !== false)
		$arr['to_xchan'] = $xchan_hash;
	else
		return array();

	return $arr;
}


function get_profile_elements($x) {

	$arr = array();

	if(($xchan_hash = import_author_xchan($x['from'])) !== false)
		$arr['xprof_hash'] = $xchan_hash;
	else
		return array();

	$arr['desc']         = (($x['title']) ? htmlspecialchars($x['title'],ENT_COMPAT,'UTF-8',false) : '');

	$arr['dob']          = datetime_convert('UTC','UTC',$x['birthday'],'Y-m-d');
	$arr['age']          = (($x['age']) ? intval($x['age']) : 0);

	$arr['gender']       = (($x['gender'])    ? htmlspecialchars($x['gender'],    ENT_COMPAT,'UTF-8',false) : '');
	$arr['marital']      = (($x['marital'])   ? htmlspecialchars($x['marital'],   ENT_COMPAT,'UTF-8',false) : '');
	$arr['sexual']       = (($x['sexual'])    ? htmlspecialchars($x['sexual'],    ENT_COMPAT,'UTF-8',false) : '');
	$arr['locale']       = (($x['locale'])    ? htmlspecialchars($x['locale'],    ENT_COMPAT,'UTF-8',false) : '');
	$arr['region']       = (($x['region'])    ? htmlspecialchars($x['region'],    ENT_COMPAT,'UTF-8',false) : '');
	$arr['postcode']     = (($x['postcode'])  ? htmlspecialchars($x['postcode'],  ENT_COMPAT,'UTF-8',false) : '');
	$arr['country']      = (($x['country'])   ? htmlspecialchars($x['country'],   ENT_COMPAT,'UTF-8',false) : '');

	$arr['keywords']     = (($x['keywords'] && is_array($x['keywords'])) ? array_sanitise($x['keywords']) : array());

	return $arr;
}


/**
 * @brief
 *
 * @param array $arr
 * @param boolean $allow_exec (optional) default false
 * @param boolean $deliver (optional) default true
 *
 * @return array
 *   * \e boolean \b success
 *   * \e int \b item_id
 */
function item_store($arr, $allow_exec = false, $deliver = true) {

	$d = array('item' => $arr, 'allow_exec' => $allow_exec);
	call_hooks('item_store', $d );
	$arr = $d['item'];
	$allow_exec = $d['allow_exec'];

	$ret = array('success' => false, 'item_id' => 0);

	if(array_key_exists('cancel',$arr) && $arr['cancel']) {
		logger('cancelled by plugin');
		return $ret;
	}		

	if(! $arr['uid']) {
		logger('item_store: no uid');
		$ret['message'] = 'No uid.';
		return $ret;
	}

	//$uplinked_comment = false;

	// If a page layout is provided, ensure it exists and belongs to us.

	if(array_key_exists('layout_mid',$arr) && $arr['layout_mid']) {
		$l = q("select item_type from item where mid = '%s' and uid = %d limit 1",
			dbesc($arr['layout_mid']),
			intval($arr['uid'])
		);
		if((! $l) || ($l[0]['item_type'] != ITEM_TYPE_PDL))
			unset($arr['layout_mid']);
	}

	// Don't let anybody set these, either intentionally or accidentally

	if(array_key_exists('id',$arr))
		unset($arr['id']);
	if(array_key_exists('parent',$arr))
		unset($arr['parent']);

	$arr['mimetype']      = ((x($arr,'mimetype'))      ? notags(trim($arr['mimetype']))      : 'text/bbcode');

	if(($arr['mimetype'] == 'application/x-php') && (! $allow_exec)) {
		logger('item_store: php mimetype but allow_exec is denied.');
		$ret['message'] = 'exec denied.';
		return $ret;
	}


	$arr['title'] = ((array_key_exists('title',$arr) && strlen($arr['title']))  ? trim($arr['title']) : '');
	$arr['body']  = ((array_key_exists('body',$arr) && strlen($arr['body']))    ? trim($arr['body'])  : '');

	$arr['diaspora_meta'] = ((x($arr,'diaspora_meta')) ? $arr['diaspora_meta']               : '');
	$arr['allow_cid']     = ((x($arr,'allow_cid'))     ? trim($arr['allow_cid'])             : '');
	$arr['allow_gid']     = ((x($arr,'allow_gid'))     ? trim($arr['allow_gid'])             : '');
	$arr['deny_cid']      = ((x($arr,'deny_cid'))      ? trim($arr['deny_cid'])              : '');
	$arr['deny_gid']      = ((x($arr,'deny_gid'))      ? trim($arr['deny_gid'])              : '');
	$arr['postopts']      = ((x($arr,'postopts'))      ? trim($arr['postopts'])              : '');
	$arr['item_private']  = ((x($arr,'item_private'))  ? intval($arr['item_private'])        : 0 );
	$arr['item_wall']     = ((x($arr,'item_wall'))     ? intval($arr['item_wall'])           : 0 );
	$arr['item_type']     = ((x($arr,'item_type'))     ? intval($arr['item_type'])           : 0 );

	// obsolete, but needed so as not to throw not-null constraints on some database driveres
	$arr['item_flags']    = ((x($arr,'item_flags'))    ? intval($arr['item_flags'])          : 0 );

	// only detect language if we have text content, and if the post is private but not yet
	// obscured, make it so.

	if((! array_key_exists('item_obscured',$arr)) || $arr['item_obscured'] == 0) {

		$arr['lang'] = detect_language($arr['body']);
		// apply the input filter here - if it is obscured it has been filtered already
		$arr['body'] = trim(z_input_filter($arr['uid'],$arr['body'],$arr['mimetype']));

		if(local_channel() && (! $arr['sig'])) {
			$channel = App::get_channel();
			if($channel['channel_hash'] === $arr['author_xchan']) {
				$arr['sig'] = base64url_encode(rsa_sign($arr['body'],$channel['channel_prvkey']));
				$arr['item_verified'] = 1;
			}
		}

		$allowed_languages = get_pconfig($arr['uid'],'system','allowed_languages');

		if((is_array($allowed_languages)) && ($arr['lang']) && (! array_key_exists($arr['lang'],$allowed_languages))) {
			$translate = array('item' => $arr, 'from' => $arr['lang'], 'to' => $allowed_languages, 'translated' => false);
			call_hooks('item_translate', $translate);
			if((! $translate['translated']) && (intval(get_pconfig($arr['uid'],'system','reject_disallowed_languages')))) {
				logger('item_store: language ' . $arr['lang'] . ' not accepted for uid ' . $arr['uid']);
				$ret['message'] = 'language not accepted';
				return $ret;
			}
			$arr = $translate['item'];
		}
	}

	if((x($arr,'obj')) && is_array($arr['obj'])) {
		activity_sanitise($arr['obj']);
		$arr['obj'] = json_encode($arr['obj']);
	}

	if((x($arr,'target')) && is_array($arr['target'])) {
		activity_sanitise($arr['target']);
		$arr['target'] = json_encode($arr['target']);
	}

	if((x($arr,'attach')) && is_array($arr['attach'])) {
		activity_sanitise($arr['attach']);
		$arr['attach'] = json_encode($arr['attach']);
	}

	$arr['aid']           = ((x($arr,'aid'))           ? intval($arr['aid'])                 : 0);
	$arr['mid']           = ((x($arr,'mid'))           ? notags(trim($arr['mid']))           : random_string());
	$arr['author_xchan']  = ((x($arr,'author_xchan'))  ? notags(trim($arr['author_xchan']))  : '');
	$arr['owner_xchan']   = ((x($arr,'owner_xchan'))   ? notags(trim($arr['owner_xchan']))   : '');
	$arr['created']       = ((x($arr,'created') !== false) ? datetime_convert('UTC','UTC',$arr['created']) : datetime_convert());
	$arr['edited']        = ((x($arr,'edited')  !== false) ? datetime_convert('UTC','UTC',$arr['edited'])  : datetime_convert());
	$arr['expires']       = ((x($arr,'expires')  !== false) ? datetime_convert('UTC','UTC',$arr['expires'])  : NULL_DATE);
	$arr['commented']     = ((x($arr,'commented')  !== false) ? datetime_convert('UTC','UTC',$arr['commented'])  : datetime_convert());
	$arr['comments_closed'] = ((x($arr,'comments_closed')  !== false) ? datetime_convert('UTC','UTC',$arr['comments_closed'])  : NULL_DATE);
	$arr['html'] = ((array_key_exists('html',$arr)) ? $arr['html'] : '');

	if($deliver) {
		$arr['received']      = datetime_convert();
		$arr['changed']       = datetime_convert();
	}
	else {

		// When deliver flag is false, we are *probably* performing an import or bulk migration.
		// If one updates the changed timestamp it will be made available to zotfeed and delivery
		// will still take place through backdoor methods. Since these fields are rarely used
		// otherwise, just preserve the original timestamp.

		$arr['received']      = ((x($arr,'received')  !== false) ? datetime_convert('UTC','UTC',$arr['received'])  : datetime_convert());
		$arr['changed']       = ((x($arr,'changed')  !== false) ? datetime_convert('UTC','UTC',$arr['changed'])  : datetime_convert());
	}

	$arr['location']      = ((x($arr,'location'))      ? notags(trim($arr['location']))      : '');
	$arr['coord']         = ((x($arr,'coord'))         ? notags(trim($arr['coord']))         : '');
	$arr['parent_mid']    = ((x($arr,'parent_mid'))    ? notags(trim($arr['parent_mid']))    : '');
	$arr['thr_parent']    = ((x($arr,'thr_parent'))    ? notags(trim($arr['thr_parent']))    : $arr['parent_mid']);
	$arr['verb']          = ((x($arr,'verb'))          ? notags(trim($arr['verb']))          : ACTIVITY_POST);
	$arr['obj_type']      = ((x($arr,'obj_type'))      ? notags(trim($arr['obj_type']))      : ACTIVITY_OBJ_NOTE);
	$arr['obj']           = ((x($arr,'obj'))           ? trim($arr['obj'])                   : '');
	$arr['tgt_type']      = ((x($arr,'tgt_type'))      ? notags(trim($arr['tgt_type']))      : '');
	$arr['target']        = ((x($arr,'target'))        ? trim($arr['target'])                : '');
	$arr['plink']         = ((x($arr,'plink'))         ? notags(trim($arr['plink']))         : '');
	$arr['attach']        = ((x($arr,'attach'))        ? notags(trim($arr['attach']))        : '');
	$arr['app']           = ((x($arr,'app'))           ? notags(trim($arr['app']))           : '');

	$arr['public_policy'] = ((x($arr,'public_policy')) ? notags(trim($arr['public_policy']))  : '' );

	$arr['comment_policy'] = ((x($arr,'comment_policy')) ? notags(trim($arr['comment_policy']))  : 'contacts' );

	if(! array_key_exists('item_unseen',$arr))
		$arr['item_unseen'] = 1;

	if((! array_key_exists('item_nocomment',$arr)) && ($arr['comment_policy'] == 'none'))
		$arr['item_nocomment'] = 1;

	// handle time travelers
	// Allow a bit of fudge in case somebody just has a slightly slow/fast clock

	$d1 = new DateTime('now +10 minutes', new DateTimeZone('UTC'));
	$d2 = new DateTime($arr['created'] . '+00:00');
	if($d2 > $d1)
		$arr['item_delayed'] = 1;

	$arr['llink'] = z_root() . '/display/' . $arr['mid'];

	if(! $arr['plink'])
		$arr['plink'] = $arr['llink'];

	if($arr['parent_mid'] === $arr['mid']) {
		$parent_id = 0;
		$parent_deleted = 0;
		$allow_cid = $arr['allow_cid'];
		$allow_gid = $arr['allow_gid'];
		$deny_cid  = $arr['deny_cid'];
		$deny_gid  = $arr['deny_gid'];
		$public_policy = $arr['public_policy'];
		$comments_closed = $arr['comments_closed'];
		$arr['item_thread_top'] = 1;
	}
	else {

		// find the parent and snarf the item id and ACL's
		// and anything else we need to inherit

		$r = q("SELECT * FROM item WHERE mid = '%s' AND uid = %d ORDER BY id ASC LIMIT 1",
			dbesc($arr['parent_mid']),
			intval($arr['uid'])
		);

		if($r) {

			// in case item_store was killed before the parent's parent attribute got set,
			// set it now. This happens with some regularity on Dreamhost. This will keep
			// us from getting notifications for threads that exist but which we can't see.

			if(($r[0]['mid'] === $r[0]['parent_mid']) && (! intval($r[0]['parent']))) {
				q("update item set parent = id where id = %d",
					intval($r[0]['id'])
				);
			}

			if(comments_are_now_closed($r[0])) {
				logger('item_store: comments closed');
				$ret['message'] = 'Comments closed.';
				return $ret;
			}

			if(($arr['obj_type'] == ACTIVITY_OBJ_NOTE) && (! $arr['obj']))
				$arr['obj_type'] = ACTIVITY_OBJ_COMMENT;

			// is the new message multi-level threaded?
			// even though we don't support it now, preserve the info
			// and re-attach to the conversation parent.

			if($r[0]['mid'] != $r[0]['parent_mid']) {
				$arr['parent_mid'] = $r[0]['parent_mid'];
				$z = q("SELECT * FROM item WHERE mid = '%s' AND parent_mid = '%s' AND uid = %d
					ORDER BY id ASC LIMIT 1",
					dbesc($r[0]['parent_mid']),
					dbesc($r[0]['parent_mid']),
					intval($arr['uid'])
				);
				if($z && count($z))
					$r = $z;
			}

			$parent_id       = $r[0]['id'];
			$parent_deleted  = $r[0]['item_deleted'];
			$allow_cid       = $r[0]['allow_cid'];
			$allow_gid       = $r[0]['allow_gid'];
			$deny_cid        = $r[0]['deny_cid'];
			$deny_gid        = $r[0]['deny_gid'];
			$public_policy   = $r[0]['public_policy'];
			$comments_closed = $r[0]['comments_closed'];

			if(intval($r[0]['item_wall']))
				$arr['item_wall'] = 1;

			// An uplinked comment might arrive with a downstream owner.
			// Fix it.

			if($r[0]['owner_xchan'] !== $arr['owner_xchan']) {
				$arr['owner_xchan'] = $r[0]['owner_xchan'];
//				$uplinked_comment = true;
			}

			// if the parent is private, force privacy for the entire conversation

			if($r[0]['item_private'])
				$arr['item_private'] = $r[0]['item_private'];

			// Edge case. We host a public forum that was originally posted to privately.
			// The original author commented, but as this is a comment, the permissions
			// weren't fixed up so it will still show the comment as private unless we fix it here.

			if(intval($r[0]['item_uplink']) && (! $r[0]['item_private']))
				$arr['item_private'] = 0;
		}
		else {
			logger('item_store: item parent was not found - ignoring item');
			$ret['message'] = 'parent not found.';
			return $ret;
		}
	}

	if($parent_deleted)
		$arr['item_deleted'] = 1;

	$r = q("SELECT id FROM item WHERE mid = '%s' AND uid = %d LIMIT 1",
		dbesc($arr['mid']),
		intval($arr['uid'])
	);
	if($r) {
		logger('item_store: duplicate item ignored. ' . print_r($arr,true));
		$ret['message'] = 'duplicate post.';
		return $ret;
	}

	call_hooks('item_store',$arr);

	// This hook remains for backward compatibility.
	call_hooks('post_remote',$arr);

	if(x($arr,'cancel')) {
		logger('item_store: post cancelled by plugin.');
		$ret['message'] = 'cancelled.';
		return $ret;
	}

	// pull out all the taxonomy stuff for separate storage

	$terms = null;
	if(array_key_exists('term',$arr)) {
		$terms = $arr['term'];
		unset($arr['term']);
	}

	$meta = null;
	if(array_key_exists('iconfig',$arr)) {
		$meta = $arr['iconfig'];
		unset($arr['iconfig']);
	}


 	if(strlen($allow_cid) || strlen($allow_gid) || strlen($deny_cid) || strlen($deny_gid) || strlen($public_policy))
		$private = 1;
	else
		$private = $arr['item_private'];

	$arr['parent']          = $parent_id;
	$arr['allow_cid']       = $allow_cid;
	$arr['allow_gid']       = $allow_gid;
	$arr['deny_cid']        = $deny_cid;
	$arr['deny_gid']        = $deny_gid;
	$arr['public_policy']   = $public_policy;
	$arr['item_private']    = $private;
	$arr['comments_closed'] = $comments_closed;

	logger('item_store: ' . print_r($arr,true), LOGGER_DATA);


	create_table_from_array('item',$arr);

	// find the item we just created

	$r = q("SELECT * FROM item WHERE mid = '%s' AND uid = %d ORDER BY id ASC ",
		$arr['mid'],           // already dbesc'd
		intval($arr['uid'])
	);

	if($r && count($r)) {
		$current_post = $r[0]['id'];
		$arr = $r[0];  // This will gives us a fresh copy of what's now in the DB and undo the db escaping, which really messes up the notifications
		logger('item_store: created item ' . $current_post, LOGGER_DEBUG);
	}
	else {
		logger('item_store: could not locate stored item');
		$ret['message'] = 'unable to retrieve.';
		return $ret;
	}
	if(count($r) > 1) {
		logger('item_store: duplicated post occurred. Removing duplicates.');
		q("DELETE FROM item WHERE mid = '%s' AND uid = %d AND id != %d ",
			$arr['mid'],
			intval($arr['uid']),
			intval($current_post)
		);
	}

	$arr['id'] = $current_post;

	if(! intval($r[0]['parent'])) {
		$x = q("update item set parent = id where id = %d",
			intval($r[0]['id'])
		);
		$arr['parent'] = $r[0]['id'];
	}


	// Store taxonomy

	if(($terms) && (is_array($terms))) {
		foreach($terms as $t) {
			q("insert into term (uid,oid,otype,ttype,term,url)
				values(%d,%d,%d,%d,'%s','%s') ",
				intval($arr['uid']),
				intval($current_post),
				intval(TERM_OBJ_POST),
				intval($t['ttype']),
				dbesc($t['term']),
				dbesc($t['url'])
			);
		}

		$arr['term'] = $terms;
	}

	if($meta) {
		foreach($meta as $m) {
			set_iconfig($current_post,$m['cat'],$m['k'],$m['v'],$m['sharing']);
		}
		$arr['iconfig'] = $meta;
	}


	$ret['item'] = $arr;

	call_hooks('post_remote_end',$arr);

	// update the commented timestamp on the parent - unless this is potentially a clone of an older item
	// which we don't wish to bring to the surface. As the queue only holds deliveries for 3 days, it's
	// suspected of being an older cloned item if the creation time is older than that. 

	if($arr['created'] > datetime_convert('','','now - 4 days')) {
		$z = q("select max(created) as commented from item where parent_mid = '%s' and uid = %d and item_delayed = 0 ",
			dbesc($arr['parent_mid']),
			intval($arr['uid'])
		);

		q("UPDATE item set commented = '%s', changed = '%s' WHERE id = %d",
			dbesc(($z) ? $z[0]['commented'] : (datetime_convert())),
			dbesc(datetime_convert()),
			intval($parent_id)
		);
	}


	// If _creating_ a deleted item, don't propagate it further or send out notifications.
	// We need to store the item details just in case the delete came in before the original post,
	// so that we have an item in the DB that's marked deleted and won't store a fresh post
	// that isn't aware that we were already told to delete it.

	if(($deliver) && (! intval($arr['item_deleted']))) {
		send_status_notifications($current_post,$arr);
		tag_deliver($arr['uid'],$current_post);
	}

	$ret['success'] = true;
	$ret['item_id'] = $current_post;

	return $ret;
}



function item_store_update($arr,$allow_exec = false, $deliver = true) {

	$d = array('item' => $arr, 'allow_exec' => $allow_exec);
	call_hooks('item_store_update', $d );
	$arr = $d['item'];
	$allow_exec = $d['allow_exec'];

	$ret = array('success' => false, 'item_id' => 0);

	if(array_key_exists('cancel',$arr) && $arr['cancel']) {
		logger('cancelled by plugin');
		return $ret;
	}		

	if(! intval($arr['uid'])) {
		logger('item_store_update: no uid');
		$ret['message'] = 'no uid.';
		return $ret;
	}
	if(! intval($arr['id'])) {
		logger('item_store_update: no id');
		$ret['message'] = 'no id.';
		return $ret;
	}

	$orig_post_id = $arr['id'];
	$uid = $arr['uid'];

	$orig = q("select * from item where id = %d and uid = %d limit 1",
		intval($orig_post_id),
		intval($uid)
	);
	if(! $orig) {
		logger('item_store_update: original post not found: ' . $orig_post_id);
		$ret['message'] = 'no original';
		return $ret;
	}

	// override the unseen flag with the original

	$arr['item_unseen'] = $orig[0]['item_unseen'];


	if(array_key_exists('edit',$arr))
		unset($arr['edit']);

	$arr['mimetype']      = ((x($arr,'mimetype'))      ? notags(trim($arr['mimetype']))      : 'text/bbcode');

	if(($arr['mimetype'] == 'application/x-php') && (! $allow_exec)) {
		logger('item_store: php mimetype but allow_exec is denied.');
		$ret['message'] = 'exec denied.';
		return $ret;
	}

    if((! array_key_exists('item_obscured', $arr)) || $arr['item_obscured'] == 0) {

		$arr['lang'] = detect_language($arr['body']);

        // apply the input filter here - if it is obscured it has been filtered already
        $arr['body'] = trim(z_input_filter($arr['uid'],$arr['body'],$arr['mimetype']));

        if(local_channel() && (! $arr['sig'])) {
            $channel = App::get_channel();
            if($channel['channel_hash'] === $arr['author_xchan']) {
                $arr['sig'] = base64url_encode(rsa_sign($arr['body'],$channel['channel_prvkey']));
                $arr['item_verified'] = 1;
            }
        }

		$allowed_languages = get_pconfig($arr['uid'],'system','allowed_languages');

		if((is_array($allowed_languages)) && ($arr['lang']) && (! array_key_exists($arr['lang'],$allowed_languages))) {
			$translate = array('item' => $arr, 'from' => $arr['lang'], 'to' => $allowed_languages, 'translated' => false);
			call_hooks('item_translate', $translate);
			if((! $translate['translated']) && (intval(get_pconfig($arr['uid'],'system','reject_disallowed_languages')))) {
				logger('item_store: language ' . $arr['lang'] . ' not accepted for uid ' . $arr['uid']);
				$ret['message'] = 'language not accepted';
				return $ret;
			}
			$arr = $translate['item'];
		}
	}

	if((x($arr,'obj')) && is_array($arr['obj'])) {
		activity_sanitise($arr['obj']);
		$arr['obj'] = json_encode($arr['obj']);
	}

	if((x($arr,'target')) && is_array($arr['target'])) {
		activity_sanitise($arr['target']);
		$arr['target'] = json_encode($arr['target']);
	}

	if((x($arr,'attach')) && is_array($arr['attach'])) {
		activity_sanitise($arr['attach']);
		$arr['attach'] = json_encode($arr['attach']);
	}

	unset($arr['id']);
	unset($arr['uid']);
	unset($arr['aid']);
	unset($arr['mid']);
	unset($arr['parent']);
	unset($arr['parent_mid']);
	unset($arr['created']);
	unset($arr['author_xchan']);
	unset($arr['owner_xchan']);
	unset($arr['thr_parent']);
	unset($arr['llink']);

	$arr['edited']        = ((x($arr,'edited')  !== false) ? datetime_convert('UTC','UTC',$arr['edited'])  : datetime_convert());
	$arr['expires']       = ((x($arr,'expires')  !== false) ? datetime_convert('UTC','UTC',$arr['expires'])  : $orig[0]['expires']);

	if(array_key_exists('comments_closed',$arr) && $arr['comments_closed'] > NULL_DATE)
		$arr['comments_closed'] = datetime_convert('UTC','UTC',$arr['comments_closed']);
	else
		$arr['comments_closed'] = $orig[0]['comments_closed'];

	$arr['commented']     = $orig[0]['commented'];

	$arr['received']      = $orig[0]['received'];
	$arr['changed']       = $orig[0]['changed'];

	$arr['route']         = ((array_key_exists('route',$arr)) ? trim($arr['route'])          : $orig[0]['route']);
	$arr['diaspora_meta'] = ((x($arr,'diaspora_meta')) ? $arr['diaspora_meta']               : $orig[0]['diaspora_meta']);
	$arr['location']      = ((x($arr,'location'))      ? notags(trim($arr['location']))      : $orig[0]['location']);
	$arr['coord']         = ((x($arr,'coord'))         ? notags(trim($arr['coord']))         : $orig[0]['coord']);
	$arr['verb']          = ((x($arr,'verb'))          ? notags(trim($arr['verb']))          : $orig[0]['verb']);
	$arr['obj_type']      = ((x($arr,'obj_type'))      ? notags(trim($arr['obj_type']))      : $orig[0]['obj_type']);
	$arr['obj']           = ((x($arr,'obj'))           ? trim($arr['obj'])                   : $orig[0]['obj']);
	$arr['tgt_type']      = ((x($arr,'tgt_type'))      ? notags(trim($arr['tgt_type']))      : $orig[0]['tgt_type']);
	$arr['target']        = ((x($arr,'target'))        ? trim($arr['target'])                : $orig[0]['target']);
	$arr['plink']         = ((x($arr,'plink'))         ? notags(trim($arr['plink']))         : $orig[0]['plink']);

	$arr['allow_cid']     = ((array_key_exists('allow_cid',$arr))  ? trim($arr['allow_cid']) : $orig[0]['allow_cid']);
	$arr['allow_gid']     = ((array_key_exists('allow_gid',$arr))  ? trim($arr['allow_gid']) : $orig[0]['allow_gid']);
	$arr['deny_cid']      = ((array_key_exists('deny_cid',$arr))   ? trim($arr['deny_cid'])  : $orig[0]['deny_cid']);
	$arr['deny_gid']      = ((array_key_exists('deny_gid',$arr))   ? trim($arr['deny_gid'])  : $orig[0]['deny_gid']);
	$arr['item_private']  = ((array_key_exists('item_private',$arr)) ? intval($arr['item_private']) : $orig[0]['item_private']);

	$arr['title'] = ((array_key_exists('title',$arr) && strlen($arr['title']))  ? trim($arr['title']) : '');
	$arr['body']  = ((array_key_exists('body',$arr) && strlen($arr['body']))    ? trim($arr['body'])  : '');
	$arr['html']  = ((array_key_exists('html',$arr) && strlen($arr['html']))    ? trim($arr['html'])  : '');

	$arr['attach']        = ((array_key_exists('attach',$arr))        ? notags(trim($arr['attach']))        : $orig[0]['attach']);
	$arr['app']           = ((array_key_exists('app',$arr))           ? notags(trim($arr['app']))           : $orig[0]['app']);

	$arr['item_origin']    = ((array_key_exists('item_origin',$arr))    ? intval($arr['item_origin'])          : $orig[0]['item_origin'] );
	$arr['item_unseen']    = ((array_key_exists('item_unseen',$arr))    ? intval($arr['item_unseen'])          : $orig[0]['item_unseen'] );
	$arr['item_starred']    = ((array_key_exists('item_starred',$arr))    ? intval($arr['item_starred'])          : $orig[0]['item_starred'] );
	$arr['item_uplink']    = ((array_key_exists('item_uplink',$arr))    ? intval($arr['item_uplink'])          : $orig[0]['item_uplink'] );
	$arr['item_consensus']    = ((array_key_exists('item_consensus',$arr))    ? intval($arr['item_consensus'])          : $orig[0]['item_consensus'] );
	$arr['item_wall']    = ((array_key_exists('item_wall',$arr))    ? intval($arr['item_wall'])          : $orig[0]['item_wall'] );
	$arr['item_thread_top']    = ((array_key_exists('item_thread_top',$arr))    ? intval($arr['item_thread_top'])          : $orig[0]['item_thread_top'] );
	$arr['item_notshown']    = ((array_key_exists('item_notshown',$arr))    ? intval($arr['item_notshown'])          : $orig[0]['item_notshown'] );
	$arr['item_nsfw']    = ((array_key_exists('item_nsfw',$arr))    ? intval($arr['item_nsfw'])          : $orig[0]['item_nsfw'] );
	$arr['item_relay']    = ((array_key_exists('item_relay',$arr))    ? intval($arr['item_relay'])          : $orig[0]['item_relay'] );
	$arr['item_mentionsme']    = ((array_key_exists('item_mentionsme',$arr))    ? intval($arr['item_mentionsme'])          : $orig[0]['item_mentionsme'] );
	$arr['item_nocomment']    = ((array_key_exists('item_nocomment',$arr))    ? intval($arr['item_nocomment'])          : $orig[0]['item_nocomment'] );
	$arr['item_obscured']    = ((array_key_exists('item_obscured',$arr))    ? intval($arr['item_obscured'])          : $orig[0]['item_obscured'] );
	$arr['item_verified']    = ((array_key_exists('item_verified',$arr))    ? intval($arr['item_verified'])          : $orig[0]['item_verified'] );
	$arr['item_retained']    = ((array_key_exists('item_retained',$arr))    ? intval($arr['item_retained'])          : $orig[0]['item_retained'] );
	$arr['item_rss']    = ((array_key_exists('item_rss',$arr))    ? intval($arr['item_rss'])          : $orig[0]['item_rss'] );
	$arr['item_deleted']    = ((array_key_exists('item_deleted',$arr))    ? intval($arr['item_deleted'])          : $orig[0]['item_deleted'] );
	$arr['item_type']    = ((array_key_exists('item_type',$arr))    ? intval($arr['item_type'])          : $orig[0]['item_type'] );
	$arr['item_hidden']    = ((array_key_exists('item_hidden',$arr))    ? intval($arr['item_hidden'])          : $orig[0]['item_hidden'] );
	$arr['item_unpublished']    = ((array_key_exists('item_unpublished',$arr))    ? intval($arr['item_unpublished'])          : $orig[0]['item_unpublished'] );
	$arr['item_delayed']    = ((array_key_exists('item_delayed',$arr))    ? intval($arr['item_delayed'])          : $orig[0]['item_delayed'] );
	$arr['item_pending_remove']    = ((array_key_exists('item_pending_remove',$arr))    ? intval($arr['item_pending_remove'])          : $orig[0]['item_pending_remove'] );
	$arr['item_blocked']    = ((array_key_exists('item_blocked',$arr))    ? intval($arr['item_blocked'])          : $orig[0]['item_blocked'] );



	$arr['sig']           = ((x($arr,'sig'))           ? $arr['sig']                         : '');
	$arr['layout_mid']    = ((array_key_exists('layout_mid',$arr)) ? dbesc($arr['layout_mid'])           : $orig[0]['layout_mid'] );

	$arr['public_policy'] = ((x($arr,'public_policy')) ? notags(trim($arr['public_policy']))  : $orig[0]['public_policy'] );
	$arr['comment_policy'] = ((x($arr,'comment_policy')) ? notags(trim($arr['comment_policy']))  : $orig[0]['comment_policy'] );

	call_hooks('post_remote_update',$arr);

	if(x($arr,'cancel')) {
		logger('item_store_update: post cancelled by plugin.');
		$ret['message'] = 'cancelled.';
		return $ret;
	}

	// pull out all the taxonomy stuff for separate storage

	$terms = null;
	if(array_key_exists('term',$arr)) {
		$terms = $arr['term'];
		unset($arr['term']);
	}

	$meta = null;
	if(array_key_exists('iconfig',$arr)) {
		$meta = $arr['iconfig'];
		unset($arr['iconfig']);
	}


	if(! dbesc_array($arr)) {
		$ret['message'] = 'DB array malformed';
		return $ret;
	}

	logger('item_store_update: ' . print_r($arr,true), LOGGER_DATA);

	$str = '';
	foreach($arr as $k => $v) {
		if($str)
			$str .= ",";
		$str .= " " . TQUOT . $k . TQUOT . " = '" . $v . "' ";
	}

	$r = dbq("update item set " . $str . " where id = " . $orig_post_id );

	if($r)
		logger('item_store_update: updated item ' . $orig_post_id, LOGGER_DEBUG);
	else {
		logger('item_store_update: could not update item');
		$ret['message'] = 'DB update failed.';
		return $ret;
	}

	// fetch an unescaped complete copy of the stored item

	$r = q("select * from item where id = %d",
		intval($orig_post_id)
	);
	if($r)
		$arr = $r[0];


	$r = q("delete from term where oid = %d and otype = %d",
		intval($orig_post_id),
		intval(TERM_OBJ_POST)
	);

	if(is_array($terms)) {
		foreach($terms as $t) {
			q("insert into term (uid,oid,otype,ttype,term,url)
				values(%d,%d,%d,%d,'%s','%s') ",
				intval($uid),
				intval($orig_post_id),
				intval(TERM_OBJ_POST),
				intval($t['ttype']),
				dbesc($t['term']),
				dbesc($t['url'])
			);
		}
		$arr['term'] = $terms;
	}

	$r = q("delete from iconfig where iid = %d",
		intval($orig_post_id)
	);

	if($meta) {
		foreach($meta as $m) {
			set_iconfig($orig_post_id,$m['cat'],$m['k'],$m['v'],$m['sharing']);
		}
		$arr['iconfig'] = $meta;
	}

	$ret['item'] = $arr;

	call_hooks('post_remote_update_end',$arr);

	if($deliver) {
		send_status_notifications($orig_post_id,$arr);
		tag_deliver($uid,$orig_post_id);
	}

	$ret['success'] = true;
	$ret['item_id'] = $orig_post_id;

	return $ret;
}



function store_diaspora_comment_sig($datarray, $channel, $parent_item, $post_id, $walltowall = false) {

	// We won't be able to sign Diaspora comments for authenticated visitors
	// - we don't have their private key

	// since Diaspora doesn't handle edits we can only do this for the original text and not update it.

	require_once('include/bb2diaspora.php');
	$signed_body = bb2diaspora_itembody($datarray,$walltowall);

	if($walltowall) {
		logger('wall to wall comment',LOGGER_DEBUG);
		// post will come across with the owner's identity. Throw a preamble onto the post to indicate the true author.
		$signed_body = "\n\n"
			. '![' . $datarray['author']['xchan_name'] . '](' . $datarray['author']['xchan_photo_m'] . ')'
			. '[' . $datarray['author']['xchan_name'] . '](' . $datarray['author']['xchan_url'] . ')' . "\n\n"
			. $signed_body;
	}

	logger('storing diaspora comment signature',LOGGER_DEBUG);

	$diaspora_handle = channel_reddress($channel);

	$signed_text = $datarray['mid'] . ';' . $parent_item['mid'] . ';' . $signed_body . ';' . $diaspora_handle;


	if( $channel && $channel['channel_prvkey'] )
		$authorsig = base64_encode(rsa_sign($signed_text, $channel['channel_prvkey'], 'sha256'));
	else
		$authorsig = '';

	$x = array('signer' => $diaspora_handle, 'body' => $signed_body, 'signed_text' => $signed_text, 'signature' => $authorsig);

	$y = json_encode($x);

	$r = q("update item set diaspora_meta = '%s' where id = %d",
		dbesc($y),
		intval($post_id)
	);


	if(! $r)
		logger('store_diaspora_comment_sig: DB write failed');

	return;
}



function send_status_notifications($post_id,$item) {

	// only send notifications for comments

	if($item['mid'] == $item['parent_mid'])
		return;

	$notify = false;
	$unfollowed = false;

	$parent = 0;

	$r = q("select channel_hash from channel where channel_id = %d limit 1",
		intval($item['uid'])
	);
	if(! $r)
		return;

	// my own post - no notification needed
	if($item['author_xchan'] === $r[0]['channel_hash'])
		return;


	// I'm the owner - notify me

	if($item['owner_hash'] === $r[0]['channel_hash'])
		$notify = true;

	// Was I involved in this conversation?

	$x = q("select * from item where parent_mid = '%s' and uid = %d",
		dbesc($item['parent_mid']),
		intval($item['uid'])
	);
	if($x) {
		foreach($x as $xx) {
			if($xx['author_xchan'] === $r[0]['channel_hash']) {

				$notify = true;

				// check for an unfollow thread activity - we should probably decode the obj and check the id
				// but it will be extremely rare for this to be wrong.

				if(($xx['verb'] === ACTIVITY_UNFOLLOW)
					&& ($xx['obj_type'] === ACTIVITY_OBJ_NOTE || $xx['obj_type'] === ACTIVITY_OBJ_PHOTO)
					&& ($xx['parent'] != $xx['id']))
					$unfollowed = true;
			}
			if($xx['id'] == $xx['parent']) {
				$parent = $xx['parent'];
			}
		}
	}

	if($unfollowed)
		return;

	$link =  z_root() . '/display/' . $item['mid'];

	$y = q("select id from notify where link = '%s' and uid = %d limit 1",
		dbesc($link),
		intval($item['uid'])
	);

	if($y)
		$notify = false;

	if(! $notify)
		return;


	Zlib\Enotify::submit(array(
		'type'         => NOTIFY_COMMENT,
		'from_xchan'   => $item['author_xchan'],
		'to_xchan'     => $r[0]['channel_hash'],
		'item'         => $item,
		'link'         => $link,
		'verb'         => ACTIVITY_POST,
		'otype'        => 'item',
		'parent'       => $parent,
		'parent_mid'   => $item['parent_mid']
	));
}


function get_item_contact($item,$contacts) {
	if(! count($contacts) || (! is_array($item)))
		return false;

	foreach($contacts as $contact) {
		if($contact['id'] == $item['contact-id']) {
			return $contact;
			break; // NOTREACHED
		}
	}

	return false;
}

/**
 * @brief Called when we deliver things that might be tagged in ways that require delivery processing.
 *
 * Handles community tagging of posts and also look for mention tags and sets up
 * a second delivery chain if appropriate.
 *
 * @param int $uid
 * @param int $item_id
 */
function tag_deliver($uid, $item_id) {

	$mention = false;

	/*
	 * Fetch stuff we need - a channel and an item
	 */

	$u = q("select * from channel left join xchan on channel_hash = xchan_hash where channel_id = %d limit 1",
		intval($uid)
	);
	if(! $u)
		return;

	$i = q("select * from item where id = %d and uid = %d limit 1",
		intval($item_id),
		intval($uid)
	);
	if(! $i)
		return;

	$i = fetch_post_tags($i);

	$item = $i[0];

	if(($item['source_xchan']) && intval($item['item_uplink'])
		&& intval($item['item_thread_top']) && ($item['edited'] != $item['created'])) {

		// this is an update (edit) to a post which was already processed by us and has a second delivery chain
		// Just start the second delivery chain to deliver the updated post
		// after resetting ownership and permission bits

		start_delivery_chain($u[0], $item, $item_id, 0);
		return;
	}

	/*
	 * Seems like a good place to plug in a poke notification.
	 */

	if (stristr($item['verb'],ACTIVITY_POKE)) {
		$poke_notify = true;

		if(($item['obj_type'] == "") || ($item['obj_type'] !== ACTIVITY_OBJ_PERSON) || (! $item['obj']))
			$poke_notify = false;

		$obj = json_decode($item['obj'],true);
		if($obj) {
			if($obj['id'] !== $u[0]['channel_hash'])
				$poke_notify = false;
		}
		if(intval($item['item_deleted']))
			$poke_notify = false;

		$verb = urldecode(substr($item['verb'],strpos($item['verb'],'#')+1));
		if($poke_notify) {
			Zlib\Enotify::submit(array(
				'to_xchan'     => $u[0]['channel_hash'],
				'from_xchan'   => $item['author_xchan'],
				'type'         => NOTIFY_POKE,
				'item'         => $item,
				'link'         => $i[0]['llink'],
				'verb'         => ACTIVITY_POKE,
				'activity'     => $verb,
				'otype'        => 'item'
			));
		}
	}

	/*
	 * Do community tagging
	 */

	if($item['obj_type'] === ACTIVITY_OBJ_TAGTERM) {

		// We received a community tag activity for a post.
		// See if we are the owner of the parent item and have given permission to tag our posts.
		// If so tag the parent post.

		logger('tag_deliver: community tag activity received');

		if(($item['owner_xchan'] === $u[0]['channel_hash']) && (! get_pconfig($u[0]['channel_id'],'system','blocktags'))) {
			logger('tag_deliver: community tag recipient: ' . $u[0]['channel_name']);
			$j_tgt = json_decode($item['target'],true);
			if($j_tgt && $j_tgt['id']) {
				$p = q("select * from item where mid = '%s' and uid = %d limit 1",
					dbesc($j_tgt['id']),
					intval($u[0]['channel_id'])
				);
				if($p) {
					$j_obj = json_decode($item['obj'],true);
					logger('tag_deliver: tag object: ' . print_r($j_obj,true), LOGGER_DATA);
					if($j_obj && $j_obj['id'] && $j_obj['title']) {
						if(is_array($j_obj['link']))
							$taglink = get_rel_link($j_obj['link'],'alternate');

						store_item_tag($u[0]['channel_id'],$p[0]['id'],TERM_OBJ_POST,TERM_COMMUNITYTAG,$j_obj['title'],$j_obj['id']);
						$x = q("update item set edited = '%s', received = '%s', changed = '%s' where mid = '%s' and uid = %d",
							dbesc(datetime_convert()),
							dbesc(datetime_convert()),
							dbesc(datetime_convert()),
							dbesc($j_tgt['id']),
							intval($u[0]['channel_id'])
						);
						Zotlabs\Daemon\Master::Summon(array('Notifier','edit_post',$p[0]['id']));
					}
				}
			}
		}
		else
			logger('tag_deliver: tag permission denied for ' . $u[0]['channel_address']);
	}

	/*
	 * A "union" is a message which our channel has sourced from another channel.
	 * This sets up a second delivery chain just like forum tags do.
	 * Find out if this is a source-able post.
	 */

	$union = check_item_source($uid,$item);
	if($union)
		logger('check_item_source returns true');


	// This might be a followup (e.g. comment) by the original post author to a tagged forum
	// If so setup a second delivery chain

	if( ! intval($item['item_thread_top'])) {
		$x = q("select * from item where id = parent and parent = %d and uid = %d limit 1",
			intval($item['parent']),
			intval($uid)
		);

		if(($x) && intval($x[0]['item_uplink'])) {
			start_delivery_chain($u[0],$item,$item_id,$x[0]);
		}
	}


	/*
	 * Now we've got those out of the way. Let's see if this is a post that's tagged for re-delivery
	 */

	$terms = get_terms_oftype($item['term'],TERM_MENTION);

	if($terms)
		logger('tag_deliver: post mentions: ' . print_r($terms,true), LOGGER_DATA);

	$link = normalise_link($u[0]['xchan_url']);

	if($terms) {
		foreach($terms as $term) {
			if(link_compare($term['url'],$link)) {
				$mention = true;
				break;
			}
		}
	}

	if($mention) {
		logger('tag_deliver: mention found for ' . $u[0]['channel_name']);

		$r = q("update item set item_mentionsme = 1 where id = %d",
			intval($item_id)
		);

		// At this point we've determined that the person receiving this post was mentioned in it or it is a union.
		// Now let's check if this mention was inside a reshare so we don't spam a forum
		// If it's private we may have to unobscure it momentarily so that we can parse it.

		$body = '';

		if(intval($item['item_obscured'])) {
			$key = get_config('system','prvkey');
			if($item['body'])
				$body = crypto_unencapsulate(json_decode($item['body'],true),$key);
		}
		else
			$body = $item['body'];

		$body = preg_replace('/\[share(.*?)\[\/share\]/','',$body);

		$tagged = false;
		$plustagged = false;
		$matches = array();

		$pattern = '/@\!?\[zrl\=' . preg_quote($term['url'],'/') . '\]' . preg_quote($term['term'],'/') . '\[\/zrl\]/';
		if(preg_match($pattern,$body,$matches))
			$tagged = true;

		$pattern = '/@\!?\[zrl\=([^\]]*?)\]((?:.(?!\[zrl\=))*?)\+\[\/zrl\]/';

		if(preg_match_all($pattern,$body,$matches,PREG_SET_ORDER)) {
			$max_forums = get_config('system','max_tagged_forums');
			if(! $max_forums)
				$max_forums = 2;
			$matched_forums = 0;
			foreach($matches as $match) {
				$matched_forums ++;
				if($term['url'] === $match[1] && $term['term'] === $match[2]) {
					if($matched_forums <= $max_forums) {
						$plustagged = true;
						break;
					}
					logger('forum ' . $term['term'] . ' exceeded max_tagged_forums - ignoring');
				}
			}
		}

		if(! ($tagged || $plustagged)) {
			logger('tag_deliver: mention was in a reshare or exceeded max_tagged_forums - ignoring');
			return;
		}

		$arr = array('channel_id' => $uid, 'item' => $item, 'body' => $body);
		call_hooks('tagged',$arr);

		/*
		 * Kill two birds with one stone. As long as we're here, send a mention notification.
		 */

		Zlib\Enotify::submit(array(
			'to_xchan'     => $u[0]['channel_hash'],
			'from_xchan'   => $item['author_xchan'],
			'type'         => NOTIFY_TAGSELF,
			'item'         => $item,
			'link'         => $i[0]['llink'],
			'verb'         => ACTIVITY_TAG,
			'otype'        => 'item'
		));

		// Just a normal tag?

		if(! $plustagged) {
			logger('tag_deliver: not a plus tag', LOGGER_DEBUG);
			return;
		}

		// plustagged - keep going, next check permissions

		if(! perm_is_allowed($uid,$item['author_xchan'],'tag_deliver')) {
			logger('tag_delivery denied for uid ' . $uid . ' and xchan ' . $item['author_xchan']);
			return;
		}
	}

	if((! $mention) && (! $union)) {
		logger('tag_deliver: no mention for ' . $u[0]['channel_name'] . ' and no union.');
		return;
	}

	// tgroup delivery - setup a second delivery chain
	// prevent delivery looping - only proceed
	// if the message originated elsewhere and is a top-level post


	if(intval($item['item_wall']) || intval($item['item_origin']) || (! intval($item['item_thread_top'])) || ($item['id'] != $item['parent'])) {
		logger('tag_deliver: item was local or a comment. rejected.');
		return;
	}

	logger('tag_deliver: creating second delivery chain.');
	start_delivery_chain($u[0],$item,$item_id,null);
}

/**
 * @brief This function is called pre-deliver to see if a post matches the criteria to be tag delivered.
 *
 * We don't actually do anything except check that it matches the criteria.
 * This is so that the channel with tag_delivery enabled can receive the post even if they turn off
 * permissions for the sender to send their stream. tag_deliver() can't be called until the post is actually stored.
 * By then it would be too late to reject it.
 */
function tgroup_check($uid,$item) {

	$mention = false;

	// check that the message originated elsewhere and is a top-level post
	// or is a followup and we have already accepted the top level post as an uplink

	if($item['mid'] != $item['parent_mid']) {
		$r = q("select id from item where mid = '%s' and uid = %d and item_uplink = 1 limit 1",
			dbesc($item['parent_mid']),
			intval($uid)
		);
		if($r)
			return true;

		return false;
	}
	if(! perm_is_allowed($uid,$item['author_xchan'],'tag_deliver'))
		return false;

	$u = q("select * from channel left join xchan on channel_hash = xchan_hash where channel_id = %d limit 1",
		intval($uid)
	);

	if(! $u)
		return false;

	$terms = get_terms_oftype($item['term'],TERM_MENTION);

	if($terms)
		logger('tgroup_check: post mentions: ' . print_r($terms,true), LOGGER_DATA);

	$link = normalise_link($u[0]['xchan_url']);

	if($terms) {
		foreach($terms as $term) {
			if(link_compare($term['url'],$link)) {
				$mention = true;
				break;
			}
		}
	}

	if($mention) {
		logger('tgroup_check: mention found for ' . $u[0]['channel_name']);
	}
	else
		return false;

	// At this point we've determined that the person receiving this post was mentioned in it.
	// Now let's check if this mention was inside a reshare so we don't spam a forum
	// note: $term has been set to the matching term


	$body = $item['body'];

	if(array_key_exists('item_obscured',$item) && intval($item['item_obscured']) && $body) {
		$key = get_config('system','prvkey');
		$body = crypto_unencapsulate(json_decode($body,true),$key);
	}

	$body = preg_replace('/\[share(.*?)\[\/share\]/','',$body);

//	$pattern = '/@\!?\[zrl\=' . preg_quote($term['url'],'/') . '\]' . preg_quote($term['term'] . '+','/') . '\[\/zrl\]/';

	$pattern = '/@\!?\[zrl\=([^\]]*?)\]((?:.(?!\[zrl\=))*?)\+\[\/zrl\]/';

	$found = false;
	$matches = array();

	if(preg_match_all($pattern,$body,$matches,PREG_SET_ORDER)) {
		$max_forums = get_config('system','max_tagged_forums');
		if(! $max_forums)
			$max_forums = 2;
		$matched_forums = 0;
		foreach($matches as $match) {
			$matched_forums ++;
			if($term['url'] === $match[1] && $term['term'] === $match[2]) {
				if($matched_forums <= $max_forums) {
					$found = true;
					break;
				}
				logger('forum ' . $term['term'] . ' exceeded max_tagged_forums - ignoring');
			}
		}
	}

	if(! $found) {
		logger('tgroup_check: mention was in a reshare or exceeded max_tagged_forums - ignoring');
		return false;
	}

	return true;
}

/**
 * Sourced and tag-delivered posts are re-targetted for delivery to the connections of the channel
 * receiving the post. This starts the second delivery chain, by resetting permissions and ensuring
 * that ITEM_UPLINK is set on the parent post, and storing the current owner_xchan as the source_xchan.
 * We'll become the new owner. If called without $parent, this *is* the parent post.
 *
 * @param array $channel
 * @param array $item
 * @param int $item_id
 * @param boolean $parent
 */
function start_delivery_chain($channel, $item, $item_id, $parent) {

	$sourced = check_item_source($channel['channel_id'],$item);

	if($sourced) {
		$r = q("select * from source where src_channel_id = %d and ( src_xchan = '%s' or src_xchan = '*' ) limit 1",
			intval($channel['channel_id']),
	        dbesc(($item['source_xchan']) ?  $item['source_xchan'] : $item['owner_xchan'])
    	);
		if($r) {
			$t = trim($r[0]['src_tag']);
			if($t) {
				$tags = explode(',',$t);
				if($tags) {
					foreach($tags as $tt) {
						$tt = trim($tt);
						if($tt) {
            				q("insert into term (uid,oid,otype,ttype,term,url)
                				values(%d,%d,%d,%d,'%s','%s') ",
                				intval($channel['channel_id']),
				                intval($item_id),
                				intval(TERM_OBJ_POST),
				                intval(TERM_CATEGORY),
                				dbesc($tt),
								dbesc(z_root() . '/channel/' . $channel['channel_address'] . '?f=&cat=' . urlencode($tt))
            				);
						}
					}
				}
			}
		}
	}

	// Change this copy of the post to a forum head message and deliver to all the tgroup members
	// also reset all the privacy bits to the forum default permissions

	$private = (($channel['channel_allow_cid'] || $channel['channel_allow_gid']
		|| $channel['channel_deny_cid'] || $channel['channel_deny_gid']) ? 1 : 0);

	$new_public_policy = map_scope(\Zotlabs\Access\PermissionLimits::Get($channel['channel_id'],'view_stream'),true);

	if((! $private) && $new_public_policy)
		$private = 1;

	$item_wall = 1;
	$item_origin = 1;
	$item_uplink = 0;
	$item_nocomment = 0;
	$item_obscured = 0;

	$flag_bits = $item['item_flags'];

	// maintain the original source, which will be the original item owner and was stored in source_xchan
	// when we created the delivery fork

	if($parent) {
		$r = q("update item set source_xchan = '%s' where id = %d",
			dbesc($parent['source_xchan']),
			intval($item_id)
		);
	}
	else {
		$item_uplink = 1;
		$r = q("update item set source_xchan = owner_xchan where id = %d",
			intval($item_id)
		);
	}

	$title = $item['title'];
	$body  = $item['body'];

	$r = q("update item set item_uplink = %d, item_nocomment = %d, item_obscured = %d, item_flags = %d, owner_xchan = '%s', allow_cid = '%s', allow_gid = '%s',
		deny_cid = '%s', deny_gid = '%s', item_private = %d, public_policy = '%s', comment_policy = '%s', title = '%s', body = '%s', item_wall = %d, item_origin = %d  where id = %d",
		intval($item_uplink),
		intval($item_nocomment),
		intval($item_obscured),
		intval($flag_bits),
		dbesc($channel['channel_hash']),
		dbesc($channel['channel_allow_cid']),
		dbesc($channel['channel_allow_gid']),
		dbesc($channel['channel_deny_cid']),
		dbesc($channel['channel_deny_gid']),
		intval($private),
		dbesc($new_public_policy),
		dbesc(map_scope(\Zotlabs\Access\PermissionLimits::Get($channel['channel_id'],'post_comments'))),
		dbesc($title),
		dbesc($body),
		intval($item_wall),
		intval($item_origin),
		intval($item_id)
	);




	if($r)
		Zotlabs\Daemon\Master::Summon(array('Notifier','tgroup',$item_id));
	else {
		logger('start_delivery_chain: failed to update item');
		// reset the source xchan to prevent loops
		$r = q("update item set source_xchan = '' where id = %d",
			intval($item_id)
		);
	}
}

/**
 * @brief
 *
 * Checks to see if this item owner is referenced as a source for this channel and if the post
 * matches the rules for inclusion in this channel. Returns true if we should create a second delivery
 * chain and false if none of the rules apply, or if the item is private.
 *
 * @param int $uid
 * @param array $item
 */
function check_item_source($uid, $item) {
	$r = q("select * from source where src_channel_id = %d and ( src_xchan = '%s' or src_xchan = '*' ) limit 1",
		intval($uid),
		dbesc(($item['source_xchan']) ?  $item['source_xchan'] : $item['owner_xchan'])
	);

	if(! $r)
		return false;

	$x = q("select abook_their_perms, abook_feed from abook where abook_channel = %d and abook_xchan = '%s' limit 1",
		intval($uid),
		dbesc($item['owner_xchan'])
	);

	if(! $x)
		return false;

	if(! get_abconfig($uid,$item['owner_xchan'],'their_perms','republish'))
		return false;

	if($item['item_private'] && (! intval($x[0]['abook_feed'])))
		return false;

	if($r[0]['src_channel_xchan'] === $item['owner_xchan'])
		return false;


	// since we now have connection filters with more features, the source filter is redundant and can probably go away

	if(! $r[0]['src_patt'])
		return true;


	require_once('include/html2plain.php');
	$text = prepare_text($item['body'],$item['mimetype']);
	$text = html2plain($text);

	$tags = ((count($item['term'])) ? $item['term'] : false);

	$words = explode("\n",$r[0]['src_patt']);
	if($words) {
		foreach($words as $word) {
			if(substr($word,0,1) === '#' && $tags) {
				foreach($tags as $t)
					if((($t['ttype'] == TERM_HASHTAG) || ($t['ttype'] == TERM_COMMUNITYTAG)) && (($t['term'] === substr($word,1)) || (substr($word,1) === '*')))
						return true;
			}
			elseif((strpos($word,'/') === 0) && preg_match($word,$text))
				return true;
			elseif(stristr($text,$word) !== false)
				return true;
		}
	}

	return false;
}

function post_is_importable($item,$abook) {

	if(! $abook)
		return true;

	if(($abook['abook_channel']) && (! feature_enabled($abook['abook_channel'],'connfilter')))
		return true;

	if(! $item)
		return false;

	if(! ($abook['abook_incl'] || $abook['abook_excl']))
		return true;

	require_once('include/html2plain.php');

	unobscure($item);

	$text = prepare_text($item['body'],$item['mimetype']);
	$text = html2plain(($item['title']) ? $item['title'] . ' ' . $text : $text);


	$lang = null;

	if((strpos($abook['abook_incl'],'lang=') !== false) || (strpos($abook['abook_excl'],'lang=') !== false)) {
		$lang = detect_language($text);
	}
	$tags = ((count($item['term'])) ? $item['term'] : false);

	// exclude always has priority

	$exclude = (($abook['abook_excl']) ? explode("\n",$abook['abook_excl']) : null);

	if($exclude) {
		foreach($exclude as $word) {
			$word = trim($word);
			if(! $word)
				continue;
			if(substr($word,0,1) === '#' && $tags) {
				foreach($tags as $t)
					if((($t['ttype'] == TERM_HASHTAG) || ($t['ttype'] == TERM_COMMUNITYTAG)) && (($t['term'] === substr($word,1)) || (substr($word,1) === '*')))
						return false;
			}
			elseif((strpos($word,'/') === 0) && preg_match($word,$text))
				return false;
			elseif((strpos($word,'lang=') === 0) && ($lang) && (strcasecmp($lang,trim(substr($word,5))) == 0))
				return false;
			elseif(stristr($text,$word) !== false)
				return false;
		}
	}

	$include = (($abook['abook_incl']) ? explode("\n",$abook['abook_incl']) : null);

	if($include) {
		foreach($include as $word) {
			$word = trim($word);
			if(! $word)
				continue;
			if(substr($word,0,1) === '#' && $tags) {
				foreach($tags as $t)
					if((($t['ttype'] == TERM_HASHTAG) || ($t['ttype'] == TERM_COMMUNITYTAG)) && (($t['term'] === substr($word,1)) || (substr($word,1) === '*')))
						return true;
			}
			elseif((strpos($word,'/') === 0) && preg_match($word,$text))
				return true;
			elseif((strpos($word,'lang=') === 0) && ($lang) && (strcasecmp($lang,trim(substr($word,5))) == 0))
				return true;
			elseif(stristr($text,$word) !== false)
				return true;
		}
	}
	else {
		return true;
	}

	return false;
}


function mail_store($arr) {

	if(! $arr['channel_id']) {
		logger('mail_store: no uid');
		return 0;
	}

	if(! $arr['mail_obscured']) {
		if((strpos($arr['body'],'<') !== false) || (strpos($arr['body'],'>') !== false))
			$arr['body'] = escape_tags($arr['body']);
	}

	if(array_key_exists('attach',$arr) && is_array($arr['attach']))
		$arr['attach'] = json_encode($arr['attach']);

	$arr['account_id']    = ((x($arr,'account_id'))           ? intval($arr['account_id'])                 : 0);
	$arr['mid']           = ((x($arr,'mid'))           ? notags(trim($arr['mid']))           : random_string());
	$arr['from_xchan']    = ((x($arr,'from_xchan'))  ? notags(trim($arr['from_xchan']))  : '');
	$arr['to_xchan']      = ((x($arr,'to_xchan'))   ? notags(trim($arr['to_xchan']))   : '');
	$arr['created']       = ((x($arr,'created') !== false) ? datetime_convert('UTC','UTC',$arr['created']) : datetime_convert());
	$arr['expires']       = ((x($arr,'expires') !== false) ? datetime_convert('UTC','UTC',$arr['expires']) : NULL_DATE);
	$arr['title']         = ((x($arr,'title'))         ? trim($arr['title'])         : '');
	$arr['parent_mid']    = ((x($arr,'parent_mid'))    ? notags(trim($arr['parent_mid']))    : '');
	$arr['body']          = ((x($arr,'body'))          ? trim($arr['body'])                  : '');
	$arr['conv_guid']     = ((x($arr,'conv_guid'))     ? trim($arr['conv_guid'])             : '');

	$arr['mail_flags']    = ((x($arr,'mail_flags'))    ? intval($arr['mail_flags'])          : 0 );

	if(! $arr['parent_mid']) {
		logger('mail_store: missing parent');
		$arr['parent_mid'] = $arr['mid'];
	}

	$r = q("SELECT id FROM mail WHERE mid = '%s' AND channel_id = %d LIMIT 1",
		dbesc($arr['mid']),
		intval($arr['channel_id'])
	);

	if($r) {
		logger('mail_store: duplicate item ignored. ' . print_r($arr,true));
		return 0;
	}

	if(! $r && $arr['mail_recalled'] == 1) {
		logger('mail_store: recalled item not found. ' . print_r($arr,true));
		return 0;
	}

	call_hooks('post_mail',$arr);

	if(x($arr,'cancel')) {
		logger('mail_store: post cancelled by plugin.');
		return 0;
	}

	logger('mail_store: ' . print_r($arr,true), LOGGER_DATA);

	create_table_from_array('mail', $arr);

	// find the item we just created

	$r = q("SELECT id FROM mail WHERE mid = '%s' AND channel_id = %d ORDER BY id ASC ",
		$arr['mid'],           // already dbesc'd
		intval($arr['channel_id'])
	);

	if($r) {
		$current_post = $r[0]['id'];
		logger('mail_store: created item ' . $current_post, LOGGER_DEBUG);
		$arr['id'] = $current_post; // for notification
	}
	else {
		logger('mail_store: could not locate created item');
		return 0;
	}
	if(count($r) > 1) {
		logger('mail_store: duplicated post occurred. Removing duplicates.');
		q("DELETE FROM mail WHERE mid = '%s' AND channel_id = %d AND id != %d ",
			$arr['mid'],
			intval($arr['channel_id']),
			intval($current_post)
		);
	}
	else {

		$notif_params = array(
			'from_xchan' => $arr['from_xchan'],
			'to_xchan'   => $arr['to_xchan'],
			'type'       => NOTIFY_MAIL,
			'item'       => $arr,
			'verb'       => ACTIVITY_POST,
			'otype'      => 'mail'
		);

		Zlib\Enotify::submit($notif_params);
	}

	call_hooks('post_mail_end',$arr);
	return $current_post;
}


function fix_private_photos($s, $uid, $item = null, $cid = 0) {

	logger('fix_private_photos', LOGGER_DEBUG);
	$site = substr(z_root(),strpos(z_root(),'://'));

	$orig_body = $s;
	$new_body = '';

	$img_start = strpos($orig_body, '[zmg');
	$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
	$img_len = ($img_start !== false ? strpos(substr($orig_body, $img_start + $img_st_close + 1), '[/zmg]') : false);
	while( ($img_st_close !== false) && ($img_len !== false) ) {

		$img_st_close++; // make it point to AFTER the closing bracket
		$image = substr($orig_body, $img_start + $img_st_close, $img_len);

		logger('fix_private_photos: found photo ' . $image, LOGGER_DEBUG);

		if(stristr($image , $site . '/photo/')) {
			// Only embed locally hosted photos
			$replace = false;
			$i = basename($image);
			$x = strpos($i,'-');

			if($x) {
				$res = substr($i,$x+1);
				$i = substr($i,0,$x);
				$r = q("SELECT * FROM photo WHERE resource_id = '%s' AND imgscale = %d AND uid = %d",
					dbesc($i),
					intval($res),
					intval($uid)
				);
				if(count($r)) {

					// Check to see if we should replace this photo link with an embedded image
					// 1. No need to do so if the photo is public
					// 2. If there's a contact-id provided, see if they're in the access list
					//    for the photo. If so, embed it.
					// 3. Otherwise, if we have an item, see if the item permissions match the photo
					//    permissions, regardless of order but first check to see if they're an exact
					//    match to save some processing overhead.

					if(has_permissions($r[0])) {
						if($cid) {
							$recips = enumerate_permissions($r[0]);
							if(in_array($cid, $recips)) {
								$replace = true;
							}
						}
						elseif($item) {
							if(compare_permissions($item,$r[0]))
								$replace = true;
						}
					}
					if($replace) {
						$data = $r[0]['data'];
						$type = $r[0]['type'];

						// If a custom width and height were specified, apply before embedding
						if(preg_match("/\[zmg\=([0-9]*)x([0-9]*)\]/is", substr($orig_body, $img_start, $img_st_close), $match)) {
							logger('fix_private_photos: scaling photo', LOGGER_DEBUG);

							$width = intval($match[1]);
							$height = intval($match[2]);

							$ph = photo_factory($data, $type);
							if($ph->is_valid()) {
								$ph->scaleImage(max($width, $height));
								$data = $ph->imageString();
								$type = $ph->getType();
							}
						}

						logger('fix_private_photos: replacing photo', LOGGER_DEBUG);
						$image = 'data:' . $type . ';base64,' . base64_encode($data);
						logger('fix_private_photos: replaced: ' . $image, LOGGER_DATA);
					}
				}
			}
		}

		$new_body = $new_body . substr($orig_body, 0, $img_start + $img_st_close) . $image . '[/zmg]';
		$orig_body = substr($orig_body, $img_start + $img_st_close + $img_len + strlen('[/zmg]'));
		if($orig_body === false)
			$orig_body = '';

		$img_start = strpos($orig_body, '[zmg');
		$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
		$img_len = ($img_start !== false ? strpos(substr($orig_body, $img_start + $img_st_close + 1), '[/zmg]') : false);
	}

	$new_body = $new_body . $orig_body;

	return($new_body);
}


function has_permissions($obj) {
	if(($obj['allow_cid'] != '') || ($obj['allow_gid'] != '') || ($obj['deny_cid'] != '') || ($obj['deny_gid'] != ''))
		return true;

	return false;
}

function compare_permissions($obj1,$obj2) {
	// first part is easy. Check that these are exactly the same.
	if(($obj1['allow_cid'] == $obj2['allow_cid'])
		&& ($obj1['allow_gid'] == $obj2['allow_gid'])
		&& ($obj1['deny_cid'] == $obj2['deny_cid'])
		&& ($obj1['deny_gid'] == $obj2['deny_gid']))
		return true;

	// This is harder. Parse all the permissions and compare the resulting set.

	$recipients1 = enumerate_permissions($obj1);
	$recipients2 = enumerate_permissions($obj2);
	sort($recipients1);
	sort($recipients2);
	if($recipients1 == $recipients2)
		return true;
	return false;
}

/**
 * @brief Returns an array of contact-ids that are allowed to see this object.
 *
 * @param object $obj
 * @return array
 */
function enumerate_permissions($obj) {
	require_once('include/group.php');

	$allow_people = expand_acl($obj['allow_cid']);
	$allow_groups = expand_groups(expand_acl($obj['allow_gid']));
	$deny_people  = expand_acl($obj['deny_cid']);
	$deny_groups  = expand_groups(expand_acl($obj['deny_gid']));
	$recipients   = array_unique(array_merge($allow_people,$allow_groups));
	$deny         = array_unique(array_merge($deny_people,$deny_groups));
	$recipients   = array_diff($recipients,$deny);

	return $recipients;
}

function item_getfeedtags($item) {

	$terms = get_terms_oftype($item['term'],array(TERM_HASHTAG,TERM_MENTION,TERM_COMMUNITYTAG));
	$ret = array();

	if(count($terms)) {
		foreach($terms as $term) {
			if(($term['ttype'] == TERM_HASHTAG) || ($term['ttype'] == TERM_COMMUNITYTAG))
				$ret[] = array('#',$term['url'],$term['term']);
			else
				$ret[] = array('@',$term['url'],$term['term']);
		}
	}

	return $ret;
}

function item_getfeedattach($item) {
	$ret = '';
	$arr = explode(',',$item['attach']);
	if(count($arr)) {
		foreach($arr as $r) {
			$matches = false;
			$cnt = preg_match('|\[attach\]href=\"(.*?)\" length=\"(.*?)\" type=\"(.*?)\" title=\"(.*?)\"\[\/attach\]|',$r,$matches);
			if($cnt) {
				$ret .= '<link rel="enclosure" href="' . xmlify($matches[1]) . '" type="' . xmlify($matches[3]) . '" ';
				if(intval($matches[2]))
					$ret .= 'length="' . intval($matches[2]) . '" ';
				if($matches[4] !== ' ')
					$ret .= 'title="' . xmlify(trim($matches[4])) . '" ';
				$ret .= ' />' . "\r\n";
			}
		}
	}

	return $ret;
}


function item_expire($uid,$days) {

	if((! $uid) || ($days < 1))
		return;

	// $expire_network_only = save your own wall posts
	// and just expire conversations started by others
	// do not enable this until we can pass bulk delete messages through zot
	//	$expire_network_only = get_pconfig($uid,'expire','network_only');

	$expire_network_only = 1;

	$sql_extra = ((intval($expire_network_only)) ? " AND item_wall = 0 " : "");

	$expire_limit = get_config('system','expire_limit');
	if(! intval($expire_limit))
		$expire_limit = 5000;

	$item_normal = item_normal();

	$r = q("SELECT id FROM item
		WHERE uid = %d
		AND created < %s - INTERVAL %s
		AND item_retained = 0
		AND item_thread_top = 1
		AND resource_type = ''
		AND item_starred = 0
		$sql_extra $item_normal LIMIT $expire_limit ",
		intval($uid),
		db_utcnow(),
		db_quoteinterval(intval($days).' DAY')
	);

	if(! $r)
		return;

	$r = fetch_post_tags($r,true);

	foreach($r as $item) {

		// don't expire filed items

		$terms = get_terms_oftype($item['term'],TERM_FILE);
		if($terms) {
			retain_item($item['id']);
			continue;
		}

		drop_item($item['id'],false);
	}

//	Zotlabs\Daemon\Master::Summon(array('Notifier','expire',$uid));
}

function retain_item($id) {
	$r = q("update item set item_retained = 1 where id = %d",
		intval($id)
	);
}

function drop_items($items) {
	$uid = 0;

	if(! local_channel() && ! remote_channel())
		return;

	if(count($items)) {
		foreach($items as $item) {
			$owner = drop_item($item,false);
			if($owner && ! $uid)
				$uid = $owner;
		}
	}

	// multiple threads may have been deleted, send an expire notification

	if($uid)
		Zotlabs\Daemon\Master::Summon(array('Notifier','expire',$uid));
}


// Delete item with given item $id. $interactive means we're running interactively, and must check
// permissions to carry out this act. If it is non-interactive, we are deleting something at the
// system's request and do not check permission. This is very important to know.

// Some deletion requests (those coming from remote sites) must be staged.
// $stage = 0 => unstaged
// $stage = 1 => set deleted flag on the item and perform intial notifications
// $stage = 2 => perform low level delete at a later stage

function drop_item($id,$interactive = true,$stage = DROPITEM_NORMAL,$force = false) {

	// locate item to be deleted

	$r = q("SELECT * FROM item WHERE id = %d LIMIT 1",
		intval($id)
	);

	if((! $r) || (intval($r[0]['item_deleted']) && ($stage === DROPITEM_NORMAL))) {
		if(! $interactive)
			return 0;
		notice( t('Item not found.') . EOL);
		goaway(z_root() . '/' . $_SESSION['return_url']);
	}

	$item = $r[0];

	$linked_item = (($item['resource_id']) ? true : false);

	$ok_to_delete = false;

	// system deletion
	if(! $interactive)
		$ok_to_delete = true;

	// owner deletion
	if(local_channel() && local_channel() == $item['uid'])
		$ok_to_delete = true;

	// sys owned item, requires site admin to delete
	$sys = get_sys_channel();
	if(is_site_admin() && $sys['channel_id'] == $item['uid'])
		$ok_to_delete = true;

	// author deletion
	$observer = App::get_observer();
	if($observer && $observer['xchan_hash'] && ($observer['xchan_hash'] === $item['author_xchan']))
		$ok_to_delete = true;

	if($ok_to_delete) {

		// set the deleted flag immediately on this item just in case the
		// hook calls a remote process which loops. We'll delete it properly in a second.

		if(($linked_item) && (! $force)) {
			$r = q("UPDATE item SET item_hidden = 1 WHERE id = %d",
				intval($item['id'])
			);
		}
		else {
			$r = q("UPDATE item SET item_deleted = 1 WHERE id = %d",
				intval($item['id'])
			);
		}

		$arr = array('item' => $item, 'interactive' => $interactive, 'stage' => $stage);
		call_hooks('drop_item', $arr );

		$notify_id = intval($item['id']);

		$items = q("select * from item where parent = %d and uid = %d",
			intval($item['id']),
			intval($item['uid'])
		);
		if($items) {
			foreach($items as $i)
				delete_item_lowlevel($i,$stage,$force);
		}
		else
			delete_item_lowlevel($item,$stage,$force);

		if(! $interactive)
			return 1;

		// send the notification upstream/downstream as the case may be
		// only send notifications to others if this is the owner's wall item.

		// This isn't optimal. We somehow need to pass to this function whether or not
		// to call the notifier, or we need to call the notifier from the calling function.
		// We'll rely on the undocumented behaviour that DROPITEM_PHASE1 is (hopefully) only
		// set if we know we're going to send delete notifications out to others.

		if((intval($item['item_wall']) && ($stage != DROPITEM_PHASE2)) || ($stage == DROPITEM_PHASE1))
			Zotlabs\Daemon\Master::Summon(array('Notifier','drop',$notify_id));

		goaway(z_root() . '/' . $_SESSION['return_url']);
	}
	else {
		if(! $interactive)
			return 0;
		notice( t('Permission denied.') . EOL);
		goaway(z_root() . '/' . $_SESSION['return_url']);
	}
}

/**
 * @warning This function does not check for permission and does not send
 * notifications and does not check recursion.
 * It merely destroys all resources associated with an item.
 * Please do not use without a suitable wrapper.
 *
 * @param array $item
 * @param int $stage
 * @param boolean $force
 * @return boolean
 */
function delete_item_lowlevel($item, $stage = DROPITEM_NORMAL, $force = false) {

	$linked_item = (($item['resource_id']) ? true : false);

	logger('item: ' . $item . ' stage: ' . $stage . ' force: ' . $force, LOGGER_DATA);

	switch($stage) {
		case DROPITEM_PHASE2:
			$r = q("UPDATE item SET item_pending_remove = 1, body = '', title = '',
				changed = '%s', edited = '%s'  WHERE id = %d",
				dbesc(datetime_convert()),
				dbesc(datetime_convert()),
				intval($item['id'])
			);
			break;

		case DROPITEM_PHASE1:
			if($linked_item && ! $force) {
				$r = q("UPDATE item SET item_hidden = 1,
					changed = '%s', edited = '%s'  WHERE id = %d",
					dbesc(datetime_convert()),
					dbesc(datetime_convert()),
					intval($item['id'])
				);
			}
			else {
				$r = q("UPDATE item set item_deleted = 1, changed = '%s', edited = '%s' where id = %d",
					dbesc(datetime_convert()),
					dbesc(datetime_convert()),
					intval($item['id'])
				);
			}

			break;

		case DROPITEM_NORMAL:
		default:
			if($linked_item && ! $force) {
				$r = q("UPDATE item SET item_hidden = 1,
					changed = '%s', edited = '%s'  WHERE id = %d",
					dbesc(datetime_convert()),
					dbesc(datetime_convert()),
					intval($item['id'])
				);
			}
			else {
				$r = q("UPDATE item SET item_deleted = 1, body = '', title = '',
					changed = '%s', edited = '%s'  WHERE id = %d",
					dbesc(datetime_convert()),
					dbesc(datetime_convert()),
					intval($item['id'])
				);
			}
			break;
	}

	// immediately remove any undesired profile likes.

	q("delete from likes where iid = %d and channel_id = %d",
		intval($item['id']),
		intval($item['uid'])
	);

	// remove delivery reports

	$c = q("select channel_hash from channel where channel_id = %d limit 1",
		intval($item['uid'])
	);
	if($c) {
		q("delete from dreport where dreport_xchan = '%s' and  dreport_mid = '%s'",
			dbesc($c[0]['channel_hash']),
			dbesc($item['mid'])
		);
	}

	// network deletion request. Keep the message structure so that we can deliver delete notifications.
	// Come back after several days (or perhaps a month) to do the lowlevel delete (DROPITEM_PHASE2).

	if($stage == DROPITEM_PHASE1)
		return true;

	$r = q("delete from term where otype = %d and oid = %d",
		intval(TERM_OBJ_POST),
		intval($item['id'])
	);

	q("delete from iconfig where iid = %d",
		intval($item['id'])
	);

	q("delete from term where oid = %d and otype = %d",
		intval($item['id']),
		intval(TERM_OBJ_POST)
	);

	/** @FIXME remove notifications for this item */

	return true;
}


function first_post_date($uid,$wall = false) {

	$wall_sql = (($wall) ? " and item_wall = 1 " : "" );
	$item_normal = item_normal();

	$r = q("select id, created from item
		where uid = %d and id = parent $item_normal $wall_sql
		order by created asc limit 1",
		intval($uid)

	);
	if($r) {
//		logger('first_post_date: ' . $r[0]['id'] . ' ' . $r[0]['created'], LOGGER_DATA);
		return substr(datetime_convert('',date_default_timezone_get(),$r[0]['created']),0,10);
	}

	return false;
}

/**
 * modified posted_dates() {below} to arrange the list in years, which we'll eventually
 * use to make a menu of years with collapsible sub-menus for the months instead of the
 * current flat list of all representative dates.
 *
 * @param int $uid
 * @param unknown $wall
 * @param unknown $mindate
 * @return array
 */
function list_post_dates($uid, $wall, $mindate) {
	$dnow = datetime_convert('',date_default_timezone_get(),'now','Y-m-d');

	if($mindate)
		$dthen = datetime_convert('',date_default_timezone_get(), $mindate);
	else
		$dthen = first_post_date($uid, $wall);
	if(! $dthen)
		return array();

	// If it's near the end of a long month, backup to the 28th so that in
	// consecutive loops we'll always get a whole month difference.

	if(intval(substr($dnow,8)) > 28)
		$dnow = substr($dnow,0,8) . '28';
	if(intval(substr($dthen,8)) > 28)
		$dthen = substr($dthen,0,8) . '28';

	$ret = array();
	// Starting with the current month, get the first and last days of every
	// month down to and including the month of the first post
	while(substr($dnow, 0, 7) >= substr($dthen, 0, 7)) {
		$dyear = intval(substr($dnow,0,4));
		$dstart = substr($dnow,0,8) . '01';
		$dend = substr($dnow,0,8) . get_dim(intval($dnow),intval(substr($dnow,5)));
		$start_month = datetime_convert('','',$dstart,'Y-m-d');
		$end_month = datetime_convert('','',$dend,'Y-m-d');
		$str = day_translate(datetime_convert('','',$dnow,'F'));
		if(! $ret[$dyear])
			$ret[$dyear] = array();
 		$ret[$dyear][] = array($str,$end_month,$start_month);
		$dnow = datetime_convert('','',$dnow . ' -1 month', 'Y-m-d');
	}

	return $ret;
}


function posted_dates($uid,$wall) {
	$dnow = datetime_convert('',date_default_timezone_get(),'now','Y-m-d');

	$dthen = first_post_date($uid,$wall);
	if(! $dthen)
		return array();

	// If it's near the end of a long month, backup to the 28th so that in
	// consecutive loops we'll always get a whole month difference.

	if(intval(substr($dnow,8)) > 28)
		$dnow = substr($dnow,0,8) . '28';
	if(intval(substr($dthen,8)) > 28)
		$dthen = substr($dthen,0,8) . '28';

	$ret = array();
	// Starting with the current month, get the first and last days of every
	// month down to and including the month of the first post
	while(substr($dnow, 0, 7) >= substr($dthen, 0, 7)) {
		$dstart = substr($dnow,0,8) . '01';
		$dend = substr($dnow,0,8) . get_dim(intval($dnow),intval(substr($dnow,5)));
		$start_month = datetime_convert('','',$dstart,'Y-m-d');
		$end_month = datetime_convert('','',$dend,'Y-m-d');
		$str = day_translate(datetime_convert('','',$dnow,'F Y'));
 		$ret[] = array($str,$end_month,$start_month);
		$dnow = datetime_convert('','',$dnow . ' -1 month', 'Y-m-d');
	}
	return $ret;
}


function fetch_post_tags($items,$link = false) {

	$tag_finder = array();
	if($items) {
		foreach($items as $item) {
			if(is_array($item)) {
				if(array_key_exists('item_id',$item)) {
					if(! in_array($item['item_id'],$tag_finder))
						$tag_finder[] = $item['item_id'];
				}
				else {
					if(! in_array($item['id'],$tag_finder))
						$tag_finder[] = $item['id'];
				}
			}
		}
	}
	$tag_finder_str = implode(', ', $tag_finder);


	if(strlen($tag_finder_str)) {
		$tags = q("select * from term where oid in ( %s ) and otype = %d",
			dbesc($tag_finder_str),
			intval(TERM_OBJ_POST)
		);
		$imeta = q("select * from iconfig where iid in ( %s )",
			dbesc($tag_finder_str)
		);

	}

	for($x = 0; $x < count($items); $x ++) {
		if($tags) {
			foreach($tags as $t) {
				if(($link) && ($t['ttype'] == TERM_MENTION))
					$t['url'] = chanlink_url($t['url']);
				if(array_key_exists('item_id',$items[$x])) {
					if($t['oid'] == $items[$x]['item_id']) {
						if(! is_array($items[$x]['term']))
							$items[$x]['term'] = array();
						$items[$x]['term'][] = $t;
					}
				}
				else {
					if($t['oid'] == $items[$x]['id']) {
						if(! is_array($items[$x]['term']))
							$items[$x]['term'] = array();
						$items[$x]['term'][] = $t;
					}
				}
			}
		}
		if($imeta) {
			foreach($imeta as $i) {
				if(array_key_exists('item_id',$items[$x])) {
					if($i['iid'] == $items[$x]['item_id']) {
						if(! is_array($items[$x]['iconfig']))
							$items[$x]['iconfig'] = array();
						$i['v'] = ((preg_match('|^a:[0-9]+:{.*}$|s',$i['v'])) ? unserialize($i['v']) : $i['v']);
						$items[$x]['iconfig'][] = $i;
					}
				}
				else {
					if($i['iid'] == $items[$x]['id']) {
						if(! is_array($items[$x]['iconfig']))
							$items[$x]['iconfig'] = array();
						$i['v'] = ((preg_match('|^a:[0-9]+:{.*}$|s',$i['v'])) ? unserialize($i['v']) : $i['v']);
						$items[$x]['iconfig'][] = $i;
					}
				}
			}
		}
	}

	return $items;
}



function zot_feed($uid,$observer_hash,$arr) {

	$result = array();
	$mindate = null;
	$message_id = null;

	require_once('include/security.php');

	if(array_key_exists('mindate',$arr)) {
		$mindate = datetime_convert('UTC','UTC',$arr['mindate']);
	}

	if(array_key_exists('message_id',$arr)) {
		$message_id = $arr['message_id'];
	}

	if(! $mindate)
		$mindate = NULL_DATE;

	$mindate = dbesc($mindate);

	logger('zot_feed: requested for uid ' . $uid . ' from observer ' . $observer_hash, LOGGER_DEBUG);
	if($message_id)
		logger('message_id: ' . $message_id,LOGGER_DEBUG);

	if(! perm_is_allowed($uid,$observer_hash,'view_stream')) {
		logger('zot_feed: permission denied.');
		return $result;
	}

	if(! is_sys_channel($uid))
		$sql_extra = item_permissions_sql($uid,$observer_hash);

	$limit = " LIMIT 100 ";

	if($mindate > NULL_DATE) {
		$sql_extra .= " and ( created > '$mindate' or changed > '$mindate' ) ";
	}

	if($message_id) {
		$sql_extra .= " and mid = '" . dbesc($message_id) . "' ";
		$limit = '';
	}


	$items = array();

	/** @FIXME re-unite these SQL statements. There is no need for them to be separate. The mySQL is convoluted with misuse of group by. As it stands, there is a slight difference where the postgres version doesn't remove the duplicate parents up to 100. In practice this doesn't matter. It could be made to match behavior by adding "distinct on (parent) " to the front of the selection list, at a not-worth-it performance penalty (page temp results to disk). duplicates are still ignored in the in() clause, you just get less than 100 parents if there are many children. */

	if(ACTIVE_DBTYPE == DBTYPE_POSTGRES) {
		$groupby = '';
	} else {
		$groupby = 'GROUP BY parent';
	}

	$item_normal = item_normal();

	if(is_sys_channel($uid)) {
		$r = q("SELECT parent, created, postopts from item
			WHERE uid != %d
			$item_normal
			AND item_wall = 1
			and item_private = 0 $sql_extra $groupby ORDER BY created ASC $limit",
			intval($uid)
		);
	}
	else {
		$r = q("SELECT parent, created, postopts from item
			WHERE uid = %d $item_normal
			AND item_wall = 1
			$sql_extra $groupby ORDER BY created ASC $limit",
			intval($uid)
		);
	}

	if($r) {
		for($x = 0; $x < count($r); $x ++) {
			if(strpos($r[$x]['postopts'],'nodeliver') !== false) {
				unset($r[$x]);
			}
		}

		$parents_str = ids_to_querystr($r,'parent');
		$sys_query = ((is_sys_channel($uid)) ? $sql_extra : '');
		$item_normal = item_normal();

		$items = q("SELECT item.*, item.id AS item_id FROM item
			WHERE item.parent IN ( %s ) $item_normal $sys_query ",
			dbesc($parents_str)
		);
	}

	if($items) {
		xchan_query($items);
		$items = fetch_post_tags($items);
		require_once('include/conversation.php');
		$items = conv_sort($items,'ascending');
	}
	else
		$items = array();

	logger('zot_feed: number items: ' . count($items),LOGGER_DEBUG);

	foreach($items as $item)
		$result[] = encode_item($item);

	return $result;
}



function items_fetch($arr,$channel = null,$observer_hash = null,$client_mode = CLIENT_MODE_NORMAL,$module = 'network') {

	$result = array('success' => false);

	$sql_extra = '';
	$sql_nets = '';
	$sql_options = '';
	$sql_extra2 = '';
	$sql_extra3 = '';
	$def_acl = '';

	$item_uids = ' true ';
	$item_normal = item_normal();


	if ($arr['uid']) $uid= $arr['uid'];

	if($channel) {
		$uid = $channel['channel_id'];
		$uidhash = $channel['channel_hash'];
		$item_uids = " item.uid = " . intval($uid) . " ";
	}

	if($arr['star'])
		$sql_options .= " and item_starred = 1 ";

	if($arr['wall'])
		$sql_options .= " and item_wall = 1 ";

	if($arr['item_id'])
		$sql_options .= " and parent = " . intval($arr['item_id']) . " ";

	if($arr['mid'])
		$sql_options .= " and parent_mid = '" . dbesc($arr['mid']) . "' ";

	$sql_extra = " AND item.parent IN ( SELECT parent FROM item WHERE item_thread_top = 1 $sql_options $item_normal ) ";

	if($arr['since_id'])
		$sql_extra .= " and item.id > " . $since_id . " ";

	if($arr['cat'])
		$sql_extra .= protect_sprintf(term_query('item', $arr['cat'], TERM_CATEGORY));

	if($arr['gid'] && $uid) {
		$r = q("SELECT * FROM groups WHERE id = %d AND uid = %d LIMIT 1",
			intval($arr['group']),
			intval($uid)
		);
		if(! $r) {
			$result['message']  = t('Privacy group not found.');
			return $result;
		}

		$contact_str = '';

		$contacts = group_get_members($r[0]['id']);
		if ($contacts) {
			foreach($contacts as $c) {
				if($contact_str)
					$contact_str .= ',';

				$contact_str .= "'" . $c['xchan'] . "'";
			}
		} else {
			$contact_str = ' 0 ';
			$result['message'] = t('Privacy group is empty.');
			return $result;
		}

		$sql_extra = " AND item.parent IN ( SELECT DISTINCT parent FROM item WHERE true $sql_options AND (( author_xchan IN ( $contact_str ) OR owner_xchan in ( $contact_str)) or allow_gid like '" . protect_sprintf('%<' . dbesc($r[0]['hash']) . '>%') . "' ) and id = parent $item_normal ) ";

		$x = group_rec_byhash($uid,$r[0]['hash']);
		$result['headline'] = sprintf( t('Privacy group: %s'),$x['gname']);
	}
	elseif($arr['cid'] && $uid) {

		$r = q("SELECT abook.*, xchan.* from abook left join xchan on abook_xchan = xchan_hash where abook_id = %d and abook_channel = %d and abook_blocked = 0 limit 1",
			intval($arr['cid']),
			intval(local_channel())
		);
		if ($r) {
			$sql_extra = " AND item.parent IN ( SELECT DISTINCT parent FROM item WHERE true $sql_options AND uid = " . intval($arr['uid']) . " AND ( author_xchan = '" . dbesc($r[0]['abook_xchan']) . "' or owner_xchan = '" . dbesc($r[0]['abook_xchan']) . "' ) $item_normal ) ";
			$result['headline'] = sprintf( t('Connection: %s'),$r[0]['xchan_name']);
		} else {
			$result['message'] = t('Connection not found.');
			return $result;
		}
	}

	if ($arr['datequery']) {
		$sql_extra3 .= protect_sprintf(sprintf(" AND item.created <= '%s' ", dbesc(datetime_convert('UTC','UTC',$arr['datequery']))));
	}
	if ($arr['datequery2']) {
		$sql_extra3 .= protect_sprintf(sprintf(" AND item.created >= '%s' ", dbesc(datetime_convert('UTC','UTC',$arr['datequery2']))));
	}

	if(! array_key_exists('nouveau',$arr)) {
		$sql_extra2 = " AND item.parent = item.id ";
//		$sql_extra3 = '';
	}

	if($arr['search']) {

        if(strpos($arr['search'],'#') === 0)
            $sql_extra .= term_query('item',substr($arr['search'],1),TERM_HASHTAG,TERM_COMMUNITYTAG);
        else
            $sql_extra .= sprintf(" AND item.body like '%s' ",
                dbesc(protect_sprintf('%' . $arr['search'] . '%'))
            );
    }

    if(strlen($arr['file'])) {
        $sql_extra .= term_query('item',$arr['files'],TERM_FILE);
    }

    if($arr['conv'] && $channel) {
        $sql_extra .= sprintf(" AND parent IN (SELECT distinct parent from item where ( author_xchan like '%s' or item_mentionsme = 1 )) ",
            dbesc(protect_sprintf($uidhash))
        );
    }

	if (($client_mode & CLIENT_MODE_UPDATE) && (! ($client_mode & CLIENT_MODE_LOAD))) {
		// only setup pagination on initial page view
		$pager_sql = '';
	} else {
		$itemspage = (($channel) ? get_pconfig($uid,'system','itemspage') : 20);
		App::set_pager_itemspage(((intval($itemspage)) ? $itemspage : 20));
		$pager_sql = sprintf(" LIMIT %d OFFSET %d ", intval(App::$pager['itemspage']), intval(App::$pager['start']));
	}

	if (isset($arr['start']) && isset($arr['records']))
		$pager_sql = sprintf(" LIMIT %d OFFSET %d ", intval($arr['records']), intval($arr['start']));

	if (array_key_exists('cmin',$arr) || array_key_exists('cmax',$arr)) {
		if (($arr['cmin'] != 0) || ($arr['cmax'] != 99)) {

			// Not everybody who shows up in the network stream will be in your address book.
			// By default those that aren't are assumed to have closeness = 99; but this isn't
			// recorded anywhere. So if cmax is 99, we'll open the search up to anybody in
			// the stream with a NULL address book entry.

			$sql_nets .= " AND ";

			if ($arr['cmax'] == 99)
				$sql_nets .= " ( ";

			$sql_nets .= "( abook.abook_closeness >= " . intval($arr['cmin']) . " ";
			$sql_nets .= " AND abook.abook_closeness <= " . intval($arr['cmax']) . " ) ";

			if ($arr['cmax'] == 99)
				$sql_nets .= " OR abook.abook_closeness IS NULL ) ";
		}
	}

    $simple_update = (($client_mode & CLIENT_MODE_UPDATE) ? " and item.item_unseen = 1 " : '');
    if($client_mode & CLIENT_MODE_LOAD)
        $simple_update = '';

	//$start = dba_timer();

	require_once('include/security.php');
	$sql_extra .= item_permissions_sql($channel['channel_id'],$observer_hash);


	if($arr['pages'])
		$item_restrict = " AND item_type = " . ITEM_TYPE_WEBPAGE . " ";
	else
		$item_restrict = " AND item_type = 0 ";

	if($arr['item_type'] === '*')
		$item_restrict = '';

	if ($arr['nouveau'] && ($client_mode & CLIENT_MODE_LOAD) && $channel) {
		// "New Item View" - show all items unthreaded in reverse created date order

		$items = q("SELECT item.*, item.id AS item_id FROM item
				WHERE $item_uids $item_restrict
				$simple_update
				$sql_extra $sql_nets
				ORDER BY item.received DESC $pager_sql"
		);

		require_once('include/items.php');

		xchan_query($items);

		$items = fetch_post_tags($items,true);
	} else {

		// Normal conversation view

		if($arr['order'] === 'post')
			$ordering = "created";
		else
			$ordering = "commented";

		if(($client_mode & CLIENT_MODE_LOAD) || ($client_mode == CLIENT_MODE_NORMAL)) {

            // Fetch a page full of parent items for this page

            $r = q("SELECT distinct item.id AS item_id, item.$ordering FROM item
                left join abook on item.author_xchan = abook.abook_xchan
                WHERE $item_uids $item_restrict
                AND item.parent = item.id
                and (abook.abook_blocked = 0 or abook.abook_flags is null)
                $sql_extra3 $sql_extra $sql_nets
                ORDER BY item.$ordering DESC $pager_sql "
            );

        }
        else {
            // update
            $r = q("SELECT item.parent AS item_id FROM item
                left join abook on item.author_xchan = abook.abook_xchan
                WHERE $item_uids $item_restrict $simple_update
                and (abook.abook_blocked = 0 or abook.abook_flags is null)
                $sql_extra3 $sql_extra $sql_nets "
            );
        }

		//$first = dba_timer();

		// Then fetch all the children of the parents that are on this page

		if($r) {

			$parents_str = ids_to_querystr($r,'item_id');

			if($arr['top'])
				$sql_extra = ' and id = parent ' . $sql_extra;

			$items = q("SELECT item.*, item.id AS item_id FROM item
				WHERE $item_uids $item_restrict
				AND item.parent IN ( %s )
				$sql_extra ",
				dbesc($parents_str)
			);

			//$second = dba_timer();

			xchan_query($items);

			//$third = dba_timer();

			$items = fetch_post_tags($items,true);

			//$fourth = dba_timer();

			require_once('include/conversation.php');
			$items = conv_sort($items,$ordering);

			//logger('items: ' . print_r($items,true));
		} else {
			$items = array();
		}

		if($parents_str && $arr['mark_seen'])
			$update_unseen = ' AND parent IN ( ' . dbesc($parents_str) . ' )';
			/** @FIXME finish mark unseen sql */
	}

	return $items;
}

function webpage_to_namespace($webpage) {

	if($webpage == ITEM_TYPE_WEBPAGE)
		$page_type = 'WEBPAGE';
	elseif($webpage == ITEM_TYPE_BLOCK)
		$page_type = 'BUILDBLOCK';
	elseif($webpage == ITEM_TYPE_PDL)
		$page_type = 'PDL';
	elseif($webpage == ITEM_TYPE_DOC)
		$page_type = 'docfile';
	else
		$page_type = 'unknown';
	return $page_type;

}



function update_remote_id($channel,$post_id,$webpage,$pagetitle,$namespace,$remote_id,$mid) {

	if(! $post_id)
		return;

	$page_type = webpage_to_namespace($webpage);

	if($page_type == 'unknown' && $namespace && $remote_id) {
		$page_type = $namespace;
		$pagetitle = $remote_id;
	}
	else {
		$page_type = '';
	}

	if($page_type) {

		// store page info as an alternate message_id so we can access it via
		//    https://sitename/page/$channelname/$pagetitle
		// if no pagetitle was given or it couldn't be transliterated into a url, use the first
		// sixteen bytes of the mid - which makes the link portable and not quite as daunting
		// as the entire mid. If it were the post_id the link would be less portable.

		\Zotlabs\Lib\IConfig::Set(
			intval($post_id),
			'system',
			$page_type,
			($pagetitle) ? $pagetitle : substr($mid,0,16),
			false
		);
	}
}


/**
 * @brief Change access control for item with message_id $mid and channel_id $uid.
 *
 * @param string $xchan_hash
 * @param string $mid
 * @param int $uid
 */
function item_add_cid($xchan_hash, $mid, $uid) {
	$r = q("select id from item where mid = '%s' and uid = %d and allow_cid like '%s'",
		dbesc($mid),
		intval($uid),
		dbesc('<' . $xchan_hash . '>')
	);
	if(! $r) {
		$r = q("update item set allow_cid = concat(allow_cid,'%s') where mid = '%s' and uid = %d",
			dbesc('<' . $xchan_hash . '>'),
			dbesc($mid),
			intval($uid)
		);
	}
}

function item_remove_cid($xchan_hash,$mid,$uid) {
	$r = q("select allow_cid from item where mid = '%s' and uid = %d and allow_cid like '%s'",
		dbesc($mid),
		intval($uid),
		dbesc('<' . $xchan_hash . '>')
	);
	if($r) {
		$x = q("update item set allow_cid = '%s' where mid = '%s' and uid = %d",
			dbesc(str_replace('<' . $xchan_hash . '>','',$r[0]['allow_cid'])),
			dbesc($mid),
			intval($uid)
		);
	}
}

// Set item permissions based on results obtained from linkify_tags()
function set_linkified_perms($linkified, &$str_contact_allow, &$str_group_allow, $profile_uid, $parent_item = false, &$private) {
	$first_access_tag = true;

	foreach($linkified as $x) {
		$access_tag = $x['access_tag'];
		if(($access_tag) && (! $parent_item)) {
			logger('access_tag: ' . $tag . ' ' . print_r($access_tag,true), LOGGER_DATA);
			if ($first_access_tag && (! get_pconfig($profile_uid,'system','no_private_mention_acl_override'))) {

				// This is a tough call, hence configurable. The issue is that one can type in a @!privacy mention
				// and also have a default ACL (perhaps from viewing a collection) and could be suprised that the
				// privacy mention wasn't the only recipient. So the default is to wipe out the existing ACL if a
				// private mention is found. This can be over-ridden if you wish private mentions to be in
				// addition to the current ACL settings.

				$str_contact_allow = '';
				$str_group_allow = '';
				$first_access_tag = false;
			}
			if(strpos($access_tag,'cid:') === 0) {
				$str_contact_allow .= '<' . substr($access_tag,4) . '>';
				$access_tag = '';
				$private = 1;
			}
			elseif(strpos($access_tag,'gid:') === 0) {
				$str_group_allow .= '<' . substr($access_tag,4) . '>';
				$access_tag = '';
				$private = 1;
			}
		}
	}
}

/**
 * We can't trust ITEM_ORIGIN to tell us if this is a local comment
 * which needs to be relayed, because it was misconfigured at one point for several
 * months and set for some remote items (in alternate delivery chains). This could
 * cause looping, so use this hackish but accurate method.
 *
 * @param array $item
 * @return boolean
 */
function comment_local_origin($item) {
	if(stripos($item['mid'], App::get_hostname()) && ($item['parent'] != $item['id']))
		return true;

	return false;
}




function send_profile_photo_activity($channel,$photo,$profile) {

	// for now only create activities for the default profile

	if(! intval($profile['is_default']))
		return;

	$arr = array();
	$arr['item_thread_top'] = 1;
	$arr['item_origin'] = 1;
	$arr['item_wall'] = 1;
	$arr['obj_type'] = ACTIVITY_OBJ_PHOTO;
	$arr['verb'] = ACTIVITY_UPDATE;

	$arr['obj'] = json_encode(array(
		'type' => $arr['obj_type'],
		'id' => z_root() . '/photo/profile/l/' . $channel['channel_id'],
		'link' => array('rel' => 'photo', 'type' => $photo['type'], 'href' => z_root() . '/photo/profile/l/' . $channel['channel_id'])
	));

	if(stripos($profile['gender'],t('female')) !== false)
		$t = t('%1$s updated her %2$s');
	elseif(stripos($profile['gender'],t('male')) !== false)
		$t = t('%1$s updated his %2$s');
	else
		$t = t('%1$s updated their %2$s');

	$ptext = '[zrl=' . z_root() . '/photos/' . $channel['channel_address'] . '/image/' . $photo['resource_id'] . ']' . t('profile photo') . '[/zrl]';

	$ltext = '[zrl=' . z_root() . '/profile/' . $channel['channel_address'] . ']' . '[zmg=150x150]' . z_root() . '/photo/' . $photo['resource_id'] . '-4[/zmg][/zrl]';

	$arr['body'] = sprintf($t,$channel['channel_name'],$ptext) . "\n\n" . $ltext;

	$acl = new Zotlabs\Access\AccessList($channel);
	$x = $acl->get();
	$arr['allow_cid'] = $x['allow_cid'];

	$arr['allow_gid'] = $x['allow_gid'];
	$arr['deny_cid'] = $x['deny_cid'];
	$arr['deny_gid'] = $x['deny_gid'];

	$arr['uid'] = $channel['channel_id'];
	$arr['aid'] = $channel['channel_account_id'];

	$arr['owner_xchan'] = $channel['channel_hash'];
	$arr['author_xchan'] = $channel['channel_hash'];

	post_activity_item($arr);


}


function sync_an_item($channel_id,$item_id) {

	$r = q("select * from item where id = %d",
		intval($item_id)
	);
	if($r) {
		xchan_query($r);
		$sync_item = fetch_post_tags($r);
		$rid = q("select * from item_id where iid = %d",
			intval($item_id)
		);
		build_sync_packet($channel_d,array('item' => array(encode_item($sync_item[0],true)),'item_id' => $rid));
	}
}


function fix_attached_photo_permissions($uid,$xchan_hash,$body,
	$str_contact_allow,$str_group_allow,$str_contact_deny,$str_group_deny) {

	if(get_pconfig($uid,'system','force_public_uploads')) {
		$str_contact_allow = $str_group_allow = $str_contact_deny = $str_group_deny = '';
	}

	$match = null;
	// match img and zmg image links
	if(preg_match_all("/\[[zi]mg(.*?)\](.*?)\[\/[zi]mg\]/",$body,$match)) {
		$images = $match[2];
		if($images) {
			foreach($images as $image) {
				if(! stristr($image,z_root() . '/photo/'))
					continue;
				$image_uri = substr($image,strrpos($image,'/') + 1);
				if(strpos($image_uri,'-') !== false)
					$image_uri = substr($image_uri,0, strpos($image_uri,'-'));
				if(strpos($image_uri,'.') !== false)
					$image_uri = substr($image_uri,0, strpos($image_uri,'.'));
				if(! strlen($image_uri))
					continue;
				$srch = '<' . $xchan_hash . '>';

				$r = q("select folder from attach where hash = '%s' and uid = %d limit 1",
					dbesc($image_uri),
					intval($uid)
				);
				if($r && $r[0]['folder']) {
					$f = q("select * from attach where hash = '%s' and is_dir = 1 and uid = %d limit 1",
						dbesc($r[0]['folder']),
						intval($uid)
					);
					if(($f) && (($f[0]['allow_cid']) || ($f[0]['allow_gid']) || ($f[0]['deny_cid']) || ($f[0]['deny_gid']))) {
						$str_contact_allow = $f[0]['allow_cid'];
						$str_group_allow = $f[0]['allow_gid'];
						$str_contact_deny = $f[0]['deny_cid'];
						$str_group_deny = $f[0]['deny_gid'];
					}
				}

				$r = q("SELECT id FROM photo
					WHERE allow_cid = '%s' AND allow_gid = '' AND deny_cid = '' AND deny_gid = ''
					AND resource_id = '%s' AND uid = %d LIMIT 1",
					dbesc($srch),
					dbesc($image_uri),
					intval($uid)
				);

				if($r) {
					$r = q("UPDATE photo SET allow_cid = '%s', allow_gid = '%s', deny_cid = '%s', deny_gid = '%s'
						WHERE resource_id = '%s' AND uid = %d ",
						dbesc($str_contact_allow),
						dbesc($str_group_allow),
						dbesc($str_contact_deny),
						dbesc($str_group_deny),
						dbesc($image_uri),
						intval($uid)
					);

					// also update the linked item (which is probably invisible)

					$r = q("select id from item
						WHERE allow_cid = '%s' AND allow_gid = '' AND deny_cid = '' AND deny_gid = ''
						AND resource_id = '%s' and resource_type = 'photo' AND uid = %d LIMIT 1",
						dbesc($srch),
						dbesc($image_uri),
						intval($uid)
					);
					if($r) {
						$private = (($str_contact_allow || $str_group_allow || $str_contact_deny || $str_group_deny) ? true : false);

						$r = q("UPDATE item SET allow_cid = '%s', allow_gid = '%s', deny_cid = '%s', deny_gid = '%s', item_private = %d
							WHERE id = %d AND uid = %d",
							dbesc($str_contact_allow),
							dbesc($str_group_allow),
							dbesc($str_contact_deny),
							dbesc($str_group_deny),
							intval($private),
							intval($r[0]['id']),
							intval($uid)
						);
					}
					$r = q("select id from attach where hash = '%s' and uid = %d limit 1",
						dbesc($image_uri),
						intval($uid)
					);
					if($r) {
						q("update attach SET allow_cid = '%s', allow_gid = '%s', deny_cid = '%s', deny_gid = '%s'
							WHERE id = %d AND uid = %d",
							dbesc($str_contact_allow),
							dbesc($str_group_allow),
							dbesc($str_contact_deny),
							dbesc($str_group_deny),
							intval($r[0]['id']),
							intval($uid)
						);
					}
				}
			}
		}
	}
}


function fix_attached_file_permissions($channel,$observer_hash,$body,
	$str_contact_allow,$str_group_allow,$str_contact_deny,$str_group_deny) {

	if(get_pconfig($channel['channel_id'],'system','force_public_uploads')) {
		$str_contact_allow = $str_group_allow = $str_contact_deny = $str_group_deny = '';
	}

	$match = false;

	if(preg_match_all("/\[attachment\](.*?)\[\/attachment\]/",$body,$match)) {
		$attaches = $match[1];
		if($attaches) {
			foreach($attaches as $attach) {
				$hash = substr($attach,0,strpos($attach,','));
				$rev = intval(substr($attach,strpos($attach,',')));
				attach_store($channel,$observer_hash,$options = 'update', array(
					'hash'      => $hash,
					'revision'  => $rev,
					'allow_cid' => $str_contact_allow,
					'allow_gid'  => $str_group_allow,
					'deny_cid'  => $str_contact_deny,
					'deny_gid'  => $str_group_deny
				));
			}
		}
	}
}


function item_create_edit_activity($post) {

	if((! $post) || (! $post['item']) || ($post['item']['item_type'] != ITEM_TYPE_POST))
		return;

	$update_item = $post['item'];

	$new_item = $update_item;

	$author = q("select * from xchan where xchan_hash = '%s' limit 1",
		dbesc($new_item['author_xchan'])
	);
	if($author)
		$item_author = $author[0];


	$new_item['id'] = 0;
	$new_item['parent'] = 0;
	$new_item['mid'] = item_message_id();
	
	$new_item['body'] = sprintf( t('[Edited %s]'), (($update_item['item_thread_top']) ? t('Post','edit_activity') : t('Comment','edit_activity')));

	$new_item['body'] .= "\n\n";
	$new_item['body'] .= $update_item['body'];
	
	$new_item['sig'] = '';

	$new_item['verb'] = ACTIVITY_UPDATE;
	$new_item['item_thread_top'] = 0;
	$new_item['created'] = $new_item['edited'] = datetime_convert();

	$new_item['obj'] = json_encode(array(
		'type'    => (($update_item['item_thread_top']) ? ACTIVITY_OBJ_NOTE : ACTIVITY_OBJ_COMMENT),
		'id'      => $update_item['mid'],
		'parent'  => $update_item['parent_mid'],
		'link'    => array(array('rel' => 'alternate','type' => 'text/html', 'href' => $update_item['plink'])),
		'title'   => $update_item['title'],
		'content' => $update_item['body'],
		'created' => $update_item['created'],
		'edited'  => $update_item['edited'],
		'author'  => array(
			'name'     => $item_author['xchan_name'],
			'address'  => $item_author['xchan_addr'],
			'guid'     => $item_author['xchan_guid'],
			'guid_sig' => $item_author['xchan_guid_sig'],
			'link'     => array(
				array('rel' => 'alternate', 'type' => 'text/html', 'href' => $item_author['xchan_url']),
				array('rel' => 'photo', 'type' => $item_author['xchan_photo_mimetype'], 'href' => $item_author['xchan_photo_m'])),
			),
	));
	


	$x = post_activity_item($new_item);	

	$post_id = $x['id'];
	if($post_id) {
		$r = q("select * from item where id = %d",
					intval($post_id)
		);
		if($r) {
			xchan_query($r);
			$sync_item = fetch_post_tags($r);
			build_sync_packet($new_item['uid'],array('item' => array(encode_item($sync_item[0],true))));
		}
	}

	\Zotlabs\Daemon\Master::Summon(array('Notifier', 'edit_activity', $post_id));
	
}
