<?php /** @file */

namespace Zotlabs\Daemon;

class Cron_daily {

	static public function run($argc,$argv) {

		logger('cron_daily: start');

		/**
		 * Cron Daily
		 *
		 */


		require_once('include/dir_fns.php');
		check_upstream_directory();


		// Fire off the Cron_weekly process if it's the correct day.
 
		$d3 = intval(datetime_convert('UTC','UTC','now','N'));
		if($d3 == 7) {
			Master::Summon(array('Cron_weekly'));
		}

		// once daily run birthday_updates and then expire in background

		// FIXME: add birthday updates, both locally and for xprof for use
		// by directory servers

		update_birthdays();

		// expire any read notifications over a month old

		q("delete from notify where seen = 1 and created < %s - INTERVAL %s",
			db_utcnow(), db_quoteinterval('30 DAY')
		);

		//update statistics in config
		require_once('include/statistics_fns.php');
		update_channels_total_stat();
		update_channels_active_halfyear_stat();
		update_channels_active_monthly_stat();
		update_local_posts_stat();


		// expire old delivery reports

		$keep_reports = intval(get_config('system','expire_delivery_reports'));
		if($keep_reports === 0)
			$keep_reports = 10;

		q("delete from dreport where dreport_time < %s - INTERVAL %s",
			db_utcnow(),
			db_quoteinterval($keep_reports . ' DAY')
		);

		// expire any expired accounts
		downgrade_accounts();

		// If this is a directory server, request a sync with an upstream
		// directory at least once a day, up to once every poll interval. 
		// Pull remote changes and push local changes.
		// potential issue: how do we keep from creating an endless update loop? 

		$dirmode = get_config('system','directory_mode');

		if($dirmode == DIRECTORY_MODE_SECONDARY || $dirmode == DIRECTORY_MODE_PRIMARY) {
			require_once('include/dir_fns.php');
			sync_directories($dirmode);
		}


		Master::Summon(array('Expire'));
		Master::Summon(array('Cli_suggest'));

		require_once('include/hubloc.php');
		remove_obsolete_hublocs();

		call_hooks('cron_daily',datetime_convert());

		set_config('system','last_expire_day',$d2);

		/**
		 * End Cron Daily
		 */
	}
}
