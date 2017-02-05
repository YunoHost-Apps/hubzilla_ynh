<?php
namespace Zotlabs\Module;

require_once('include/security.php');
require_once('include/bbcode.php');
require_once('include/items.php');



class Subthread extends \Zotlabs\Web\Controller {

	function get() {
	
		if((! local_channel()) && (! remote_channel())) {
			return;
		}
	
		$item_id = ((argc() > 2) ? notags(trim(argv(2))) : 0);
	
		if(argv(1) === 'sub')
			$activity = ACTIVITY_FOLLOW;
		elseif(argv(1) === 'unsub')
			$activity = ACTIVITY_UNFOLLOW;
	
	
		$r = q("SELECT parent FROM item WHERE id = '%s'",
			dbesc($item_id)
		);
	
		if($r) {
			$r = q("select * from item where id = parent and id = %d limit 1",
				dbesc($r[0]['parent'])
			);
		}
	
		if((! $item_id) || (! $r)) {
			logger('subthread: no item ' . $item_id);
			return;
		}
	
		$item = $r[0];
	
		$owner_uid = $item['uid'];
		$observer = \App::get_observer();
		$ob_hash = (($observer) ? $observer['xchan_hash'] : '');
	
		if(! perm_is_allowed($owner_uid,$ob_hash,'post_comments'))
			return;
	
		$sys = get_sys_channel();
	
		$owner_uid = $item['uid'];
		$owner_aid = $item['aid'];
	
		// if this is a "discover" item, (item['uid'] is the sys channel),
		// fallback to the item comment policy, which should've been
		// respected when generating the conversation thread.
		// Even if the activity is rejected by the item owner, it should still get attached
		// to the local discover conversation on this site. 
	
		if(($owner_uid != $sys['channel_id']) && (! perm_is_allowed($owner_uid,$observer['xchan_hash'],'post_comments'))) {
				notice( t('Permission denied') . EOL);
				killme();
		}
	
		$r = q("select * from xchan where xchan_hash = '%s' limit 1",
			dbesc($item['owner_xchan'])
		);
		if($r)
			$thread_owner = $r[0];
		else
			killme();
	
		$r = q("select * from xchan where xchan_hash = '%s' limit 1",
			dbesc($item['author_xchan'])
		);
		if($r)
			$item_author = $r[0];
		else
			killme();
	
	
	
	
		$mid = item_message_id();
	
		$post_type = (($item['resource_type'] === 'photo') ? t('photo') : t('status'));
	
		$links = array(array('rel' => 'alternate','type' => 'text/html', 'href' => $item['plink']));
		$objtype = (($item['resource_type'] === 'photo') ? ACTIVITY_OBJ_PHOTO : ACTIVITY_OBJ_NOTE ); 
	
		$body = $item['body'];
	
		$obj = json_encode(array(
			'type'    => $objtype,
			'id'      => $item['mid'],
			'parent'  => (($item['thr_parent']) ? $item['thr_parent'] : $item['parent_mid']),
			'link'    => $links,
			'title'   => $item['title'],
			'content' => $item['body'],
			'created' => $item['created'],
			'edited'  => $item['edited'],
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
	
		if(! intval($item['item_thread_top']))
			$post_type = 'comment';		
	
		if($activity === ACTIVITY_FOLLOW)
			$bodyverb = t('%1$s is following %2$s\'s %3$s');
		if($activity === ACTIVITY_UNFOLLOW)
			$bodyverb = t('%1$s stopped following %2$s\'s %3$s');
	
		$arr = array();
	
		$arr['mid']           = $mid;
		$arr['aid']           = $owner_aid;
		$arr['uid']           = $owner_uid;
		$arr['parent']        = $item['id'];
		$arr['parent_mid']    = $item['mid'];
		$arr['thr_parent']    = $item['mid'];
		$arr['owner_xchan']   = $thread_owner['xchan_hash'];
		$arr['author_xchan']  = $observer['xchan_hash'];
		$arr['item_origin']   = 1;
		$arr['item_notshown'] = 1;
		if(intval($item['item_wall']))
			$arr['item_wall'] = 1;
		else
			$arr['item_wall'] = 0;
	
		$ulink = '[zrl=' . $item_author['xchan_url'] . ']' . $item_author['xchan_name'] . '[/zrl]';
		$alink = '[zrl=' . $observer['xchan_url'] . ']' . $observer['xchan_name'] . '[/zrl]';
		$plink = '[zrl=' . z_root() . '/display/' . $item['mid'] . ']' . $post_type . '[/zrl]';
		
		$arr['body']          =  sprintf( $bodyverb, $alink, $ulink, $plink );
	
		$arr['verb']          = $activity;
		$arr['obj_type']      = $objtype;
		$arr['obj']           = $obj;
	
		$arr['allow_cid']     = $item['allow_cid'];
		$arr['allow_gid']     = $item['allow_gid'];
		$arr['deny_cid']      = $item['deny_cid'];
		$arr['deny_gid']      = $item['deny_gid'];
	
		$post = item_store($arr);	
		$post_id = $post['item_id'];
	
		$arr['id'] = $post_id;
	
		call_hooks('post_local_end', $arr);
	
		killme();
	
	
	}
	
	
	
	
}
