<?php


require_once('include/cli_startup.php');
require_once('include/zot.php');


function deliver_hooks_run($argv, $argc) {

	cli_startup();

	$a = get_app();

	if($argc < 2)
		return;


	$r = q("select * from item where id = '%d'",
		intval($argv[1])
	);
	if($r)
		call_hooks('notifier_normal',$r[0]);

}

if (array_search(__file__,get_included_files())===0){
  deliver_hooks_run($argv,$argc);
  killme();
}
