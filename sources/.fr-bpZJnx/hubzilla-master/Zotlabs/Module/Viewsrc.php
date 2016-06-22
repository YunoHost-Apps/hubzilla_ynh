<?php
namespace Zotlabs\Module;



class Viewsrc extends \Zotlabs\Web\Controller {

	function get() {
	
		$o = '';
	
		$sys = get_sys_channel();
	
		$item_id = ((argc() > 1) ? intval(argv(1)) : 0);
		$json    = ((argc() > 2 && argv(2) === 'json') ? true : false);
	
		if(! local_channel()) {
			notice( t('Permission denied.') . EOL);
		}
	
	
		if(! $item_id) {
			\App::$error = 404;
			notice( t('Item not found.') . EOL);
		}
	
		$item_normal = item_normal();
	
		if(local_channel() && $item_id) {
			$r = q("select id, item_flags, item_obscured, body from item where uid in (%d , %d) and id = %d $item_normal limit 1",
				intval(local_channel()),
				intval($sys['channel_id']),
				intval($item_id)
			);
	
			if($r) {
				if(intval($r[0]['item_obscured']))
					$r[0]['body'] = crypto_unencapsulate(json_decode($r[0]['body'],true),get_config('system','prvkey')); 
				$o = (($json) ? json_encode($r[0]['body']) : str_replace("\n",'<br />',$r[0]['body']));
			}
		}
	
		if(is_ajax()) {
			print '<div><i class="fa fa-pencil"> ' . t('Source of Item') . ' ' . $r[0]['id'] . '</i></div>';
			echo $o;
			killme();
		} 
	
		return $o;
	}
	
	
}
