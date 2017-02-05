<?php

namespace Zotlabs\Module;

class Fetch extends \Zotlabs\Web\Controller {

	function init() {

		if ((argc() != 3) || (! in_array(argv(1), [ 'post', 'status_message', 'reshare' ] ))) {
			http_status_exit(404,'Not found');
		}

		$guid = argv(2);

	
		// Fetch the item
		$item = q("SELECT * from item where mid = '%s' and item_private = 0 and mid = parent_mid limit 1",
			dbesc($guid)
		);
		if(! $item) {
			http_status_exit(404,'Not found');
		}

		xchan_query($item);
		$item = fetch_post_tags($item,true);
	
		$channel = channelx_by_hash($item[0]['author_xchan']);
		if(! $channel) {
			$r = q("select * from xchan where xchan_hash = '%s' limit 1",
				dbesc($item[0]['author_xchan'])
			);
			if($r) {
				$url = $r[0]['xchan_url'];
				if(strpos($url,z_root()) === false) {
					$m = parse_url($url);
					goaway($m['scheme'] . '://' . $m['host'] . (($m['port']) ? ':' . $m['port'] : '') 
						. '/fetch/' . argv(1) . '/' . argv(2));
				}
			}
			http_status_exit(404,'Not found');
		}

		$status = diaspora_build_status($item[0],$channel);

		header("Content-type: application/magic-envelope+xml; charset=utf-8");
		echo diaspora_magic_env($channel,$status);

		killme();
	}
}

