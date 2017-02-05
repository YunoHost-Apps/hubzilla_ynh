<?php

namespace Zotlabs\Daemon;

class Cron_weekly {

	static public function run($argc,$argv) {		

		/**
		 * Cron Weekly
		 * 
		 * Actions in the following block are executed once per day only on Sunday (once per week).
		 *
		 */

		call_hooks('cron_weekly',datetime_convert());

		z_check_cert();

		require_once('include/hubloc.php');
		prune_hub_reinstalls();
	
		mark_orphan_hubsxchans();


		// get rid of really old poco records

		q("delete from xlink where xlink_updated < %s - INTERVAL %s and xlink_static = 0 ",
			db_utcnow(), db_quoteinterval('14 DAY')
		);

		$dirmode = intval(get_config('system','directory_mode'));
		if($dirmode === DIRECTORY_MODE_SECONDARY || $dirmode === DIRECTORY_MODE_PRIMARY) {
			logger('regdir: ' . print_r(z_fetch_url(get_directory_primary() . '/regdir?f=&url=' . urlencode(z_root()) . '&realm=' . urlencode(get_directory_realm())),true));
		}

		// Check for dead sites
		Master::Summon(array('Checksites'));
			
		// update searchable doc indexes
		Master::Summon(array('Importdoc'));

		/**
		 * End Cron Weekly
		 */

	}
}