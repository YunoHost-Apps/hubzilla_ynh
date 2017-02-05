<?php
/**
 * Name: Randpost
 * Description: Make random posts/replies (requires fortunate and/or a fortunate server)
 * Version: 1.0
 * Author: Mike Macgirvin
 * Maintainer: none
 */

require_once('include/crypto.php');

function randpost_load() {
	register_hook('cron', 'addon/randpost/randpost.php', 'randpost_fetch');
	register_hook('enotify_store', 'addon/randpost/randpost.php', 'randpost_enotify_store');
}

function randpost_unload() {
	unregister_hook('cron', 'addon/randpost/randpost.php', 'randpost_fetch');
	unregister_hook('enotify_store', 'addon/randpost/randpost.php', 'randpost_enotify_store');
}


function randpost_enotify_store(&$a,&$b) {

	if(! ($b['ntype'] == NOTIFY_COMMENT || $b['ntype'] == NOTIFY_TAGSELF))
		return;

	if(! get_pconfig($b['uid'],'randpost','enable'))
		return;


	$fort_server = get_config('fortunate','server');
	if(! $fort_server)
		return;


	$c = q("select * from channel where channel_id = %d limit 1",
		intval($b['uid'])
	);
	if(! $c)
		return;

	$my_conversation = false;

	$p = q("select id, item_flags, author_xchan from item where parent_mid = mid and parent_mid = '%s' and uid = %d limit 1",
		dbesc($b['item']['parent_mid']),
		intval($b['uid'])
	);
	if(! $p)
		return;

	$p = fetch_post_tags($p,true);

	if(intval($p[0]['item_obscured']))
		return;


	if($b['ntype'] == NOTIFY_TAGSELF)
		$my_conversation = true;
	elseif($p[0]['author_xchan'] === $c[0]['channel_hash'])
		$my_conversation = true;
	elseif($p[0]['term']) {
		$v = get_terms_oftype($p[0]['term'],TERM_MENTION);
		$link = normalise_link(z_root() . '/channel/' . $c[0]['channel_address']);
		if($v) {
			foreach($v as $vv) {
				if(link_compare($vv['url'],$link)) {			
					$my_conversation = true;
					break;
				}
			}
		}				
	}
	
	// don't hijack somebody else's conversation, but respond (once) if invited to. 

	if(! $my_conversation)
		return;

	// This conversation is boring me.

	$limit = mt_rand(5,20);

	$h = q("select id, body from item where author_xchan = '%s' and parent_mid = '%s' and uid = %d",
		dbesc($c[0]['channel_hash']),
		dbesc($b['item']['parent_mid']),
		intval($b['uid'])
	);
	if($h && count($h) > $limit)
		return;

 
	
	// Be gracious and not obnoxious if thanked

	$replies = array(
		t('You\'re welcome.'),
		t('Ah shucks...'),
		t('Don\'t mention it.'),
		t('&lt;blush&gt;'),
		':like'
	);


	// TODO: if you really want to freak somebody out, add a relevance search function to mod_zotfeed and
	// use somebody's own words from long ago to craft a reply to them....

	require_once('include/bbcode.php');
	require_once('include/html2plain.php');

	if($b['item'] && $b['item']['body']) {
		if(stristr($b['item']['body'],'nocomment'))
			return;
		$txt = preg_replace('/\@\[z(.*?)\[\/zrl\]/','',$b['item']['body']);
		$txt = html2plain(bbcode($txt));
		$pattern = substr($txt,0,255);
	}

	if($b['item']['author_xchan']) {
		$z = q("select * from xchan where xchan_hash = '%s' limit 1",
			dbesc($b['item']['author_xchan'])
		);
		if($z) {
			$mention = '@' . '[zrl=' . $z[0]['xchan_url'] . ']' . $z[0]['xchan_name'] . '[/zrl]' . "\n\n";
		}
	}

	if(stristr($b['item']['body'],$c[0]['channel_name']) && mb_strlen($pattern) < 36 && stristr($pattern,'thank')) {
		$reply = $replies[mt_rand(0,count($replies)-1)];
	}

	$x = array();

	if($reply) {
		$x['body'] = $mention . $reply;
	}
	else {
		require_once('include/html2bbcode.php');

		$valid = false;

		do {
			$url = 'http://' . $fort_server . '/cookie.php?f=&lang=any&off=a&pattern=' . urlencode($pattern);

			$s = z_fetch_url($url);

			if($s['success'] && (! $s['body']))
				$s = z_fetch_url('http://' . $fort_server . '/cookie.php');

			if((! $s['success']) || (! $s['body']))
				return;

			// if it might be a quote make it a quote
			if(strpos($s['body'],'--'))
				$x['body'] = '[quote]' . html2bbcode($s['body']) . '[/quote]';
			else
				$x['body'] = html2bbcode($s['body']);

			$found_text = false;

			if($h) {
				foreach($h as $hh) {
					if(stripos($hh['body'],$x['body']) !== false) {
						$pattern = '';
						$found_text = true;
						break;
					}
				}
			}
			if(! $found_text)
				$valid = true;

		}
		while(! $valid);
	}

	if($mention) {
		$x['body'] = $mention . $x['body'];

		$x['term'] = array(array(
			'uid' => $c[0]['channel_id'],
			'type' => TERM_MENTION,
			'otype' => TERM_OBJ_POST,
			'term' => $z[0]['xchan_name'],
			'url' => $z[0]['xchan_url']
		));
	}

	$x['uid'] = $c[0]['channel_id'];
	$x['aid'] = $c[0]['channel_account_id'];
	$x['mid'] = item_message_id();
	$x['parent'] = $p[0]['id'];
	$x['parent_mid'] = $b['item']['parent_mid'];
	$x['author_xchan'] = $c[0]['channel_hash'];
	$x['owner_xchan'] = $b['item']['owner_xchan'];

	$x['item_origin'] = 1;
	$x['item_verified'] = 1;

	// You can't pass a Turing test if you reply in milliseconds. 
	// Also I believe we've got ten minutes fudge before we declare a post as time traveling.
	// Otherwise we'll just set it to now and it will still go out in milliseconds. 
	// So set the reply to post sometime in the next 15-45 minutes (depends on poller interval)

	$fudge = mt_rand(15,30);
	$x['created'] = $x['edited'] = datetime_convert('UTC','UTC','now + ' . $fudge . ' minutes');

	$x['body'] = trim($x['body']);
	$x['sig'] = base64url_encode(rsa_sign($x['body'],$c[0]['channel_prvkey']));

	$post = item_store($x);
	$post_id = $post['item_id'];

	$x['id'] = $post_id;

	call_hooks('post_local_end', $x);

	Zotlabs\Daemon\Master::Summon(array('Notifier','comment-new',$post_id));	

}



function randpost_fetch(&$a,&$b) {

	$fort_server = get_config('fortunate','server');
	if(! $fort_server)
		return;

	$r = q("select * from pconfig where cat = 'randpost' and k = 'enable'");

	if($r) {
		foreach($r as $rr) {
			if(! $rr['v'])
				continue;
//			logger('randpost');

			// cronhooks run every 10-15 minutes typically
			// try to keep from posting frequently.

			$test = mt_rand(0,100);
			if($test == 25) {
				$c = q("select * from channel where channel_id = %d limit 1",
					intval($rr['uid'])
				);
				if(! $c)
					continue;

				$mention = '';

				require_once('include/html2bbcode.php');

				$s = z_fetch_url('http://' . $fort_server . '/cookie.php?numlines=2&equal=1&rand=' . mt_rand());
				if(! $s['success'])
					continue;

				$x = array();
				$x['uid'] = $c[0]['channel_id'];
				$x['aid'] = $c[0]['channel_account_id'];
				$x['mid'] = $x['parent_mid'] = item_message_id();
				$x['author_xchan'] = $x['owner_xchan'] = $c[0]['channel_hash'];
				$x['item_thread_top'] = 1;
				$x['item_origin'] = 1;
				$x['item_verified'] = 1;
				$x['item_wall'] = 1;

				// if it might be a quote make it a quote
				if(strpos($s['body'],'--'))
					$x['body'] = $mention . '[quote]' . html2bbcode($s['body']) . '[/quote]';
				else
					$x['body'] = $mention . html2bbcode($s['body']);

				$x['sig'] = base64url_encode(rsa_sign($x['body'],$c[0]['channel_prvkey']));

				$post = item_store($x);
				$post_id = $post['item_id'];

				$x['id'] = $post_id;

				call_hooks('post_local_end', $x);

				Zotlabs\Daemon\Master::Summon(array('Notifier','wall-new',$post_id));
			}
		}
	}
}

