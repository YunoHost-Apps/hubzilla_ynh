<?php
namespace Zotlabs\Module;


class Branchtopic extends \Zotlabs\Web\Controller {

	function init() {
	
		if(! local_channel())
			return;
	
		$item_id = 0;
	
		if(argc() > 1)
			$item_id = intval(argv(1));	
	
		if(! $item_id)
			return;
	
		$channel = \App::get_channel();
	
		if(! $channel)
			return;
	
	
		$r = q("select * from item where id = %d and uid = %d and owner_xchan = '%s' and id != parent limit 1",
			intval($item_id),
			intval(local_channel()),
			dbesc($channel['channel_hash'])
		);
	
		if(! $r)
			return;
	
		$p = q("select * from item where id = %d and uid = %d limit 1",
			intval($r[0]['parent']),
			intval(local_channel())
		);
	
		$x = q("update item set parent = id, route = '', item_thread_top = 1 where id = %d",
			intval($item_id)
		);
	
		return;
	}
	
}
