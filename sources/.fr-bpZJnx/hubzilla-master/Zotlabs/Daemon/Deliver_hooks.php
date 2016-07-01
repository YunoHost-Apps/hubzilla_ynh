<?php

namespace Zotlabs\Daemon;

require_once('include/zot.php');

class Deliver_hooks {

	static public function run($argc,$argv) {

		if($argc < 2)
			return;


		$r = q("select * from item where id = '%d'",
			intval($argv[1])
		);
		if($r)
			call_hooks('notifier_normal',$r[0]);

	}
}


