<?php /** @file */

// import page design element

require_once('include/menu.php');

function impel_init(&$a) {

	$ret = array('success' => false);

	if(! local_channel())
		json_return_and_die($ret);

	logger('impel: ' . print_r($_REQUEST,true), LOGGER_DATA);

	$elm = $_REQUEST['element'];
	$x = base64url_decode($elm);
	if(! $x)
		json_return_and_die($ret);

	$j = json_decode($x,true);
	if(! $j)
		json_return_and_die($ret);

	$channel = $a->get_channel();

	$arr = array();
	$is_menu = false;

	// a portable menu has its links rewritten with the local baseurl
	$portable_menu = false;

	switch($j['type']) {
		case 'webpage':
			$arr['item_type'] = ITEM_TYPE_WEBPAGE;
			$namespace = 'WEBPAGE';
			$installed_type = t('webpage');
			break;
		case 'block':
			$arr['item_type'] = ITEM_TYPE_BLOCK;
			$namespace = 'BUILDBLOCK';
			$installed_type = t('block');
			break;
		case 'layout':
			$arr['item_type'] = ITEM_TYPE_PDL;
			$namespace = 'PDL';
			$installed_type = t('layout');
			break;
		case 'portable-menu':
			$portable_menu = true;
			// fall through
		case 'menu':
			$is_menu = true;
			$installed_type = t('menu');
			break;			
		default:
			logger('mod_impel: unrecognised element type' . print_r($j,true));
			break;
	}

	if($is_menu) {
		$m = array();
		$m['menu_channel_id'] = local_channel();
		$m['menu_name'] = $j['pagetitle'];
		$m['menu_desc'] = $j['desc'];
		if($j['created'])
			$m['menu_created'] = datetime_convert($j['created']);
		if($j['edited'])
			$m['menu_edited'] = datetime_convert($j['edited']);

		$m['menu_flags'] = 0;
		if($j['flags']) {
			if(in_array('bookmark',$j['flags']))
				$m['menu_flags'] |= MENU_BOOKMARK;
			if(in_array('system',$j['flags']))
				$m['menu_flags'] |= MENU_SYSTEM;

		}

		$menu_id = menu_create($m);

		if($menu_id) {
			if(is_array($j['items'])) {
				foreach($j['items'] as $it) {
					$mitem = array();

					$mitem['mitem_link'] = str_replace('[baseurl]',z_root(),$it['link']);
					$mitem['mitem_desc'] = escape_tags($it['desc']);
					$mitem['mitem_order'] = intval($it['order']);
					if(is_array($it['flags'])) {
						$mitem['mitem_flags'] = 0;
						if(in_array('zid',$it['flags']))
							$mitem['mitem_flags'] |= MENU_ITEM_ZID;
						if(in_array('new-window',$it['flags']))
							$mitem['mitem_flags'] |= MENU_ITEM_NEWWIN;
						if(in_array('chatroom',$it['flags']))
							$mitem['mitem_flags'] |= MENU_ITEM_CHATROOM;
					}
					menu_add_item($menu_id,local_channel(),$mitem);
				}
				if($j['edited']) {
					$x = q("update menu set menu_edited = '%s' where menu_id = %d and menu_channel_id = %d",
						dbesc(datetime_convert('UTC','UTC',$j['edited'])),
						intval($menu_id),
						intval(local_channel())
					);
				}
			}	
			$ret['success'] = true;
		}
		$x = $ret;
	}
	else {
		$arr['uid'] = local_channel();
		$arr['aid'] = $channel['channel_account_id'];
		$arr['title'] = $j['title'];
		$arr['body'] = $j['body'];
		$arr['term'] = $j['term'];
		$arr['layout_mid'] = $j['layout_mid'];
		$arr['created'] = datetime_convert('UTC','UTC', $j['created']);
		$arr['edited'] = datetime_convert('UTC','UTC',$j['edited']);
		$arr['owner_xchan'] = get_observer_hash();
		$arr['author_xchan'] = (($j['author_xchan']) ? $j['author_xchan'] : get_observer_hash());
		$arr['mimetype'] = (($j['mimetype']) ? $j['mimetype'] : 'text/bbcode');

		if(! $j['mid'])
			$j['mid'] = item_message_id();

		$arr['mid'] = $arr['parent_mid'] = $j['mid'];


		if($j['pagetitle']) {
			require_once('library/urlify/URLify.php');
			$pagetitle = strtolower(URLify::transliterate($j['pagetitle']));
		}



		// Verify ability to use html or php!!!

	    $execflag = false;

		if($arr['mimetype'] === 'application/x-php') {
			$z = q("select account_id, account_roles, channel_pageflags from account left join channel on channel_account_id = account_id where channel_id = %d limit 1",
				intval(local_channel())
			);

			if($z && (($z[0]['account_roles'] & ACCOUNT_ROLE_ALLOWCODE) || ($z[0]['channel_pageflags'] & PAGE_ALLOWCODE))) {
				$execflag = true;
			}
		}

		$remote_id = 0;

		$z = q("select * from item_id where sid = '%s' and service = '%s' and uid = %d limit 1",
			dbesc($pagetitle),
			dbesc($namespace),
			intval(local_channel())
		);

		$i = q("select id, edited, item_deleted from item where mid = '%s' and uid = %d limit 1",
			dbesc($arr['mid']),
			intval(local_channel())
		);

		if($z && $i) {
			$remote_id = $z[0]['id'];
			$arr['id'] = $i[0]['id'];
			// don't update if it has the same timestamp as the original
			if($arr['edited'] > $i[0]['edited'])
				$x = item_store_update($arr,$execflag);
		}
		else {
			if(($i) && (intval($i[0]['item_deleted']))) {
				// was partially deleted already, finish it off
				q("delete from item where mid = '%s' and uid = %d",
					dbesc($arr['mid']),
					intval(local_channel())
				);
			}
			$x = item_store($arr,$execflag);
		}

		if($x['success']) {
			$item_id = $x['item_id'];
			update_remote_id($channel,$item_id,$arr['item_type'],$pagetitle,$namespace,$remote_id,$arr['mid']);
		}
	}

	if($x['success']) {
		$ret['success'] = true;
		info( sprintf( t('%s element installed'), $installed_type)); 
	}
	else {
		notice( sprintf( t('%s element installation failed'), $installed_type)); 
	}

//??? should perhaps return ret? 
	json_return_and_die(true);

}
