<?php
/**
 * @file include/directory.php
 * @brief executes directory_run()
 */

require_once('boot.php');
require_once('include/zot.php');
require_once('include/cli_startup.php');
require_once('include/dir_fns.php');
require_once('include/queue_fn.php');

/**
 * @brief
 *
 * @param array $argv
 * @param array $argc
 */
function directory_run($argv, $argc){

	cli_startup();

	if($argc < 2)
		return;

	$force = false;
	$pushall = true;

	if($argc > 2) {
		if($argv[2] === 'force')
			$force = true;
		if($argv[2] === 'nopush')
			$pushall = false;
	}

	logger('directory update', LOGGER_DEBUG);

	$dirmode = get_config('system','directory_mode');
	if($dirmode === false)
		$dirmode = DIRECTORY_MODE_NORMAL;

	$x = q("select * from channel where channel_id = %d limit 1",
		intval($argv[1])
	);
	if(! $x)
		return;

	$channel = $x[0];

	if($dirmode != DIRECTORY_MODE_NORMAL) {

		// this is an in-memory update and we don't need to send a network packet.

		local_dir_update($argv[1],$force);

		q("update channel set channel_dirdate = '%s' where channel_id = %d",
			dbesc(datetime_convert()),
			intval($channel['channel_id'])
		);

		// Now update all the connections
		if($pushall) 
			proc_run('php','include/notifier.php','refresh_all',$channel['channel_id']);

		return;
	}

	// otherwise send the changes upstream

	$directory = find_upstream_directory($dirmode);
	$url = $directory['url'] . '/post';

	// ensure the upstream directory is updated

	$packet = zot_build_packet($channel,(($force) ? 'force_refresh' : 'refresh'));
	$z = zot_zot($url,$packet);

	// re-queue if unsuccessful

	if(! $z['success']) {

		/** @FIXME we aren't updating channel_dirdate if we have to queue
		 * the directory packet. That means we'll try again on the next poll run.
		 */

		$hash = random_string();

		queue_insert(array(
			'hash'       => $hash,
			'account_id' => $channel['channel_account_id'],
			'channel_id' => $channel['channel_id'],
			'posturl'    => $url,
			'notify'     => $packet,
		));

	}
	else {
		q("update channel set channel_dirdate = '%s' where channel_id = %d",
			dbesc(datetime_convert()),
			intval($channel['channel_id'])
		);
	}

	// Now update all the connections
	if($pushall)
		proc_run('php','include/notifier.php','refresh_all',$channel['channel_id']);

}

if (array_search(__file__, get_included_files()) === 0) {
	directory_run($argv, $argc);
	killme();
}
