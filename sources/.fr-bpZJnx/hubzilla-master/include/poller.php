<?php /** @file */

require_once('include/cli_startup.php');

function poller_run($argc,$argv){

	cli_startup();
	\Zotlabs\Daemon\Master::Summon(array('Cron'));

}

if (array_search(__file__,get_included_files())===0){
  poller_run($argc,$argv);
  killme();
}
