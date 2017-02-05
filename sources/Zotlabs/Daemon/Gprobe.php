<?php /** @file */

namespace Zotlabs\Daemon;

require_once('include/zot.php');

// performs zot_finger on $argv[1], which is a hex_encoded webbie/reddress

class Gprobe {
	static public function run($argc,$argv) {

		if($argc != 2)
			return;

		$url = hex2bin($argv[1]);

		if(! strpos($url,'@'))
			return;

		$r = q("select * from xchan where xchan_addr = '%s' limit 1",
			dbesc($url)
		);

		if(! $r) {
			$j = \Zotlabs\Zot\Finger::run($url,null);
			if($j['success']) {
				$y = import_xchan($j);
			}
		}

		return;
	}
}
