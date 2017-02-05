<?php
namespace Zotlabs\Module;

require_once('include/security.php');

class Lockview extends \Zotlabs\Web\Controller {

	function get() {

		$atokens = array();

		if(local_channel()) {
			$at = q("select * from atoken where atoken_uid = %d",
				intval(local_channel())
			);
			if($at) {
				foreach($at as $t) {
					$atokens[] = atoken_xchan($t);
				}
			}
		}
	  
		$type = ((argc() > 1) ? argv(1) : 0);
		if (is_numeric($type)) {
			$item_id = intval($type);
			$type='item';
		} 
		else {
			$item_id = ((argc() > 2) ? intval(argv(2)) : 0);
		}
	  
		if(! $item_id)
			killme();
	
		if (!in_array($type, array('item','photo','event', 'menu_item', 'chatroom')))
			killme();
	
		//we have different naming in in menu_item table and chatroom table
		switch($type) {
			case 'menu_item':
				$id = 'mitem_id';
				break;
			case 'chatroom':
				$id = 'cr_id';
				break;
			default:
				$id = 'id';
				break;
		}
	
		$r = q("SELECT * FROM %s WHERE $id = %d LIMIT 1",
			dbesc($type),
			intval($item_id)
		);
	
		if(! $r)
			killme();
	
		$item = $r[0];
	
		//we have different naming in in menu_item table and chatroom table
		switch($type) {
			case 'menu_item':
				$uid = $item['mitem_channel_id'];
				break;
			case 'chatroom':
				$uid = $item['cr_uid'];
				break;
			default:
				$uid = $item['uid'];
				break;
		}
	
		if($uid != local_channel()) {
			echo '<li>' . t('Remote privacy information not available.') . '</li>';
			killme();
		}
	
		if(($item['item_private'] == 1) && (! strlen($item['allow_cid'])) && (! strlen($item['allow_gid'])) 
			&& (! strlen($item['deny_cid'])) && (! strlen($item['deny_gid']))) {
	
			// if the post is private, but public_policy is blank ("visible to the internet"), and there aren't any
			// specific recipients, we're the recipient of a post with "bcc" or targeted recipients; so we'll just show it
			// as unknown specific recipients. The sender will have the visibility list and will fall through to the
			// next section.
	 
			echo '<li>' . translate_scope((! $item['public_policy']) ? 'specific' : $item['public_policy']) . '</li>';
			killme();
		}
	
		$allowed_users = expand_acl($item['allow_cid']);
		$allowed_groups = expand_acl($item['allow_gid']);
		$deny_users = expand_acl($item['deny_cid']);
		$deny_groups = expand_acl($item['deny_gid']);
	
		$o = '<li>' . t('Visible to:') . '</li>';
		$l = array();
	
		stringify_array_elms($allowed_groups,true);
		stringify_array_elms($allowed_users,true);
		stringify_array_elms($deny_groups,true);
		stringify_array_elms($deny_users,true);
	
		if(count($allowed_groups)) {
			$r = q("SELECT gname FROM groups WHERE hash IN ( " . implode(', ', $allowed_groups) . " )");
			if($r)
				foreach($r as $rr) 
					$l[] = '<li><b>' . $rr['gname'] . '</b></li>';
		}
		if(count($allowed_users)) {
			$r = q("SELECT xchan_name FROM xchan WHERE xchan_hash IN ( " . implode(', ',$allowed_users) . " )");
			if($r)
				foreach($r as $rr) 
					$l[] = '<li>' . $rr['xchan_name'] . '</li>';
			if($atokens) {
				foreach($atokens as $at) {
					if(in_array("'" . $at['xchan_hash'] . "'",$allowed_users)) {	
						$l[] = '<li>' . $at['xchan_name'] . '</li>';
					}
				}
			}
		}
		if(count($deny_groups)) {
			$r = q("SELECT gname FROM groups WHERE hash IN ( " . implode(', ', $deny_groups) . " )");
			if($r)
				foreach($r as $rr) 
					$l[] = '<li><b><strike>' . $rr['gname'] . '</strike></b></li>';
		}
		if(count($deny_users)) {
			$r = q("SELECT xchan_name FROM xchan WHERE xchan_hash IN ( " . implode(', ', $deny_users) . " )");
			if($r)
				foreach($r as $rr) 
					$l[] = '<li><strike>' . $rr['xchan_name'] . '</strike></li>';

			if($atokens) {
				foreach($atokens as $at) {
					if(in_array("'" . $at['xchan_hash'] . "'",$deny_users)) {	
						$l[] = '<li><strike>' . $at['xchan_name'] . '</strike></li>';
					}
				}
			}


		}
	
		echo $o . implode($l);
		killme();
	
	
	}
	
}
