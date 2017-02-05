<?php
namespace Zotlabs\Module;

require_once('include/menu.php');
require_once('include/channel.php');


class Menu extends \Zotlabs\Web\Controller {

	function init() {
		if (array_key_exists('sys', $_REQUEST) && $_REQUEST['sys'] && is_site_admin()) {
			$sys = get_sys_channel();
			if ($sys && intval($sys['channel_id'])) {
				\App::$is_sys = true;
			}
		}
	}
	
		function post() {
	
		$uid = local_channel();
	
		if(array_key_exists('sys', $_REQUEST) && $_REQUEST['sys'] && is_site_admin()) {
			$sys = get_sys_channel();
			$uid = intval($sys['channel_id']);
			\App::$is_sys = true;
		}
	
		if(! $uid)
			return;
	
		$_REQUEST['menu_channel_id'] = $uid;
	
		if($_REQUEST['menu_bookmark'])
			$_REQUEST['menu_flags'] |= MENU_BOOKMARK;
		if($_REQUEST['menu_system'])
			$_REQUEST['menu_flags'] |= MENU_SYSTEM;
	
		$menu_id = ((argc() > 1) ? intval(argv(1)) : 0);
		if($menu_id) {
			$_REQUEST['menu_id'] = intval(argv(1));
			$r = menu_edit($_REQUEST);
			if($r) {
				menu_sync_packet($uid,get_observer_hash(),$menu_id);
				//info( t('Menu updated.') . EOL);
				goaway(z_root() . '/mitem/' . $menu_id . ((\App::$is_sys) ? '?f=&sys=1' : '')); 
			}
			else
				notice( t('Unable to update menu.'). EOL);
		}
		else {
			$r = menu_create($_REQUEST);
			if($r) {
				menu_sync_packet($uid,get_observer_hash(),$r);
	
				//info( t('Menu created.') . EOL);
				goaway(z_root() . '/mitem/' . $r . ((\App::$is_sys) ? '?f=&sys=1' : '')); 
			}
			else
				notice( t('Unable to create menu.'). EOL);
	
		}
	}
	
	
	
	
	function get() {
	
		$uid = local_channel();
	
		if (\App::$is_sys && is_site_admin()) {
			$sys = get_sys_channel();
			$uid = intval($sys['channel_id']);
		}
	
		if(! $uid) {
			notice( t('Permission denied.') . EOL);
			return '';
		}
	
		if(argc() == 1) {
	
			$channel = (($sys) ? $sys : \App::get_channel());
	
			// list menus
			$x = menu_list($uid);
			if($x) {
				for($y = 0; $y < count($x); $y ++) {
					$m = menu_fetch($x[$y]['menu_name'],$uid,get_observer_hash());
					if($m)
						$x[$y]['element'] = '[element]' . base64url_encode(json_encode(menu_element($channel,$m))) . '[/element]';
					$x[$y]['bookmark'] = (($x[$y]['menu_flags'] & MENU_BOOKMARK) ? true : false);
				}
			}
	
			$create = replace_macros(get_markup_template('menuedit.tpl'), array(
				'$menu_name' => array('menu_name', t('Menu Name'), '', t('Unique name (not visible on webpage) - required'), '*'),
				'$menu_desc' => array('menu_desc', t('Menu Title'), '', t('Visible on webpage - leave empty for no title'), ''),
				'$menu_bookmark' => array('menu_bookmark', t('Allow Bookmarks'), 0 , t('Menu may be used to store saved bookmarks'), array(t('No'), t('Yes'))),
				'$submit' => t('Submit and proceed'),
				'$sys' => \App::$is_sys,
				'$display' => 'none'
			));
	
			$o = replace_macros(get_markup_template('menulist.tpl'),array(
				'$title' => t('Menus'),
				'$create' => $create,
				'$menus' => $x,
				'$nametitle' => t('Menu Name'),
				'$desctitle' => t('Menu Title'),
				'$edit' => t('Edit'),
				'$drop' => t('Drop'),
				'$created' => t('Created'),
				'$edited' => t('Edited'),
				'$new' => t('New'),
				'$bmark' => t('Bookmarks allowed'),
				'$hintnew' => t('Create'),
				'$hintdrop' => t('Delete this menu'),
				'$hintcontent' => t('Edit menu contents'),
				'$hintedit' => t('Edit this menu'),
				'$sys' => \App::$is_sys
			));
	
			return $o;
	
		}
	
		if(argc() > 1) {
			if(intval(argv(1))) {
	
				if(argc() == 3 && argv(2) == 'drop') {
					menu_sync_packet($uid,get_observer_hash(),intval(argv(1)),true);
					$r = menu_delete_id(intval(argv(1)),$uid);
					if(!$r)
						notice( t('Menu could not be deleted.'). EOL);
	
					goaway(z_root() . '/menu' . ((\App::$is_sys) ? '?f=&sys=1' : ''));
				}
	
				$m = menu_fetch_id(intval(argv(1)),$uid);
	
				if(! $m) {
					notice( t('Menu not found.') . EOL);
					return '';
				}
	
				$o = replace_macros(get_markup_template('menuedit.tpl'), array(
					'$header' => t('Edit Menu'),
					'$sys' => \App::$is_sys,
					'$menu_id' => intval(argv(1)),
					'$menu_edit_link' => 'mitem/' . intval(argv(1)) . ((\App::$is_sys) ? '?f=&sys=1' : ''),
					'$hintedit' => t('Add or remove entries to this menu'),
					'$editcontents' => t('Edit menu contents'),
					'$menu_name' => array('menu_name', t('Menu name'), $m['menu_name'], t('Must be unique, only seen by you'), '*'),
					'$menu_desc' => array('menu_desc', t('Menu title'), $m['menu_desc'], t('Menu title as seen by others'), ''),
					'$menu_bookmark' => array('menu_bookmark', t('Allow bookmarks'), (($m['menu_flags'] & MENU_BOOKMARK) ? 1 : 0), t('Menu may be used to store saved bookmarks'), array(t('No'), t('Yes'))),
					'$menu_system' => (($m['menu_flags'] & MENU_SYSTEM) ? 1 : 0),
					'$submit' => t('Submit and proceed')
				));
	
				return $o;
	
			}
			else {
				notice( t('Not found.') . EOL);
				return;
			}
		}
	
	}
	
}
