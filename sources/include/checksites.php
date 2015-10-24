<?php /** @file */

require_once('boot.php');
require_once('include/cli_startup.php');
require_once('include/zot.php');
require_once('include/hubloc.php');



function checksites_run($argv, $argc){


	cli_startup();
	$a = get_app();

	logger('checksites: start');
	
	if(($argc > 1) && ($argv[1]))
		$site_id = $argv[1];

	if($site_id)
		$sql_options = " and site_url = '" . dbesc($argv[1]) . "' ";

	$days = intval(get_config('system','sitecheckdays'));
	if($days < 1)
		$days = 30;

	$r = q("select * from site where site_dead = 0 and site_update < %s - INTERVAL %s and site_type = %d $sql_options ",
		db_utcnow(), db_quoteinterval($days . ' DAY'),
		intval(SITE_TYPE_ZOT)
	);

	if(! $r)
		return;

	foreach($r as $rr) {
		if(! strcasecmp($rr['site_url'],z_root()))
			continue;

		$x = ping_site($rr['site_url']);
		if($x['success']) {
			logger('checksites: ' . $rr['site_url']);
			q("update site set site_update = '%s' where site_url = '%s' ",
				dbesc(datetime_convert()),
				dbesc($rr['site_url'])
			);
		}
		else {
			logger('marking dead site: ' . $x['message']);
			q("update site set site_dead = 1 where site_url = '%s' ",
				dbesc($rr['site_url'])
			);
		}
	}

	return;
}

if (array_search(__file__,get_included_files())===0){
  checksites_run($argv,$argc);
  killme();
}
