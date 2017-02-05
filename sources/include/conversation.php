<?php /** @file */

require_once('include/items.php');

// Note: the code in 'item_extract_images' and 'item_redir_and_replace_images'
// is identical to the code in mod/message.php for 'item_extract_images' and
// 'item_redir_and_replace_images'


function item_extract_images($body) {

	$saved_image = array();
	$orig_body = $body;
	$new_body = '';

	$cnt = 0;
	$img_start = strpos($orig_body, '[img');
	$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
	$img_end = ($img_start !== false ? strpos(substr($orig_body, $img_start), '[/img]') : false);
	while(($img_st_close !== false) && ($img_end !== false)) {

		$img_st_close++; // make it point to AFTER the closing bracket
		$img_end += $img_start;

		if(! strcmp(substr($orig_body, $img_start + $img_st_close, 5), 'data:')) {
			// This is an embedded image

			$saved_image[$cnt] = substr($orig_body, $img_start + $img_st_close, $img_end - ($img_start + $img_st_close));
			$new_body = $new_body . substr($orig_body, 0, $img_start) . '[!#saved_image' . $cnt . '#!]';

			$cnt++;
		}
		else
			$new_body = $new_body . substr($orig_body, 0, $img_end + strlen('[/img]'));

		$orig_body = substr($orig_body, $img_end + strlen('[/img]'));

		if($orig_body === false) // in case the body ends on a closing image tag
			$orig_body = '';

		$img_start = strpos($orig_body, '[img');
		$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
		$img_end = ($img_start !== false ? strpos(substr($orig_body, $img_start), '[/img]') : false);
	}

	$new_body = $new_body . $orig_body;

	return array('body' => $new_body, 'images' => $saved_image);
}


function item_redir_and_replace_images($body, $images, $cid) {

	$origbody = $body;
	$newbody = '';

	$observer = App::get_observer();
	$obhash = (($observer) ? $observer['xchan_hash'] : '');
	$obaddr = (($observer) ? $observer['xchan_addr'] : '');

	for($i = 0; $i < count($images); $i++) {
		$search = '/\[url\=(.*?)\]\[!#saved_image' . $i . '#!\]\[\/url\]' . '/is';
		$replace = '[url=' . magiclink_url($obhash,$obaddr,'$1') . '][!#saved_image' . $i . '#!][/url]' ;

		$img_end = strpos($origbody, '[!#saved_image' . $i . '#!][/url]') + strlen('[!#saved_image' . $i . '#!][/url]');
		$process_part = substr($origbody, 0, $img_end);
		$origbody = substr($origbody, $img_end);

		$process_part = preg_replace($search, $replace, $process_part);
		$newbody = $newbody . $process_part;
	}
	$newbody = $newbody . $origbody;

	$cnt = 0;
	foreach($images as $image) {
		// We're depending on the property of 'foreach' (specified on the PHP website) that
		// it loops over the array starting from the first element and going sequentially
		// to the last element
		$newbody = str_replace('[!#saved_image' . $cnt . '#!]', '[img]' . $image . '[/img]', $newbody);
		$cnt++;
	}

	return $newbody;
}



/**
 * Render actions localized
 */

function localize_item(&$item){

	if (activity_match($item['verb'],ACTIVITY_LIKE) || activity_match($item['verb'],ACTIVITY_DISLIKE)){
	
		if(! $item['obj'])
			return;

		if(intval($item['item_thread_top']))
			return;	

		$obj = json_decode($item['obj'],true);
		if((! $obj) && ($item['obj'])) {
			logger('localize_item: failed to decode object: ' . print_r($item['obj'],true));
		}
		
		if($obj['author'] && $obj['author']['link'])
			$author_link = get_rel_link($obj['author']['link'],'alternate');
		else
			$author_link = '';

		$author_name = (($obj['author'] && $obj['author']['name']) ? $obj['author']['name'] : '');

		$item_url = get_rel_link($obj['link'],'alternate');

		$Bphoto = '';

		switch($obj['type']) {
			case ACTIVITY_OBJ_PHOTO:
				$post_type = t('photo');
				break;
			case ACTIVITY_OBJ_EVENT:
				$post_type = t('event');
				break;
			case ACTIVITY_OBJ_PERSON:
				$post_type = t('channel');
				$author_name = $obj['title'];
				if($obj['link']) {
					$author_link  = get_rel_link($obj['link'],'alternate');
					$Bphoto = get_rel_link($obj['link'],'photo');
				}
				break;
			case ACTIVITY_OBJ_THING:
				$post_type = $obj['title'];
				if($obj['owner']) {
					if(array_key_exists('name',$obj['owner']))
						$obj['owner']['name'];
					if(array_key_exists('link',$obj['owner']))
						$author_link = get_rel_link($obj['owner']['link'],'alternate');
				}
				if($obj['link']) {
					$Bphoto = get_rel_link($obj['link'],'photo');
				}
				break;

			case ACTIVITY_OBJ_NOTE:
			default:
				$post_type = t('status');
				if($obj['id'] != $obj['parent'])
					$post_type = t('comment');
				break;
		}

		// If we couldn't parse something useful, don't bother translating.
		// We need something better than zid here, probably magic_link(), but it needs writing

		if($author_link && $author_name && $item_url) {
			$author	 = '[zrl=' . chanlink_url($item['author']['xchan_url']) . ']' . $item['author']['xchan_name'] . '[/zrl]';
			$objauthor =  '[zrl=' . chanlink_url($author_link) . ']' . $author_name . '[/zrl]';
		
			$plink = '[zrl=' . zid($item_url) . ']' . $post_type . '[/zrl]';

			if(activity_match($item['verb'],ACTIVITY_LIKE)) {
				$bodyverb = t('%1$s likes %2$s\'s %3$s');
			}
			elseif(activity_match($item['verb'],ACTIVITY_DISLIKE)) {
				$bodyverb = t('%1$s doesn\'t like %2$s\'s %3$s');
			}
			$item['body'] = $item['localize'] = sprintf($bodyverb, $author, $objauthor, $plink);
			if($Bphoto != "") 
				$item['body'] .= "\n\n\n" . '[zrl=' . chanlink_url($author_link) . '][zmg=80x80]' . $Bphoto . '[/zmg][/zrl]';

		}
		else {
			logger('localize_item like failed: link ' . $author_link . ' name ' . $author_name . ' url ' . $item_url);
		}

	}

	if (activity_match($item['verb'],ACTIVITY_FRIEND)) {

		if ($item['obj_type'] == "" || $item['obj_type'] !== ACTIVITY_OBJ_PERSON) 
			return;

		$Aname = $item['author']['xchan_name'];
		$Alink = $item['author']['xchan_url'];


		$obj= json_decode($item['obj'],true);
		
		$Blink = $Bphoto = '';

		if($obj['link']) {
			$Blink  = get_rel_link($obj['link'],'alternate');
			$Bphoto = get_rel_link($obj['link'],'photo');
		}
		$Bname = $obj['title'];


		$A = '[zrl=' . chanlink_url($Alink) . ']' . $Aname . '[/zrl]';
		$B = '[zrl=' . chanlink_url($Blink) . ']' . $Bname . '[/zrl]';
		if ($Bphoto!="") $Bphoto = '[zrl=' . chanlink_url($Blink) . '][zmg=80x80]' . $Bphoto . '[/zmg][/zrl]';

		$item['body'] = $item['localize'] = sprintf( t('%1$s is now connected with %2$s'), $A, $B);
		$item['body'] .= "\n\n\n" . $Bphoto;
	}

	if (stristr($item['verb'], ACTIVITY_POKE)) {

		/** @FIXME for obscured private posts, until then leave untranslated */
		return;

		$verb = urldecode(substr($item['verb'],strpos($item['verb'],'#')+1));
		if(! $verb)
			return;

		if ($item['obj_type']=="" || $item['obj_type']!== ACTIVITY_OBJ_PERSON) return;

		$Aname = $item['author']['xchan_name'];
		$Alink = $item['author']['xchan_url'];

		$obj= json_decode($item['obj'],true);

		$Blink = $Bphoto = '';

		if($obj['link']) {
			$Blink  = get_rel_link($obj['link'],'alternate');
			$Bphoto = get_rel_link($obj['link'],'photo');
		}
		$Bname = $obj['title'];

		$A = '[zrl=' . chanlink_url($Alink) . ']' . $Aname . '[/zrl]';
		$B = '[zrl=' . chanlink_url($Blink) . ']' . $Bname . '[/zrl]';
		if ($Bphoto!="") $Bphoto = '[zrl=' . chanlink_url($Blink) . '][zmg=80x80]' . $Bphoto . '[/zmg][/zrl]';

		// we can't have a translation string with three positions but no distinguishable text
		// So here is the translate string.

		$txt = t('%1$s poked %2$s');

		// now translate the verb

		$txt = str_replace( t('poked'), t($verb), $txt);

		// then do the sprintf on the translation string

		$item['body'] = $item['localize'] = sprintf($txt, $A, $B);
		$item['body'] .= "\n\n\n" . $Bphoto;
	}
	if (stristr($item['verb'],ACTIVITY_MOOD)) {
		$verb = urldecode(substr($item['verb'],strpos($item['verb'],'#')+1));
		if(! $verb)
			return;

		$Aname = $item['author']['xchan_name'];
		$Alink = $item['author']['xchan_url'];

		$A = '[zrl=' . chanlink_url($Alink) . ']' . $Aname . '[/zrl]';
		
		$txt = t('%1$s is %2$s','mood');

		$item['body'] = sprintf($txt, $A, t($verb));
	}



/*
// FIXME store parent item as object or target
// (and update to json storage)

 	if (activity_match($item['verb'],ACTIVITY_TAG)) {
		$r = q("SELECT * from item,contact WHERE 
		item.contact-id=contact.id AND item.mid='%s';",
		 dbesc($item['parent_mid']));
		if(count($r)==0) return;
		$obj=$r[0];
		
		$author	 = '[zrl=' . zid($item['author-link']) . ']' . $item['author-name'] . '[/zrl]';
		$objauthor =  '[zrl=' . zid($obj['author-link']) . ']' . $obj['author-name'] . '[/zrl]';
		
		switch($obj['verb']){
			case ACTIVITY_POST:
				switch ($obj['obj_type']){
					case ACTIVITY_OBJ_EVENT:
						$post_type = t('event');
						break;
					default:
						$post_type = t('status');
				}
				break;
			default:
				if($obj['resource_id']){
					$post_type = t('photo');
					$m=array(); preg_match("/\[[zu]rl=([^]]*)\]/", $obj['body'], $m);
					$rr['plink'] = $m[1];
				} else {
					$post_type = t('status');
				}
		}
		$plink = '[zrl=' . $obj['plink'] . ']' . $post_type . '[/zrl]';

		$parsedobj = parse_xml_string($xmlhead.$item['obj']);

		$tag = sprintf('#[zrl=%s]%s[/zrl]', $parsedobj->id, $parsedobj->content);
		$item['body'] = sprintf( t('%1$s tagged %2$s\'s %3$s with %4$s'), $author, $objauthor, $plink, $tag );

	}

	if (activity_match($item['verb'],ACTIVITY_FAVORITE)){

		if ($item['obj_type']== "")
			return;

		$Aname = $item['author']['xchan_name'];
		$Alink = $item['author']['xchan_url'];

		$xmlhead="<"."?xml version='1.0' encoding='UTF-8' ?".">";

		$obj = parse_xml_string($xmlhead.$item['obj']);
		if(strlen($obj->id)) {
			$r = q("select * from item where mid = '%s' and uid = %d limit 1",
					dbesc($obj->id),
					intval($item['uid'])
			);
			if(count($r) && $r[0]['plink']) {
				$target = $r[0];
				$Bname = $target['author-name'];
				$Blink = $target['author-link'];
				$A = '[zrl=' . zid($Alink) . ']' . $Aname . '[/zrl]';
				$B = '[zrl=' . zid($Blink) . ']' . $Bname . '[/zrl]';
				$P = '[zrl=' . $target['plink'] . ']' . t('post/item') . '[/zrl]';
				$item['body'] = sprintf( t('%1$s marked %2$s\'s %3$s as favorite'), $A, $B, $P)."\n";

			}
		}
	}
*/

/*
	$matches = null;
	if(strpos($item['body'],'[zrl') !== false) {
		if(preg_match_all('/@\[zrl=(.*?)\]/is',$item['body'],$matches,PREG_SET_ORDER)) {
			foreach($matches as $mtch) {
				if(! strpos($mtch[1],'zid='))
					$item['body'] = str_replace($mtch[0],'@[zrl=' . zid($mtch[1]). ']',$item['body']);
			}
		}
	}

	if(strpos($item['body'],'[zmg') !== false) {
		// add zid's to public images
		if(preg_match_all('/\[zrl=(.*?)\/photos\/(.*?)\/image\/(.*?)\]\[zmg(.*?)\]h(.*?)\[\/zmg\]\[\/zrl\]/is',$item['body'],$matches,PREG_SET_ORDER)) {
			foreach($matches as $mtch) {
				$item['body'] = str_replace($mtch[0],'[zrl=' . zid( $mtch[1] . '/photos/' . $mtch[2] . '/image/' . $mtch[3]) . '][zmg' . $mtch[4] . ']h' . $mtch[5]  . '[/zmg][/zrl]',$item['body']);
			}
		}
	}
*/

	// if item body was obscured and we changed it, re-obscure it
	// FIXME - we need a better filter than just the string 'data'; try and
	// match the fact that it's json encoded

	if(intval($item['item_obscured'])
		&& strlen($item['body']) && (! strpos($item['body'],'data'))) {
		$item['body']  = z_obscure($item['body']);
	}

}

/**
 * @brief Count the total of comments on this item and its desendants.
 *
 * @param array $item an assoziative item-array which provides:
 *  * \e array \b children
 * @return number
 */
function count_descendants($item) {

	$total = count($item['children']);

	if ($total > 0) {
		foreach ($item['children'] as $child) {
			if (! visible_activity($child))
				$total --;

			$total += count_descendants($child);
		}
	}

	return $total;
}

/**
 * @brief Check if the activity of the item is visible.
 *
 * likes (etc.) can apply to other things besides posts. Check if they are post
 * children, in which case we handle them specially. Activities which are unrecognised
 * as having special meaning and hidden will be treated as posts or comments and visible
 * in the stream.  
 *
 * @param array $item
 * @return boolean
 */
function visible_activity($item) {
	$hidden_activities = [ ACTIVITY_LIKE, ACTIVITY_DISLIKE, ACTIVITY_AGREE, ACTIVITY_DISAGREE, ACTIVITY_ABSTAIN, ACTIVITY_ATTEND, ACTIVITY_ATTENDNO, ACTIVITY_ATTENDMAYBE ];

	if(intval($item['item_notshown']))
		return false;

	foreach ($hidden_activities as $act) {
		if ((activity_match($item['verb'], $act)) && ($item['mid'] != $item['parent_mid'])) {
			return false;
		}
	}

	if(is_edit_activity($item))
		return false;

	return true;
}

/**
 * @brief Check if a given activity is an edit activity
 * 
 *
 * @param array $item
 * @return boolean
 */

function is_edit_activity($item) {

	$post_types = [ ACTIVITY_OBJ_NOTE, ACTIVITY_OBJ_COMMENT, basename(ACTIVITY_OBJ_NOTE), basename(ACTIVITY_OBJ_COMMENT)]; 

	// In order to share edits with networks which have no concept of editing, we'll create 
	// separate activities to indicate the edit. Our network will not require them, since our
	// edits are automatically applied and the activity indicated.  

	if(($item['verb'] === ACTIVITY_UPDATE) && (in_array($item['obj_type'],$post_types)))
		return true;

	return false;
}

/**
 * @brief "Render" a conversation or list of items for HTML display.
 *
 * There are two major forms of display:
 *  - Sequential or unthreaded ("New Item View" or search results)
 *  - conversation view
 *
 * The $mode parameter decides between the various renderings and also
 * figures out how to determine page owner and other contextual items
 * that are based on unique features of the calling module.
 *
 * @param App &$a
 * @param array $items
 * @param string $mode
 * @param boolean $update
 * @param string $page_mode default traditional
 * @param string $prepared_item
 * @return string
 */
function conversation(&$a, $items, $mode, $update, $page_mode = 'traditional', $prepared_item = '') {

	$content_html = '';
	$o = '';

	require_once('bbcode.php');

	$ssl_state = ((local_channel()) ? true : false);

	if (local_channel())
		load_pconfig(local_channel(),'');

	$profile_owner   = 0;
	$page_writeable  = false;
	$live_update_div = '';

	$preview = (($page_mode === 'preview') ? true : false);
	$previewing = (($preview) ? ' preview ' : '');

	if ($mode === 'network') {

		$profile_owner = local_channel();
		$page_writeable = true;

		if (!$update) {
			// The special div is needed for liveUpdate to kick in for this page.
			// We only launch liveUpdate if you aren't filtering in some incompatible
			// way and also you aren't writing a comment (discovered in javascript).

			$live_update_div = '<div id="live-network"></div>' . "\r\n"
				. "<script> var profile_uid = " . $_SESSION['uid']
				. "; var netargs = '" . substr(App::$cmd,8)
				. '?f='
				. ((x($_GET,'cid'))    ? '&cid='    . $_GET['cid']    : '')
				. ((x($_GET,'search')) ? '&search=' . $_GET['search'] : '')
				. ((x($_GET,'star'))   ? '&star='   . $_GET['star']   : '')
				. ((x($_GET,'order'))  ? '&order='  . $_GET['order']  : '')
				. ((x($_GET,'bmark'))  ? '&bmark='  . $_GET['bmark']  : '')
				. ((x($_GET,'liked'))  ? '&liked='  . $_GET['liked']  : '')
				. ((x($_GET,'conv'))   ? '&conv='   . $_GET['conv']   : '')
				. ((x($_GET,'spam'))   ? '&spam='   . $_GET['spam']   : '')
				. ((x($_GET,'nets'))   ? '&nets='   . $_GET['nets']   : '')
				. ((x($_GET,'cmin'))   ? '&cmin='   . $_GET['cmin']   : '')
				. ((x($_GET,'cmax'))   ? '&cmax='   . $_GET['cmax']   : '')
				. ((x($_GET,'file'))   ? '&file='   . $_GET['file']   : '')
				. ((x($_GET,'uri'))    ? '&uri='    . $_GET['uri']   : '')
				. "'; var profile_page = " . App::$pager['page'] . "; </script>\r\n";
		}
	}

	elseif ($mode === 'channel') {
		$profile_owner = App::$profile['profile_uid'];
		$page_writeable = ($profile_owner == local_channel());

		if (!$update) {
			$tab = notags(trim($_GET['tab']));
			if ($tab === 'posts') {
				// This is ugly, but we can't pass the profile_uid through the session to the ajax updater,
				// because browser prefetching might change it on us. We have to deliver it with the page.

				$live_update_div = '<div id="live-channel"></div>' . "\r\n"
					. "<script> var profile_uid = " . App::$profile['profile_uid']
					. "; var netargs = '?f='; var profile_page = " . App::$pager['page'] . "; </script>\r\n";
			}
		}
	}

	elseif ($mode === 'display') {
		$profile_owner = local_channel();
		$page_writeable = false;
		$live_update_div = '<div id="live-display"></div>' . "\r\n";
	}

	elseif ($mode === 'page') {
		$profile_owner = App::$profile['uid'];
		$page_writeable = ($profile_owner == local_channel());
		$live_update_div = '<div id="live-page"></div>' . "\r\n";
	}

	elseif ($mode === 'search') {
		$live_update_div = '<div id="live-search"></div>' . "\r\n";
	}

	elseif ($mode === 'photos') {
		$profile_onwer = App::$profile['profile_uid'];
		$page_writeable = ($profile_owner == local_channel());
		$live_update_div = '<div id="live-photos"></div>' . "\r\n";
		// for photos we've already formatted the top-level item (the photo)
		$content_html = App::$data['photo_html'];
	}

	$page_dropping = ((local_channel() && local_channel() == $profile_owner) ? true : false);

	if (! feature_enabled($profile_owner,'multi_delete'))
		$page_dropping = false;


	$channel = App::get_channel();
	$observer = App::get_observer();

	if($update)
		$return_url = $_SESSION['return_url'];
	else
		$return_url = $_SESSION['return_url'] = App::$query_string;

	load_contact_links(local_channel());

	$cb = array('items' => $items, 'mode' => $mode, 'update' => $update, 'preview' => $preview);
	call_hooks('conversation_start',$cb);

	$items = $cb['items'];

	$conv_responses = array(
		'like' => array('title' => t('Likes','title')),'dislike' => array('title' => t('Dislikes','title')),
		'agree' => array('title' => t('Agree','title')),'disagree' => array('title' => t('Disagree','title')), 'abstain' => array('title' => t('Abstain','title')), 
		'attendyes' => array('title' => t('Attending','title')), 'attendno' => array('title' => t('Not attending','title')), 'attendmaybe' => array('title' => t('Might attend','title'))
	);


	// array with html for each thread (parent+comments)
	$threads = array();
	$threadsid = -1;

	$page_template = get_markup_template("conversation.tpl");

	if($items) {

		if($mode === 'network-new' || $mode === 'search' || $mode === 'community') {

			// "New Item View" on network page or search page results
			// - just loop through the items and format them minimally for display


			//$tpl = get_markup_template('search_item.tpl');
			$tpl = 'search_item.tpl';

			foreach($items as $item) {

				$x = [ 'mode' => $mode, 'item' => $item ];
				call_hooks('stream_item',$x);
				
				if($x['item']['blocked'])
					continue;

				$item = $x['item'];

				$threadsid++;

				$comment     = '';
				$owner_url   = '';
				$owner_photo = '';
				$owner_name  = '';
				$sparkle     = '';

				if($mode === 'search' || $mode === 'community') {
					if(((activity_match($item['verb'],ACTIVITY_LIKE)) || (activity_match($item['verb'],ACTIVITY_DISLIKE))) 
						&& ($item['id'] != $item['parent']))
						continue;
					$nickname = $item['nickname'];
				}
				else
					$nickname = App::$user['nickname'];

				$profile_name   = ((strlen($item['author-name']))   ? $item['author-name']   : $item['name']);
				if($item['author-link'] && (! $item['author-name']))
					$profile_name = $item['author-link'];

				$sp = false;
				$profile_link = best_link_url($item,$sp);
				if($sp)
					$sparkle = ' sparkle';
				else
					$profile_link = zid($profile_link);

				$normalised = normalise_link((strlen($item['author-link'])) ? $item['author-link'] : $item['url']);

				$profile_name = $item['author']['xchan_name'];
				$profile_link = $item['author']['xchan_url'];
				$profile_avatar = $item['author']['xchan_photo_m'];

				$location = format_location($item);

				localize_item($item);
				if($mode === 'network-new')
					$dropping = true;
				else
					$dropping = false;

				$drop = array(
					'pagedropping' => $page_dropping,
					'dropping' => $dropping,
					'select' => t('Select'), 
					'delete' => t('Delete'),
				);

				$star = false;
				$isstarred = "unstarred fa-star-o";

				$lock = (($item['item_private'] || strlen($item['allow_cid']) || strlen($item['allow_gid']) || strlen($item['deny_cid']) || strlen($item['deny_gid']))
					? t('Private Message')
					: false
				);

				$likebuttons = false;
				$shareable = false;

				$verified = (intval($item['item_verified']) ? t('Message signature validated') : '');
				$forged = ((($item['sig']) && (! intval($item['item_verified']))) ? t('Message signature incorrect') : '');

				$unverified = '';

//				$tags=array();
//				$terms = get_terms_oftype($item['term'],array(TERM_HASHTAG,TERM_MENTION,TERM_UNKNOWN,TERM_COMMUNITYTAG));
//				if(count($terms))
//					foreach($terms as $tag)
//						$tags[] = format_term_for_display($tag);

				$body = prepare_body($item,true);

				$has_tags = (($body['tags'] || $body['categories'] || $body['mentions'] || $body['attachments'] || $body['folders']) ? true : false);

				$tmp_item = array(
					'template' => $tpl,
					'toplevel' => 'toplevel_item',
					'mode' => $mode,
					'id' => (($preview) ? 'P0' : $item['item_id']),
					'linktitle' => sprintf( t('View %s\'s profile @ %s'), $profile_name, $profile_url),
					'profile_url' => $profile_link,
					'item_photo_menu' => item_photo_menu($item),
					'name' => $profile_name,
					'sparkle' => $sparkle,
					'lock' => $lock,
					'thumb' => $profile_avatar,
					'title' => $item['title'],
					'body' => $body['html'],
					'event' => $body['event'],
					'photo' => $body['photo'],
					'tags' => $body['tags'],
					'categories' => $body['categories'],
					'mentions' => $body['mentions'],
					'attachments' => $body['attachments'],
					'folders' => $body['folders'],
					'verified' => $verified,
					'unverified' => $unverified,
					'forged' => $forged,
					'txt_cats' => t('Categories:'),
					'txt_folders' => t('Filed under:'),
					'has_cats' => ((count($body['categories'])) ? 'true' : ''),
					'has_folders' => ((count($body['folders'])) ? 'true' : ''),
					'text' => strip_tags($body['html']),
					'ago' => relative_date($item['created']),
					'app' => $item['app'],
					'str_app' => sprintf( t('from %s'), $item['app']),
					'isotime' => datetime_convert('UTC', date_default_timezone_get(), $item['created'], 'c'),
					'localtime' => datetime_convert('UTC', date_default_timezone_get(), $item['created'], 'r'),
					'editedtime' => (($item['edited'] != $item['created']) ? sprintf( t('last edited: %s'), datetime_convert('UTC', date_default_timezone_get(), $item['edited'], 'r')) : ''),
					'expiretime' => (($item['expires'] > NULL_DATE) ? sprintf( t('Expires: %s'), datetime_convert('UTC', date_default_timezone_get(), $item['expires'], 'r')):''),
					'location' => $location,
					'indent' => '',
					'owner_name' => $owner_name,
					'owner_url' => $owner_url,
					'owner_photo' => $owner_photo,
					'plink' => get_plink($item,false),
					'edpost' => false,
					'isstarred' => $isstarred,
					'star' => $star,
					'drop' => $drop,
					'vote' => $likebuttons,
					'like' => '',
					'dislike' => '',
					'comment' => '',
					'conv' => (($preview) ? '' : array('href'=> z_root() . '/display/' . $item['mid'], 'title'=> t('View in context'))),
					'previewing' => $previewing,
					'wait' => t('Please wait'),
					'thread_level' => 1,
					'has_tags' => $has_tags,
				);

				$arr = array('item' => $item, 'output' => $tmp_item);
				call_hooks('display_item', $arr);

//				$threads[$threadsid]['id'] = $item['item_id'];
				$threads[] = $arr['output'];
			}
		}
		else {

			// Normal View
//			logger('conv: items: ' . print_r($items,true));

			$conv = new Zotlabs\Lib\ThreadStream($mode, $preview, $prepared_item);

			// In the display mode we don't have a profile owner. 

			if($mode === 'display' && $items)
				$conv->set_profile_owner($items[0]['uid']);

			// get all the topmost parents
			// this shouldn't be needed, as we should have only them in our array
			// But for now, this array respects the old style, just in case

			$threads = array();
			foreach($items as $item) {

				// Check for any blocked authors


				$x = [ 'mode' => $mode, 'item' => $item ];
				call_hooks('stream_item',$x);
				
				if($x['item']['blocked'])
					continue;

				$item = $x['item'];

				builtin_activity_puller($item, $conv_responses);

				if(! visible_activity($item)) {
					continue;
				}


				$item['pagedrop'] = $page_dropping;

				if($item['id'] == $item['parent']) {

					$item_object = new Zotlabs\Lib\ThreadItem($item);
					$conv->add_thread($item_object);
					if($page_mode === 'list') {
						$item_object->set_template('conv_list.tpl');
						$item_object->set_display_mode('list');
					}
				}
			}

			$threads = $conv->get_template_data($conv_responses);
			if(!$threads) {
				logger('[ERROR] conversation : Failed to get template data.', LOGGER_DEBUG);
				$threads = array();
			}
		}
	}

	if($page_mode === 'traditional' || $page_mode === 'preview') {
		$page_template = get_markup_template("threaded_conversation.tpl");
	}
	elseif($update) {
		$page_template = get_markup_template("convobj.tpl");
	}
	else {
		$page_template = get_markup_template("conv_frame.tpl");
		$threads = null;
	}

//	if($page_mode === 'preview')
//		logger('preview: ' . print_r($threads,true));

//  Do not un-comment if smarty3 is in use
//	logger('page_template: ' . $page_template);

//	logger('nouveau: ' . print_r($threads,true));


	$o .= replace_macros($page_template, array(
		'$baseurl' => z_root(),
		'$photo_item' => $content_html,
		'$live_update' => $live_update_div,
		'$remove' => t('remove'),
		'$mode' => $mode,
		'$user' => App::$user,
		'$threads' => $threads,
		'$wait' => t('Loading...'),
		'$dropping' => ($page_dropping?t('Delete Selected Items'):False),
	));

	return $o;
}


function best_link_url($item) {

	$best_url = '';
	$sparkle  = false;

	$clean_url = normalise_link($item['author-link']);

	if((local_channel()) && (local_channel() == $item['uid'])) {
		if(isset(App::$contacts) && x(App::$contacts,$clean_url)) {
			if(App::$contacts[$clean_url]['network'] === NETWORK_DFRN) {
				$best_url = z_root() . '/redir/' . App::$contacts[$clean_url]['id'];
				$sparkle = true;
			}
			else
				$best_url = App::$contacts[$clean_url]['url'];
		}
	}
	if(! $best_url) {
		if(strlen($item['author-link']))
			$best_url = $item['author-link'];
		else
			$best_url = $item['url'];
	}

	return $best_url;
}



function item_photo_menu($item){

	$contact = null;

	$ssl_state = false;

	$sub_link="";
	$poke_link="";
	$contact_url="";
	$pm_url="";
	$vsrc_link = "";
	$follow_url = "";

	$local_channel = local_channel();

	if($local_channel) {
		$ssl_state = true;
		if(! count(App::$contacts))
			load_contact_links($local_channel);
		$channel = App::get_channel();
		$channel_hash = (($channel) ? $channel['channel_hash'] : '');
	}

	if(($local_channel) && $local_channel == $item['uid']) {
		$vsrc_link = 'javascript:viewsrc(' . $item['id'] . '); return false;';
		if($item['parent'] == $item['id'] && $channel && ($channel_hash != $item['author_xchan'])) {
			$sub_link = 'javascript:dosubthread(' . $item['id'] . '); return false;';
		}
		if($channel) {
			$unsub_link = 'javascript:dounsubthread(' . $item['id'] . '); return false;';
		}
	}

	$profile_link = chanlink_hash($item['author_xchan']);
	if($item['uid'] > 0)
		$pm_url = z_root() . '/mail/new/?f=&hash=' . $item['author_xchan'];

	if(App::$contacts && array_key_exists($item['author_xchan'],App::$contacts))
		$contact = App::$contacts[$item['author_xchan']];
	else
		if($local_channel && $item['author']['xchan_addr'])
			$follow_url = z_root() . '/follow/?f=&url=' . $item['author']['xchan_addr'];

	if($contact) {
		$poke_link = z_root() . '/poke/?f=&c=' . $contact['abook_id'];
		if (! intval($contact['abook_self']))  
			$contact_url = z_root() . '/connedit/' . $contact['abook_id'];
		$posts_link = z_root() . '/network/?cid=' . $contact['abook_id'];

		$clean_url = normalise_link($item['author-link']);
	}

	$rating_enabled = get_config('system','rating_enabled');

	$ratings_url = (($rating_enabled) ? z_root() . '/ratings/' . urlencode($item['author_xchan']) : '');

	$post_menu = Array(
		t("View Source") => $vsrc_link,
		t("Follow Thread") => $sub_link,
		t("Unfollow Thread") => $unsub_link,
	);

	$author_menu = array(
		t("View Profile") => $profile_link,
		t("Activity/Posts") => $posts_link,
		t("Connect") => $follow_url,
		t("Edit Connection") => $contact_url,
		t("Message") => $pm_url,
		t('Ratings') => $ratings_url,
		t("Poke") => $poke_link
	);


	$args = array('item' => $item, 'post_menu' => $post_menu, 'author_menu' => $author_menu);

	call_hooks('item_photo_menu', $args);

	$menu = array_merge($args['post_menu'],$args['author_menu']);

	$o = "";
	foreach($menu as $k=>$v){
		if(strpos($v,'javascript:') === 0) {
			$v = substr($v,11);
			$o .= "<li><a href=\"#\" onclick=\"$v\">$k</a></li>\n";
		}
		elseif ($v!="") $o .= "<li><a href=\"$v\">$k</a></li>\n";
	}

	return $o;
}

/**
 * @brief Checks item to see if it is one of the builtin activities (like/dislike, event attendance, consensus items, etc.)
 *
 * Increments the count of each matching activity and adds a link to the author as needed.
 *
 * @param array $item
 * @param array &$conv_responses (already created with builtin activity structure)
 */
function builtin_activity_puller($item, &$conv_responses) {

	// if this item is a post or comment there's nothing for us to do here, just return.

	if(activity_match($item['verb'],ACTIVITY_POST))
		return;

	foreach($conv_responses as $mode => $v) {

		$url = '';

		switch($mode) {
			case 'like':
				$verb = ACTIVITY_LIKE;
				break;
			case 'dislike':
				$verb = ACTIVITY_DISLIKE;
				break;
			case 'agree':
				$verb = ACTIVITY_AGREE;
				break;
			case 'disagree':
				$verb = ACTIVITY_DISAGREE;
				break;
			case 'abstain':
				$verb = ACTIVITY_ABSTAIN;
				break;
			case 'attendyes':
				$verb = ACTIVITY_ATTEND;
				break;
			case 'attendno':
				$verb = ACTIVITY_ATTENDNO;
				break;
			case 'attendmaybe':
				$verb = ACTIVITY_ATTENDMAYBE;
				break;
			default:
				return;
				break;
		}

		if((activity_match($item['verb'], $verb)) && ($item['id'] != $item['parent'])) {
			$name = (($item['author']['xchan_name']) ? $item['author']['xchan_name'] : t('Unknown'));
			$url = (($item['author']['xchan_url'] && $item['author']['xchan_photo_s']) 
				? '<a href="' . chanlink_url($item['author']['xchan_url']) . '">' . '<img class="dropdown-menu-img-xs" src="' . zid($item['author']['xchan_photo_s'])  . '" alt="' . urlencode($name) . '" />' . $name . '</a>' 
				: '<a href="#" class="disabled">' . $name . '</a>'
			);

			if(! $item['thr_parent'])
				$item['thr_parent'] = $item['parent_mid'];

			if(! ((isset($conv_responses[$mode][$item['thr_parent'] . '-l'])) 
				&& (is_array($conv_responses[$mode][$item['thr_parent'] . '-l']))))
				$conv_responses[$mode][$item['thr_parent'] . '-l'] = array();

			// only list each unique author once
			if(in_array($url,$conv_responses[$mode][$item['thr_parent'] . '-l']))
				continue;

			if(! isset($conv_responses[$mode][$item['thr_parent']]))
				$conv_responses[$mode][$item['thr_parent']] = 1;
			else
				$conv_responses[$mode][$item['thr_parent']] ++;

			$conv_responses[$mode][$item['thr_parent'] . '-l'][] = $url;
			if(get_observer_hash() && get_observer_hash() === $item['author_xchan']) {
				$conv_responses[$mode][$item['thr_parent'] . '-m'] = true;
			}

			// there can only be one activity verb per item so if we found anything, we can stop looking
			return;
		}
	}
}


/**
 * @brief Format the like/dislike text for a profile item.
 *
 * @param int $cnt number of people who like/dislike the item
 * @param array $arr array of pre-linked names of likers/dislikers
 * @param string $type one of 'like, 'dislike'
 * @param int $id item id
 * @return string formatted text
 */
function format_like($cnt, $arr, $type, $id) {
	$o = '';
	if ($cnt == 1) {
		$o .= (($type === 'like') ? sprintf( t('%s likes this.'), $arr[0]) : sprintf( t('%s doesn\'t like this.'), $arr[0])) . EOL ;
	} else {
		$spanatts = 'class="fakelink" onclick="openClose(\'' . $type . 'list-' . $id . '\');"';
		$o .= (($type === 'like') ?
					sprintf( tt('<span  %1$s>%2$d people</span> like this.','<span  %1$s>%2$d people</span> like this.',$cnt), $spanatts, $cnt)
					 :
					sprintf( tt('<span  %1$s>%2$d people</span> don\'t like this.','<span  %1$s>%2$d people</span> don\'t like this.',$cnt), $spanatts, $cnt) );
		$o .= EOL;
		$total = count($arr);
		if($total >= MAX_LIKERS)
			$arr = array_slice($arr, 0, MAX_LIKERS - 1);
		if($total < MAX_LIKERS)
			$arr[count($arr)-1] = t('and') . ' ' . $arr[count($arr)-1];
		$str = implode(', ', $arr);
		if($total >= MAX_LIKERS)
			$str .= sprintf( tt(', and %d other people',', and %d other people',$total - MAX_LIKERS), $total - MAX_LIKERS );
		$str = (($type === 'like') ? sprintf( t('%s like this.'), $str) : sprintf( t('%s don\'t like this.'), $str));
		$o .= "\t" . '<div id="' . $type . 'list-' . $id . '" style="display: none;" >' . $str . '</div>';
	}

	return $o;
}

/**
 * This is our general purpose content editor. 
 * It was once nicknamed "jot" and you may see references to "jot" littered throughout the code.
 * They are referring to the content editor or components thereof. 
 */

function status_editor($a, $x, $popup = false) {

	$o = '';

	$c = channelx_by_n($x['profile_uid']);
	if($c && $c['channel_moved'])
		return $o;

	$plaintext = true;

//	if(feature_enabled(local_channel(),'richtext'))
//		$plaintext = false;

	$feature_voting = feature_enabled($x['profile_uid'], 'consensus_tools');
	if(x($x, 'hide_voting'))
		$feature_voting = false;
	
	$feature_nocomment = feature_enabled($x['profile_uid'], 'disable_comments');
	if(x($x, 'disable_comments'))
		$feature_nocomment = false;

	$feature_expire = ((feature_enabled($x['profile_uid'], 'content_expire') && (! $webpage)) ? true : false);
	if(x($x, 'hide_expire'))
		$feature_expire = false;

	$feature_future = ((feature_enabled($x['profile_uid'], 'delayed_posting') && (! $webpage)) ? true : false);
	if(x($x, 'hide_future'))
		$feature_future = false;

	$geotag = (($x['allow_location']) ? replace_macros(get_markup_template('jot_geotag.tpl'), array()) : '');
	$setloc = t('Set your location');
	$clearloc = ((get_pconfig($x['profile_uid'], 'system', 'use_browser_location')) ? t('Clear browser location') : '');
	if(x($x, 'hide_location'))
		$geotag = $setloc = $clearloc = '';

	$mimetype = ((x($x,'mimetype')) ? $x['mimetype'] : 'text/bbcode');

	$mimeselect = ((x($x,'mimeselect')) ? $x['mimeselect'] : false);
	if($mimeselect)
		$mimeselect = mimetype_select($x['profile_uid'], $mimetype);
	else
		$mimeselect = '<input type="hidden" name="mimetype" value="' . $mimetype . '" />';

	$weblink = (($mimetype === 'text/bbcode') ? t('Insert web link') : false);
	if(x($x, 'hide_weblink'))
		$weblink = false;
	
	$embedPhotos = t('Embed image from photo albums');

	$writefiles = (($mimetype === 'text/bbcode') ? perm_is_allowed($x['profile_uid'], get_observer_hash(), 'write_storage') : false);
	if(x($x, 'hide_attach'))
		$writefiles = false;

	$layout = ((x($x,'layout')) ? $x['layout'] : '');

	$layoutselect = ((x($x,'layoutselect')) ? $x['layoutselect'] : false);
	if($layoutselect)
		$layoutselect = layout_select($x['profile_uid'], $layout);
	else
		$layoutselect = '<input type="hidden" name="layout_mid" value="' . $layout . '" />';

	if(array_key_exists('channel_select',$x) && $x['channel_select']) {
		require_once('include/channel.php');
		$id_select = identity_selector();
	}
	else
		$id_select = '';

	$webpage = ((x($x,'webpage')) ? $x['webpage'] : '');

	$tpl = get_markup_template('jot-header.tpl');

	App::$page['htmlhead'] .= replace_macros($tpl, array(
		'$baseurl' => z_root(),
		'$editselect' => (($plaintext) ? 'none' : '/(profile-jot-text|prvmail-text)/'),
		'$pretext' => ((x($x,'pretext')) ? $x['pretext'] : ''),
		'$geotag' => $geotag,
		'$nickname' => $x['nickname'],
		'$linkurl' => t('Please enter a link URL:'),
		'$term' => t('Tag term:'),
		'$whereareu' => t('Where are you right now?'),
		'$editor_autocomplete'=> ((x($x,'editor_autocomplete')) ? $x['editor_autocomplete'] : ''),
		'$bbco_autocomplete'=> ((x($x,'bbco_autocomplete')) ? $x['bbco_autocomplete'] : ''),
		'$modalchooseimages' => t('Choose images to embed'),
		'$modalchoosealbum' => t('Choose an album'),
		'$modaldiffalbum' => t('Choose a different album...'),
		'$modalerrorlist' => t('Error getting album list'),
		'$modalerrorlink' => t('Error getting photo link'),
		'$modalerroralbum' => t('Error getting album'),
		'$nocomment_enabled' => t('Comments enabled'),
		'$nocomment_disabled' => t('Comments disabled'),
	));

	$tpl = get_markup_template('jot.tpl');

	$preview = t('Preview');
	if(x($x, 'hide_preview'))
		$preview = '';

	$defexpire = ((($z = get_pconfig($x['profile_uid'], 'system', 'default_post_expire')) && (! $webpage)) ? $z : '');
	if($defexpire)
		$defexpire = datetime_convert('UTC',date_default_timezone_get(),$defexpire,'Y-m-d H:i');

	$defpublish = ((($z = get_pconfig($x['profile_uid'], 'system', 'default_post_publish')) && (! $webpage)) ? $z : '');
	if($defpublish)
		$defpublish = datetime_convert('UTC',date_default_timezone_get(),$defpublish,'Y-m-d H:i');

	$cipher = get_pconfig($x['profile_uid'], 'system', 'default_cipher');
	if(! $cipher)
		$cipher = 'aes256';

	// avoid illegal offset errors
	if(! array_key_exists('permissions',$x)) 
		$x['permissions'] = [ 'allow_cid' => '', 'allow_gid' => '', 'deny_cid' => '', 'deny_gid' => '' ];

	$jotplugins = '';
	call_hooks('jot_tool', $jotplugins);

	$jotnets = '';
	if(x($x,'jotnets')) {
		call_hooks('jot_networks', $jotnets);
	}

	$o .= replace_macros($tpl, array(
		'$return_path' => ((x($x, 'return_path')) ? $x['return_path'] : App::$query_string),
		'$action' =>  z_root() . '/item',
		'$share' => (x($x,'button') ? $x['button'] : t('Share')),
		'$webpage' => $webpage,
		'$placeholdpagetitle' => ((x($x,'ptlabel')) ? $x['ptlabel'] : t('Page link name')),
		'$pagetitle' => (x($x,'pagetitle') ? $x['pagetitle'] : ''),
		'$id_select' => $id_select,
		'$id_seltext' => t('Post as'),
		'$writefiles' => $writefiles,
		'$bold' => t('Bold'),
		'$italic' => t('Italic'),
		'$underline' => t('Underline'),
		'$quote' => t('Quote'),
		'$code' => t('Code'),
		'$attach' => t('Attach file'),
		'$weblink' => $weblink,
		'$embedPhotos' => $embedPhotos,
		'$embedPhotosModalTitle' => t('Embed an image from your albums'),
		'$embedPhotosModalCancel' => t('Cancel'),
		'$embedPhotosModalOK' => t('OK'),
		'$setloc' => $setloc,
		'$voting' => t('Toggle voting'),
		'$feature_voting' => $feature_voting,
		'$consensus' => 0,
		'$nocommenttitle' => t('Disable comments'),
		'$nocommenttitlesub' => t('Toggle comments'),
		'$feature_nocomment' => $feature_nocomment,
		'$nocomment' => 0,
		'$clearloc' => $clearloc,
		'$title' => ((x($x, 'title')) ? htmlspecialchars($x['title'], ENT_COMPAT,'UTF-8') : ''),
		'$placeholdertitle' => ((x($x, 'placeholdertitle')) ? $x['placeholdertitle'] : t('Title (optional)')),
		'$catsenabled' => ((feature_enabled($x['profile_uid'], 'categories') && (! $webpage)) ? 'categories' : ''),
		'$category' => ((x($x, 'category')) ? $x['category'] : ''),
		'$placeholdercategory' => t('Categories (optional, comma-separated list)'),
		'$permset' => t('Permission settings'),
		'$ptyp' => ((x($x, 'ptyp')) ? $x['ptyp'] : ''),
		'$content' => ((x($x,'body')) ? htmlspecialchars($x['body'], ENT_COMPAT,'UTF-8') : ''),
		'$attachment' => ((x($x, 'attachment')) ? $x['attachment'] : ''),
		'$post_id' => ((x($x, 'post_id')) ? $x['post_id'] : ''),
		'$defloc' => $x['default_location'],
		'$visitor' => $x['visitor'],
		'$lockstate' => $x['lockstate'],
		'$acl' => $x['acl'],
		'$allow_cid' => acl2json($x['permissions']['allow_cid']),
		'$allow_gid' => acl2json($x['permissions']['allow_gid']),
		'$deny_cid' => acl2json($x['permissions']['deny_cid']),
		'$deny_gid' => acl2json($x['permissions']['deny_gid']),
		'$mimeselect' => $mimeselect,
		'$layoutselect' => $layoutselect,
		'$showacl' => ((array_key_exists('showacl', $x)) ? $x['showacl'] : true),
		'$bang' => $x['bang'],
		'$profile_uid' => $x['profile_uid'],
		'$preview' => $preview,
		'$source' => ((x($x, 'source')) ? $x['source'] : ''),
		'$jotplugins' => $jotplugins,
		'$jotnets' => $jotnets,
		'$jotnets_label' => t('Other networks and post services'),
		'$defexpire' => $defexpire,
		'$feature_expire' => $feature_expire,
		'$expires' => t('Set expiration date'),
		'$defpublish' => $defpublish,
		'$feature_future' => $feature_future,
		'$future_txt' => t('Set publish date'),
		'$feature_encrypt' => ((feature_enabled($x['profile_uid'], 'content_encrypt') && (! $webpage)) ? true : false),
		'$encrypt' => t('Encrypt text'),
		'$cipher' => $cipher,
		'$expiryModalOK' => t('OK'),
		'$expiryModalCANCEL' => t('Cancel'),
		'$expanded' => ((x($x, 'expanded')) ? $x['expanded'] : false),
		'$bbcode' => ((x($x, 'bbcode')) ? $x['bbcode'] : false),
		'$parent' => ((array_key_exists('parent',$x) && $x['parent']) ? $x['parent'] : 0)
	));

	if ($popup === true) {
		$o = '<div id="jot-popup" style="display:none">' . $o . '</div>';
	}

	return $o;
}


function get_item_children($arr, $parent) {
	$children = array();
	foreach($arr as $item) {
		if($item['id'] != $item['parent']) {
			if(get_config('system','thread_allow')) {
				// Fallback to parent_mid if thr_parent is not set
				$thr_parent = $item['thr_parent'];
				if($thr_parent == '')
					$thr_parent = $item['parent_mid'];
				
				if($thr_parent == $parent['mid']) {
					$item['children'] = get_item_children($arr, $item);
					$children[] = $item;
				}
			}
			else if($item['parent'] == $parent['id']) {
				$children[] = $item;
			}
		}
	}
	return $children;
}

function sort_item_children($items) {
	$result = $items;
	usort($result,'sort_thr_created_rev');
	foreach($result as $k => $i) {
		if(count($result[$k]['children'])) {
			$result[$k]['children'] = sort_item_children($result[$k]['children']);
		}
	}
	return $result;
}

function add_children_to_list($children, &$arr) {
	foreach($children as $y) {
		$arr[] = $y;
		if(count($y['children']))
			add_children_to_list($y['children'], $arr);
	}
}

function conv_sort($arr, $order) {

	if((!(is_array($arr) && count($arr))))
		return array();

	$parents = array();

	foreach($arr as $x)
		if($x['id'] == $x['parent'])
				$parents[] = $x;

	if(stristr($order,'created'))
		usort($parents,'sort_thr_created');
	elseif(stristr($order,'commented'))
		usort($parents,'sort_thr_commented');
	elseif(stristr($order,'ascending'))
		usort($parents,'sort_thr_created_rev');


	if(count($parents))
		foreach($parents as $i=>$_x)
			$parents[$i]['children'] = get_item_children($arr, $_x);

	if(count($parents)) {
		foreach($parents as $k => $v) {
			if(count($parents[$k]['children'])) {
				$parents[$k]['children'] = sort_item_children($parents[$k]['children']);
			}
		}
	}

	$ret = array();
	if(count($parents)) {
		foreach($parents as $x) {
			$ret[] = $x;
			if(count($x['children']))
				add_children_to_list($x['children'], $ret);
		}
	}

	return $ret;
}


function sort_thr_created($a,$b) {
	return strcmp($b['created'],$a['created']);
}

function sort_thr_created_rev($a,$b) {
	return strcmp($a['created'],$b['created']);
}

function sort_thr_commented($a,$b) {
	return strcmp($b['commented'],$a['commented']);
}

function find_thread_parent_index($arr,$x) {
	foreach($arr as $k => $v)
		if($v['id'] == $x['parent'])
			return $k;

	return false;
}

function format_location($item) {

	if(strpos($item['location'],'#') === 0) {
		$location = substr($item['location'],1);
		$location = ((strpos($location,'[') !== false) ? bbcode($location) : $location);
	}
	else {
		$locate = array('location' => $item['location'], 'coord' => $item['coord'], 'html' => '');
		call_hooks('render_location',$locate);
		$location = ((strlen($locate['html'])) ? $locate['html'] : render_location_default($locate));
	}
	return $location;
}

function render_location_default($item) {

	$location = $item['location'];
	$coord = $item['coord'];

	if($coord) {
		if($location)
			$location .= '&nbsp;<span class="smalltext">(' . $coord . ')</span>';
		else
			$location = '<span class="smalltext">' . $coord . '</span>';
	}

	return $location;
}


function prepare_page($item) {

	$naked = 1;
//	$naked = ((get_pconfig($item['uid'],'system','nakedpage')) ? 1 : 0);
	$observer = App::get_observer();
	//240 chars is the longest we can have before we start hitting problems with suhosin sites
	$preview = substr(urlencode($item['body']), 0, 240);
	$link = z_root() . '/' . App::$cmd;
	if(array_key_exists('webpage',App::$layout) && array_key_exists('authored',App::$layout['webpage'])) {
		if(App::$layout['webpage']['authored'] === 'none')
			$naked = 1;
		// ... other possible options
	}

	// prepare_body calls unobscure() as a side effect. Do it here so that
	// the template will get passed an unobscured title.

	$body = prepare_body($item, true);
	$tpl = get_pconfig($item['uid'], 'system', 'pagetemplate');
	if (! $tpl)
		$tpl = 'page_display.tpl';

	return replace_macros(get_markup_template($tpl), array(
		'$author' => (($naked) ? '' : $item['author']['xchan_name']),
		'$auth_url' => (($naked) ? '' : zid($item['author']['xchan_url'])),
		'$date' => (($naked) ? '' : datetime_convert('UTC', date_default_timezone_get(), $item['created'], 'Y-m-d H:i')),
		'$title' => smilies(bbcode($item['title'])),
		'$body' => $body['html'],
		'$preview' => $preview,
		'$link' => $link,
	));
}


function network_tabs() {

	$no_active='';
	$starred_active = '';
	$new_active = '';
	$all_active = '';
	$search_active = '';
	$conv_active = '';
	$spam_active = '';
	$postord_active = '';
	$public_active = '';

	if(x($_GET,'new')) {
		$new_active = 'active';
	}

	if(x($_GET,'search')) {
		$search_active = 'active';
	}

	if(x($_GET,'star')) {
		$starred_active = 'active';
	}

	if(x($_GET,'conv')) {
		$conv_active = 'active';
	}

	if(x($_GET,'spam')) {
		$spam_active = 'active';
	}

	if(x($_GET,'fh')) {
		$public_active = 'active';
	}

	if (($new_active == '') 
		&& ($starred_active == '') 
		&& ($conv_active == '')
		&& ($search_active == '')
		&& ($spam_active == '')
		&& ($public_active == '')) {
			$no_active = 'active';
	}

	if ($no_active=='active' && x($_GET,'order')) {
		switch($_GET['order']){
			case 'post': $postord_active = 'active'; $no_active=''; break;
			case 'comment' : $all_active = 'active'; $no_active=''; break;
		}
	}

	if ($no_active=='active') $all_active='active';

	$cmd = App::$cmd;

	// tabs
	$tabs = array();

	$d = get_config('system','disable_discover_tab');
	if($d === false)
		$d = 1;

	if(! $d) {
		$tabs[] = array(
			'label' => t('Discover'),
			'url' => z_root() . '/' . $cmd . '?f=&fh=1' ,
			'sel' => $public_active,
			'title' => t('Imported public streams'),
		);
	}

	$tabs[] = array(
		'label' => t('Commented Order'),
		'url'=>z_root() . '/' . $cmd . '?f=&order=comment' . ((x($_GET,'cid')) ? '&cid=' . $_GET['cid'] : '') . ((x($_GET,'gid')) ? '&gid=' . $_GET['gid'] : ''),
		'sel'=>$all_active,
		'title'=> t('Sort by Comment Date'),
	);

	$tabs[] = array(
		'label' => t('Posted Order'),
		'url'=>z_root() . '/' . $cmd . '?f=&order=post' . ((x($_GET,'cid')) ? '&cid=' . $_GET['cid'] : '') . ((x($_GET,'gid')) ? '&gid=' . $_GET['gid'] : ''),
		'sel'=>$postord_active,
		'title' => t('Sort by Post Date'),
	);

	if(feature_enabled(local_channel(),'personal_tab')) {
		$tabs[] = array(
			'label' => t('Personal'),
			'url' => z_root() . '/' . $cmd . '?f=' . ((x($_GET,'cid')) ? '&cid=' . $_GET['cid'] : '') . '&conv=1',
			'sel' => $conv_active,
			'title' => t('Posts that mention or involve you'),
		);
	}

	if(feature_enabled(local_channel(),'new_tab')) {
		$tabs[] = array(
			'label' => t('New'),
			'url' => z_root() . '/' . $cmd . '?f=' . ((x($_GET,'cid')) ? '&cid=' . $_GET['cid'] : '') . '&new=1' . ((x($_GET,'gid')) ? '&gid=' . $_GET['gid'] : ''),
			'sel' => $new_active,
			'title' => t('Activity Stream - by date'),
		);
	}

	if(feature_enabled(local_channel(),'star_posts')) {
		$tabs[] = array(
			'label' => t('Starred'),
			'url'=>z_root() . '/' . $cmd . ((x($_GET,'cid')) ? '/?f=&cid=' . $_GET['cid'] : '') . '&star=1',
			'sel'=>$starred_active,
			'title' => t('Favourite Posts'),
		);
	}
	// Not yet implemented

	if(feature_enabled(local_channel(),'spam_filter')) {
		$tabs[] = array(
			'label' => t('Spam'),
			'url'=> z_root() . '/network?f=&spam=1',
			'sel'=> $spam_active,
			'title' => t('Posts flagged as SPAM'),
		);
	}

	$arr = array('tabs' => $tabs);
	call_hooks('network_tabs', $arr);

	$tpl = get_markup_template('common_tabs.tpl');

	return replace_macros($tpl, array('$tabs' => $arr['tabs']));
}

/**
 * @brief
 *
 * @param App $a
 * @param boolean $is_owner default false
 * @param string $nickname default null
 * @return void|string
 */
function profile_tabs($a, $is_owner = false, $nickname = null){

	// Don't provide any profile tabs if we're running as the sys channel

	if (App::$is_sys)
		return;

	$channel = App::get_channel();

	if (is_null($nickname))
		$nickname  = $channel['channel_address'];


	$uid = ((App::$profile['profile_uid']) ? App::$profile['profile_uid'] : local_channel());
	$account_id = ((App::$profile['profile_uid']) ? App::$profile['channel_account_id'] : App::$channel['channel_account_id']);

	if($uid == local_channel()) {
		$cal_link = '';
	}
	else {
		$cal_link = '/cal/' . $nickname;
	}


	if (get_pconfig($uid, 'system', 'noprofiletabs'))
		return;

	if (x($_GET, 'tab'))
		$tab = notags(trim($_GET['tab']));

	$url = z_root() . '/channel/' . $nickname;
	$pr  = z_root() . '/profile/' . $nickname;

	$tabs = array(
		array(
			'label' => t('Channel'),
			'url'   => $url,
			'sel'   => ((argv(0) == 'channel') ? 'active' : ''),
			'title' => t('Status Messages and Posts'),
			'id'    => 'status-tab',
		),
	);

	$p = get_all_perms($uid,get_observer_hash());

	if ($p['view_profile']) {
		$tabs[] = array(
			'label' => t('About'),
			'url'   => $pr,
			'sel'   => ((argv(0) == 'profile') ? 'active' : ''),
			'title' => t('Profile Details'),
			'id'    => 'profile-tab',
		);
	}
	if ($p['view_storage']) {
		$tabs[] = array(
			'label' => t('Photos'),
			'url'   => z_root() . '/photos/' . $nickname,
			'sel'   => ((argv(0) == 'photos') ? 'active' : ''),
			'title' => t('Photo Albums'),
			'id'    => 'photo-tab',
		);
		$tabs[] = array(
			'label' => t('Files'),
			'url'   => z_root() . '/cloud/' . $nickname,
			'sel'   => ((argv(0) == 'cloud' || argv(0) == 'sharedwithme') ? 'active' : ''),
			'title' => t('Files and Storage'),
			'id'    => 'files-tab',
		);
	}

	if($p['view_stream'] && $cal_link) {
		$tabs[] = array(
			'label' => t('Events'),
			'url'   => z_root() . $cal_link,
			'sel'   => ((argv(0) == 'cal' || argv(0) == 'events') ? 'active' : ''),
			'title' => t('Events'),
			'id'    => 'event-tab',
		);
	}


	if ($p['chat'] && feature_enabled($uid,'ajaxchat')) {
		$has_chats = Zotlabs\Lib\Chatroom::list_count($uid);
		if ($has_chats) {
			$tabs[] = array(
				'label' => t('Chatrooms'),
				'url'   => z_root() . '/chat/' . $nickname,
				'sel'   => ((argv(0) == 'chat') ? 'active' : '' ),
				'title' => t('Chatrooms'),
				'id'    => 'chat-tab',
			);
		}
	}

	require_once('include/menu.php');
	$has_bookmarks = menu_list_count(local_channel(),'',MENU_BOOKMARK) + menu_list_count(local_channel(),'',MENU_SYSTEM|MENU_BOOKMARK);
	if ($is_owner && $has_bookmarks) {
		$tabs[] = array(
			'label' => t('Bookmarks'),
			'url'   => z_root() . '/bookmarks',
			'sel'   => ((argv(0) == 'bookmarks') ? 'active' : ''),
			'title' => t('Saved Bookmarks'),
			'id'    => 'bookmarks-tab',
		);
	}

	if ($p['write_pages'] && feature_enabled($uid,'webpages')) {
		$tabs[] = array(
			'label' => t('Webpages'),
			'url'   => z_root() . '/webpages/' . $nickname,
			'sel'   => ((argv(0) == 'webpages') ? 'active' : ''),
			'title' => t('Manage Webpages'),
			'id'    => 'webpages-tab',
		);
	} 

	if(feature_enabled($uid,'wiki') && (get_account_techlevel($account_id) > 3)) {
		$tabs[] = array(
			'label' => t('Wikis'),
			'url'   => z_root() . '/wiki/' . $nickname,
			'sel'   => ((argv(0) == 'wiki') ? 'active' : ''),
			'title' => t('Wiki'),
			'id'    => 'wiki-tab',
		);
	}


	$arr = array('is_owner' => $is_owner, 'nickname' => $nickname, 'tab' => (($tab) ? $tab : false), 'tabs' => $tabs);
	call_hooks('profile_tabs', $arr);
	
	$tpl = get_markup_template('common_tabs.tpl');

	return replace_macros($tpl,array('$tabs' => $arr['tabs']));
}


function get_responses($conv_responses,$response_verbs,$ob,$item) {

	$ret = array();
	foreach($response_verbs as $v) {
		$ret[$v] = array();
		$ret[$v]['count'] = ((x($conv_responses[$v],$item['mid'])) ? $conv_responses[$v][$item['mid']] : '');
		$ret[$v]['list']  = ((x($conv_responses[$v],$item['mid'])) ? $conv_responses[$v][$item['mid'] . '-l'] : '');
		if(count($ret[$v]['list']) > MAX_LIKERS) {
			$ret[$v]['list_part'] = array_slice($ret[$v]['list'], 0, MAX_LIKERS);
			array_push($ret[$v]['list_part'], '<a href="#" data-toggle="modal" data-target="#' . $v . 'Modal-' 
				. (($ob) ? $ob->get_id() : $item['id']) . '"><b>' . t('View all') . '</b></a>');
		} else {
			$ret[$v]['list_part'] = '';
		}
		$ret[$v]['button'] = get_response_button_text($v,$ret[$v]['count']);
		$ret[$v]['title'] = $conv_responses[$v]['title'];
	}

	$count = 0;
	foreach ($ret as $key) {
		if ($key['count'] == true)
			$count++;
	}

	$ret['count'] = $count;

//logger('ret: ' . print_r($ret,true));

	return $ret;
}

function get_response_button_text($v,$count) {
	switch($v) {
		case 'like':
			return tt('Like','Likes',$count,'noun');
			break;
		case 'dislike':
			return tt('Dislike','Dislikes',$count,'noun');
			break;
		case 'attendyes':
			return tt('Attending','Attending',$count,'noun');
			break;
		case 'attendno':
			return tt('Not Attending','Not Attending',$count,'noun');
			break;
		case 'attendmaybe':
			return tt('Undecided','Undecided',$count,'noun');
			break;
		case 'agree':
			return tt('Agree','Agrees',$count,'noun');
			break;
		case 'disagree':
			return tt('Disagree','Disagrees',$count,'noun');
			break;
		case 'abstain':
			return tt('Abstain','Abstains',$count,'noun');
			break;
		default:
			return '';
			break;
	}
}
