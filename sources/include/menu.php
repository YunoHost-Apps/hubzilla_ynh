<?php /** @file */

require_once('include/security.php');
require_once('include/bbcode.php');

function menu_fetch($name,$uid,$observer_xchan) {

	$sql_options = permissions_sql($uid);

	$r = q("select * from menu where menu_channel_id = %d and menu_name = '%s' limit 1",
		intval($uid),
		dbesc($name)
	);
	if($r) {
		$x = q("select * from menu_item where mitem_menu_id = %d and mitem_channel_id = %d
			$sql_options 
			order by mitem_order asc, mitem_desc asc",
			intval($r[0]['menu_id']),
			intval($uid)
		);
		return array('menu' => $r[0], 'items' => $x );
	}

	return null;
}
	
function menu_element($menu) {

	$arr = array();
	$arr['type'] = 'menu';
	$arr['pagetitle'] = $menu['menu']['menu_name'];
	$arr['desc'] = $menu['menu']['menu_desc'];
	$arr['created'] = $menu['menu']['menu_created'];
	$arr['edited'] = $menu['menu']['menu_edited'];

	$arr['baseurl'] = z_root();
	if($menu['menu_flags']) {
		$arr['flags'] = array();
		if($menu['menu_flags'] & MENU_BOOKMARK)
			$arr['flags'][] = 'bookmark';
		if($menu['menu_flags'] & MENU_SYSTEM)
			$arr['flags'][] = 'system';
	}
	if($menu['items']) {
		$arr['items'] = array();
		foreach($menu['items'] as $it) {
			$entry = array();
			$entry['link'] = str_replace(z_root(),'[baseurl]',$it['mitem_link']);
			$entry['desc'] = $it['mitem_desc'];
			$entry['order'] = $it['mitem_order'];
			if($it['mitem_flags']) {
				$entry['flags'] = array();
				if($it['mitem_flags'] & MENU_ITEM_ZID)
					$entry['flags'][] = 'zid';
				if($it['mitem_flags'] & MENU_ITEM_NEWWIN)
					$entry['flags'][] = 'new-window';
				if($it['mitem_flags'] & MENU_ITEM_CHATROOM)
					$entry['flags'][] = 'chatroom';
			}
			$arr['items'][] = $entry;
		}
	}	

	return $arr;
}



function menu_render($menu, $class='', $edit = false, $var = array()) {

	if(! $menu)
		return '';

	$channel_id = ((is_array(get_app()->profile)) ? get_app()->profile['profile_uid'] : 0);
	if ((! $channel_id) && (local_channel()))
		$channel_id = local_channel();

	$menu_list = menu_list($channel_id);
	$menu_names = array();

	foreach($menu_list as $menus) {
		if($menus['menu_name'] != $menu['menu']['menu_name'])
			$menu_names[] = $menus['menu_name'];
	}

	for($x = 0; $x < count($menu['items']); $x ++) {
		if(in_array($menu['items'][$x]['mitem_link'], $menu_names)) {
			$m = menu_fetch($menu['items'][$x]['mitem_link'], $channel_id, get_observer_hash());
			$submenu = menu_render($m, 'dropdown-menu', $edit = false, array('wrap' => 'none'));
			$menu['items'][$x]['submenu'] = $submenu;
		}

		if($menu['items'][$x]['mitem_flags'] & MENU_ITEM_ZID)
			$menu['items'][$x]['mitem_link'] = zid($menu['items'][$x]['mitem_link']);

		if($menu['items'][$x]['mitem_flags'] & MENU_ITEM_NEWWIN)
			$menu['items'][$x]['newwin'] = '1';

		$menu['items'][$x]['mitem_desc'] = bbcode($menu['items'][$x]['mitem_desc']);
	}

	$wrap = (($var['wrap'] === 'none') ? false : true);

	$ret = replace_macros(get_markup_template('usermenu.tpl'),array(
		'$menu' => $menu['menu'],
		'$class' => $class,
		'$edit' => (($edit) ? t("Edit") : ''),
		'$id' => $menu['menu']['menu_id'],
		'$items' => $menu['items'],
		'$wrap' => $wrap
	));

	return $ret;
}



function menu_fetch_id($menu_id,$channel_id) {

	$r = q("select * from menu where menu_id = %d and menu_channel_id = %d limit 1",
		intval($menu_id),
		intval($channel_id)
	);

	return (($r) ? $r[0] : false);
}



function menu_create($arr) {
	$menu_name = trim(escape_tags($arr['menu_name']));
	$menu_desc = trim(escape_tags($arr['menu_desc']));
	$menu_flags = intval($arr['menu_flags']);

	//allow menu_desc (title) to be empty
	//if(! $menu_desc)
	//	$menu_desc = $menu_name;

	if(! $menu_name)
		return false;

	if(! $menu_flags)
		$menu_flags = 0;


	$menu_channel_id = intval($arr['menu_channel_id']);

	$r = q("select * from menu where menu_name = '%s' and menu_channel_id = %d limit 1",
		dbesc($menu_name),
		intval($menu_channel_id)
	);

	if($r)
		return false;

	$t = datetime_convert();

	$r = q("insert into menu ( menu_name, menu_desc, menu_flags, menu_channel_id, menu_created, menu_edited ) 
		values( '%s', '%s', %d, %d, '%s', '%s' )",
 		dbesc($menu_name),
		dbesc($menu_desc),
		intval($menu_flags),
		intval($menu_channel_id),
		dbesc(datetime_convert('UTC','UTC',(($arr['menu_created']) ? $arr['menu_created'] : $t))),
		dbesc(datetime_convert('UTC','UTC',(($arr['menu_edited']) ? $arr['menu_edited'] : $t)))
	);
	if(! $r)
		return false;

	$r = q("select menu_id from menu where menu_name = '%s' and menu_channel_id = %d limit 1",
		dbesc($menu_name),
		intval($menu_channel_id)
	);
	if($r)
		return $r[0]['menu_id'];
	return false;

}

/**
 * If $flags is present, check that all the bits in $flags are set
 * so that MENU_SYSTEM|MENU_BOOKMARK will return entries with both
 * bits set. We will use this to find system generated bookmarks.
 */

function menu_list($channel_id, $name = '', $flags = 0) {

	$sel_options = '';
	$sel_options .= (($name) ? " and menu_name = '" . protect_sprintf(dbesc($name)) . "' " : '');
	$sel_options .= (($flags) ? " and menu_flags = " . intval($flags) . " " : '');

	$r = q("select * from menu where menu_channel_id = %d $sel_options order by menu_desc",
		intval($channel_id)
	);
	return $r;
}

function menu_list_count($channel_id, $name = '', $flags = 0) {

	$sel_options = '';
	$sel_options .= (($name) ? " and menu_name = '" . protect_sprintf(dbesc($name)) . "' " : '');
	$sel_options .= (($flags) ? " and menu_flags = " . intval($flags) . " " : '');

	$r = q("select count(*) as total from menu where menu_channel_id = %d $sel_options",
		intval($channel_id)
	);
	return $r[0]['total'];
}

function menu_edit($arr) {

	$menu_id   = intval($arr['menu_id']);

	$menu_name = trim(escape_tags($arr['menu_name']));
	$menu_desc = trim(escape_tags($arr['menu_desc']));
	$menu_flags = intval($arr['menu_flags']);

	//allow menu_desc (title) to be empty
	//if(! $menu_desc)
	//	$menu_desc = $menu_name;

	if(! $menu_name)
		return false;

	if(! $menu_flags)
		$menu_flags = 0;


	$menu_channel_id = intval($arr['menu_channel_id']);

	$r = q("select menu_id from menu where menu_name = '%s' and menu_channel_id = %d limit 1",
		dbesc($menu_name),
		intval($menu_channel_id)
	);
	if(($r) && ($r[0]['menu_id'] != $menu_id)) {
		logger('menu_edit: duplicate menu name for channel ' . $menu_channel_id);
		return false;
	}


	$r = q("select * from menu where menu_id = %d and menu_channel_id = %d limit 1",
		intval($menu_id),
		intval($menu_channel_id)
	);
	if(! $r) {
		logger('menu_edit: not found: ' . print_r($arr,true));
		return false;
	}

	return q("update menu set menu_name = '%s', menu_desc = '%s', menu_flags = %d, menu_edited = '%s'
		where menu_id = %d and menu_channel_id = %d", 
 		dbesc($menu_name),
		dbesc($menu_desc),
		intval($menu_flags),
		dbesc(datetime_convert()),
		intval($menu_id),
		intval($menu_channel_id)
	);
}

function menu_delete($menu_name, $uid) {
	$r = q("select menu_id from menu where menu_name = '%s' and menu_channel_id = %d limit 1",
		dbesc($menu_name),
		intval($uid)
	);

	if($r)
		return menu_delete_id($r[0]['menu_id'],$uid);
	return false;
}

function menu_delete_id($menu_id, $uid) {
	$r = q("select menu_id from menu where menu_id = %d and menu_channel_id = %d limit 1",
		intval($menu_id),
		intval($uid)
	);
	if($r) {
		$x = q("delete from menu_item where mitem_menu_id = %d and mitem_channel_id = %d",
			intval($menu_id),
			intval($uid)
		);
		return q("delete from menu where menu_id = %d and menu_channel_id = %d limit 1",
			intval($menu_id),
			intval($uid)
		);
	}			
	return false;
}


function menu_add_item($menu_id, $uid, $arr) {

	$mitem_link = escape_tags($arr['mitem_link']);
	$mitem_desc = escape_tags($arr['mitem_desc']);
	$mitem_order = intval($arr['mitem_order']);	
	$mitem_flags = intval($arr['mitem_flags']);

	if(local_channel() == $uid) {
		$channel = get_app()->get_channel();	
	}

	$str_group_allow   = perms2str($arr['group_allow']);
	$str_contact_allow = perms2str($arr['contact_allow']);
	$str_group_deny    = perms2str($arr['group_deny']);
	$str_contact_deny  = perms2str($arr['contact_deny']);

	$r = q("insert into menu_item ( mitem_link, mitem_desc, mitem_flags, allow_cid, allow_gid, deny_cid, deny_gid, mitem_channel_id, mitem_menu_id, mitem_order ) values ( '%s', '%s', %d, '%s', '%s', '%s', '%s', %d, %d, %d ) ",
		dbesc($mitem_link),
		dbesc($mitem_desc),
		intval($mitem_flags),
		dbesc($str_contact_allow),
		dbesc($str_group_allow),
		dbesc($str_contact_deny),
		dbesc($str_group_deny),
		intval($uid),
		intval($menu_id),
		intval($mitem_order)
	);

	$x = q("update menu set menu_edited = '%s' where menu_id = %d and menu_channel_id = %d",
		dbesc(datetime_convert()),
		intval($menu_id),
		intval($uid)
	);

	return $r;

}

function menu_edit_item($menu_id, $uid, $arr) {


	$mitem_id = intval($arr['mitem_id']);
	$mitem_link = escape_tags($arr['mitem_link']);
	$mitem_desc = escape_tags($arr['mitem_desc']);
	$mitem_order = intval($arr['mitem_order']);	
	$mitem_flags = intval($arr['mitem_flags']);


	if(local_channel() == $uid) {
		$channel = get_app()->get_channel();	
	}

	$str_group_allow   = perms2str($arr['group_allow']);
	$str_contact_allow = perms2str($arr['contact_allow']);
	$str_group_deny    = perms2str($arr['group_deny']);
	$str_contact_deny  = perms2str($arr['contact_deny']);

	$r = q("update menu_item set mitem_link = '%s', mitem_desc = '%s', mitem_flags = %d, allow_cid = '%s', allow_gid = '%s', deny_cid = '%s', deny_gid = '%s', mitem_order = %d  where mitem_channel_id = %d and mitem_menu_id = %d and mitem_id = %d",
		dbesc($mitem_link),
		dbesc($mitem_desc),
		intval($mitem_flags),
		dbesc($str_contact_allow),
		dbesc($str_group_allow),
		dbesc($str_contact_deny),
		dbesc($str_group_deny),
		intval($mitem_order),
		intval($uid),
		intval($menu_id),
		intval($mitem_id)
	);

	$x = q("update menu set menu_edited = '%s' where menu_id = %d and menu_channel_id = %d",
		dbesc(datetime_convert()),
		intval($menu_id),
		intval($uid)
	);

	return $r;
}




function menu_del_item($menu_id,$uid,$item_id) {
	$r = q("delete from menu_item where mitem_menu_id = %d and mitem_channel_id = %d and mitem_id = %d",
		intval($menu_id),
		intval($uid),
		intval($item_id)
	);

	$x = q("update menu set menu_edited = '%s' where menu_id = %d and menu_channel_id = %d",
		dbesc(datetime_convert()),
		intval($menu_id),
		intval($uid)
	);

	return $r;
}

