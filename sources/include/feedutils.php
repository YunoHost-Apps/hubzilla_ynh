<?php


/**
 * @brief Generate an Atom feed.
 *
 * @param array $channel
 * @param array $params
 */
function get_public_feed($channel, $params) {

	$type      = 'xml';
	$begin     = NULL_DATE;
	$end       = '';
	$start     = 0;
	$records   = 40;
	$direction = 'desc';
	$pages     = 0;

	if(! $params)
		$params = array();

	$params['type']      = ((x($params,'type'))      ? $params['type']          : 'xml');
	$params['begin']     = ((x($params,'begin'))     ? $params['begin']         : NULL_DATE);
	$params['end']       = ((x($params,'end'))       ? $params['end']           : datetime_convert('UTC','UTC','now'));
	$params['start']     = ((x($params,'start'))     ? $params['start']         : 0);
	$params['records']   = ((x($params,'records'))   ? $params['records']       : 40);
	$params['direction'] = ((x($params,'direction')) ? $params['direction']     : 'desc');
	$params['pages']     = ((x($params,'pages'))     ? intval($params['pages']) : 0);
	$params['top']       = ((x($params,'top'))       ? intval($params['top'])   : 0);
	$params['cat']       = ((x($params,'cat'))       ? $params['cat']          : '');


	// put a sane lower limit on feed requests if not specified

//	if($params['begin'] <= NULL_DATE)
//		$params['begin'] = datetime_convert('UTC','UTC','now - 1 month');

	switch($params['type']) {
		case 'json':
			header("Content-type: application/atom+json");
			break;
		case 'xml':
		default:
			header("Content-type: application/atom+xml");
			break;
	}

	return get_feed_for($channel, get_observer_hash(), $params);
}

/**
 * @brief
 *
 * @param array $channel
 * @param string $observer_hash
 * @param array $params
 * @return string
 */
function get_feed_for($channel, $observer_hash, $params) {

	if(! channel)
		http_status_exit(401);

	if($params['pages']) {
		if(! perm_is_allowed($channel['channel_id'],$observer_hash,'view_pages'))
			http_status_exit(403);
	} else {
		if(! perm_is_allowed($channel['channel_id'],$observer_hash,'view_stream'))
			http_status_exit(403);
	}
	$items = items_fetch(array(
		'wall' => '1',
		'datequery' => $params['end'],
		'datequery2' => $params['begin'],
		'start' => $params['start'],          // FIXME
	 	'records' => $params['records'],      // FIXME
		'direction' => $params['direction'],  // FIXME
		'pages' => $params['pages'],
		'order' => 'post',
		'top'   => $params['top'],
		'cat'   => $params['cat']
		), $channel, $observer_hash, CLIENT_MODE_NORMAL, App::$module);
	

	$feed_template = get_markup_template('atom_feed.tpl');

	$atom = '';

	$atom .= replace_macros($feed_template, array(
		'$version'      => xmlify(Zotlabs\Lib\System::get_project_version()),
		'$red'          => xmlify(Zotlabs\Lib\System::get_platform_name()),
		'$feed_id'      => xmlify($channel['xchan_url']),
		'$feed_title'   => xmlify($channel['channel_name']),
		'$feed_updated' => xmlify(datetime_convert('UTC', 'UTC', 'now' , ATOM_TIME)) ,
		'$hub'          => '', // feed_hublinks(),
		'$salmon'       => '', // feed_salmonlinks($channel['channel_address']),
		'$name'         => xmlify($channel['channel_name']),
		'$profile_page' => xmlify($channel['xchan_url']),
		'$mimephoto'    => xmlify($channel['xchan_photo_mimetype']),
		'$photo'        => xmlify($channel['xchan_photo_l']),
		'$thumb'        => xmlify($channel['xchan_photo_m']),
		'$picdate'      => '',
		'$uridate'      => '',
		'$namdate'      => '',
		'$birthday'     => '',
		'$community'    => '',
	));


	call_hooks('atom_feed', $atom);

	if($items) {
		$type = 'html';
		foreach($items as $item) {
			if($item['item_private'])
				continue;

			/** @BUG $owner is undefined in this call */
			$atom .= atom_entry($item, $type, null, $owner, true);
		}
	}

	call_hooks('atom_feed_end', $atom);

	$atom .= '</feed>' . "\r\n";

	return $atom;
}

/**
 * @brief
 *
 * @param array $item an associative array with
 *   * \b string \b verb
 * @return string item's verb if set, default ACTIVITY_POST see boot.php
 */
function construct_verb($item) {
	if ($item['verb'])
		return $item['verb'];

	return ACTIVITY_POST;
}

function construct_activity_object($item) {

	if($item['obj']) {
		$o = '<as:object>' . "\r\n";
		$r = json_decode($item['obj'],false);

		if(! $r)
			return '';
		if($r->type)
			$o .= '<as:obj_type>' . xmlify($r->type) . '</as:obj_type>' . "\r\n";
		if($r->id)
			$o .= '<id>' . xmlify($r->id) . '</id>' . "\r\n";
		if($r->title)
			$o .= '<title>' . xmlify($r->title) . '</title>' . "\r\n";
		if($r->links) {
			/** @FIXME!! */
			if(substr($r->link,0,1) === '<') {
				$r->link = preg_replace('/\<link(.*?)\"\>/','<link$1"/>',$r->link);
				$o .= $r->link;
			}
			else
				$o .= '<link rel="alternate" type="text/html" href="' . xmlify($r->link) . '" />' . "\r\n";
		}
		if($r->content)
			$o .= '<content type="html" >' . xmlify(bbcode($r->content)) . '</content>' . "\r\n";
		$o .= '</as:object>' . "\r\n";
		return $o;
	}

	return '';
}

function construct_activity_target($item) {

	if($item['target']) {
		$o = '<as:target>' . "\r\n";
		$r = json_decode($item['target'],false);
		if(! $r)
			return '';
		if($r->type)
			$o .= '<as:obj_type>' . xmlify($r->type) . '</as:obj_type>' . "\r\n";
		if($r->id)
			$o .= '<id>' . xmlify($r->id) . '</id>' . "\r\n";
		if($r->title)
			$o .= '<title>' . xmlify($r->title) . '</title>' . "\r\n";
		if($r->links) {
			/** @FIXME !!! */
			if(substr($r->link,0,1) === '<') {
				if(strstr($r->link,'&') && (! strstr($r->link,'&amp;')))
					$r->link = str_replace('&','&amp;', $r->link);
				$r->link = preg_replace('/\<link(.*?)\"\>/','<link$1"/>',$r->link);
				$o .= $r->link;
			}
			else
				$o .= '<link rel="alternate" type="text/html" href="' . xmlify($r->link) . '" />' . "\r\n";
		}
		if($r->content)
			$o .= '<content type="html" >' . xmlify(bbcode($r->content)) . '</content>' . "\r\n";

		$o .= '</as:target>' . "\r\n";

		return $o;
	}

	return '';
}

/**
 * @param object $feed
 * @param array $item
 * @param[out] array $author
 * @return multitype:multitype: string NULL number Ambigous <NULL, string, number> Ambigous <mixed, string> Ambigous <multitype:multitype:string Ambigous <NULL, string>  , multitype:multitype:string unknown  > multitype:NULL unknown
 */
function get_atom_elements($feed, $item, &$author) {

	//$best_photo = array();

	$res = array();

	$found_author = $item->get_author();
	if($found_author) {
		$author['author_name'] = unxmlify($found_author->get_name());
		$author['author_link'] = unxmlify($found_author->get_link());
		$author['author_is_feed'] = false;
	}
	else {
		$author['author_name'] = unxmlify($feed->get_title());
		$author['author_link'] = unxmlify($feed->get_permalink());
		$author['author_is_feed'] = true;
	}

	if(substr($author['author_link'],-1,1) == '/')
		$author['author_link'] = substr($author['author_link'],0,-1);

	$res['mid'] = base64url_encode(unxmlify($item->get_id()));
	$res['title'] = unxmlify($item->get_title());
	$res['body'] = unxmlify($item->get_content());
	$res['plink'] = unxmlify($item->get_link(0));
	$res['item_rss'] = 1;


	// removing the content of the title if its identically to the body
	// This helps with auto generated titles e.g. from tumblr

	if (title_is_body($res["title"], $res["body"]))
		$res['title'] = "";

	if($res['plink'])
		$base_url = implode('/', array_slice(explode('/',$res['plink']),0,3));
	else
		$base_url = '';

	// look for a photo. We should check media size and find the best one,
	// but for now let's just find any author photo

	$rawauthor = $item->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_10,'author');

	if($rawauthor && $rawauthor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link']) {
		$base = $rawauthor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link'];
		foreach($base as $link) {
			if(!x($author, 'author_photo') || ! $author['author_photo']) {
				if($link['attribs']['']['rel'] === 'photo' || $link['attribs']['']['rel'] === 'avatar')
					$author['author_photo'] = unxmlify($link['attribs']['']['href']);
			}
		}
	}

	$rawactor = $item->get_item_tags(NAMESPACE_ACTIVITY, 'actor');

	if($rawactor && activity_match($rawactor[0]['child'][NAMESPACE_ACTIVITY]['obj_type'][0]['data'],ACTIVITY_OBJ_PERSON)) {
		$base = $rawactor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link'];
		if($base && count($base)) {
			foreach($base as $link) {
				if($link['attribs']['']['rel'] === 'alternate' && (! $res['author_link']))
					$author['author_link'] = unxmlify($link['attribs']['']['href']);
				if(!x($author, 'author_photo') || ! $author['author_photo']) {
					if($link['attribs']['']['rel'] === 'avatar' || $link['attribs']['']['rel'] === 'photo')
						$author['author_photo'] = unxmlify($link['attribs']['']['href']);
				}
			}
		}
	}

	// check for a yahoo media element (github etc.)

	if(! $author['author_photo']) {
		$rawmedia = $item->get_item_tags(NAMESPACE_YMEDIA,'thumbnail');
		if($rawmedia && $rawmedia[0]['attribs']['']['url']) {
			$author['author_photo'] = strip_tags(unxmlify($rawmedia[0]['attribs']['']['url']));
		}
	}


	// No photo/profile-link on the item - look at the feed level

	if((! (x($author,'author_link'))) || (! (x($author,'author_photo')))) {
		$rawauthor = $feed->get_feed_tags(SIMPLEPIE_NAMESPACE_ATOM_10,'author');
		if($rawauthor && $rawauthor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link']) {
			$base = $rawauthor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link'];
			foreach($base as $link) {
				if($link['attribs']['']['rel'] === 'alternate' && (! $author['author_link'])) {
					$author['author_link'] = unxmlify($link['attribs']['']['href']);
					$author['author_is_feed'] = true;
				}
				if(! $author['author_photo']) {
					if($link['attribs']['']['rel'] === 'photo' || $link['attribs']['']['rel'] === 'avatar')
						$author['author_photo'] = unxmlify($link['attribs']['']['href']);
				}
			}
		}

		$rawactor = $feed->get_feed_tags(NAMESPACE_ACTIVITY, 'subject');

		if($rawactor && activity_match($rawactor[0]['child'][NAMESPACE_ACTIVITY]['obj_type'][0]['data'],ACTIVITY_OBJ_PERSON)) {
			$base = $rawactor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link'];

			if($base && count($base)) {
				foreach($base as $link) {
					if($link['attribs']['']['rel'] === 'alternate' && (! $res['author_link']))
						$author['author_link'] = unxmlify($link['attribs']['']['href']);
					if(! (x($author,'author_photo'))) {
						if($link['attribs']['']['rel'] === 'avatar' || $link['attribs']['']['rel'] === 'photo')
							$author['author_photo'] = unxmlify($link['attribs']['']['href']);
					}
				}
			}
		}
	}

	$apps = $item->get_item_tags(NAMESPACE_STATUSNET,'notice_info');
	if($apps && $apps[0]['attribs']['']['source']) {
		$res['app'] = strip_tags(unxmlify($apps[0]['attribs']['']['source']));
	}

	/*
	 * If there's a copy of the body content which is guaranteed to have survived mangling in transit, use it.
	 */

	$have_real_body = false;

	$rawenv = $item->get_item_tags(NAMESPACE_DFRN, 'env');
	if($rawenv) {
		$have_real_body = true;
		$res['body'] = $rawenv[0]['data'];
		$res['body'] = str_replace(array(' ',"\t","\r","\n"), array('','','',''),$res['body']);
		// make sure nobody is trying to sneak some html tags by us
		$res['body'] = notags(base64url_decode($res['body']));

		// We could probably turn these old Friendica bbcode bookmarks into bookmark tags but we'd have to
		// create a term table item for them. For now just make sure they stay as links.

		$res['body'] = preg_replace('/\[bookmark(.*?)\](.*?)\[\/bookmark\]/','[url$1]$2[/url]',$res['body']);
	}

	$res['body'] = limit_body_size($res['body']);

	// It isn't certain at this point whether our content is plaintext or html and we'd be foolish to trust
	// the content type. Our own network only emits text normally, though it might have been converted to
	// html if we used a pubsubhubbub transport. But if we see even one html tag in our text, we will
	// have to assume it is all html and needs to be purified.

	// It doesn't matter all that much security wise - because before this content is used anywhere, we are
	// going to escape any tags we find regardless, but this lets us import a limited subset of html from
	// the wild, by sanitising it and converting supported tags to bbcode before we rip out any remaining
	// html.

	if((strpos($res['body'],'<') !== false) && (strpos($res['body'],'>') !== false)) {

		$res['body'] = reltoabs($res['body'],$base_url);

		$res['body'] = html2bb_video($res['body']);

		$res['body'] = oembed_html2bbcode($res['body']);

		$res['body'] = purify_html($res['body']);

		$res['body'] = @html2bbcode($res['body']);
	}
	elseif(! $have_real_body) {

		// it's not one of our messages and it has no tags
		// so it's probably just text. We'll escape it just to be safe.

		$res['body'] = escape_tags($res['body']);
	}

	if($res['plink'] && $res['title']) {
		$res['body'] = '#^[url=' . $res['plink'] . ']' . $res['title'] . '[/url]' . "\n\n" . $res['body'];
		$terms = array();
		$terms[] = array(
			'otype' => TERM_OBJ_POST,
			'ttype' => TERM_BOOKMARK,
			'url'   => $res['plink'],
			'term'  => $res['title'],
		);
	}
	elseif($res['plink']) {
		$res['body'] = '#^[url]' . $res['plink'] . '[/url]' . "\n\n" . $res['body'];
		$terms = array();
		$terms[] = array(
			'otype' => TERM_OBJ_POST,
			'ttype' => TERM_BOOKMARK,
			'url'   => $res['plink'],
			'term'  => $res['plink'],
		);
	}

	$private = $item->get_item_tags(NAMESPACE_DFRN,'private');
	if($private && intval($private[0]['data']) > 0)
		$res['item_private'] = ((intval($private[0]['data'])) ? 1 : 0);
	else
		$res['item_private'] = 0;

	$rawlocation = $item->get_item_tags(NAMESPACE_DFRN, 'location');
	if($rawlocation)
		$res['location'] = unxmlify($rawlocation[0]['data']);

	$rawcreated = $item->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_10,'published');
	if($rawcreated)
		$res['created'] = unxmlify($rawcreated[0]['data']);

	$rawedited = $item->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_10,'updated');
	if($rawedited)
		$res['edited'] = unxmlify($rawedited[0]['data']);

	if((x($res,'edited')) && (! (x($res,'created'))))
		$res['created'] = $res['edited'];

	if(! $res['created'])
		$res['created'] = $item->get_date('c');

	if(! $res['edited'])
		$res['edited'] = $item->get_date('c');


	// Disallow time travelling posts

	$d1 = strtotime($res['created']);
	$d2 = strtotime($res['edited']);
	$d3 = strtotime('now');

	if($d1 > $d3)
		$res['created'] = datetime_convert();
	if($d2 > $d3)
		$res['edited'] = datetime_convert();

	$res['created'] = datetime_convert('UTC','UTC',$res['created']);
	$res['edited'] = datetime_convert('UTC','UTC',$res['edited']);

	$rawowner = $item->get_item_tags(NAMESPACE_DFRN, 'owner');
	if(! $rawowner)
		$rawowner = $item->get_item_tags(NAMESPACE_ZOT,'owner');

	if($rawowner[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['name'][0]['data'])
		$author['owner_name'] = unxmlify($rawowner[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['name'][0]['data']);
	elseif($rawowner[0]['child'][NAMESPACE_DFRN]['name'][0]['data'])
		$author['owner_name'] = unxmlify($rawowner[0]['child'][NAMESPACE_DFRN]['name'][0]['data']);
	if($rawowner[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['uri'][0]['data'])
		$author['owner_link'] = unxmlify($rawowner[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['uri'][0]['data']);
	elseif($rawowner[0]['child'][NAMESPACE_DFRN]['uri'][0]['data'])
		$author['owner_link'] = unxmlify($rawowner[0]['child'][NAMESPACE_DFRN]['uri'][0]['data']);

	if($rawowner[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link']) {
		$base = $rawowner[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link'];

		foreach($base as $link) {
			if(!x($author, 'owner_photo') || ! $author['owner_photo']) {
				if($link['attribs']['']['rel'] === 'photo' || $link['attribs']['']['rel'] === 'avatar')
					$author['owner_photo'] = unxmlify($link['attribs']['']['href']);
			}
		}
	}

	$rawgeo = $item->get_item_tags(NAMESPACE_GEORSS,'point');
	if($rawgeo)
		$res['coord'] = unxmlify($rawgeo[0]['data']);


	$rawverb = $item->get_item_tags(NAMESPACE_ACTIVITY, 'verb');

	// select between supported verbs

	if($rawverb) {
		$res['verb'] = unxmlify($rawverb[0]['data']);
	}

	// translate OStatus unfollow to activity streams if it happened to get selected

	if((x($res,'verb')) && ($res['verb'] === 'http://ostatus.org/schema/1.0/unfollow'))
		$res['verb'] = ACTIVITY_UNFOLLOW;

	$cats = $item->get_categories();
	if($cats) {
		if(is_null($terms))
			$terms = array();
		foreach($cats as $cat) {
			$term = $cat->get_term();
			if(! $term)
				$term = $cat->get_label();
			$scheme = $cat->get_scheme();
			$termurl = '';
			if($scheme && $term && stristr($scheme,'X-DFRN:')) {
				$termtype = ((substr($scheme,7,1) === '#') ? TERM_HASHTAG : TERM_MENTION);
				$termurl = unxmlify(substr($scheme,9));
			}
			else {
				$termtype = TERM_CATEGORY;
			}
			$termterm = notags(trim(unxmlify($term)));

			if($termterm) {
				$terms[] = array(
					'otype' => TERM_OBJ_POST,
					'ttype'  => $termtype,
					'url'   => $termurl,
					'term'  => $termterm,
				);
			}
		}
	}

	if(! is_null($terms))
		$res['term'] =  $terms;

	$attach = $item->get_enclosures();
	if($attach) {
		$res['attach'] = array();
		foreach($attach as $att) {
			$len   = intval($att->get_length());
			$link  = str_replace(array(',','"'),array('%2D','%22'),notags(trim(unxmlify($att->get_link()))));
			$title = str_replace(array(',','"'),array('%2D','%22'),notags(trim(unxmlify($att->get_title()))));
			$type  = str_replace(array(',','"'),array('%2D','%22'),notags(trim(unxmlify($att->get_type()))));
			if(strpos($type,';'))
				$type = substr($type,0,strpos($type,';'));
			if((! $link) || (strpos($link,'http') !== 0))
				continue;

			if(! $title)
				$title = ' ';
			if(! $type)
				$type = 'application/octet-stream';

			$res['attach'][] = array('href' => $link, 'length' => $len, 'type' => $type, 'title' => $title );
		}
	}

	$rawobj = $item->get_item_tags(NAMESPACE_ACTIVITY, 'object');

	if($rawobj) {
		$obj = array();

		$child = $rawobj[0]['child'];
		if($child[NAMESPACE_ACTIVITY]['obj_type'][0]['data']) {
			$res['obj_type'] = $child[NAMESPACE_ACTIVITY]['obj_type'][0]['data'];
			$obj['type'] = $child[NAMESPACE_ACTIVITY]['obj_type'][0]['data'];
		}
		if($child[NAMESPACE_ACTIVITY]['object-type'][0]['data']) {
			$res['obj_type'] = $child[NAMESPACE_ACTIVITY]['object-type'][0]['data'];
			$obj['type'] = $child[NAMESPACE_ACTIVITY]['object-type'][0]['data'];
		}
		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'id') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['id'][0]['data'])
			$obj['id'] = $child[SIMPLEPIE_NAMESPACE_ATOM_10]['id'][0]['data'];
		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'link') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['link'])
			$obj['link'] = encode_rel_links($child[SIMPLEPIE_NAMESPACE_ATOM_10]['link']);
		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'title') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['title'][0]['data'])
			$obj['title'] =  $child[SIMPLEPIE_NAMESPACE_ATOM_10]['title'][0]['data'];
		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'content') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['content'][0]['data']) {
			$body = $child[SIMPLEPIE_NAMESPACE_ATOM_10]['content'][0]['data'];
			if(! $body)
				$body = $child[SIMPLEPIE_NAMESPACE_ATOM_10]['summary'][0]['data'];
			// preserve a copy of the original body content in case we later need to parse out any microformat information, e.g. events
			$obj['orig'] = xmlify($body);
			if((strpos($body,'<') !== false) || (strpos($body,'>') !== false)) {
				$body = purify_html($body);
				$body = html2bbcode($body);
			}

			$obj['content'] = $body;
		}

		$res['obj'] = $obj;
	}

	$rawobj = $item->get_item_tags(NAMESPACE_ACTIVITY, 'target');

	if($rawobj) {
		$obj = array();

		$child = $rawobj[0]['child'];
		if($child[NAMESPACE_ACTIVITY]['obj_type'][0]['data']) {
			$res['tgt_type'] = $child[NAMESPACE_ACTIVITY]['obj_type'][0]['data'];
			$obj['type'] = $child[NAMESPACE_ACTIVITY]['obj_type'][0]['data'];
		}
		if($child[NAMESPACE_ACTIVITY]['object-type'][0]['data']) {
			$res['tgt_type'] = $child[NAMESPACE_ACTIVITY]['object-type'][0]['data'];
			$obj['type'] = $child[NAMESPACE_ACTIVITY]['object-type'][0]['data'];
		}
		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'id') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['id'][0]['data'])
			$obj['id'] = $child[SIMPLEPIE_NAMESPACE_ATOM_10]['id'][0]['data'];
		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'link') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['link'])
			$obj['link'] = encode_rel_links($child[SIMPLEPIE_NAMESPACE_ATOM_10]['link']);
		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'title') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['title'][0]['data'])
			$obj['title'] =  $child[SIMPLEPIE_NAMESPACE_ATOM_10]['title'][0]['data'];
		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'content') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['content'][0]['data']) {
			$body = $child[SIMPLEPIE_NAMESPACE_ATOM_10]['content'][0]['data'];
			if(! $body)
				$body = $child[SIMPLEPIE_NAMESPACE_ATOM_10]['summary'][0]['data'];

			// preserve a copy of the original body content in case we later need to parse out any microformat information, e.g. events
			$obj['orig'] = xmlify($body);
			if((strpos($body,'<') !== false) || (strpos($body,'>') !== false)) {
				$body = purify_html($body);
				$body = html2bbcode($body);
			}

			$obj['content'] = $body;
		}

		$res['target'] = $obj;
	}

	$arr = array('feed' => $feed, 'item' => $item, 'result' => $res);

	call_hooks('parse_atom', $arr);
	logger('get_atom_elements: author: ' . print_r($author,true),LOGGER_DATA);

	logger('get_atom_elements: ' . print_r($res,true),LOGGER_DATA);

	return $res;
}

function encode_rel_links($links) {
	$o = array();
	if(! ((is_array($links)) && (count($links))))
		return $o;

	foreach($links as $link) {
		$l = array();
		if($link['attribs']['']['rel'])
			$l['rel'] =  $link['attribs']['']['rel'];
		if($link['attribs']['']['type'])
			$l['type'] =  $link['attribs']['']['type'];
		if($link['attribs']['']['href'])
			$l['href'] = $link['attribs']['']['href'];
		if( (x($link['attribs'],NAMESPACE_MEDIA)) && $link['attribs'][NAMESPACE_MEDIA]['width'])
			$l['width'] = $link['attribs'][NAMESPACE_MEDIA]['width'];
		if( (x($link['attribs'],NAMESPACE_MEDIA)) && $link['attribs'][NAMESPACE_MEDIA]['height'])
			$l['height'] = $link['attribs'][NAMESPACE_MEDIA]['height'];

		if($l)
			$o[] = $l;
	}
	return $o;
}

/**
 * @brief Process atom feed and update anything/everything we might need to update.
 *
 * @param array $xml
 *   The (atom) feed to consume - RSS isn't as fully supported but may work for simple feeds.
 * @param $importer
 *   The contact_record (joined to user_record) of the local user who owns this
 *   relationship. It is this person's stuff that is going to be updated.
 * @param $contact
 *   The person who is sending us stuff. If not set, we MAY be processing a "follow" activity
 *   from an external network and MAY create an appropriate contact record. Otherwise, we MUST
 *   have a contact record.
 * @param int $pass by default ($pass = 0) we cannot guarantee that a parent item has been
 *   imported prior to its children being seen in the stream unless we are certain
 *   of how the feed is arranged/ordered.
 *  * With $pass = 1, we only pull parent items out of the stream.
 *  * With $pass = 2, we only pull children (comments/likes).
 *
 * So running this twice, first with pass 1 and then with pass 2 will do the right
 * thing regardless of feed ordering. This won't be adequate in a fully-threaded
 * model where comments can have sub-threads. That would require some massive sorting
 * to get all the feed items into a mostly linear ordering, and might still require
 * recursion.
 */
function consume_feed($xml, $importer, &$contact, $pass = 0) {

	require_once('library/simplepie/simplepie.inc');

	if(! strlen($xml)) {
		logger('consume_feed: empty input');
		return;
	}

	$sys_expire = intval(get_config('system','default_expire_days'));
	$chn_expire = intval($importer['channel_expire_days']);

	$expire_days = $sys_expire;

	if(($chn_expire != 0) && ($chn_expire < $sys_expire))
		$expire_days = $chn_expire;

	// logger('expire_days: ' . $expire_days);

	$feed = new SimplePie();
	$feed->set_raw_data($xml);
	$feed->init();

	if($feed->error())
		logger('consume_feed: Error parsing XML: ' . $feed->error());

	$permalink = $feed->get_permalink();

	// Check at the feed level for updated contact name and/or photo

	// process any deleted entries

	$del_entries = $feed->get_feed_tags(NAMESPACE_TOMB, 'deleted-entry');
	if(is_array($del_entries) && count($del_entries) && $pass != 2) {
		foreach($del_entries as $dentry) {
			$deleted = false;
			if(isset($dentry['attribs']['']['ref'])) {
				$mid = $dentry['attribs']['']['ref'];
				$deleted = true;
				if(isset($dentry['attribs']['']['when'])) {
					$when = $dentry['attribs']['']['when'];
					$when = datetime_convert('UTC','UTC', $when, 'Y-m-d H:i:s');
				}
				else
					$when = datetime_convert('UTC','UTC','now','Y-m-d H:i:s');
			}

			if($deleted && is_array($contact)) {
				$r = q("SELECT * from item where mid = '%s' and author_xchan = '%s' and uid = %d limit 1",
					dbesc(base64url_encode($mid)),
					dbesc($contact['xchan_hash']),
					intval($importer['channel_id'])
				);

				if($r) {
					$item = $r[0];

					if(! intval($item['item_deleted'])) {
						logger('consume_feed: deleting item ' . $item['id'] . ' mid=' . base64url_decode($item['mid']), LOGGER_DEBUG);
						drop_item($item['id'],false);
					}
				}
			}
		}
	}

	// Now process the feed

	if($feed->get_item_quantity()) {

		logger('consume_feed: feed item count = ' . $feed->get_item_quantity(), LOGGER_DEBUG);

		$items = $feed->get_items();

		foreach($items as $item) {

			$is_reply = false;
			$item_id = base64url_encode($item->get_id());

			logger('consume_feed: processing ' . $item_id, LOGGER_DEBUG);

			$rawthread = $item->get_item_tags( NAMESPACE_THREAD,'in-reply-to');
			if(isset($rawthread[0]['attribs']['']['ref'])) {
				$is_reply = true;
				$parent_mid = base64url_encode($rawthread[0]['attribs']['']['ref']);
			}

			if($is_reply) {

				if($pass == 1)
					continue;

				// Have we seen it? If not, import it.

				$item_id  = base64url_encode($item->get_id());
				$author = array();
				$datarray = get_atom_elements($feed,$item,$author);

				if($contact['xchan_network'] === 'rss') {
					$datarray['public_policy'] = 'specific';
					$datarray['comment_policy'] = 'none';
				}

				if((! x($author,'author_name')) || ($author['author_is_feed']))
					$author['author_name'] = $contact['xchan_name'];
				if((! x($author,'author_link')) || ($author['author_is_feed']))
					$author['author_link'] = $contact['xchan_url'];
				if((! x($author,'author_photo'))|| ($author['author_is_feed']))
					$author['author_photo'] = $contact['xchan_photo_m'];

				$datarray['author_xchan'] = '';

				if($author['author_link'] != $contact['xchan_url']) {
					$x = import_author_unknown(array('name' => $author['author_name'],'url' => $author['author_link'],'photo' => array('src' => $author['author_photo'])));
					if($x)
						$datarray['author_xchan'] = $x;
				}
				if(! $datarray['author_xchan'])
					$datarray['author_xchan'] = $contact['xchan_hash'];

				$datarray['owner_xchan'] = $contact['xchan_hash'];

				$r = q("SELECT edited FROM item WHERE mid = '%s' AND uid = %d LIMIT 1",
					dbesc($item_id),
					intval($importer['channel_id'])
				);


				// Update content if 'updated' changes

				if($r) {
					if((x($datarray,'edited') !== false)
						&& (datetime_convert('UTC','UTC',$datarray['edited']) !== $r[0]['edited'])) {

						// do not accept (ignore) an earlier edit than one we currently have.
						if(datetime_convert('UTC','UTC',$datarray['edited']) < $r[0]['edited'])
							continue;

						update_feed_item($importer['channel_id'],$datarray);
					}
					continue;
				}

				$datarray['parent_mid'] = $parent_mid;
				$datarray['aid'] = $importer['channel_account_id'];
				$datarray['uid'] = $importer['channel_id'];

				logger('consume_feed: ' . print_r($datarray,true),LOGGER_DATA);

				$xx = item_store($datarray);
				$r = $xx['item_id'];
				continue;
			}
			else {

				// Head post of a conversation. Have we seen it? If not, import it.

				$item_id  = base64url_encode($item->get_id());
				$author = array();
				$datarray = get_atom_elements($feed,$item,$author);

				if($contact['xchan_network'] === 'rss') {
					$datarray['public_policy'] = 'specific';
					$datarray['comment_policy'] = 'none';
				}


				if(is_array($contact)) {
					if((! x($author,'author_name')) || ($author['author_is_feed']))
						$author['author_name'] = $contact['xchan_name'];
					if((! x($author,'author_link')) || ($author['author_is_feed']))
						$author['author_link'] = $contact['xchan_url'];
					if((! x($author,'author_photo'))|| ($author['author_is_feed']))
						$author['author_photo'] = $contact['xchan_photo_m'];
				}

				if((! x($author,'author_name')) || (! x($author,'author_link'))) {
					logger('consume_feed: no author information! ' . print_r($author,true));
					continue;
				}

				$datarray['author_xchan'] = '';

				if(activity_match($datarray['verb'],ACTIVITY_FOLLOW) && $datarray['obj_type'] === ACTIVITY_OBJ_PERSON) {
					$cb = array('item' => $datarray,'channel' => $importer, 'xchan' => null, 'author' => $author, 'caught' => false);
					call_hooks('follow_from_feed',$cb);
					if($cb['caught']) {
						if($cb['return_code'])
							http_status_exit($cb['return_code']);
						continue;
					}
				}

				if($author['author_link'] != $contact['xchan_url']) {
					$x = import_author_unknown(array('name' => $author['author_name'],'url' => $author['author_link'],'photo' => array('src' => $author['author_photo'])));
					if($x)
						$datarray['author_xchan'] = $x;
				}
				if(! $datarray['author_xchan'])
					$datarray['author_xchan'] = $contact['xchan_hash'];

				$datarray['owner_xchan'] = $contact['xchan_hash'];

				if(array_key_exists('created',$datarray) && $datarray['created'] > NULL_DATE && $expire_days) {
					$t1 = $datarray['created'];
					$t2 = datetime_convert('UTC','UTC','now - ' . $expire_days . 'days');
					if($t1 < $t2) {
						logger('feed content older than expiration. Ignoring.', LOGGER_DEBUG, LOG_INFO);
						continue;
					}
				}



				$r = q("SELECT edited FROM item WHERE mid = '%s' AND uid = %d LIMIT 1",
					dbesc($item_id),
					intval($importer['channel_id'])
				);

				// Update content if 'updated' changes

				if($r) {
					if((x($datarray,'edited') !== false)
						&& (datetime_convert('UTC','UTC',$datarray['edited']) !== $r[0]['edited'])) {

						// do not accept (ignore) an earlier edit than one we currently have.
						if(datetime_convert('UTC','UTC',$datarray['edited']) < $r[0]['edited'])
							continue;

						update_feed_item($importer['channel_id'],$datarray);
					}

					continue;
				}

				$datarray['parent_mid'] = $item_id;
				$datarray['uid'] = $importer['channel_id'];
				$datarray['aid'] = $importer['channel_account_id'];

				if(! link_compare($author['owner_link'],$contact['xchan_url'])) {
					logger('consume_feed: Correcting item owner.', LOGGER_DEBUG);
					$author['owner_name']   = $contact['name'];
					$author['owner_link']   = $contact['url'];
					$author['owner_avatar'] = $contact['thumb'];
				}

				if(! post_is_importable($datarray,$contact))
					continue;

				logger('consume_feed: author ' . print_r($author,true),LOGGER_DEBUG);

				logger('consume_feed: ' . print_r($datarray,true),LOGGER_DATA);

				$xx = item_store($datarray);
				$r = $xx['item_id'];
				continue;
			}
		}
	}
}


/**
 * @brief Process atom feed and return the first post and structure
 *
 * @param array $xml
 *   The (atom) feed to consume - RSS isn't as fully supported but may work for simple feeds.
 * @param $importer
 *   The contact_record (joined to user_record) of the local user who owns this
 *   relationship. It is this person's stuff that is going to be updated.
 */

function process_salmon_feed($xml, $importer) {

	$ret = array();

	require_once('library/simplepie/simplepie.inc');

	if(! strlen($xml)) {
		logger('process_feed: empty input');
		return;
	}

	$feed = new SimplePie();
	$feed->set_raw_data($xml);
	$feed->init();

	if($feed->error())
		logger('Error parsing XML: ' . $feed->error());

	$permalink = $feed->get_permalink();

	if($feed->get_item_quantity()) {

		// this should be exactly one

		logger('feed item count = ' . $feed->get_item_quantity(), LOGGER_DEBUG);

		$items = $feed->get_items();

		foreach($items as $item) {

			$item_id = base64url_encode($item->get_id());

			logger('processing ' . $item_id, LOGGER_DEBUG);

			$rawthread = $item->get_item_tags( NAMESPACE_THREAD,'in-reply-to');
			if(isset($rawthread[0]['attribs']['']['ref'])) {
				$is_reply = true;
				$parent_mid = base64url_encode($rawthread[0]['attribs']['']['ref']);
			}

			if($is_reply)
				$ret['parent_mid'] = $parent_mid;

			$ret['author'] = array();

			$datarray = get_atom_elements($feed,$item,$ret['author']);

			// reset policies which are restricted by default for RSS connections
			// This item is likely coming from GNU-social via salmon and allows public interaction 
			$datarray['public_policy'] = '';
			$datarray['comment_policy'] = '';

			$ret['item'] = $datarray;			
		}
	}

	return $ret;
}

/*
 * Given an xml (atom) feed, find author and hub links
 */


function feed_meta($xml) {
	require_once('library/simplepie/simplepie.inc');

	$ret = array();

    if(! strlen($xml)) {
        logger('empty input');
        return $ret;
    }

    $feed = new SimplePie();
    $feed->set_raw_data($xml);
    $feed->init();

    if($feed->error()) {
        logger('Error parsing XML: ' . $feed->error());
		return $ret;
	}

    $ret['hubs'] = $feed->get_links('hub');

//     logger('consume_feed: hubs: ' . print_r($hubs,true), LOGGER_DATA);
	
	$author = array();

    $found_author = $feed->get_author();
    if($found_author) {
        $author['author_name'] = unxmlify($found_author->get_name());
        $author['author_link'] = unxmlify($found_author->get_link());

    	$rawauthor = $feed->get_feed_tags(SIMPLEPIE_NAMESPACE_ATOM_10,'author');
		logger('rawauthor: ' . print_r($rawauthor,true));

	    if($rawauthor) {
			if($rawauthor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link']) {
	    	    $base = $rawauthor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link'];
    	    	foreach($base as $link) {
        	    	if(!x($author, 'author_photo') || ! $author['author_photo']) {
            	    	if($link['attribs']['']['rel'] === 'photo' || $link['attribs']['']['rel'] === 'avatar') {
                	    	$author['author_photo'] = unxmlify($link['attribs']['']['href']);
							break;
						}
    	        	}
        		}
			}
			if($rawauthor[0]['child'][NAMESPACE_POCO]['displayName'][0]['data'])
				$author['full_name'] = unxmlify($rawauthor[0]['child'][NAMESPACE_POCO]['displayName'][0]['data']);
			if($rawauthor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['uri'][0]['data'])
				$author['author_uri'] = unxmlify($rawauthor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['uri'][0]['data']);
			
		}
    }

    if(substr($author['author_link'],-1,1) == '/')
        $author['author_link'] = substr($author['author_link'],0,-1);

	$ret['author'] = $author; 

	return $ret;
}



function update_feed_item($uid,$datarray) {
	logger('update_feed_item: not implemented! ' . $uid . ' ' . print_r($datarray,true), LOGGER_DATA);
}


function handle_feed($uid,$abook_id,$url) {

	$channel = channelx_by_n($uid);
	if(! $channel)
		return;

	$x = q("select * from abook left join xchan on abook_xchan = xchan_hash where abook_id = %d and abook_channel = %d limit 1",
		dbesc($abook_id),
		intval($uid)
	);

	$recurse = 0;
	$z = z_fetch_url($url,false,$recurse,array('novalidate' => true));

//logger('handle_feed:' . print_r($z,true));

	if($z['success']) {
		consume_feed($z['body'],$channel,$x[0],1);
		consume_feed($z['body'],$channel,$x[0],2);
	}
}


function atom_author($tag,$name,$uri,$h,$w,$type,$photo) {
	$o = '';
	if(! $tag)
		return $o;

	$name = xmlify($name);
	$uri = xmlify($uri);
	$h = intval($h);
	$w = intval($w);
	$photo = xmlify($photo);

	$o .= "<$tag>\r\n";
	$o .= "<name>$name</name>\r\n";
	$o .= "<uri>$uri</uri>\r\n";
	$o .= '<link rel="photo"  type="' . $type . '" media:width="' . $w . '" media:height="' . $h . '" href="' . $photo . '" />' . "\r\n";
	$o .= '<link rel="avatar" type="' . $type . '" media:width="' . $w . '" media:height="' . $h . '" href="' . $photo . '" />' . "\r\n";

	call_hooks('atom_author', $o);

	$o .= "</$tag>\r\n";

	return $o;
}

function atom_entry($item,$type,$author,$owner,$comment = false,$cid = 0) {

	if(! $item['parent'])
		return;

	if($item['deleted'])
		return '<at:deleted-entry ref="' . xmlify($item['mid']) . '" when="' . xmlify(datetime_convert('UTC','UTC',$item['edited'] . '+00:00',ATOM_TIME)) . '" />' . "\r\n";


	create_export_photo_body($item);

	if($item['allow_cid'] || $item['allow_gid'] || $item['deny_cid'] || $item['deny_gid'])
		$body = fix_private_photos($item['body'],$owner['uid'],$item,$cid);
	else
		$body = $item['body'];

	$o = "\r\n\r\n<entry>\r\n";

	if(is_array($author))
		$o .= atom_author('author',$author['xchan_name'],$author['xchan_url'],80,80,$author['xchan_photo_mimetype'],$author['xchan_photo_m']);
	else
		$o .= atom_author('author',$item['author']['xchan_name'],$item['author']['xchan_url'],80,80,$item['author']['xchan_photo_mimetype'], $item['author']['xchan_photo_m']);

	$o .= atom_author('zot:owner',$item['owner']['xchan_name'],$item['owner']['xchan_url'],80,80,$item['owner']['xchan_photo_mimetype'],$item['owner']['xchan_photo_m']);

	if(($item['parent'] != $item['id']) || ($item['parent_mid'] !== $item['mid']) || (($item['thr_parent'] !== '') && ($item['thr_parent'] !== $item['mid']))) {
		$parent_item = (($item['thr_parent']) ? $item['thr_parent'] : $item['parent_mid']);
		$o .= '<thr:in-reply-to ref="' . z_root() . '/display/' . xmlify($parent_item) . '" type="text/html" href="' .  xmlify($item['plink']) . '" />' . "\r\n";
	}

	if(activity_match($item['obj_type'],ACTIVITY_OBJ_EVENT) && activity_match($item['verb'],ACTIVITY_POST)) {
		$obj = ((is_array($item['obj'])) ? $item['obj'] : json_decode($item['obj'],true));
 
		$o .= '<title>' . xmlify($item['title']) . '</title>' . "\r\n";
		$o .= '<summary xmlns="urn:ietf:params:xml:ns:xcal">' . xmlify(bbcode($obj['title'])) . '</summary>' . "\r\n";
		$o .= '<dtstart xmlns="urn:ietf:params:xml:ns:xcal">' . datetime_convert('UTC','UTC', $obj['dtstart'],'Ymd\\THis' . (($obj['adjust']) ? '\\Z' : '')) .  '</dtstart>' . "\r\n";
		$o .= '<dtend xmlns="urn:ietf:params:xml:ns:xcal">' . datetime_convert('UTC','UTC', $obj['dtend'],'Ymd\\THis' . (($obj['adjust']) ? '\\Z' : '')) .  '</dtend>' . "\r\n";
		$o .= '<location xmlns="urn:ietf:params:xml:ns:xcal">' . xmlify(bbcode($obj['location'])) . '</location>' . "\r\n";
		$o .= '<content type="' . $type . '" >' . xmlify(bbcode($obj['description'])) . '</content>' . "\r\n";
	}
	else {
		$o .= '<title>' . xmlify($item['title']) . '</title>' . "\r\n";
		$o .= '<content type="' . $type . '" >' . xmlify(prepare_text($body,$item['mimetype'])) . '</content>' . "\r\n";
	}

	$o .= '<id>' . z_root() . '/display/' . xmlify($item['mid']) . '</id>' . "\r\n";
	$o .= '<published>' . xmlify(datetime_convert('UTC','UTC',$item['created'] . '+00:00',ATOM_TIME)) . '</published>' . "\r\n";
	$o .= '<updated>' . xmlify(datetime_convert('UTC','UTC',$item['edited'] . '+00:00',ATOM_TIME)) . '</updated>' . "\r\n";

	$o .= '<link rel="alternate" type="text/html" href="' . xmlify($item['plink']) . '" />' . "\r\n";

	if($item['location']) {
		$o .= '<zot:location>' . xmlify($item['location']) . '</zot:location>' . "\r\n";
		$o .= '<poco:address><poco:formatted>' . xmlify($item['location']) . '</poco:formatted></poco:address>' . "\r\n";
	}

	if($item['coord'])
		$o .= '<georss:point>' . xmlify($item['coord']) . '</georss:point>' . "\r\n";

	if(($item['item_private']) || strlen($item['allow_cid']) || strlen($item['allow_gid']) || strlen($item['deny_cid']) || strlen($item['deny_gid']))
		$o .= '<zot:private>' . (($item['item_private']) ? $item['item_private'] : 1) . '</zot:private>' . "\r\n";

	if($item['app'])
		$o .= '<statusnet:notice_info local_id="' . $item['id'] . '" source="' . xmlify($item['app']) . '" ></statusnet:notice_info>' . "\r\n";

	$verb = construct_verb($item);
	$o .= '<as:verb>' . xmlify($verb) . '</as:verb>' . "\r\n";
	$actobj = construct_activity_object($item);
	if(strlen($actobj))
		$o .= $actobj;
	$actarg = construct_activity_target($item);
	if(strlen($actarg))
		$o .= $actarg;

	// FIXME
//	$tags = item_getfeedtags($item);
//	if(count($tags)) {
//		foreach($tags as $t) {
//			$o .= '<category scheme="X-DFRN:' . xmlify($t[0]) . ':' . xmlify($t[1]) . '" term="' . xmlify($t[2]) . '" />' . "\r\n";
//		}
//	}

// FIXME
//	$o .= item_getfeedattach($item);

//	$mentioned = get_mentions($item,$tags);
//	if($mentioned)
//		$o .= $mentioned;

	call_hooks('atom_entry', $o);

	$o .= '</entry>' . "\r\n";

	return $o;
}


function gen_asld($items) {
	$ret = array();
	if(! $items)
		return $ret;
	foreach($items as $item) {
		$ret[] = i2asld($item);
	}
	return $ret;
}


function i2asld($i) {

	if(! $i)
		return array();

	$ret = array();

	$ret['@context'] = array( 'http://www.w3.org/ns/activitystreams', 'zot' => 'http://purl.org/zot/protocol');

	if($i['verb']) {
		if(strpos(dirname($i['verb'],'activitystrea.ms/schema/1.0'))) {
			$ret['@type'] = ucfirst(basename($i['verb']));
		}
		elseif(strpos(dirname($i['verb'],'purl.org/zot'))) {
			$ret['@type'] = 'zot:' . ucfirst(basename($i['verb']));
		}
	}
	$ret['@id'] = $i['plink'];

	$ret['published'] = datetime_convert('UTC','UTC',$i['created'],ATOM_TIME);

	// we need to pass the parent into this
//	if($i['id'] != $i['parent'] && $i['obj_type'] === ACTIVITY_OBJ_NOTE) {
//		$ret['inReplyTo'] = asencode_note
//	}

	if($i['obj_type'] === ACTIVITY_OBJ_NOTE)
		$ret['object'] = asencode_note($i);


	$ret['actor'] = asencode_person($i['author']);

	
	return $ret;
	
}

function asencode_note($i) {

	$ret = array();

	$ret['@type'] = 'Note';
	$ret['@id'] = $i['plink'];
	if($i['title'])
		$ret['title'] = bbcode($i['title']);
	$ret['content'] = bbcode($i['body']);
	$ret['zot:owner'] = asencode_person($i['owner']);
	$ret['published'] = datetime_convert('UTC','UTC',$i['created'],ATOM_TIME);
	if($i['created'] !== $i['edited'])
		$ret['updated'] = datetime_convert('UTC','UTC',$i['edited'],ATOM_TIME);

	return $ret;
}


function asencode_person($p) {
	$ret = array();
	$ret['@type'] = 'Person';
	$ret['@id'] = 'acct:' . $p['xchan_addr'];
	$ret['displayName'] = $p['xchan_name'];
	$ret['icon'] = array(
		'@type' => 'Link',
		'mediaType' => $p['xchan_photo_mimetype'],
		'href' => $p['xchan_photo_m']
	);
	$ret['url'] = array(
		'@type' => 'Link',
		'mediaType' => 'text/html',
		'href' => $p['xchan_url']
	);

	return $ret;
}
