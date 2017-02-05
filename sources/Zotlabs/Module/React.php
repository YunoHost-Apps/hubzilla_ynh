<?php

namespace Zotlabs\Module;


class React extends \Zotlabs\Web\Controller {

	function get() {
		if(! local_channel())
			return;

		$postid = $_REQUEST['postid'];

		if(! $postid)
			return;

		$emoji = $_REQUEST['emoji'];
		if($_REQUEST['emoji']) {

			$i = q("select * from item where id = %d and uid = %d",
				intval($postid),
				intval(local_channel())
			);

			if(! $i)
				return;

			$channel = \App::get_channel();

			$n = array();
			$n['aid'] = $channel['channel_account_id'];
			$n['uid'] = $channel['channel_id'];
			$n['item_origin'] = true;
			$n['parent'] = $postid;
			$n['parent_mid'] = $i[0]['mid'];
			$n['mid'] = item_message_id();
			$n['verb'] = ACTIVITY_REACT . '#' . $emoji;
			$n['body'] = "\n\n[zmg=32x32]" . z_root() . '/images/emoji/' . $emoji . '.png[/zmg]' . "\n\n";
			$n['author_xchan'] = $channel['channel_hash'];

			$x = item_store($n); 
			if($x['success']) {
				$nid = $x['item_id'];
				 \Zotlabs\Daemon\Master::Summon(array('Notifier','like',$nid));
			}

		}

	}

}