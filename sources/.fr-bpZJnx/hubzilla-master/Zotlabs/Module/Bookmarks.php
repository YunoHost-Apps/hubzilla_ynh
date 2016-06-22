<?php
namespace Zotlabs\Module;


class Bookmarks extends \Zotlabs\Web\Controller {

	function init() {
		if(! local_channel())
			return;
		$item_id = intval($_REQUEST['item']);
		$burl = trim($_REQUEST['burl']);
	
		if(! $item_id)
			return;
	
		$u = \App::get_channel();
	
		$item_normal = item_normal();
	
		$i = q("select * from item where id = %d and uid = %d $item_normal limit 1",
			intval($item_id),
			intval(local_channel())
		);
	
		if(! $i)
			return;
	
		$i = fetch_post_tags($i);
	
		$item = $i[0];
	
		$terms = get_terms_oftype($item['term'],TERM_BOOKMARK);
	
		if($terms) {
			require_once('include/bookmarks.php');
	
			$s = q("select * from xchan where xchan_hash = '%s' limit 1",
				dbesc($item['author_xchan'])
			);
			if(! $s) {
				logger('mod_bookmarks: author lookup failed.');
				killme();
			}
			foreach($terms as $t) {
				if($burl) {
					if($burl == $t['url']) {
						bookmark_add($u,$s[0],$t,$item['item_private']);
					}
				}
				else
					bookmark_add($u,$s[0],$t,$item['item_private']);
	
				info( t('Bookmark added') . EOL);
			}
		}
		killme();
	}
	
		function get() {
		if(! local_channel()) {
			notice( t('Permission denied.') . EOL);
			return;
		}
	
	
		require_once('include/menu.php');
		require_once('include/conversation.php');
	
		$channel = \App::get_channel();
	
		$o = profile_tabs($a,true,$channel['channel_address']);
	
		$o .= '<div class="generic-content-wrapper-styled">';
	
		$o .= '<h3>' . t('My Bookmarks') . '</h3>';
	
		$x = menu_list(local_channel(),'',MENU_BOOKMARK);
	
		if($x) {
			foreach($x as $xx) {
				$y = menu_fetch($xx['menu_name'],local_channel(),get_observer_hash());
				$o .= menu_render($y,'',true);
			}
		}
	
		$o .= '<h3>' . t('My Connections Bookmarks') . '</h3>';
	
	
		$x = menu_list(local_channel(),'',MENU_SYSTEM|MENU_BOOKMARK);
	
		if($x) {
			foreach($x as $xx) {
				$y = menu_fetch($xx['menu_name'],local_channel(),get_observer_hash());
				$o .= menu_render($y,'',true);
			}
		}
	
		$o .= '</div>';
	
		return $o;
	
	}
	
	
}
